<?

/* Insert standard copyright thing here */

require_once "../conf/general";
include_once '../templates/page.php';
include_once 'contact.php';
include_once '../phplib/db.php';
include_once '../phplib/fns.php';

# Crappy cross-PHP installation checking
# From http://uk.php.net/manual/en/security.magicquotes.disabling.php
set_magic_quotes_runtime(0);
if (get_magic_quotes_gpc()) {
	function stripslashes_deep($value) {
		$value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
		return $value;
	}
	$_POST = array_map('stripslashes_deep', $_POST);
	$_GET = array_map('stripslashes_deep', $_GET);
	$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
}

db_connect();

$today = date('Y-m-d');
$nowA = array( 'day' => date('d'), 'month' => date('m'), 'year' => date('Y') );

page_header();

if ($_GET['confirmp']) confirm_pledge();
elseif ($_GET['confirms']) confirm_signatory();
elseif ($_POST['add_signatory']) add_signatory();
elseif ($_GET['pledge']) view_pledge();
elseif ($_GET['new']) pledge_form();
elseif ($_POST['new']) pledge_form_submitted();
elseif ($_GET['faq']) view_faq();
elseif ($_GET['contact']) contact_form();
elseif ($_POST['contact']) contact_form_submitted();
elseif ($_GET['all']) list_all_pledges();
elseif ($_GET['admin']=='pledgebank') admin();
else front_page();

page_footer();

# --------------------------------------------------------------------

function pledge_form($errors = array()) {
# <!-- <p><big><strong>Before people can create pledges we should have a stiff warning page, with few, select, bold words about what makes for good &amp; bad pledges (we want to try to get people to keep their target numbers down).</strong></big></p> -->
	if (sizeof($errors)) {
		print '<ul id="errors"><li>';
		print join ('</li><li>', $errors);
		print '</li></ul>';
	} ?>
<script type="text/javascript">
function pluralize(t) {
	if (document && document.getElementById) {
		d = document.getElementById('type');
		s = document.getElementById('signplural');
		v = d.value;
		if (t==1) {
			s.innerHTML = 's';			
			if (v=='people') {
				d.value = 'person';
			}
		} else {
			s.innerHTML = '';
			if (v=='person') {
				d.value = 'people';
			}
		}
	}
}
</script>
<!-- <p>To create a new pledge, please fill in the form below.</p> -->
<form id="pledge" method="post" action="./"><input type="hidden" name="new" value="1">
<h2>New Pledge</h2>
<p>I will <input title="Pledge" type="text" name="action" value="<?=htmlentities($_POST['action']) ?>" size="82"></p>
<p>if <input onchange="pluralize(this.value)" title="Target number of people" size="5" type="text" name="people" value="<?=htmlentities($_POST['people']) ?>">
other <input type="text" id="type" name="type" size="30" value="people"> sign<span id="signplural"></span> up before <input title="Deadline date" type="text" name="date" value="<?=htmlentities($_POST['date']) ?>">.</p>

<p>Choose a unique reference for your pledge:<br>http://pledgebank.com/<input type="text" size="10" name="ref" value="<?=htmlentities($_POST['ref']) ?>"> <small>(letters, numbers, -, _)</small>
<p>Name: <input type="text" size="20" name="name" value="<?=htmlentities($_POST['name']) ?>">
Email: <input type="text" size="30" name="email" value="<?=htmlentities($_POST['email']) ?>">
&nbsp;
<input type="submit" value="Submit"></p>
</p>
</form>
<? }

function pledge_form_submitted() {
	global $nowA;
	$action = $_POST['action'];
	$people = $_POST['people'];
	$type = $_POST['type'];
	$date = parse_date($_REQUEST['date']);
	$name = $_POST['name'];
	$email = $_POST['email'];
	$ref = $_POST['ref'];
	if (!$action) $errors[] = 'Please enter a pledge';
	if (!$people) $errors[] = 'Please enter a target';
	if (!ctype_digit($people)) $errors[] = 'The target must be a number';
	if (!$date) $errors[] = 'Please enter a deadline';
	if (!$type) $errors[] = 'Please enter a type';
	if ($date['year']<$nowA['year'] || ($date['year']==$nowA['year'] && $date['month']<$nowA['month']) || ($date['year']==$nowA['year'] && $date['month']==$nowA['month'] && $date['day']<=$nowA['day']) ) {
		$errors[] = 'The deadline must be in the future';
	}
	if (!$ref) $errors[] = 'Please enter a PledgeBank reference';
	if (preg_match('/[^a-z0-9-]/i',$ref)) $errors[] = 'The reference must only contain letters, numbers, -';
	if ($date['error']) $errors[] = 'Please enter a valid date';
	if (!$name) $errors[] = 'Please enter your name';
	if (!$email) $errors[] = 'Please enter your email address';
	if (sizeof($errors)) {
		pledge_form($errors);
	} else {
		create_new_pledge($action,$people,$type,$date,$name,$email,$ref);
	}
}

