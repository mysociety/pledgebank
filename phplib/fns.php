<?  
// fns.php:
// General functions for PledgeBank
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: fns.php,v 1.162 2007-07-18 10:41:42 francis Exp $

require_once '../phplib/alert.php';
require_once '../phplib/gaze-controls.php';
require_once '../phplib/microsites.php';
require_once '../phplib/pbperson.php';
require_once "../../phplib/evel.php";
require_once '../../phplib/utility.php';
require_once '../../phplib/gaze.php';
require_once '../../phplib/datetime.php';
require_once '../../phplib/importparams.php';
require_once "pledge.php";

// HTML shortcuts
function p($s) { return "<p>$s</p>\n"; }
function h2($s) { return "<h2>$s</h2>\n"; }
function h3($s) { return "<h3>$s</h3>\n"; }
function strong($s) { return "<strong>$s</strong>"; }
function dt($s) { print "<dt>$s</dt>\n"; }
function dd($s) { print "<dd>$s</dd>\n"; }
function li($s) { return "<li>$s</li>\n"; }

// Language domains, such as promessotheque.com. 
if (OPTION_WEB_DOMAIN == 'pledgebank.com') {
    $language_domains = array(
        'eo' => 'promesobanko.com',
        'fr' => 'promessotheque.com',
    );
} elseif (OPTION_WEB_DOMAIN == 'pledgebank.cat') {
    // Francis's test ones
    $language_domains = array(
        'eo' => 'promesobanko.cat',
        'fr' => 'promessotheque.cat',
    );
} else {
    $language_domains = array();
}

# pb_domain_url returns current URL with country and language in it.
# Defaults to keeping country country or language, unless param contains:
#   'lang' - language to change to, or "explicit" to explicitly include current language in URL
#   'country' - country to change to, or "explicit" to explicitly include current country in URL
#   'microsite' - certain microsite, or "explicit" to explicitly include current microsite in URL
#   'explicit' - if present and true, overrides lang, country and microsite to "explicit"
# Parameters are:
#   'path' - path component, if not present uses request URI
function pb_domain_url($params = array('path'=>'/')) {
    global $domain_lang, $microsite, $lang, $site_country, $locale_current, $locale_stack, $language_domains;

    if (array_key_exists('explicit', $params) && $params['explicit']) {
        $params['lang'] = 'explicit';
        $params['country'] = 'explicit';
        $params['microsite'] = 'explicit';
    }

    # Language
    $l = $domain_lang;
    if (array_key_exists('lang', $params))
        $l = ($params['lang'] == "explicit") ? $lang : $params['lang'];
    # Where language has been changed, use that in URL
    # (this is mainly for emails about pledges)
    if ($locale_current && count($locale_stack) > 0)
        $l = $locale_current; 

    # Country / microsite
    $c = $microsite;
    if (array_key_exists('country', $params))
        $c = ($params['country'] == "explicit") ? $site_country : $params['country'];
    if (array_key_exists('microsite', $params)) {
        if ($params['microsite'] == 'explicit') {
            if ($microsite)
                $c = $microsite;
        } else {
            $c = $params['microsite'];
        }
    }

    $url = 'http://';

    global $microsites_to_extra_domains;
    if (array_key_exists($c, $microsites_to_extra_domains)) {
        # For example, pledge.global-cool.com
        $url .= $microsites_to_extra_domains[$c];
    } else {
        # Construct URL using microsite/country and/or language
        if ($c)
            $url .= strtolower("$c.");
        else
            $url .= 'www.';
        if  (array_key_exists($l, $language_domains)) {
            $url .= $language_domains[$l];
        } else { 
            if ($l)
                $url .= "$l.";
            # XXX: Not sure this is accurate
            if (OPTION_WEB_HOST != 'www' && OPTION_WEB_HOST != $c) {
                $url .= OPTION_WEB_HOST.'.';
            }
            $url .= OPTION_WEB_DOMAIN;
        }
    }

    if (array_key_exists('path', $params) && $params['path'])
        $url .= htmlspecialchars($params['path']);
    else
        $url .= htmlspecialchars($_SERVER['REQUEST_URI']);
    return $url;
}

