<?
// ref-alert.php:
// Signing up for alerts.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: alert.php,v 1.12 2005-06-24 09:51:10 matthew Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/auth.php';
require_once '../phplib/alert.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';

$title = _('New pledge alerts');
page_header($title);
if (get_http_var('subscribe_alert')) {
    $errors = do_local_alert_subscribe();
    if (is_array($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
        local_alert_subscribe_box();
    }
} elseif (get_http_var('direct_unsubscribe')) {
    // Clicked from email to unsubscribe
    $alert_id = get_http_var('direct_unsubscribe');
    $P = person_if_signed_on();
    if (!$P) 
        err(_('Unexpectedly not signed on after following unsubscribe link'));
    $desc = alert_h_description($alert_id);
    if ($desc) {
        alert_unsubscribe($P->id(), $alert_id);
        printf(_("<p>Thanks!  You won't receive more email about %s.</p>"), $desc);
    } else {
        print _("<p>Thanks!  You are already unsubscribed from that alert.</p>");
    }
} else {
    local_alert_subscribe_box();
}
page_footer();

function do_local_alert_subscribe() {
    global $q_email, $q_name, $q_showname, $q_ref, $q_pin;
    $errors = importparams(
                array('email',      '/^[^@]+@.+/',      _('Please give your email')),
                array('postcode',      "importparams_validate_postcode")
            );
    if (!is_null($errors))
        return $errors;

    /* Get the user to log in. */
    $r = array();
    $r['reason_web'] = _('Before subscribing you to local pledge email alerts, we need to confirm your email address.');
    $r['reason_email'] = _("You'll then be emailed whenever a new pledge appears in your area.");
    $r['reason_email_subject'] = _("Subscribe to local pledge alerts at PledgeBank.com");
    $person = person_signon($r, $q_email);

    db_query('insert into local_alert (person_id, postcode) values (?, ?)', array($person->id(), $q_email));
    db_commit();
        ?>
<p id="loudmessage" align="center"><?=_("Thanks for subscribing!  When this is finished, you'll get emailed when there are new pledges in your area.") ?> <a href="/"><?=_('PledgeBank home page') ?></a></p>
<?
}

/* Display form for email alert sign up. */
function local_alert_subscribe_box() {
    $email = get_http_var('email');
    $postcode = get_http_var('postcode');

    $P = person_if_signed_on();
    if (!is_null($P)) {
        if (is_null($email) || !$email)
            $email = $P->email();
    }

?>
<form accept-charset="utf-8" class="pledge" name="localalert" action="/alert" method="post">
<input type="hidden" name="subscribe_alert" value="1">
<?=_('<h2>Get emails about local pledges (UK)</h2>') ?>
<?=_("<p>Fill in the form, and we'll email you when someone creates a new pledge near you.</p>") ?>
<p>
<label for="email"><strong><?=_('Email:') ?></strong></label> 
<input type="text" size="20" name="email" id="email" value="<?=htmlspecialchars($email) ?>">
<label for="postcode"><strong><?=_('UK Postcode:') ?></strong></label> 
<input type="text" size="15" name="postcode" id="postcode" value="<?=htmlspecialchars($postcode) ?>">
<input type="submit" name="submit" value="<?=_('Subscribe') ?>">
</p>
</form>

<? 

}

?>
