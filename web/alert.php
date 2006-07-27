<?
// ref-alert.php:
// Signing up for alerts.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: alert.php,v 1.59 2006-07-27 11:14:53 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/pledge.php';
require_once '../phplib/alert.php';
require_once '../phplib/gaze-controls.php';
require_once '../phplib/pbperson.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/gaze.php';

// Get any inputs and process a bit
$email = get_http_var('email');
$location = gaze_controls_get_location();

$track = get_http_var('track');

// Display page
$title = _('New pledge alerts');
page_header($title);
if (get_http_var('subscribe_local_alert')) {
    $errors = do_local_alert_subscribe($location);
    if (is_array($errors)) {
        local_alert_subscribe_box($location, $errors);
    }
} elseif (get_http_var('direct_unsubscribe')) {
    // Clicked from email to unsubscribe
    $alert_id = get_http_var('direct_unsubscribe');
    $P = pb_person_if_signed_on();
    if (!$P) 
        err(_('Unexpectedly not signed on after following unsubscribe link'));
    $desc = alert_h_description($alert_id);
    if ($desc) {
        alert_unsubscribe($P->id(), $alert_id);
        print p(sprintf(_("Thanks!  You won't receive more email about %s."), $desc));
        print(p(_("You might like to <a href=\"/alert/\">subscribe to a new local alert</a>, or <a href=\"/your\">manage all your alerts</a>.")));
    } else {
        print p(_("Thanks!  You are already unsubscribed from that alert."));
    }
} else {
    local_alert_subscribe_box($location);
}

$params = array('nolocalsignup'=>true);
if ($track)
    $params['extra'] = $track;
page_footer($params);

function do_local_alert_subscribe(&$location) {
    global $email, $track;

    $errors = array();
    if (!$email) $errors['email'] = _("Please enter your email address");
    if (!validate_email($email)) $errors['email'] = _("Please enter a valid email address");
    gaze_controls_validate_location($location, $errors);

    if (count($errors))
        return $errors;

    /* Get the user to log in. */
    $r = array();
    $r['reason_web'] = _('Before subscribing you to local pledge email alerts, we need to confirm your email address.');
    $r['reason_email'] = _("You'll then be emailed whenever a new pledge appears in your area.");
    $r['reason_email_subject'] = _("Subscribe to local pledge alerts at PledgeBank.com");
    $person = pb_person_signon($r, $email);
    $params = $location;
    alert_signup($person->id(), "pledges/local", $params);
    db_commit();
    if ($track)
        $track .= '; subscribed'
?>
<p class="loudmessage" align="center"><?=_("Thanks for subscribing!  You'll now get emailed once a day when there are new pledges in your area.") ?> </p>

<p class="noisymessage"><?=_("To add an alert for another place, <a href=\"/alert\">click here</a>.")?></p>

<p class="noisymessage"><?=_("To see all your local pledge alerts, <a href=\"/your\">click here</a>.")?></p>

<? if ($params['country'] == 'GB') { ?>
<p class="loudmessage"><strong><?=_('Have a <a href="http://www.hearfromyourmp.com">long term relationship</a> with your MP!')?></strong>
<? } else { ?>
<p class="loudmessage"><?=_('Make your own <a href="/new/">new pledge</a>! Others in your area will be emailed about it automatically.')?>
<? } ?>
<?
}

/* Display form for email alert sign up. */
function local_alert_subscribe_box($location, $errors = array()) {
    global $email, $track;

    $P = pb_person_if_signed_on();
    if (!is_null($P)) {
        if (is_null($email) || !$email)
            $email = $P->email();
    }

    $disambiguate_form = false;
    if ($errors == array('gaze_place' => 'NOTICE')) {
        $disambiguate_form = true;
        unset($errors['gaze_place']); # remove NOTICE
    }

    if (count($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    }

 ?>

<form accept-charset="utf-8" class="pledge" name="pledge" method="post" action="/alert">
<input type="hidden" name="subscribe_local_alert" value="1">
<?  if ($track)
        print '<input type="hidden" name="track" value="' . htmlentities($track) . '">';
?>
<?    if ($disambiguate_form) { ?>
<input type="hidden" name="email" value="<?=htmlspecialchars($email)?>"> 
<input type="hidden" name="country" value="<?=$location['country']?>">
<input type="hidden" name="prev_country" value="<?=$location['country']?>">
<?    } else { ?>
<h2><?=_('Get emails about local pledges') ?></h2>
<p><?=_("Fill in the form, and we'll email you when someone creates a new pledge near you.") ?></p>

<p>
<label for="email"><strong><?=_('Email:') ?></strong></label> 
<input <? if (array_key_exists('email', $errors)) print ' class="error"' ?> type="text" size="20" name="email" id="email" value="<?=htmlspecialchars($email) ?>">
</p>
<p><strong><?=_('Country:') ?></strong>
<? gaze_controls_print_country_choice($location['country'], $location['state'], $errors, array('noglobal'=>true, 'gazeonly'=>true)); ?>
</p>

<p>
<strong><?=_("Where in that country?")?></strong>
<?    } ?>

<? gaze_controls_print_place_choice($location['place'], $location['gaze_place'], $location['places'], $errors, $location['postcode']); ?>

<p><input type="submit" name="submit" value="<?=_('Subscribe') ?>"></p>

</form>

<? 
}

?>