// Used by email and Facebook sending
function pb_message_add_template_values($values) {
    // TODO: perhaps these days, this pb_send_email_template should take a pledge
    // object as a parameter, and the $values should only be extra values
    $p = null;
    if (array_key_exists('id', $values))
        $p = new Pledge($values['id']);
    elseif (array_key_exists('title', $values))
        $p = new Pledge($values);

    if ($p) {
        $values['sentence_first'] = $p->sentence(array('firstperson' => true));
        $values['sentence_first_withname'] = $p->sentence(array('firstperson' => 'includename'));
    }

    if (array_key_exists('id', $values)) {
        $values['actual'] = db_getOne('select count(id) from signers where pledge_id = ?', $values['id']);
        if ($values['actual'] >= $values['target'])
            $values['exceeded_or_met'] = ($values['actual'] > $values['target'] ? _('exceeded') : _('met'));
    }
    if (array_key_exists('ref', $values)) {
        $values['pledge_url'] = pb_domain_url(array('path'=> "/" . $values['ref']));
        $values['pledge_url_email'] = pb_domain_url(array('path'=> "/" . $values['ref'] . "/email"));
        $values['pledge_url_flyers'] = pb_domain_url(array('path'=> "/" . $values['ref'] . "/flyers"));
        $values['pledge_facebook_url'] = OPTION_FACEBOOK_CANVAS . $values['ref'];
    }
    if (array_key_exists('date', $values))
        $values['pretty_date'] = prettify($values['date'], false);
    if (array_key_exists('name', $values)) {
        $values['creator_name'] = $values['name'];
        $values['name'] = null;
    }
    if (array_key_exists('email', $values)) {
        $values['creator_email'] = $values['email'];
        $values['email'] = null;
    }
    if (array_key_exists('signers', $values))
        $values['signers_ordinal'] = ordinal($values['signers']);
    $values['sms_number'] = OPTION_PB_SMS_DISPLAY_NUMBER;
    $values['pledgebank_url'] = pb_domain_url(array('path'=>'/'));
        
    $values['signature'] = _("-- the PledgeBank.com team");

    return $values;
}

// $to can be one recipient address in a string, or an array of addresses
function pb_send_email_template($to, $template_name, $values, $headers = array()) {
    $values = pb_message_add_template_values($values);
    $template = file_get_contents("../templates/emails/$template_name");
    $template = _($template);

    $spec = array(
        '_template_' => $template,
        '_parameters_' => $values
    );
    $spec = array_merge($spec, $headers);
    return pb_send_email_internal($to, $spec);
}

// $to can be one recipient address in a string, or an array of addresses
function pb_send_email($to, $subject, $message, $headers = array()) {
    $spec = array(
        '_unwrapped_body_' => $message,
        'Subject' => $subject,
    );
    $spec = array_merge($spec, $headers);
    return pb_send_email_internal($to, $spec);
}

function pb_send_email_internal($to, $spec) {
    // Construct parameters

    // Add standard PledgeBank from header
    if (!array_key_exists("From", $spec)) {
        $spec['From'] = '"' . _('PledgeBank.com') . '" <' . OPTION_CONTACT_EMAIL . ">";
    }

    // With one recipient, put in header.  Otherwise default to undisclosed recip.
    if (!is_array($to)) {
        $spec['To'] = $to;
        $to = array($to);
    }

    // Send the message
    $result = evel_send($spec, $to);
    $error = evel_get_error($result);
    if ($error) 
        error_log("pb_send_email_internal: " . $error);
    $success = $error ? FALSE : TRUE;

    return $success;
}

# PledgeBank version of local date parsing
function parse_date($date) {
    global $pb_time, $lang, $site_country;
    return datetime_parse_local_date($date, $pb_time, $lang, $site_country);
}

function view_friends_form($p, $errors = array(), $track=null) {
    $name = get_http_var('fromname', true);
    $email = get_http_var('fromemail');
    $P = pb_person_if_signed_on();
    if (!is_null($P)) {
        if (is_null($name) || !$name)
            $name = $P->name_or_blank();
        if (is_null($email) || !$email)
            $email = $P->email();
    }
    
    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    } else {
        # <p>Here's a reminder of the pledge you're telling people about:</p> - Not sure this is necessary
    }
    $p->render_box(array('showdetails'=>false));
