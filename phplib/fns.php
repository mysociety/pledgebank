<?

function pb_send_email($to, $subject, $message, $headers = '') {
	$headers = $headers . 
	"From: PledgeBank.com <" . CONTACTEMAIL . ">\r\n" .
	"Reply-To: PledgeBank.com <" . CONTACTEMAIL . ">\r\n" .
	"X-Mailer: PHP/" . phpversion();
	$success = mail ($to, $subject, $message, $headers);
	return $success;
}

function prettify($s) {
	if (preg_match('#^(\d{4})-(\d\d)-(\d\d)$#',$s,$m)) {
		list(,$y,$m,$d) = $m;
		return date('j<\sup>S</\sup> F Y', mktime(12,0,0,$m,$d,$y));
	}
	return $s;
}

# Stolen from my railway script
# TODO: Return an epoch instead?
function parse_date($date) {
	global $now;
	$error = 0;
	if (!$date) return;

	$date = preg_replace('#((\b([a-z]|on|an|of|in|the|year of our lord))|(?<=\d)(st|nd|rd|th))\b#','',$date);

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
				$now = strtotime('3 days');
			} elseif ($dayofweek == 4) {
				$now = strtotime('4 days');
			} else {
				$now = strtotime('5 days');
			}
		}
		$t = strtotime($date,$now);
		if ($t != -1) {
			$day = date('d',$t); $month = date('m',$t); $year = date('Y',$t);
		} else {
			$error = 1;
		}
	}
	return array('day'=>$day, 'month'=>$month, 'year'=>$year, 'error'=>$error);
}

?>
