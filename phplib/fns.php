<?
// fns.php:
// General functions for PledgeBank
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: fns.php,v 1.18 2005-04-01 12:08:55 francis Exp $

require_once "../../phplib/evel.php";

// $to can be one recipient address in a string, or an array of addresses
function pb_send_email_template($to, $template_name, $values, $headers = array()) {
    $values['sentence_first'] = pledge_sentence($values['id'], array('firstperson' => true));
    $values['sentence_third'] = pledge_sentence($values['id'], array('firstperson' => false));
    $values['signature'] = "-- the PledgeBank.com team\n";

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
        '_body_' => $message,
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
        $spec['Reply-To'] = '"PledgeBank.com" <' . OPTION_CONTACT_EMAIL . ">";
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
        error_log("pb_send_email: " . $error);
    $success = $error ? FALSE : TRUE;

    return $success;
}

/* prettify THING [HTML]
   Returns a nicer form of THING for things that it knows about, otherwise just returns the string.
 */
function prettify($s, $html = true) {
    if (preg_match('#^(\d{4})-(\d\d)-(\d\d)$#',$s,$m)) {
        list(,$y,$m,$d) = $m;
        $e = mktime(12,0,0,$m,$d,$y);
        if ($html)
            return date('j<\sup>S</\sup> F Y', $e);
        return date('jS F Y', $e);
    }
    if (ctype_digit($s)) {
        return number_format($s);
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

?>