?>
<form id="pledgeaction" name="pledge" action="<?=$p->url_main() ?>/email" method="post"><input type="hidden" name="ref" value="<?=$p->url_main() ?>">
<?  if ($track)
        print '<input type="hidden" name="track" value="' . htmlentities($track) . '">';
    if (get_http_var('pin', true)) print '<input type="hidden" name="pin" value="'.htmlspecialchars(get_http_var('pin', true)).'">';
print h2(_('Email this pledge'));
if (microsites_intranet_site())
    print p('Please enter these details so that we can send your message to your contacts.');
else
    print p(_('Please enter these details so that we can send your message to your contacts.
We will not give or sell either your or their email address to anyone else.')); ?>
<p><strong><?=_('Other people\'s email addresses:') ?></strong></p>
<div class="formrow"><input <? if (array_key_exists('email1', $errors)) print ' class="error"' ?> type="text" name="email1" value="<? if (get_http_var('email1')) print htmlentities(get_http_var('email1'));?>" size="40"></div>
<div class="formrow"><input <? if (array_key_exists('email2', $errors)) print ' class="error"' ?> type="text" name="email2" value="<? if (get_http_var('email2')) print htmlentities(get_http_var('email2'));?>" size="40"></div>
<div class="formrow"><input <? if (array_key_exists('email3', $errors)) print ' class="error"' ?> type="text" name="email3" value="<? if (get_http_var('email3')) print htmlentities(get_http_var('email3'));?>" size="40"></div>
<div class="formrow"><input <? if (array_key_exists('email4', $errors)) print ' class="error"' ?> type="text" name="email4" value="<? if (get_http_var('email4')) print htmlentities(get_http_var('email4'));?>" size="40"></div>
<div class="formrow"><input <? if (array_key_exists('email5', $errors)) print ' class="error"' ?> type="text" name="email5" value="<? if (get_http_var('email5')) print htmlentities(get_http_var('email5'));?>" size="40"></div>

<p><strong><?=_('Add a message, if you want:') ?></strong></p>
<div class="formrow"><textarea <? if (array_key_exists('frommessage', $errors)) print ' class="error"' ?> name="frommessage" rows="8" cols="40"></textarea></div>

<p>
<div class="formrow"><strong><?=_('Your name:') ?></strong> <input <? if (array_key_exists('fromname', $errors)) print ' class="error"' ?> type="text" name="fromname" value="<?=htmlspecialchars($name) ?>" size="20">
<br><strong><?=_('Email:') ?></strong> <input <? if (array_key_exists('fromemail', $errors)) print ' class="error"' ?> type="text" name="fromemail" value="<?=htmlspecialchars($email) ?>" size="30"></div>

<p><input name="submit" type="submit" value="<?=_('Send message') ?>"></p>

</form>

<?
}

// Return array of country codes for countries which have SMS
function sms_countries() {
    return array('GB');
}
// Return description of countries which have SMS (for use in FAQ)
function sms_countries_description() {
    return _("the UK");
}
// Return whether site country or microsite country supports SMS
function sms_site_country() {
    global $site_country;
    if (in_array(microsites_site_country(), sms_countries()))
        return true;
    if (!$site_country) 
        return false;
    return in_array($site_country, sms_countries());
}

function pb_get_change_country_link($or_site = true) {
    global $site_country, $microsite;
    $change = '';
    if ($microsite) {
        if ($or_site && microsites_change_microsite_allowed()) {
            # TRANS: i.e. "choose website" or "choose local place where action will be taken?" I've assumed the former. (Tim Morley, 2005-11-21)
            # Yes, it's choose website. (Matthew Somerville, http://www.mysociety.org/pipermail/mysociety-i18n/2005-November/000092.html)
            $change = _("choose site");
        }
    } elseif ($site_country)
        $change = _("change country");
    else
        $change = _("choose country");
    if ($change) {
        $change = ' (<a href="/where?r='.urlencode($_SERVER['REQUEST_URI']).'">' . $change . '</a>)';
    }
    return $change;
}

function pb_get_change_language_link() {
    $change = '<a href="/lang?r='.urlencode($_SERVER['REQUEST_URI']).'">';
    $change .= _("change language");
    $change .= '</a>';
    return $change;
}

function pb_print_change_language_links($path = null) {
    global $lang, $langs, $site_country;
    print _('Available in');
    $out = array();
    foreach ($langs as $l => $pretty) {
        $params = array('lang'=>$l, 'country'=>$site_country);
        if ($path)
            $params['path'] = $path;
        $url = pb_domain_url($params);
        if ($l == $lang) $o = '<strong>';
        else $o = '<a href="'.$url.'" lang="' . $l . '" hreflang="' . $l . '">';
        $o .= $pretty;
        if ($l == $lang) $o .= '</strong>';
        else $o .= '</a>';
        $out[] = $o;
    }
    $first = array_splice($out, 0, -2);
    if (count($first)) print ' ' . join(', ', $first) . ',';
    if (count($out) >= 2)
        print ' ' . $out[count($out)-2] . ' ' . _('and') . ' ' . $out[count($out)-1];
    elseif (count($out) == 1)
        print ' ' . $out[0];
    print '. <br><a href="/translate/">'._('Translate PledgeBank into your language').'</a>.';
}

/* pb_site_pledge_filter_main SQL_PARAMS
 * Returns an SQL query string fragment with the clause for the site.
 * e.g. Country only pledges, or glastonbury only pledges.
 * We return pledges of any language, as the country/microsite will
 * be very specific.
 * SQL_PARAMS is array ref, query parameters are pushed on here. 
 */
function pb_site_pledge_filter_main(&$sql_params) {
    global $site_country, $microsite, $lang; 

    if ($microsite) {
        return microsites_filter_main($sql_params);
    } else {
        $query_fragment = "(";
        if ($site_country) {
            $query_fragment .= "country = ?";
            $sql_params[] = $site_country;
        } else {
            $query_fragment .= "1 = 0"; # get no pledges
        }
        # also show all Esperanto pledges when in Esperanto
        if ($lang == 'eo') {
            $query_fragment .= " or lang = ?";
            $sql_params[] = $lang;
        }
        $query_fragment .= ")";
        return $query_fragment;
    }
}
/* pb_site_pledge_filter_general
 * Returns SQL query fragment for getting general pledges, i.e. global ones. 
 * Compare pb_site_pledge_filter_main.
 */
function pb_site_pledge_filter_general(&$sql_params) {
    global $lang, $microsite;
    if ($microsite) {
        return microsites_filter_general($sql_params);
    } else {
        # In Esperanto we have already caught these above
        if ($lang == 'eo') {
            return "(1=0)";
        } else {
            $sql_params[] = $lang; 
            return "(country IS NULL AND lang = ?)";
        }
    }
}
/* pb_site_pledge_filter_foreign
 * Same as pb_site_pledge_filter_main except returns foreign pledges.
 * i.e. for other countries only. */
function pb_site_pledge_filter_foreign(&$sql_params) {
    global $site_country, $microsite, $lang; 
    if ($microsite) {
        return microsites_filter_foreign($sql_params);
    } else {
        $locale_clause = "(";
        if ($site_country) {
            $locale_clause .= "country <> ?";
            $sql_params[] = $site_country;
        } else {
            $locale_clause .= "1 = 0"; # get no pledges
        }
        # Esperanto pledges already shown, so don't include again in foreign
        if ($lang == 'eo') {
            $locale_clause .= " and lang <> ?";
            $sql_params[] = $lang;
        }
        $locale_clause .= ")";
        return $locale_clause;
    }
}

/* Prints description of main OR general filter with links to change
 * country/language/microsite */
function pb_print_filter_link_main_general($attrs = "") {
    global $site_country, $lang, $langs, $microsite;
    $change_country = pb_get_change_country_link();
    $change_language = pb_get_change_language_link();
    $langname = $langs[$lang];

    if ($microsite) {
        print "<p $attrs>";
        if ($microsite == 'everywhere') 
            printf(_('%s%s pledges listed'), microsites_get_name(), $change_country);
        elseif ($change_country)
            printf(_('%s%s pledges only listed'), microsites_get_name(), $change_country);
        print '</p>';
    }
    else {
        print "<p $attrs>";
        # TRANS: Worth thinking about word order here. The English reads e.g. "UK (change country) pledges and global English (change language) pledges listed. Even in English, and certainly in other languages, it'd probably be clearer as something like: "Listed below are pledges for the UK (change country) and global pledges written in English (change language)." (Tim Morley, 2005-11-27)
        if ($site_country)
            printf(_('%s%s pledges and global %s (%s) pledges listed'), pb_site_country_name('in'), $change_country, $langname, $change_language);
        else
            printf(_('%s%s pledges in %s (%s) only listed'), pb_site_country_name('in'), $change_country, $langname, $change_language);
        print '</p>';
    }
}

function pb_print_no_featured_link() {
    $change = pb_get_change_country_link();
    print '<p>';
    if ($change)
        printf(_('There are no featured pledges for %s%s at the moment.'), pb_site_country_name('to'), $change);
    else
        print _('There are no featured pledges at the moment.');
    print '</p>';
}

/* pb_site_country_name
 * Returns name of site/microsite to display next to PledgeBank logo. */
function pb_site_country_name($fr_prep = '') {
    global $countries_code_to_name, $site_country, $microsite; 
    if ($microsite)
        return microsites_get_name();
    elseif (!$site_country)
        return 'Global';
    else {
        if ($fr_prep == 'to')
            return countries_with_to($site_country);
        elseif ($fr_prep == 'in')
            return countries_with_in($site_country);
        else
            return $countries_code_to_name[$site_country];
    }
}

// Return SQL fragment which guesses as to what the number of signers will
// reach, if rate in last 7 days continues until deadline.
function pb_chivvy_probable_will_reach_clause() {
    global $pb_timestamp, $pb_today;
    return "(round((select count(*) from signers 
        where signers.pledge_id = pledges.id
            and signers.signtime > '$pb_timestamp'::timestamp - '7 day'::interval)::numeric 
                / 7::numeric * (date - '$pb_today'),0) + 
        (select count(*) from signers where signers.pledge_id = pledges.id))";
}

