<?php
/*
 * sms.php:
 * Convert SMS to email subscription, when user supplies a token.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: sms.php,v 1.21 2005-05-25 12:03:25 chris Exp $
 * 
 */

require_once "../phplib/db.php";
require_once "../phplib/pb.php";
require_once "../phplib/fns.php";
require_once "../phplib/pledge.php";
require_once "../phplib/auth.php";

require_once "../../phplib/importparams.php";
require_once "../../phplib/utility.php";


$errs = importparams(
                array('token',  '/^[0-9a-f]{4}-[0-9a-f]{4}$/',  "The code you've entered isn't valid")
            );

if (is_null($q_token))
    bad_token($q_unchecked_token);

/* We have a token. See whether it's valid. */
$r = db_getRow('
            select pledge_id, signer_id
            from smssubscription
            where token = ?
            for update', $q_token);
if (is_null($r))
    bad_token($q_unchecked_token);
elseif (!isset($r['signer_id'])) {
    /* We're not signed up (because we haven't had confirmation that the
     * conversion SMS was delivered, presumably). Try a signup now. */
    $res = pledge_dbresult_to_code(
                db_getOne('select smssubscription_sign(null, ?)', $q_token)
            );
    if ($res != PLEDGE_OK)
        oops($res, "before our SMS to you was delivered");
    else {
        /* We've now signed up, so just redirect to this script. */
        header("Location: " . invoked_url());
        exit();
    }
}

/* We have signed up. Obtain pledge ID. */
$signer_id = $r['signer_id'];
$pledge_id = db_getOne('
                    select pledge_id from signers where id = ?',
                    $signer_id
                );

/* Don't allow conversion on private pledges. */
if (!is_null(db_getOne('select pin from pledge where id = ?', $pledge_id)))
    err('Permission denied');

/* Have we already converted? */
if (db_getOne(
            'select person_id from signers where id = ?',
            $signer_id)) {
    /* ... and converted. */
    page_header('SMS');
    print <<<EOF
        <p>
    You've already signed up and given us your name and email address.
    There's no need to do so again.
        </p>
EOF;
    page_footer(array('nonav' => 1));
    exit();
}

$errs = importparams(
            array('phone',  '/[\d\s-+]$/',                  "Please enter your phone number"),
            array('name',   '/[a-z]+/i',                    "Please enter your name"),
            array('email',  '/^[^@]+@[^@]+$/',              "Please enter your email address"),
            array('showname',
                            '/^1$/',                        "", 0),
            array('f',      '/^1$/',                        "", 0)
        );

$showform = true;

$p = preg_replace("/[^\d]/", '', $q_phone);
$phone = db_getOne('select mobile from signers where id = ?', $signer_id);
if (!$phone)
    err("No mobile number recorded for SMS signer $signer_id");
else if (substr($p, -6) == substr($phone, -6)) {
    /* Compare last few characters of the phone numbers only, so that we avoid
     * having to know anything about their format. */
    if (!$errs)
        $errs = array();
    $errs['phone'] = "That phone number doesn't match our records";
}

if ($errs) {
    /* Form to supply info for the subscription */
    page_header('SMS');
    conversion_form($q_f ? $errs : null, $pledge_id);
    page_footer(array('nonav' => 1));
    exit();
}

/* OK, they win. Make them sign on. */
$data = db_getRow('select * from pledge where id = (select pledge_id from signers where signers.id = ?)', $signer_id);
$data['template'] = 'sms-confirm';
$data['reason'] = 'confirm your email address';
$P = person_signon($data, $q_name, $q_email);

$r = pledge_is_valid_to_sign($pledge_id, $P->email());

page_header('SMS');
if ($r == PLEDGE_OK) {
    /* No existing signer, so just stick this person in
     * to the SMS-signed record. */
    db_query('update signers set person_id = ?, name = ? where id = ?', array($P->id(), $P->name(), $signer_id));
} else if ($r == PLEDGE_SIGNED) {
    /* Either the pledge creator or somebody who's already
     * signed up. */
    db_query('lock table signers in share mode');
    $signer_id2 = db_getOne('select id from signers where person_id = ?', $P->id());
    if (!is_null($signer_id2))
        /* Somebody else has already signed under this
         * email address, so combine the two
         * subscriptions. */
        db_query('select signers_combine_2(?, ?)', array($signer_id, $signer_id2));
    else {
        /* Creator trying to sign their own pledge. Need to remove the old
         * signer record. */
        ?>
        <p><strong>You cannot sign your own pledge!</strong></p>
        <?
    }
} else {
    oops($r);
}
db_commit();

?>
<p><strong>Thanks for signing up to this pledge!</strong></p>
<?

page_tail(array('nonav' => 1));

/* bad_token TOKEN
 * Display some text about TOKEN being invalid. */
function bad_token($x) {
    if (is_null($x)) {
        err("Oops");
    } else {
        $x = htmlspecialchars($x);
        print <<<EOF
            <p>
        Sorry, we can't make sense of the code '$x'. Please
        could you re-check the address you typed in; the last part of it should
        be two groups of four letters and numbers, joined by a hyphen ("-"),
        something like "1234-abcd".
            </p>
EOF;
    }
    exit();
}

/* oops RESULT [WHAT]
 * Given a RESULT (a PLEDGE_... constant) and WHAT, some descriptive blurb (see
 * function), print a paragraph of stuff about why the user couldn't be signed
 * up to the pledge. */
function oops($r, $what = null) {
    print "<p><strong>Sorry, we couldn't sign you up to that pledge:</strong></p>";
    if ($r == PLEDGE_FULL || $r == PLEDGE_FINISHED) {
        /* Print a fuller explanation in this (common) case */
        $how = ($r == PLEDGE_FULL ? /* XXX i18n */
                    "somebody else beat you to the last place on that pledge"
                    : "the pledge finished");
        if (is_null($what))
            $what = 'before we could subscribe you';
        print <<<EOF
<p>Unfortunately, $what, $how.
We're very sorry &mdash; better luck next time!</p>
EOF;
    } else {
        print "<p>" . htmlspecialchars(pledge_strerror($r)) . "</p>";
        if (!pledge_is_permanent_error($r))
            print "<p><strong>Please try again a bit later.</strong></p>";
    }
}

/* conversion_form ERRORS
 * Display the form for a user to convert their SMS subscription to email.
 * ERRORS is an array of errors to display for each field. */
function conversion_form($errs, $pledge_id) {
    global $q_h_token, $q_unchecked_h_phone, $q_unchecked_h_email, $q_unchecked_h_name;
    print <<<EOF
<h2>Thanks for signing up!</h2>

<p>On this page you can let us have your name and email address so that you can
get email from the pledge creator and, if you want, discuss the pledge with
other signers. If you give us your email address we can also email you when the
pledge succeeds, rather than sending an SMS.</p>
EOF;
    if ($errs) {
        print '<div id="errors"><ul>';
        if (array_key_exists('phone', $errs))
            print "<li>" . htmlspecialchars($errs['phone']) . "</li";
        if (array_key_exists('name', $errs))
            print "<li>" . htmlspecialchars($errs['name']) . "</li>";
        if (array_key_exists('email', $errs))
            print "<li>" . htmlspecialchars($errs['email']) . "</li>";
        print '</ul></div>';
    } else {
        $pledge_info = db_getRow('select * from pledges where id = ? and pin is null', $pledge_id);
        $sentence = pledge_sentence($pledge_info, array('firstperson'=>true, 'html'=>true));
        $pretty_date = prettify($pledge_info['date']);
        $pretty_name = htmlspecialchars($pledge_info['name']);
    
        print <<<EOF
        <div id="tips">
        <p style="margin-top: 0">&quot;$sentence&quot;</p>
        <p>Deadline: <strong>$pretty_date</strong></p>
        </div>
EOF;

    }

    print <<<EOF
<form accept-charset="utf-8" class="pledge" method="post" name="pledge">
<input type="hidden" name="f" value="1">
<input type="hidden" name="token" value="$q_h_token">
<p>
Phone number: <input type="text" name="phone" value="$q_unchecked_h_phone"><br/>
Name: <input type="text" name="name" value="$q_unchecked_h_name"><br/>
Email: <input type="text" name="email" size="30" value="$q_unchecked_h_email"><br/>
Show my name on this pledge: <input name="showname" value="1" checked="checked" type="checkbox"><br/>
<input type="submit" name="submit" value="Submit">
EOF;
}
            
?>
