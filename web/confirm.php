<?php
/*
 * confirm.php:
 * Confirm a user's subscription to a pledge, or creation of a pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: confirm.php,v 1.40 2005-05-26 18:27:02 chris Exp $
 * 
 */

# TODO: delete this file when tokens have expired

require_once "../phplib/db.php";
require_once "../phplib/pb.php";
require_once "../phplib/fns.php";
require_once "../phplib/pledge.php";
require_once "../phplib/auth.php";

require_once "../../phplib/importparams.php";

$err = importparams(
            array('token',      '/.+/',             ""),
            array('type',      '/(pledge|signature)/',             "")
        );

if (!is_null($err))
    err("Sorry -- something seems to have gone wrong");

$local_pledge = false;

if ($q_type == 'pledge') {
    # TODO: Remove all this now pledge confirmation happens in new system
    /* Pledges are confirmed by saving a token in the database and sending it to
     * the user. */
    $pledge_id = pledge_confirm($q_token);
    if (pledge_is_error($pledge_id)) {
        err("That pledge hasn't been recognised.  Please check the URL is copied correctly from your email.");
    }

    /* Success. */
    $q = db_query('select * from pledges where id = ?', $pledge_id);
    $r = db_fetch_array($q);
    $local_pledge = pledge_is_local($row);

    if ($local_pledge) {
        page_header("PRINT THIS - CUT IT UP - DELIVER LOCALLY", array('nonav' => true, 'ref'=>'/'.$r['ref']));
    } else {
        page_header('Pledge Confirmation', array('ref'=>'/'.$r['ref']) );
    }
    db_commit();
    $url = htmlspecialchars(OPTION_BASE_URL . "/" . urlencode($r['ref']));
?>
    <p class="noprint" align="center"><strong>Thank you for confirming your pledge.</strong></p>
    <p class="noprint" align="center">It is now live at <strong><a href="<?=$url?>"><?=$url?></a></strong> and people can sign up to it there.</p>
<?  post_confirm_advertise($r);
} elseif ($q_type == 'signature') {
    /* OK, that wasn't a pledge confirmation token. So we must be signing a
     * pledge. */
    $data = auth_token_retrieve('signup-web', $q_token);
    if (!$data) {
        err("Your signature hasn't been recognised.  Please check the URL is copied correctly from your email.");
    }

    # Hack this to do the person/login thing. We do this by creating a person
    # record, if necessary, then constructing the appropriate stash object and
    # redirecting.

    $signer_id = db_getOne('select signers.id from signers, person where signers.pledge_id = ? and signers.person_id = person.id and person.email = ?', array($data['pledge_id'], $data['email']));
    $P = person_get($data['email']);
    if (is_null($P))
        $P = person_get_or_create($data['email'], $data['name']);

    # Create the stash.
    $key = bin2hex(random_bytes(4));
    $stashed_POST = array('email' => $data['email'], 'name' => $data['name'], 'ref' => db_getOne('select ref from pledges where id = ?', $data['pledge_id']), 'showname' => $data['showname']);
    if (array_key_exists('pin', $data))
        $stashed_POST['pin'] = $data['pin'];
    $ser = '';
    rabx_wire_wr($stashed_POST, $ser);

    # Extra data
    $extra = array('template' => 'signature-confirm', 'reason' => 'sign the pledge');
    $ser2 = '';
    rabx_wire_wr($extra, $ser2);
    
    db_query("insert into requeststash (key, method, url, post_data, extra) values (?, 'POST', ?, ?, ?)", array($key, "/${stashed_POST['ref']}/sign", $ser, $ser2));

    db_commit();
        
    setcookie('pb_person_id', person_cookie_token($P->id()), null, '/', OPTION_WEB_DOMAIN, false);
    stash_redirect($key);

    exit();
}
if ($local_pledge) {
    page_footer(array('nonav'=>true));
} else {
    page_footer();
}

/* oops CODE
 * Print a message explaining CODE. */
function oops($r) {
    global $q_f;
    page_header("Sorry, we couldn't sign you up to that pledge");
    if ($r == PLEDGE_FULL || $r == PLEDGE_FINISHED) {
        /* Print a fuller explanation in this (common) case */
        print "<p><strong>Sorry, we couldn't sign you up to that pledge:</strong></p>";
        $what = $q_f ? 'filling in the form' : 'waiting for our email to arrive'; /* XXX l18n */
        $how = ($r == PLEDGE_FULL ?
                    "somebody else beat you to the last place on that pledge"
                    : "the pledge finished");
        print <<<EOF
<p>Unfortunately, while you were $what, $how.
We're very sorry &mdash; better luck next time!</p>
EOF;
    } else {
        print "<p><strong>Sorry, we couldn't sign you up.</strong></p>";
        print "<p>" . htmlspecialchars(pledge_strerror($r)) . "</p>";
        if (!pledge_is_permanent_error($r))
            print "<p><strong>Please try again a bit later.</strong></p>";
    }
    page_footer();
}

?>