/* pb_pretty_distance DISTANCE COUNTRY [AWAY]
 * Given DISTANCE, in km, and an ISO COUNTRY code, return a text string
 * describing the DISTANCE in human-readable form, accounting for particular
 * local foibles of the given COUNTRY. Specificy AWAY as false to not put
 * "away" at the end of the string, or round distances less than 1 km/mile. */
function pb_pretty_distance($distance, $country, $away = true) {
    $dist_miles = round($distance / 1.609344, 0);
    $dist_usmiles = round($distance / 1.6093472, 0);
    $dist_km = round($distance, 0);
    if ($away && $country == 'US' && $dist_usmiles < 1)
        return _('less than 1 mile away');
    elseif ($away && $country == 'GB' && $dist_miles < 1)
        return _('less than 1 mile away');
    elseif ($away && $dist_km < 1)
        return _('less than 1 km away');
    elseif ($country == 'US')
        return sprintf(($away ? ngettext('%d mile away', '%d miles away', $dist_usmiles)
            : ngettext('%d mile', '%d miles', $dist_usmiles)), $dist_usmiles);
    elseif ($country == 'GB')
        return sprintf(($away ? ngettext('%d mile away', '%d miles away', $dist_miles)
            : ngettext('%d mile', '%d miles', $dist_miles)), $dist_miles);
    else
        return sprintf($away ? _('%d km away') : _('%d km'), $dist_km);
}