function front_page() {
	global $today;
?>
<p>Welcome to PledgeBank, the site that lets you say "I'll do something if other people will do it too."</p>
<p id="start"><a href="./?new=1"><strong>Start your own pledge &raquo;</strong></a></p>
<form id="search" onsubmit="return false" action="" method="get">
<h2>Search</h2>
<p><label for="s">Enter a PledgeBank Reference, or a search string:</label>
<input type="text" id="s" name="s" size="10" value=""></p>
<p style="margin-top: 1em; text-align: right"><input type="submit" value="Go"></p>
</form>
<h2>Five Newest Pledges</h2>
<?	$q = db_query('SELECT *, date - CURRENT_DATE AS daysleft FROM pledges WHERE date >= CURRENT_DATE AND confirmed=1 ORDER BY id DESC LIMIT 10');
    $new = '';
    $k = 5;
    while ($k && $r = db_fetch_array($q)) {
        $days = $r['daysleft'];
        $new .= '<li>' . htmlentities($r['name']) . ' will <a href="./?pledge=' . $r['id'] . '">' . htmlentities($r['title']) . '</a> if ' . htmlentities($r['target']) . ' other ' . $r['type'] . ' will too (';
        $new .= $days . ' '.pluralize($days,'day').' left';
#			$new .= 'by '.htmlentities($r['date']);
        $new .= ')</li>'."\n";
        $k--;
    }
    if (!$new) {
        print '<p>No pledges yet!</p>';
    } else {
        print '<ol>'.$new.'</ol>';
    }
?>

<h2>Five Highest Signup Pledges</h2>
<?	$q = db_query('SELECT pledges.id, 
max(pledges.name), max(pledges.title),max(pledges.date), max(pledges.target), max(pledges.type),
COUNT(signers.id) AS count,max(date)-CURRENT_DATE
AS daysleft FROM pledges,signers WHERE pledges.id=signers.pledge_id AND
pledges.date>=CURRENT_DATE AND pledges.confirmed=1 AND signers.confirmed=1 GROUP
BY pledges.id ORDER BY count DESC');
    $new = '';
    $k = 5;
    while ($k && $r = db_fetch_array($q)) {
        $days = $r['daysleft'];
        $new .= '<li>'.$r['count'].' '.pluralize($r['count'],'pledge').' : '.htmlentities($r['name']).' will <a href="./?pledge='.$r['id'].'">'.htmlentities($r['title']).'</a> if '.htmlentities($r['target']).' other ' . htmlentities($r['type']) . ' will too (';
        $new .= 'by '.prettify(htmlentities($r['date']));
        $new .= ')</li>'."\n";
        $k--;
    }
    if (!$new) {
        print '<p>No pledges yet!</p>';
    } else {
        print '<ol>'.$new.'</ol>';
    }
}

function view_faq() { ?>
<h2>FAQ</h2>
<dl>
<dt>So what's this all about, then?
<dd>A Bank. Of Pledges.
<dt>And who do we have to thank for this?
<dd>MySociety.
</dl>
<? }

# Someone wishes to sign a pledge
function add_signatory() {
	$email = $_POST['email'];
	$showname = $_POST['showname'] ? 1 : 0;
	$id = $_POST['pledge_id'];

	$q = db_query('SELECT title,email,confirmed,date FROM pledges WHERE id=?', array($id));
	if (!$q) {
		print '<p>Illegal PledgeBank reference!</p>';
		return false;
	}

	$r = db_fetch_array($q); $action = $r['title'];

	if (!$r['confirmed']) {
		print '<p>Illegal PledgeBank reference!</p>';
		return false;
	}

	if ($email == $r['email']) {
		print '<p>You can\'t sign your own pledge!</p>';
		return false;
	}

	if ($r['date']<$today) {
		print '<p>You can\'t sign up to a closed Pledge!</p>';
		return false;
	}
	
	$q = db_query('SELECT signemail FROM signers WHERE pledge_id = ? AND signemail= ?', array($id, $email));
	if (db_num_rows($q)) {
		print '<p>You have already signed this pledge!</p>';
		return false;
	}

	$token = str_replace('.','X',substr(crypt($id.' '.$email),0,16));
	$add = db_query('INSERT INTO signers (pledge_id, signname,
        signemail, showname, signtime, token, confirmed) VALUES
        (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, 0)', 
        array($id, $_POST['name'], $email, $showname, $token));
    $link = str_replace('index.php', '', 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . '?confirms=' . $token);
    $success = pb_send_email($email, 'Signing up to "'.$action.'" at PledgeBank.com', "Thank you for submitting your signature to a pledge at PledgeBank. To confirm your email address, please click on this link:\n\n$link\n\n");
    if ($success) { ?>
<p>An email has been sent to the address you gave to confirm it is yours. <strong>Please check your email, and follow the link given there.</strong> <a href="./?pledge=<?=htmlentities($_POST['pledge_id']) ?>">Back to pledge page</a></p>
<?			return true;
    } else { ?>
<p>Unfortunately, something bad has gone wrong, and we couldn't send an email to the address you gave. Oh dear.</p>
<?			return false;
    }
}

