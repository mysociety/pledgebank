<?

/* Insert standard copyright thing here */

require_once "../conf/general";
include_once '../templates/page.php';
include_once 'contact.php';
include_once '../phplib/db.php';
include_once '../phplib/fns.php';
include_once '../../phplib/utility.php';

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
elseif ($_GET['pdf']) pdfs();
else front_page();

page_footer();

# --------------------------------------------------------------------

function pledge_form($errors = array()) {
# <!-- <p><big><strong>Before people can create pledges we should have a stiff warning page, with few, select, bold words about what makes for good &amp; bad pledges (we want to try to get people to keep their target numbers down).</strong></big></p> -->
	if (sizeof($errors)) {
		print '<ul id="errors"><li>';
		print join ('</li><li>', $errors);
		print '</li></ul>';
	}
?>
<!-- <p>To create a new pledge, please fill in the form below.</p> -->
<form id="pledge" method="post" action="./"><input type="hidden" name="new" value="1">
<h2>New Pledge</h2>
<p>I will <input onblur="fadeout(this)" onfocus="fadein(this)" title="Pledge" type="text" name="action" id="action" value="<?=htmlspecialchars($_POST['action']) ?>" size="82"></p>
<p>if <input onchange="pluralize(this.value)" title="Target number of people" size="5" type="text" name="people" value="<?=htmlspecialchars($_POST['people']) ?>">
other <input type="text" id="type" name="type" size="30" value="people"> <input type="text" id="signup" name="signup" size="10" value="sign up"> before
<input title="Deadline date" type="text" id="date" name="date" onfocus="fadein(this)" onblur="fadeout(this)" value="<?=htmlspecialchars($_POST['date']) ?>">.</p>

<p>Choose a short name for your pledge (e.g. mySocPledge) :<br>http://pledgebank.com/<input type="text" size="20" name="ref" value="<?=htmlspecialchars($_POST['ref']) ?>"> <small>(letters, numbers, -)</small>
<p style="margin-bottom: 1em;">Name: <input type="text" size="20" name="name" value="<?=htmlspecialchars($_POST['name']) ?>">
Email: <input type="text" size="30" name="email" value="<?=htmlspecialchars($_POST['email']) ?>">
&nbsp;
<input type="submit" value="Submit"></p>
<hr style="color: #522994; background-color: #522994; height: 1px; border: none;" >
<h3>Optional Information</h3>
<p id="moreinfo" style="text-align: left">More details about your pledge:
<br><textarea name="moreinfo" rows="10" cols="60"><?=htmlspecialchars($_POST['moreinfo']) ?></textarea>
<input type="submit" value="Submit">
</form>
<? }

function pledge_form_submitted() {
	global $today;
	$action = $_POST['action']; if ($action=='<Enter your pledge>') $action = '';
	$people = $_POST['people'];
	$type = $_POST['type']; if (!$type) $type = 'people';
	$date = parse_date($_REQUEST['date']);
	$name = $_POST['name'];
	$email = $_POST['email'];
	$ref = $_POST['ref'];
        $signup = $_POST['signup']; if (!$signup) $signup = 'sign up';
	if (!$action) $errors[] = 'Please enter a pledge';
	if (!$people) $errors[] = 'Please enter a target';
	elseif (!ctype_digit($people) || $people < 1) $errors[] = 'The target must be a positive number';
	if (!$date) $errors[] = 'Please enter a deadline';
	if ($date['iso'] < $today) $errors[] = 'The deadline must be in the future';
	if (!$ref) $errors[] = 'Please enter a PledgeBank reference';
	if (preg_match('/[^a-z0-9-]/i',$ref)) $errors[] = 'The reference must only contain letters, numbers, -';
	if ($date['error']) $errors[] = 'Please enter a valid date';
	if (!$name) $errors[] = 'Please enter your name';
	if (!$email) $errors[] = 'Please enter your email address';
	if (sizeof($errors)) {
		pledge_form($errors);
	} else {
		create_new_pledge($action,$people,$type,$date,$name,$email,$ref,$signup);
	}
}