# pb_view_local_alert_quick_signup
# Display quick signup form for local alerts. Parameters can contain:
# newflash - if true, put message in bold and show "works in any country" flash
# place - default value for place
global $place_postcode_label; # ids must be unique (this will break 
# the javascript on the few pages which have this form twice, but I couldn't
# see an easy worthwhile way round this)
function pb_view_local_alert_quick_signup($class, $params = array('newflash'=>true)) {
    global $place_postcode_label, $microsite;
    $email = '';
    $P = pb_person_if_signed_on();
    if (!is_null($P)) {
        $email = $P->email();
    } 
    $newflash = false;
    if (array_key_exists('newflash', $params) && $params['newflash'])
        $newflash = true;
    $place = "";
    if (array_key_exists('place', $params) && $params['place'])
        $place = $params['place'];

    # Microsite specific changes
    $london = false;
    $any_country = true;
    $force_country = false;
    if ($microsite && $microsite == 'london') {
        $london = true;
        $any_country = false;
        $force_country = 'GB';
    }
?>
<form accept-charset="utf-8" id="<?=$class?>" name="localalert" action="/alert" method="post">
<input type="hidden" name="subscribe_local_alert" value="1">
<?  if (array_key_exists('track', $params) && $params['track'])
        print '<input type="hidden" name="track" value="' . htmlentities($params['track']) . '">';
?>
<p><strong><?=$london ? 'Sign up for emails when people make pledges in your part of London' : _('Sign up for emails when people make pledges in your local area')?> 
<? if ($any_country) { ?>
<?=$newflash?'&mdash;':''?> <?=$newflash?_('Works in any country!'):''?> 
<? } ?>
</strong>
<br><span style="white-space: nowrap"><?=_('Email:') ?> <input type="text" size="18" name="email" value="<?=htmlspecialchars($email) ?>"></span>
<? if ($force_country) { ?>
<input type="hidden" name="country" value="<?=$force_country?>">
<span style="white-space: nowrap"><?=_('Postcode:')?>&nbsp;<input type="text" size="12" name="place" value="<?=htmlspecialchars($place)?>"></span>
<? } else { ?>
<span style="white-space: nowrap"><?=_('Country:') ?> <? gaze_controls_print_country_choice(microsites_site_country(), null, array(), array('noglobal' => true, 'gazeonly' => true)); ?></span>
<span style="white-space: nowrap"><span id="place_postcode_label<?=($place_postcode_label ? $place_postcode_label : '')?>"><?=_('Town:')?></span>&nbsp;<input type="text" size="12" name="place" value="<?=htmlspecialchars($place)?>"></span>
<? } ?>
<input type="submit" name="submit" value="<?=_('Subscribe') ?>"> </p>
</form>
<?
    $place_postcode_label++;
}