# Pledgee has clicked on link in their email
function confirm_signatory() {
	$token = $_GET['confirms'];
	$q = db_query('SELECT pledge_id,confirmed FROM signers WHERE token = ?', array($token));
	$row = db_fetch_array($q);
	if (!$row) {
		print '<p>Confirmation token not recognised!</p>';
		return false;
	}

	$pledge_id = $row['pledge_id'];
	$confirmed = $row['confirmed'];
	if ($confirmed) {
		print '<p>You have already confirmed your signature for this pledge!</p>';
		return false;
	}

	db_query('UPDATE signers SET confirmed=1 WHERE token = ?', array($token));
	$success = db_affected_rows();
	if ($success) {
		$q = db_query('SELECT * FROM pledges,signers WHERE pledges.id=? AND pledges.id=signers.pledge_id AND pledges.confirmed=1 AND signers.confirmed=1', array($pledge_id));
		if ($q) {
			$r = db_fetch_array($q);
			$signedup = db_num_rows($q);
			$target = $r['target'];
			if ($signedup == $target) {
				print '<p><strong>Your signature has made this Pledge reach its target! Woohoo!</strong></p>';
				if (send_success_email($pledge_id)) {
					print '<p><em>An email has been sent to all concerned.</em></p>';
				} else {
					print '<p><em>Something went wrong sending a success email.</em></p>';
				}
			}
		}	?>
<p>Thank you for confirming your signature. LINK TO PLEDGE PAGE AND OTHER STUFF HERE.</p>
<?	} else { ?>
<p>Something went wrong confirming your signature, hmm.</p>
<?	}
}

function send_success_email($pledge_id) {
    $q = db_query('SELECT * FROM pledges,signers WHERE pledges.id=? AND pledges.id=signers.pledge_id AND pledges.confirmed=1 AND signers.confirmed=1', array($pledge_id));
	$globalsuccess = 1;
	while ($r = db_fetch_array($q)) {
		if (!$action) $action = $r['title'];
		if (!$email) {
			$email = $r['email'];
			$success = pb_send_email($email, 'PledgeBank pledge success! "'.$action.'"', "This pledge has just received its last needed signup,\nso congratulations to everyone.\nNow do whatever it said. :)\n\n(Yes, text will be changed!)");
			if ($success==0) $globalsuccess = 0;
		}
		$signemail = $r['signemail'];
		$success = pb_send_email($signemail, 'PledgeBank pledge success! "'.$action.'"', "This pledge has just received its last needed signup,\nso congratulations to everyone.\nNow do whatever it said. :)\n\n(Yes, text will be changed!)");
		if ($success==0) $globalsuccess = 0;
	}
	return $globalsuccess;
}