function front_page() {
?>
<p>Welcome to PledgeBank, the site that lets you say "I'll do something
if other people will do it too." </p>
<p>The site is still in development, it isn't finished yet.   Hopefully
it'll be ready in February.
If you'd like to use PledgeBank when it is launched, or have any good
ideas for organisations who might like to use it, email <a
href="mailto:pb@mysociety.org">Tom Steinberg</a>.  You can also <a
href="mailto:help@pledgebank.com">email us</a> if it just
doesn't work, or you have any other suggests or comments. 
</p>
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
        $signatures = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ? AND confirmed=1', array($r['id']));
        $days = $r['daysleft'];
        $new .= '<li>' . htmlspecialchars($r['name']) . ' will <a href="./?pledge=' . $r['id'] . '">' . htmlspecialchars($r['title']) . '</a> if ' . htmlspecialchars($r['target']) . ' other ' . $r['type'] . ' ';
        $new .= ($r['signup']=='sign up' ? 'will too' : $r['signup']);
        $new .= ' (';
        $new .= $days . ' '.make_plural($days,'day').' left';
#			$new .= 'by '.htmlspecialchars($r['date']);
        $new .= ', '.($r['target']-$signatures).' more needed)</li>'."\n";
        $k--;
    }
    if (!$new) {
        print '<p>No pledges yet!</p>';
    } else {
        print '<ol>'.$new.'</ol>';
    }
?>

<h2>Five Highest Signup Pledges</h2>
<?	$q = db_query('SELECT pledges.id, pledges.name, pledges.title,
pledges.signup, pledges.date, pledges.target, pledges.type,
COUNT(signers.id) AS count, max(date)-CURRENT_DATE
AS daysleft FROM pledges, signers WHERE pledges.id=signers.pledge_id AND
pledges.date>=CURRENT_DATE AND pledges.confirmed=1 AND signers.confirmed=1 GROUP
BY pledges.id,pledges.name,pledges.title,pledges.date,pledges.target,pledges.type,pledges.signup ORDER BY count DESC');
    $new = '';
    $k = 5;
    while ($k && $r = db_fetch_array($q)) {
        $days = $r['daysleft'];
        $new .= '<li>'.$r['count'].' '.make_plural($r['count'],'pledge').' : '.htmlspecialchars($r['name']).' will <a href="./?pledge='.$r['id'].'">'.htmlspecialchars($r['title']).'</a> if '.htmlspecialchars($r['target']).' other ' . htmlspecialchars($r['type']) . ' ';
        $new .= ($r['signup']=='sign up' ? 'will too' : $r['signup']);
        $new .= ' (';
        $new .= 'by '.prettify(htmlspecialchars($r['date']));
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
    global $today;

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
<p>An email has been sent to the address you gave to confirm it is yours. <strong>Please check your email, and follow the link given there.</strong> <a href="./?pledge=<?=htmlspecialchars($_POST['pledge_id']) ?>">Back to pledge page</a></p>
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
                $signup = $r['signup'];

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
<form id="pledge" action="./" method="post"><input type="hidden" name="pledge_id" value="<?=htmlspecialchars($_GET['pledge']) ?>">
<input type="hidden" name="add_signatory" value="1">
<p style="margin-top: 0">&quot;I will <strong><?=htmlspecialchars($action) ?></strong> if <strong><?=htmlspecialchars($people) ?></strong> <?=htmlspecialchars($type) ?> <?=($signup=='sign up'?'will do the same':$signup) ?>&quot;</p>
<p>Deadline: <strong><?=prettify($date) ?></strong></p>

<p style="text-align: center; font-style: italic;"><?=$curr ?> <?=make_plural($curr,'person has','people have') ?> signed up<?=($left<0?' ('.(-$left).' over target :) )':', '.$left.' more needed') ?></p>

<? if (!$finished) { ?>
<div style="text-align: left; margin-left: 50%">
<h2 style="margin-top: 1em; font-size: 120%">Sign me up</h2>
<p style="text-align: left">Name: <input type="text" size="20" name="name" value="<?=htmlspecialchars($_POST['name']) ?>">
<br>Email: <input type="text" size="30" name="email" value="<?=htmlspecialchars($_POST['email']) ?>">
<br><small>(we need this so we can tell you when the pledge is completed and let the pledge creator get in touch)</small>
<br>Show my name on this pledge: <input type="checkbox" name="showname" value="1" checked>
&nbsp;
<input type="submit" value="Submit"></p>
</div>
<? } ?>
</form>

<p style="text-align: center"><a href="./?pdf=<?=$_GET['pledge'] ?>" title="Stick them places!">Print out customised flyers</a> | <a href="" onclick="return false">Chat about this Pledge</a><? if (!$finished) { ?> | <a href="" onclick="return false">SMS this Pledge</a> | <a href="" onclick="return false">Email this Pledge</a><? } ?></p>
<!-- <p><em>Need some way for originator to view email addresses of everyone, needs countdown, etc.</em></p> -->

<h2>Current signatories</h2><?
		$out = '<li>'.htmlspecialchars($name).' (Pledge Author)</li>';
		$anon = 0;
		while ($r = db_fetch_array($q)) {
			$showname = $r['showname'];
			if ($showname) {
				$out .= '<li>'.htmlspecialchars($r['signname']).'</li>';
			} else {
				$anon++;
			}
		}
		print '<ul>'.$out;
		if ($anon) {
			print '<li>Plus '.$anon.' '.make_plural($anon,'other').' who did not want to give their name</li>';
		}
		print '</ul>';
	}
}

