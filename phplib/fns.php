<?  // fns.php:
// General functions for PledgeBank
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: fns.php,v 1.66 2005-09-08 16:47:24 francis Exp $

require_once '../phplib/alert.php';
require_once "../../phplib/evel.php";
require_once '../../phplib/person.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/gaze.php';
require_once "pledge.php";

// HTML shortcuts
function p($s) { return "<p>$s</p>\n"; }
function h2($s) { return "<h2>$s</h2>\n"; }
function h3($s) { return "<h3>$s</h3>\n"; }
function strong($s) { return "<strong>$s</strong>"; }
function dt($s) { print "<dt>$s</dt>\n"; }
function dd($s) { print "<dd>$s</dd>\n"; }

# pb_domain_url returns current URL with country and language in it.
# Defaults to keeping country country or language, unless param contains:
#   'lang' - language to change to
#   'country' - country to change to
#   'path' - path component, if not present uses request URI
function pb_domain_url($params = array('path'=>'/')) {
    global $domain_lang, $domain_country;

    $l = $domain_lang;
    if (array_key_exists('lang', $params))
        $l = $params['lang'];
    $c = $domain_country;
    if (array_key_exists('country', $params))
        $c = $params['country'];
     
    $url = 'http://';

    if (OPTION_WEB_HOST == 'www') {
        if (!$c)
            $url .= 'www.';
    } else {
        $url .= OPTION_WEB_HOST;
        $url .= ($c) ? '-' : '.';
    }
    if ($c)
        $url .= strtolower("$c.");
    if ($l)
        $url .= "$l.";
    $url .= OPTION_WEB_DOMAIN;
    if (array_key_exists('path', $params) && $params['path'])
        $url .= htmlspecialchars($params['path']);
    else
        $url .= htmlspecialchars($_SERVER['REQUEST_URI']);
    return $url;
}