# Individual pledge page
function view_pledge() {
	global $today;

	$q = db_query('SELECT * FROM pledges WHERE id=?',array($_GET['pledge']));
	if (!db_num_rows($q)) {
		print '<p>Illegal PledgeBank reference!</p>';
		return false;
	} else {
		$r = db_fetch_array($q);
		$confirmed = $r['confirmed'];
		if (!$confirmed) {
			print '<p>Illegal PledgeBank reference!</p>';
			return false;
		}
		$action = $r['title'];
		$people = $r['target'];
		$type = $r['type'];
		$date = $r['date'];
		$name = $r['name'];
		$email = $r['email'];

		$q = db_query('SELECT * FROM signers WHERE confirmed=1 AND pledge_id=?', array($r['id']));
		$curr = db_num_rows($q);
		$left = $people - $curr;

		$finished = 0;
		if ($r['date']<$today) {
			$finished = 1;
			print '<p class="finished">This pledge is now closed, its deadline has passed.</p>';
		}
		if ($left<=0) {
			print '<p class="success">This pledge has been successful!</p>';
		}
?>
<p>Here is the pledge:</p>
<form id="pledge" action="./" method="post"><input type="hidden" name="pledge_id" value="<?=htmlentities($_GET['pledge']) ?>">
<input type="hidden" name="add_signatory" value="1">
<p style="margin-top: 0">&quot;I will <strong><?=htmlentities($action) ?></strong> if <strong><?=htmlentities($people) ?></strong> <?=htmlentities($type) ?> will do the same&quot;</p>
<p>Deadline: <strong><?=prettify($date) ?></strong></p>

<p style="text-align: center; font-style: italic;"><?=$curr ?> <?=pluralize($curr,'person has','people have') ?> signed up<?=($left<0?' ('.(-$left).' over target :) )':', '.$left.' more needed') ?></p>

<? if (!$finished) { ?>
<div style="text-align: left; margin-left: 50%">
<h2 style="margin-top: 1em; font-size: 120%">Sign me up</h2>
<p style="text-align: left">Name: <input type="text" size="20" name="name" value="<?=htmlentities($_POST['name']) ?>">
<br>Email: <input type="text" size="30" name="email" value="<?=htmlentities($_POST['email']) ?>">
<br>Show my name on this pledge: <input type="checkbox" name="showname" value="1" checked>
&nbsp;
<input type="submit" value="Submit"></p>
</div>
<? } ?>
</form>

<p style="text-align: center"><a href="" onclick="return false" title="Stick them places!">Print out customised flyers</a> | <a href="" onclick="return false">Chat about this Pledge</a><? if (!$finished) { ?> | <a href="" onclick="return false">SMS this Pledge</a> | <a href="" onclick="return false">Email this Pledge</a><? } ?></p>
<!-- <p><em>Need some way for originator to view email addresses of everyone, needs countdown, etc.</em></p> -->

<h2>Current signatories</h2><?
		$out = '<li>'.htmlentities($name).' (Pledge Author)</li>';
		$anon = 0;
		while ($r = db_fetch_array($q)) {
			$showname = $r['showname'];
			if ($showname) {
				$out .= '<li>'.htmlentities($r['signname']).'</li>';
			} else {
				$anon++;
			}
		}
		print '<ul>'.$out;
		if ($anon) {
			print '<li>Plus '.$anon.' '.pluralize($anon,'other').' who did not want to give their name</li>';
		}
		print '</ul>';
	}
}

# Someone has submitted a new pledge
function create_new_pledge($action, $people, $type, $date, $name, $email,$ref) {
	$isodate = "$date[year]-$date[month]-$date[day]";
	$token = str_replace('.','X',substr(crypt($id.' '.$email),0,16));
	$add = db_query('INSERT INTO pledges (title, target, type, date,
        name, email, ref, token, confirmed, creationtime) VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)', 
        array($action, $people, $type, $isodate, 
        $name, $email, $ref, $token));
?>
<p>Thank you very much for submitting your pledge:</p>
<div id="pledge">
<p style="margin-top: 0">&quot;I will <strong><?=htmlentities($action) ?></strong> if <strong><?=htmlentities($people) ?></strong> <?=htmlentities($type) ?> will do the same&quot;</p>
<p>Deadline: <strong><?=prettify($isodate) ?></strong></p>
<p style="text-align: right">&mdash; <?=htmlentities($name) ?></p>
</div>
<?
	$link = str_replace('index.php', '', 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . '?confirmp=' . $token);
	$success = pb_send_email($email, 'New pledge at PledgeBank.com : '.$action, "Thank you for submitting your pledge to PledgeBank. To confirm your email address, please click on this link:\n\n$link\n\n");
	if ($success) { ?>
<p>An email has been sent to the address you gave to confirm the address is yours. <strong>Please check your email, and follow the link given there.</strong></p>
<?		return true;
	} else { ?>
<p>Unfortunately, something bad has gone wrong, and we couldn't send an email to the address you gave. Oh dear.</p>
<?		return false;
	}
}

# Pledger has clicked on link in their email
function confirm_pledge() {
	$token = $_GET['confirmp'];
	$q = db_query('SELECT confirmed FROM pledges WHERE token = ?', array($token));
	$row = db_fetch_array($q);
	if (!$row) {
		print '<p>Confirmation token not recognised!</p>';
		return false;
	}

	$confirmed = $row['confirmed'];
	if ($confirmed) {
		print '<p>You have already confirmed this pledge!</p>';
		return false;
	}

	db_query('UPDATE pledges SET confirmed=1 WHERE token = ?', array($token));
	$success = db_affected_rows();
	if ($success) { ?>
<p>Thank you for confirming that pledge. It is now live, and people can sign up to it. OTHER STUFF.</p>
<?	} else { ?>
<p>Something went wrong confirming your pledge, hmm.</p>
<?	}
	return $success;
}

?>