# Someone has submitted a new pledge
function create_new_pledge($action, $people, $type, $date, $name, $email, $ref, $signup) {
	$isodate = $date['iso'];
	$token = str_replace('.','X',substr(crypt($id.' '.$email),0,16));
	$add = db_query('INSERT INTO pledges (title, target, type, signup, date,
        name, email, ref, token, confirmed, creationtime) VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)', 
        array($action, $people, $type, $signup, $isodate, 
        $name, $email, $ref, $token));
?>
<p>Thank you very much for submitting your pledge:</p>
<div id="pledge">
<p style="margin-top: 0">&quot;I will <strong><?=htmlspecialchars($action) ?></strong> if <strong><?=htmlspecialchars($people) ?></strong> <?=htmlspecialchars($type) ?> <?=($signup=='sign up'?'will do the same':$signup) ?>&quot;</p>
<p>Deadline: <strong><?=prettify($isodate) ?></strong></p>
<p style="text-align: right">&mdash; <?=htmlspecialchars($name) ?></p>
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

function list_all_pledges() {
        print '<p>There wil have to be some sort of way into all the pledges, but I guess the below looks far more like the admin interface will, at present:</p>';
        $q = db_query('SELECT id,title,target,date,name,ref FROM pledges WHERE confirmed=1');
        print '<table><tr><th>ID</th><th>Title</th><th>Target</th><th>Date</th><th>Name</th><th>Ref</th></tr>';
        while ($r = db_fetch_row($q)) {
                $r[1] = '<a href="./?pledge='.$r[0].'">'.$r[1].'</a>';
                print '<tr><td>'.join('</td><td>',array_map('prettify',$r)).'</td></tr>';        }
        print '</table>';
}

function pdfs() {
        if (!$_GET['pdf']) {
} ?>
<h2>Customised Flyers</h2>
<p>Below you can generate PDFs containing your pledge data, to print out, display, hand out, or whatever.</p>
<ul>
<li>Tear-off format (like accommodation rental ones)
<ul><li>A4</ul>
<li>Sheet of little pledge cards:
<ul>
<li>A3
<li>A4
<li>A5
</ul>
</li>
<li>Poster format
<ul>
<li>Design 1
<ul><li>A3<li>A4</ul>
<li>Design 2
<ul><li>A3<li>A4</ul>
</ul>
</ul>

<?
}

?>