// $to can be one recipient address in a string, or an array of addresses
function pb_send_email_template($to, $template_name, $values, $headers = array()) {
    global $lang;

    if (array_key_exists('id', $values)) {
        $values['sentence_first'] = pledge_sentence($values['id'], array('firstperson' => true));
        $values['sentence_third'] = pledge_sentence($values['id'], array('firstperson' => false));
        $values['actual'] = db_getOne('select count(id) from signers where pledge_id = ?', $values['id']);
        if ($values['actual'] >= $values['target'])
            $values['exceeded_or_met'] = ($values['actual'] > $values['target'] ? 'exceeded' : 'met');
    } elseif (array_key_exists('title', $values)) {
        $values['sentence_first'] = pledge_sentence($values, array('firstperson' => true));
        $values['sentence_third'] = pledge_sentence($values, array('firstperson' => false));
    }
    if (array_key_exists('ref', $values)) {
        $values['pledge_url'] = OPTION_BASE_URL . "/" . $values['ref'];
        $values['pledge_url_email'] = OPTION_BASE_URL . "/" . $values['ref'] . "/email";
        $values['pledge_url_flyers'] = OPTION_BASE_URL . "/" . $values['ref'] . "/flyers";
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
    if (array_key_exists('signers', $values)) {
        $values['signers_ordinal'] = english_ordinal($values['signers']);
    }
    $values['sms_number'] = OPTION_PB_SMS_DISPLAY_NUMBER;
        
    $values['signature'] = _("-- the PledgeBank.com team");

    if (is_file("../templates/emails/$lang/$template_name"))
        $template = file_get_contents("../templates/emails/$lang/$template_name");
    else
        $template = file_get_contents("../templates/emails/$template_name");

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
        $spec['From'] = '"PledgeBank.com" <' . OPTION_CONTACT_EMAIL . ">";
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

# Stolen from my railway script
function parse_date($date) {
    global $pb_time;
	$now = $pb_time;
	$error = 0;
	if (!$date)  {
        return null;
    }

	$date = preg_replace('#((\b([a-z]|on|an|of|in|the|year of our lord))|(?<=\d)(st|nd|rd|th))\b#','',$date);

    $epoch = 0;
    $day = null;
    $year = null;
    $month = null;
	if (preg_match('#(\d+)/(\d+)/(\d+)#',$date,$m)) {
		$day = $m[1]; $month = $m[2]; $year = $m[3];
	} elseif (preg_match('#(\d+)/(\d+)#',$date,$m)) {
		$day = $m[1]; $month = $m[2]; $year = date('Y');
	} elseif (preg_match('#^([0123][0-9])([01][0-9])([0-9][0-9])$#',$date,$m)) {
		$day = $m[1]; $month = $m[2]; $year = $m[3];
	} else {
		$dayofweek = date('w'); # 0 Sunday, 6 Saturday
		if (preg_match('#next\s+(sun|sunday|mon|monday|tue|tues|tuesday|wed|wednes|wednesday|thu|thur|thurs|thursday|fri|friday|sat|saturday)\b#i',$date,$m)) {
			$date = preg_replace('#next#i','this',$date);
			if ($dayofweek == 5) {
				$now = strtotime('3 days', $now);
			} elseif ($dayofweek == 4) {
				$now = strtotime('4 days', $now);
			} else {
				$now = strtotime('5 days', $now);
			}
		}
		$t = strtotime($date,$now);
		if ($t != -1) {
			$day = date('d',$t); $month = date('m',$t); $year = date('Y',$t); $epoch = $t;
		} else {
			$error = 1;
		}
	}
    if (!$epoch && $day && $month && $year)
        $epoch = mktime(0,0,0,$month,$day,$year);

    if ($epoch == 0) 
        return null;

    return array('iso'=>"$year-$month-$day", 'epoch'=>$epoch, 'day'=>$day, 'month'=>$month, 'year'=>$year, 'error'=>$error);
}

function view_friends_form($p, $errors = array()) {

    $name = get_http_var('fromname');
    $email = get_http_var('fromemail');
    $P = person_if_signed_on();
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
<? if (get_http_var('pin')) print '<input type="hidden" name="pin" value="'.htmlspecialchars(get_http_var('pin')).'">';
print h2(_('Email this pledge'));
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

// Prints HTML for radio buttons to select a list of places drawn from Gaze
function pb_view_gaze_places_choice($places, $place, $selected_gaze_place) {
    print "<strong>" . sprintf(_("There are several possible places which match '%s'. Please choose one:"),$place) . "</strong><br>";
    $nn = 0;
    foreach ($places as $p) {
        list($name, $in, $near, $lat, $lon, $st, $score) = $p;
        $desc = $name;
        if ($in) $desc .= ", $in";
        if ($st) $desc .= ", $st";
        if ($near) $desc .= " (" . _('near') . " " . htmlspecialchars($near) . ")";
        $t = htmlspecialchars("$lat,$lon,$desc");
        if ($score) $desc .= "<!--($score%)-->";
        $checked = '';
        if ($t == $selected_gaze_place) {
            $checked = 'checked';
        }
        $nn++;
        print "<input type=\"radio\" name=\"gaze_place\" value=\"$t\" id=\"gaze_place_$nn\" $checked>\n<label for=\"gaze_place_$nn\">$desc</label><br>\n";
    }
    print "<strong>"._("If it isn't any of those, try a different spelling, or the name of another nearby town:")."</strong>";
}

function country_sort($a, $b) {
    global $countries_code_to_name;
    return strcmp($countries_code_to_name[$a], $countries_code_to_name[$b]);
}

/* pb_view_gaze_country_choice
 * Draws a drop down box for choice of country. $selected_country and $selected_state are
 * which items to select by default. $errors array is used to highlight in red
 * if countains key 'country'.  params can contain
 *      'noglobal' - don't offer "any country" choice
 *      'gazeonly' - only list countries for which we have local gaze place
 */
function pb_view_gaze_country_choice($selected_country, $selected_state, $errors, $params = array()) {
    global $countries_name_to_code, $countries_code_to_name, $countries_statecode_to_name, $ip_country, $site_country;

    /* Save previous value of country, so that we can detect if it's changed after
     * one of a list of placenames is selected. */
    if ($selected_country)
        printf("<input type=\"hidden\" name=\"prev_country\" value=\"%s\">", htmlspecialchars($selected_country));

?>
<select <? if (array_key_exists('country', $errors)) print ' class="error"' ?> id="country" name="country" onchange="update_place_local(this, true)">
  <option value="(choose one)"><?=_('(choose one)') ?></option>
<? if (!array_key_exists('noglobal', $params)) { ?>
  <option value="Global"<? if ($selected_country=='Global') print ' selected'; ?>><?=_('None &mdash; applies anywhere') ?></option>
<? } ?>
  <!-- needs explicit values for IE Javascript -->
<?
    if ($selected_country and array_key_exists($selected_country, $countries_code_to_name)) {
        print "<option value=\"$selected_country\"";
        if (!$selected_state) {
            print " selected";
        }
        print ">"
                . htmlspecialchars($countries_code_to_name[$selected_country])
                . "</option>";
/* Disabled for now, as not necessary
        if (array_key_exists($selected_country, $countries_statecode_to_name)) {
            foreach ($countries_statecode_to_name[$selected_country] as $opt_statecode => $opt_statename) {
                print "<option value=\"$selected_country,$opt_statecode\"";
                if ($selected_state && "$opt_statecode" == $selected_state)
                    print ' selected';
                print "> &raquo; "
                        . htmlspecialchars($opt_statename)
                        . "</option>";
                
            }
        }
*/
    }
    if ($selected_country != $site_country && $site_country) {
        print "<option value=\"$site_country\">";
        print htmlspecialchars($countries_code_to_name[$site_country]);
        print "</option>";
    }
    if ($selected_country != $ip_country && $ip_country && $ip_country != $site_country) {
        print "<option value=\"$ip_country\">";
        print htmlspecialchars($countries_code_to_name[$ip_country]);
        print "</option>";
    }
?>
  <option value="(separator)"><?=_('---------------------------------------------------') ?></option>
<?
    if (array_key_exists('gazeonly', $params)) {
        $countries_list = gaze_get_find_places_countries();
        gaze_check_error($countries_list);
        usort($countries_list, "country_sort");
    } else {
        $countries_list = array_values($countries_name_to_code);
    }

    foreach ($countries_list as $opt_code) {
        $opt_country = $countries_code_to_name[$opt_code];
        print "<option value=\"$opt_code\">"
                . htmlspecialchars($opt_country)
                . "</option>";
/* Disabled for now, as not necessary
        if (array_key_exists($opt_code, $countries_statecode_to_name)) {
            foreach ($countries_statecode_to_name[$opt_code] as $opt_statecode => $opt_statename) {
                print "<option value=\"$opt_code,$opt_statecode\">"
                        . "&raquo; "
                        . htmlspecialchars($opt_statename)
                        . "</option>";
            }
        }
*/
    }
?>
</select>
<?
}

/* pb_view_gaze_place_choice
 * Display options for choosing a local place
 */
function pb_view_gaze_place_choice($selected_place, $selected_gaze_place, $places, $errors) {
    ?> <ul>
    <li><p id="place_line"> <?

    /* Save previous value of 'place' so we can show a new selection list in the
     * case where the user types a different place name after clicking on one of
     * the selections. */
    if ($selected_place)
        printf("<input type=\"hidden\" name=\"prev_place\" value=\"%s\">", htmlspecialchars($selected_place));

    /* If the user has already typed a place name, then we need to grab the
     * possible places from Gaze. */
    if (!$selected_place || array_key_exists('place', $errors) || count($places) == 0) {
        ?>
           <?=_('Place name:') ?>
        <?
    } else {
        pb_view_gaze_places_choice($places, $selected_place, $selected_gaze_place);
    }

    ?>
    <input <? if (array_key_exists('place', $errors)) print ' class="error"' ?> type="text" name="place" id="place" value="<? if ($selected_place) print htmlspecialchars($selected_place) ?>">
</p></li>

    <li><p id="postcode_line">
    <?=_('Or, UK only, you can give a postcode area:') ?>
    <input <? if (array_key_exists('postcode', $errors)) print ' class="error"' ?> type="text" name="postcode" id="postcode" value="<? if (isset($data['postcode'])) print htmlspecialchars($data['postcode']) ?>">
    <br><small><?=_('(just the start of the postcode, such as WC1)') ?></small>
    </p></li>
    </ul>
    <?
}

function pb_view_local_alert_quick_signup($class) {
    $email = '';
    $P = person_if_signed_on();
    if (!is_null($P)) {
        $email = $P->email();
    } 
?>
<form accept-charset="utf-8" id="<?=$class?>" name="localalert" action="/alert" method="post">
<input type="hidden" name="subscribe_local_alert" value="1">
<input type="hidden" name="from_frontpage" value="1">
<p><strong><?=_('Sign up for emails when people make pledges in your local area')?> &mdash; <?=_('NEW! Now works in any country') ?> </strong>
<br><label for="localquick_email"><?=_('Email:') ?></label><input type="text" size="18" name="email" id="localquick_email" value="<?=htmlspecialchars($email) ?>">
<?=_('Country:') ?><? global $site_country; pb_view_gaze_country_choice($site_country, null, array(), array('noglobal' => true, 'gazeonly' => true)); ?>
<label for="localquick_place"><span id="place_postcode_label"><?=_('Town:')?></span></label> <input type="text" size="12" name="place" id="localquick_place" value="">
<input type="submit" name="submit" value="<?=_('Subscribe') ?>"> </p>
</form>
<?
}

function pb_get_change_country_link() {
    global $site_country;
    $change = '<a href="/where?r='.urlencode($_SERVER['REQUEST_URI']).'">';
    if ($site_country)
        $change .= _("change country");
    else
        $change .= _("choose country");
    $change .= '</a>';
    return $change;
}

function pb_print_change_country_link($attrs = "") {
    global $site_country;
    $change = pb_get_change_country_link();
    if ($site_country)
        print "<p $attrs>".sprintf(_('%s (%s) and global pledges listed'), pb_site_country_name(), $change);
    else
        print "<p $attrs>".sprintf(_('%s (%s) pledges only listed'), pb_site_country_name(), $change);
    print '</p>';
}

// Return array of country codes for countries which have SMS
function sms_countries() {
    return array('GB');
}
// Return description of countries which have SMS (for use in FAQ)
function sms_countries_description() {
    return _("the UK");
}
// Return whether site country supports SMS
function sms_site_country() {
    global $site_country;
    if (!$site_country) 
        return false;
    return in_array($site_country, sms_countries());
}


