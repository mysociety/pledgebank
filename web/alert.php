<?
// ref-alert.php:
// Signing up for alerts.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: alert.php,v 1.29 2005-08-26 14:08:54 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/pledge.php';
require_once '../phplib/alert.php';
require_once '../../phplib/person.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/gaze.php';

// Get any inputs and process a bit
$email = get_http_var('email');
$country = get_http_var('country');
$state = null;
if ($country) {
    $a = array();
    if (preg_match('/^([A-Z]{2}),(.+)$/', $country, $a))
        list($x, $country, $state) = $a;
}
$place = get_http_var('place');
if ($country && $country == 'Global')
    $place = null;
else {
    # Check gaze has this country
    $countries_with_gazetteer = gaze_get_find_places_countries();
    gaze_check_error($countries_with_gazetteer);
    if (!in_array($country, $countries_with_gazetteer)) {
        $place = null;
    }
}
$gaze_place = get_http_var('gaze_place');
$postcode = get_http_var('postcode');
if ($country && $country != 'GB') $postcode = '';
if ($country && $country == '(choose one)') $country = null;
if ($country && $country == '(separator)') $country = null;
 
// Display page
$title = _('New pledge alerts');
page_header($title, array("gazejs" => true));
if (get_http_var('subscribe_local_alert')) {
    $errors = do_local_alert_subscribe();
    if (is_array($errors)) {
        local_alert_subscribe_box($errors);
    }
} elseif (get_http_var('direct_unsubscribe')) {
    // Clicked from email to unsubscribe
    $alert_id = get_http_var('direct_unsubscribe');
    $P = person_if_signed_on();
    if (!$P) 
        err(_('Unexpectedly not signed on after following unsubscribe link'));
    $desc = alert_h_description($alert_id);
    print '<p>';
    if ($desc) {
        alert_unsubscribe($P->id(), $alert_id);
        printf(_("Thanks!  You won't receive more email about %s."), $desc);
    } else {
        print _("Thanks!  You are already unsubscribed from that alert.");
    }
    print '</p>';
} else {
    local_alert_subscribe_box();
}
page_footer();

function do_local_alert_subscribe() {
    global $email, $country, $state, $place, $gaze_place, $postcode;
    $errors = array();
    if (!$email) $errors['email'] = _("Please enter your email address");
    if (!validate_email($email)) $errors['email'] = _("Please enter a valid email address");
    if (!$country) $errors['country'] = _("Please choose a country");
    if ($country == 'GB') {
        if ($postcode && $place)
            $errors['place'] = _("Please enter either a place name or a postcode, but not both");
    } else {
        if ($postcode)
            $errors['postcode'] = _("You can only enter a postcode if your pledge applies to the UK");
    }
    if ($postcode) {
        if (!validate_postcode($postcode))
            $errors['postcode'] = _('Please enter a valid postcode or first part of a postcode; for example, OX1 3DR or WC1.');
        else if (mapit_get_error(mapit_get_location($postcode, 1)))
            $errors['postcode'] = _("We couldn't recognise that postcode or part of a postcode; please re-check it");
        else
            $postcode = canonicalise_postcode($postcode);
    } elseif ($place) {
        if (!$gaze_place) {
            $errors['gaze_place'] = "NOTICE";
        }
    } else {
        if ($country == 'GB') {
            $errors['place'] = _("Please enter either a place name or a postcode");
        } else {
            $errors['place'] = _("Please enter a place name");
        }
    }
    if ($place && ($country != get_http_var('prev_country') || $place != get_http_var('prev_place'))) {
        $errors['gaze_place'] = "NOTICE";
    }
    if (count($errors))
        return $errors;

    /* Get the user to log in. */
    $r = array();
    $r['reason_web'] = _('Before subscribing you to local pledge email alerts, we need to confirm your email address.');
    $r['reason_email'] = _("You'll then be emailed whenever a new pledge appears in your area.");
    $r['reason_email_subject'] = _("Subscribe to local pledge alerts at PledgeBank.com");
    $person = person_signon($r, $email);
    $params = array();
    $params['country'] = $country;
    $params['state'] = $state;
    $params['place'] = $place;
    $params['gaze_place'] = $gaze_place;
    $params['postcode'] = $postcode;
    alert_signup($person->id(), "pledges/local", $params);
    db_commit();
        ?>
<p class="loudmessage" align="center"><?=_("Thanks for subscribing!  You'll now get emailed once a day when there are new pledges in your area.") ?> </p>

<p class="noisymessage"><strong><?=_("Summer pledgin'!")?></strong> <?=_("Given that it is summer time, why not use PledgeBank to ")?>
<a href="<?=OPTION_BASE_URL."/new/streetparty"?>"><?=_("organise a street party")?></a>?
Or <a href="<?=OPTION_BASE_URL."/new/picnic"?>"><?=_("a picnic")?></a>?
<?
}

/* Display form for email alert sign up. */
function local_alert_subscribe_box($errors = array()) {
    global $email, $country, $state, $place, $gaze_place, $postcode;

    $places = null;
    if ($place) {
        # Look up nearby places
        $places = gaze_find_places($country, $state, $place, 10);
        gaze_check_error($places);
        if (array_key_exists('gaze_place', $errors)) {
            if (count($places) > 0) {
                print '<div id="formnote"><ul><li>';
                print _('Please select one of the possible places; if none of them is right, please type the name of another nearby place');
                print '</li></ul></div>';
            } else {
                $errors['place'] = sprintf(_("Unfortunately, we couldn't find anywhere with a name like '%s'.  Please try a different spelling, or another nearby village, town or city."),
                htmlspecialchars($place));
            }
          unset($errors['gaze_place']); # remove NOTICE
        } 
    }

    $P = person_if_signed_on();
    if (!is_null($P)) {
        if (is_null($email) || !$email)
            $email = $P->email();
    }

    if (count($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    }
    
 ?>

<form accept-charset="utf-8" class="pledge" name="pledge" method="post" action="/alert">
<input type="hidden" name="subscribe_local_alert" value="1">
<h2><?=_('Get emails about local pledges') ?></h2>
<p><?=_("Fill in the form, and we'll email you when someone creates a new pledge near you.") ?></p>
<p>

<p>
<label for="email"><strong><?=_('Email:') ?></strong></label> 
<input <? if (array_key_exists('email', $errors)) print ' class="error"' ?> type="text" size="20" name="email" id="email" value="<?=htmlspecialchars($email) ?>">
</p>

<p><strong><?=_('Country:') ?></strong>
<? pb_view_gaze_country_choice($country, $state, $errors, array('noglobal'=>true, 'gazeonly'=>true)); ?>
</p>

<p id="ifyes_line">
<strong><?=_("Where in that country?")?></strong>
<? pb_view_gaze_place_choice($place, $gaze_place, $places, $errors); ?>

<p><input type="submit" name="submit" value="<?=_('Subscribe') ?>"></p>

</form>

<? 
}

?>
