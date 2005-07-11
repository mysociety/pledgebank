<?
// fns.php:
// General functions for PledgeBank
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: fns.php,v 1.51 2005-07-11 12:09:39 francis Exp $

require_once "../../phplib/evel.php";
require_once '../../phplib/person.php';
require_once "pledge.php";

// HTML shortcuts
function p($s) { return "<p>$s</p>\n"; }
function h2($s) { return "<h2>$s</h2>\n"; }
function h3($s) { return "<h3>$s</h3>\n"; }
function strong($s) { return "<strong>$s</strong>"; }
function dt($s) { print "<dt>$s</dt>\n"; }
function dd($s) { print "<dd>$s</dd>\n"; }

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

/* prettify THING [HTML]
   Returns a nicer form of THING for things that it knows about, otherwise just returns the string.
 */
function prettify($s, $html = true) {
    global $lang;

    if (preg_match('#^(\d{4})-(\d\d)-(\d\d)$#',$s,$m)) {
        list(,$y,$m,$d) = $m;
        $e = mktime(12,0,0,$m,$d,$y);
        if ($lang == 'en') {
            if ($html)
                return date('j<\sup>S</\sup> F Y', $e);
            return date('jS F Y', $e);
        }
        return strftime('%e %B %Y', $e);
    }
    if (preg_match('#^(\d{4})-(\d\d)-(\d\d) (\d\d:\d\d:\d\d)$#',$s,$m)) {
        list(,$y,$m,$d,$tim) = $m;
        $e = mktime(12,0,0,$m,$d,$y);
        if ($lang == 'en') {
            if ($html)
                return date('j<\sup>S</\sup> F Y', $e);
            return date('jS F Y', $e);
        }
        return strftime('%e %B %Y', $e)." $tim";
    }
    if ($s>100000000) {
        # Assume it's an epoch
        $tt = strftime('%H:%M', $s);
        $t = time();
        if (strftime('%Y%m%d', $s) == strftime('%Y%m%d', $t))
            $tt = "$tt today";
        elseif (strftime('%U', $s) == strftime('%U', $t))
            $tt = "$tt, " . strftime('%A', $s);
        elseif (strftime('%Y', $s) == strftime('%Y', $t))
            $tt = "$tt, " . strftime('%A&nbsp;%e&nbsp;%B', $s);
        else
            $tt = "$tt, " . strftime('%a&nbsp;%e&nbsp;%B&nbsp;%Y', $s);
        return $tt;
    }
    if (ctype_digit($s)) {
        $locale_info = localeconv();
        return number_format($s, 0, $locale_info['decimal_point'], $locale_info['thousands_sep']);
    }
    return $s;
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
    
    if (sizeof($errors) && get_http_var('submit')) {
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


?>
