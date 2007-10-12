<?php
/*
 * sms.php:
 * Convert SMS to email subscription, when user supplies a token.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: sms.php,v 1.46 2007-10-12 13:12:48 matthew Exp $
 * 
 */

require_once "../phplib/pb.php";
require_once "../../phplib/db.php";
require_once "../phplib/fns.php";
require_once "../phplib/pledge.php";
require_once '../phplib/pbperson.php';

require_once "../../phplib/importparams.php";
require_once "../../phplib/utility.php";


$errs = importparams(
                array('token',  '/^[0-9a-f]{4}-[0-9a-f]{4}$/',  _("The code you've entered isn't valid"))
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
    db_commit();
    if ($res != PLEDGE_OK)
        oops($res, 'sms');
    else {
        /* We've now signed up, so just redirect to this script. */
        header("Location: " . url_invoked());
        exit();
    }
}

/* We have signed up. Obtain pledge ID. */
$signer_id = $r['signer_id'];
$pledge_id = db_getOne('select pledge_id from signers where id = ?', $signer_id);
$pledge = new Pledge(intval($pledge_id));

/* Don't allow conversion on private pledges. */
if (!is_null(db_getOne('select pin from pledges where id = ?', $pledge_id)))
    err(_('Permission denied'), E_USER_NOTICE);

/* Have we already converted? If so just show the usual "thank you" page. */
if (db_getOne('select email from signers left join person on person.id = signers.person_id where signers.id = ?', $signer_id)) {
    page_header(_('SMS'));
    print p(strong(_('Thanks for signing up to this pledge!')));
    post_confirm_advertise();
    page_footer();
    exit();
}

$errs = importparams(
            array('phone',  '/[\d\s-+]$/',                  _("Please enter your phone number")),
            array('name',   '/[a-z]+/i',                    _("Please enter your name")),
            array('email',  'importparams_validate_email'),
            array('showname',
                            '/^1$/',                        "", 0),
            array('f',      '/^1$/',                        "", 0)
        );
if (!$errs)
    $errs = array();

$showform = true;

$p = preg_replace("/[^\d]/", '', $q_phone);
$phone = db_getOne('select mobile from signers left join person on person.id = signers.person_id where signers.id = ?', $signer_id);
if (!$phone)
    err(sprintf(_('No mobile number recorded for SMS signer %d'), $signer_id));
else if (substr($p, -6) != substr($phone, -6)) {
    /* Compare last few characters of the phone numbers only, so that we avoid
     * having to know anything about their format. */
    $errs['phone'] = _("That phone number doesn't match our records");
}
if ($email_err = microsites_invalid_email_address($q_email))
    $errs['email'] = $email_err;

page_header(_('SMS'));

if (count($errs)) {
    /* Form to supply info for the subscription */
    conversion_form($q_f ? $errs : null, $pledge_id);
    page_footer(array('nonav' => 1));
    exit();
}

/* OK, they win. Make them sign on. */
$data = db_getRow('select * from pledges where id = (select pledge_id from signers where signers.id = ?)', $signer_id);
$data['reason_web'] = _('Next, we need to verify your email address.');
$data['template'] = 'sms-confirm';
$P = pb_person_signon($data, $q_email, $q_name);

$r = pledge_is_valid_to_sign($pledge_id, $P->email());

if (!pledge_is_error($r)) {
    /* No existing signer, so make this person the one the SMS-signer 
     * record now uses. */
    $old_person_id = db_getOne('select person.id from signers left join person on person.id = signers.person_id where signers.id = ?', $signer_id);
    if (!$old_person_id) err('Expected old_person_id');
    db_query('update signers set person_id = ?, name = ?, showname = ? where id = ?', array($P->id(), $P->name(), $q_showname ? 't' : 'f', $signer_id));
    if ($old_person_id != $P->id()) {
        db_query('update signers set person_id = ? where person_id = ?', array($P->id(), $old_person_id));
        db_query('delete from person where id = ?', array($old_person_id));
    }
    /* This will only keep their most recent mobile, so what. */
    db_query('update person set mobile = ? where id = ?', array($phone, $P->id()));
} else if ($r == PLEDGE_SIGNED) {
    /* Either the pledge creator or somebody who's already signed up. */
    print p(strong(_('You either made or already signed this pledge!')));
    page_footer();
    exit();
} else
    oops($r);

