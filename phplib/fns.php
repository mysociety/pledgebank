<?
// fns.php:
// General functions for PledgeBank
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: fns.php,v 1.16 2005-03-30 18:12:05 francis Exp $

require_once "../../phplib/evel.php";

// $to can be one recipient address in a string, or an array of addresses
function pb_send_email_template($to, $template_name, $values, $headers = '') {
    $values['sentence_first'] = pledge_sentence($values['id'], array('firstperson' => true));
    $values['sentence_third'] = pledge_sentence($values['id'], array('firstperson' => false));

    if (file_exists("../templates/emails/$template_name")) {
        ob_start();
        require "../templates/emails/$template_name";
        $template_content = ob_get_contents();
        ob_end_clean();
    }
    else
        err("email template file for \"$template_name\" does not exist");

    $count = preg_match("/^Subject: ([^\n]*)\n\n(.*)$/s", $template_content, $matches);
    if ($count != 1)
        err("template \"$template_name\" doesn't have subject/body");
    $subject = $matches[1];
    $message = $matches[2];
    return pb_send_email($to, $subject, $message, $headers);
}

// $to can be one recipient address in a string, or an array of addresses
function pb_send_email($to, $subject, $message, $headers = '') {
    if (!strstr($headers, 'From:')) {
        $headers .= 'From: "PledgeBank.com" <' . OPTION_CONTACT_EMAIL . ">\r\n" .
                    'Reply-To: "PledgeBank.com" <' . OPTION_CONTACT_EMAIL . ">\r\n";
    }
    $headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";

    if (!is_array($to)) {
        $to = array($to);
    }

    // TODO: remove all this when EvEl can do mail formatting
    // Send with just one evel_send call and passing $to array
    $globalsuccess = TRUE;
    foreach ($to as $oneto) {
        $wire_message = "To: $oneto\r\n" . 
                "Subject: $subject\r\n" . 
                $headers . 
                "\r\n" .
                $message;

        // Send the message
        $result = evel_send($wire_message, $oneto);
        $error = evel_get_error($result);
        if ($error) 
            error_log("pb_send_email: " . $error);
        $success = $error ? FALSE : TRUE;
        if (!$success) {
            $globalsuccess = FALSE;
        }
    }
    return $globalsuccess;
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