# Change/update your personal details
# (called from phplib/page.php and web/your.php)
function change_personal_details($yourpage = false) {
    global $q_UpdateDetails, $q_pw1, $q_pw2, $P;
    $idclass = 'id="setpassword"';
    if ($yourpage) 
        $idclass = "";
    $has_password = $P->has_password();
    ?>
    <div class="noprint">
    <form <?=$idclass?> name="setpassword" action="/your" method="post"><input type="hidden" name="UpdateDetails" value="1">
    <? if ($yourpage) { ?>
    <h2><?=$P->has_password() ? _('Change password') : _('Set password') ?></h2>
    <? } ?>
    <?

    importparams(
    #        array('email',          '/./',          '', null),
            array('pw1',            '/[^\s]+/',     '', null),
            array('pw2',            '/[^\s]+/',     '', null),
            array('UpdateDetails',  '/^.+$/',       '', false)
    );

    $error = null;
    if ($q_UpdateDetails) {
        if (is_null($q_pw1) || is_null($q_pw2))
            $error = _("Please type your new password twice");
        elseif (strlen($q_pw1)<5 || strlen($q_pw2)<5)
            $error = _('Your password must be at least 5 characters long');
        elseif ($q_pw1 != $q_pw2)
            $error = _("Please type the same password twice");
        else {
            $P->password($q_pw1);
            db_commit();
            print '<p class="success">' . ($has_password ? _('Password successfully updated') 
                : _('Password successfully set'))
            . '</p>';
            $has_password = true;

        }
    }
    if (!is_null($error))
        print "<p id=\"error\">$error</p>";
    ?>
    <p><?=$yourpage ? '' : '<strong>'?><?=$has_password ? _('If you wish to change your password, you can do so here.') 
        : _('Set a password, and we won\'t need to check your email address each time you use PledgeBank.') ?>
    <?=$yourpage ? '' : '</strong>'?></p>
    <p>
    <?=_('New password:') ?> <input type="password" name="pw1" id="pw1" size="15">
    <br><?=_('New password, again:') ?> <input type="password" name="pw2" id="pw2" size="10">
    <input name="submit" type="submit" value="<?=_('Submit') ?>"></p>
    </form>
    <p>Your email: <?=htmlspecialchars($P->email())?> (<a href="/contact">contact us</a> to change this)
    </p>
    </div>

    <?
}