db_commit();

print p(strong(_('Thanks for signing up to this pledge!')));

post_confirm_advertise();

page_footer();

/* bad_token TOKEN
 * Display some text about TOKEN being invalid. */
function bad_token($x) {
    page_header('SMS');
    if (is_null($x)) {
        err(_("We couldn't recognise the link you've followed"));
    } else {
        $x = htmlspecialchars($x);
        printf(p(_("Sorry, we can't make sense of the code '%s'. Please
        could you re-check the address you typed in; the last part of it should
        be two groups of four letters and numbers, joined by a hyphen (\"-\"),
        something like \"1234-abcd\".")), $x);
    }
    page_footer(array('nonav' => 1));
    exit();
}

/* oops RESULT [WHAT]
 * Given a RESULT (a PLEDGE_... constant) and WHAT, an id of some descriptive blurb
 * (see function), print a paragraph of stuff about why the user couldn't be signed
 * up to the pledge. */
function oops($r, $what = null) {
    page_header(_('SMS'));
    print p(strong(_("Sorry, we couldn't sign you up to that pledge:")));
    if ($r == PLEDGE_FULL || $r == PLEDGE_FINISHED) {
        /* Print a fuller explanation in this (common) case */
        print '<p>';
        if ($r == PLEDGE_FULL) {
            if ($what == 'sms') {
                print _('Unfortunately, before our SMS to you was delivered, somebody else beat you to the last place on that pledge.');
            } elseif (is_null($what)) {
                print _('Unfortunately, before we could subscribe you, somebody else beat you to the last place on that pledge.');
            }
        } else {
            if ($what == 'sms') {
                print _('Unfortunately, before our SMS to you was delivered, the pledge finished.');
            } elseif (is_null($what)) {
                print _('Unfortunately, before we could subscribe you, the pledge finished.');
            }
        }
        print _("We're very sorry &mdash; better luck next time!");
        print '</p>';
    } else {
        print "<p>" . htmlspecialchars(pledge_strerror($r)) . "</p>";
        if (!pledge_is_permanent_error($r))
            print p(strong(_("Please try again a bit later.")));
    }
    page_footer();
    exit();
}

/* conversion_form ERRORS
 * Display the form for a user to convert their SMS subscription to email.
 * ERRORS is an array of errors to display for each field. */
function conversion_form($errs, $pledge_id) {
    global $q_h_token, $q_unchecked_h_phone, $q_unchecked_h_email, $q_unchecked_h_name, $q_showname;
    print _('<h2>Thanks for signing up!</h2>');
    print p(_('On this page you can <strong>let us have your name and email address</strong> so that you can
get email from the pledge creator.  We will then <strong>email you when the pledge succeeds</strong>,
rather than sending an SMS.'));
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
        $p = new Pledge(intval($pledge_id));
        if ($p->pin()) { err(_("No SMS for private pledges")); }
        $p->render_box(array('showdetails' => false));
    }

    $showname = $q_showname ? ' checked' : '';

    print '<form accept-charset="utf-8" class="pledge" method="post" action="/sms" name="pledge">';
    # TRANS: Heading of a form to get details to sign up
    print _('<h2>Get updates by email instead of SMS</h2>');
?>
<input type="hidden" name="f" value="1">
<input type="hidden" name="token" value="<?=$q_h_token ?>">
<p>
<?=_('Phone number:') ?> <input type="text" name="phone" value="<?=$q_unchecked_h_phone ?>"><br>
<?=_('Name:') ?> <input type="text" name="name" value="<?=$q_unchecked_h_name ?>"><br>
<?=_('Email:') ?> <input type="text" name="email" size="30" value="<?=$q_unchecked_h_email ?>"><br>
<?=_('Show my name on this pledge') ?>: <input name="showname" value="1" <?=$showname?> type="checkbox"><br>
<input type="submit" name="submit" value="<?=_('Submit') ?>">
</form>
<?
}
?>
