<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.28 2005-02-23 15:45:00 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/db.php';
require_once '../phplib/fns.php';

require_once 'contact.php';

#$foo = array("foo");
#$x = $foo['hello'];

db_connect();

$today = date('Y-m-d');

if (get_http_var('search')) {
    $search_results = search();
}

page_header("NOTITLE");
if (get_http_var('report') && ctype_digit(get_http_var('report'))) report_form();
elseif (get_http_var('report') && ctype_digit(get_http_var('report'))) send_report();
elseif (get_http_var('confirmp')) confirm_pledge();
elseif (get_http_var('confirms')) confirm_signatory();
elseif (get_http_var('add_signatory')) add_signatory();
elseif (get_http_var('pledge')) view_pledge();
elseif (get_http_var('new')) pledge_form();
elseif (get_http_var('new')) pledge_form_submitted();
elseif (get_http_var('faq')) view_faq();
elseif (get_http_var('contact')) contact_form();
elseif (get_http_var('contact')) contact_form_submitted();
elseif (get_http_var('all')) list_all_pledges();
elseif (get_http_var('admin')=='pledgebank') admin();
elseif (get_http_var('pdf')) pdfs();
elseif (get_http_var('search')) print $search_results;
else front_page();


page_footer();

# --------------------------------------------------------------------

function report_form() {
    $q = db_query('SELECT * FROM signers,pledges WHERE signers.pledge_id=pledges.id AND signers.confirmed=1 AND signers.id=?', array(get_http_var('report')));
    if (!db_num_rows($q)) {
        print '<p>Illegal PledgeBank id!</p>';
	return false;
    } else {
        $r = db_fetch_array($q);
        print '<form action="./" method="post"><input type="hidden" name="report" value="'.get_http_var('report').'">';
        print '<h2>Signature reporting</h2>';
        print '<p>You are reporting the signature "'.$r['signname'].'" on the pledge "'.$r['title'].'"</p>';
        print '<p>Please give a (short) reason for reporting this signature:</p>';
        print '<textarea name="reason" rows="5" cols="50"></textarea>';
        print '<p><input type="submit" value="Submit"></p>';
        print '</form>';
    }
}

function send_report() {
    $q = db_query('SELECT * FROM signers,pledges WHERE signers.pledge_id=pledges.id AND signers.confirmed=1 AND signers.id=?', array(get_http_var('report')));
    if (!db_num_rows($q)) {
        print '<p>Illegal PledgeBank id!</p>';
	return false;
    } else {
        $r = db_fetch_array($q);
        $reason = get_http_var('reason');
        pb_send_email(OPTION_CONTACT_EMAIL, "Signature reporting", "Reporting of '$r[signname]' in pledge '$r[title]'\n\nReason given: $reason\n\n");
        db_query('UPDATE signers SET showname=0,reported=1 WHERE id=?', array(get_http_var('report')));
        print '<p>Thank you for reporting that signature; it will be looked at asap.</p>';
    }
}

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
<p>I will <input onblur="fadeout(this)" onfocus="fadein(this)" title="Pledge" type="text" name="action" id="action" value="<?=htmlspecialchars(get_http_var('action')) ?>" size="82"></p>
<p>if <input onchange="pluralize(this.value)" title="Target number of people" size="5" type="text" name="people" value="<?=htmlspecialchars(get_http_var('people')) ?>">
other <input type="text" id="type" name="type" size="30" value="people"> <input type="text" id="signup" name="signup" size="10" value="sign up"> before
<input title="Deadline date" type="text" id="date" name="date" onfocus="fadein(this)" onblur="fadeout(this)" value="<?=htmlspecialchars(get_http_var('date')) ?>">.</p>

<p>Choose a short name for your pledge (e.g. mySocPledge) :<br>http://pledgebank.com/<input type="text" size="20" name="ref" value="<?=htmlspecialchars(get_http_var('ref')) ?>"> <small>(letters, numbers, -)</small></p>
<!-- <p>Do you want this pledge to be visible around the site? <input type="checkbox" checked name="open" value="1"> Yes</p> -->
<p style="margin-bottom: 1em;">Name: <input type="text" size="20" name="name" value="<?=htmlspecialchars(get_http_var('name')) ?>">
Email: <input type="text" size="30" name="email" value="<?=htmlspecialchars(get_http_var('email')) ?>">
&nbsp;
<input type="submit" value="Submit"></p>
<hr style="color: #522994; background-color: #522994; height: 1px; border: none;" >
<h3>Optional Information</h3>
<p id="moreinfo" style="text-align: left">More details about your pledge:
<br><textarea name="moreinfo" rows="10" cols="60"><?=htmlspecialchars(get_http_var('moreinfo')) ?></textarea>
<input type="submit" value="Submit">
</form>
<? }

function pledge_form_submitted() {
	global $today;
	$action = get_http_var('action'); if ($action=='<Enter your pledge>') $action = '';
	$people = get_http_var('people');
	$type = get_http_var('type'); if (!$type) $type = 'people';
	$date = parse_date($_REQUEST['date']);
	$name = get_http_var('name');
	$email = get_http_var('email');
	$ref = get_http_var('ref');
        $detail = get_http_var('detail');
        $open = get_http_var('open'); if ($open) $open=1; else $open = 0;
        $dupe = db_getOne('SELECT id FROM pledges WHERE ref=?', array($ref));
        if ($dupe) $errors[] = 'That reference is already taken!';
        $signup = get_http_var('signup'); if (!$signup) $signup = 'sign up';
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
		create_new_pledge($action,$people,$type,$date,$name,$email,$ref,$signup,$detail,$open);
	}
}

function front_page() {
?>
<p>Welcome to PledgeBank, the site that lets you say "I'll do something
if other people will do it too." </p>
<p>The site is still in development, it isn't finished yet.   Hopefully
it'll be ready soon.
If you'd like to use PledgeBank when it is launched, or have any good
ideas for organisations who might like to use it, email <a
href="mailto:pb@mysociety.org">Tom Steinberg</a>.  You can also <a
href="mailto:help@pledgebank.com">email us</a> if it just
doesn't work, or you have any other suggests or comments. 
</p>
<p id="start"><a href="./?new=1"><strong>Start your own pledge &raquo;</strong></a></p>
<form id="search" action="./" method="get">
<h2>Search</h2>
<p><label for="s">Enter a PledgeBank Reference, or a search string:</label>
<input type="text" id="s" name="search" size="10" value=""></p>
<p style="margin-top: 1em; text-align: right"><input type="submit" value="Go"></p>
</form>
<h2>Sign up to one of our five newest pledges</h2>
<?	$q = db_query('SELECT *, date - CURRENT_DATE AS daysleft FROM pledges WHERE date >= CURRENT_DATE AND confirmed=1 AND open=1 ORDER BY id DESC LIMIT 10');
    $new = '';
    $k = 5;
    while ($k && $r = db_fetch_array($q)) {
        $signatures = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ? AND confirmed=1', array($r['id']));
        $days = $r['daysleft'];
        $new .= '<li>' . htmlspecialchars($r['name']) . ' will <a href="' . $r['ref'] . '">' . htmlspecialchars($r['title']) . '</a> if ' . htmlspecialchars($r['target']) . ' other ' . $r['type'] . ' ';
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
pledges.signup, pledges.date, pledges.target, pledges.type, pledges.ref,
COUNT(signers.id) AS count, max(date)-CURRENT_DATE
AS daysleft FROM pledges, signers WHERE pledges.id=signers.pledge_id AND
pledges.date>=CURRENT_DATE AND pledges.confirmed=1 AND signers.confirmed=1 AND pledges.open=1 GROUP
BY pledges.id,pledges.name,pledges.title,pledges.date,pledges.target,pledges.type,pledges.signup,pledges.ref ORDER BY count DESC');
    $new = '';
    $k = 5;
    while ($k && $r = db_fetch_array($q)) {
        $days = $r['daysleft'];
        $new .= '<li>'.$r['count'].' '.make_plural($r['count'],'pledge').' : '.htmlspecialchars($r['name']).' will <a href="'.$r['ref'].'">'.htmlspecialchars($r['title']).'</a> if '.htmlspecialchars($r['target']).' other ' . htmlspecialchars($r['type']) . ' ';
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

function view_faq() {
    include 'faq.php';
}

# Someone wishes to sign a pledge
function add_signatory() {
    global $today;

	$email = get_http_var('email');
	$showname = get_http_var('showname') ? 1 : 0;
	$ref = get_http_var('pledge_id');

	$q = db_query('SELECT id,title,email,confirmed,date FROM pledges WHERE ref=?', array($ref));
	if (!$q) {
		print '<p>Illegal PledgeBank reference!</p>';
		return false;
	}

	$r = db_fetch_array($q);
        $action = $r['title']; $id = $r['id'];

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

	$token = str_replace('.', 'X', substr(crypt($id.' '.$email.microtime()), 12, 16));
	$add = db_query('INSERT INTO signers (pledge_id, signname,
        signemail, showname, signtime, token, confirmed) VALUES
        (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, 0)', 
        array($id, get_http_var('name'), $email, $showname, $token));
    $link = str_replace('index.php', '', 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . '?confirms=' . $token);
    $success = pb_send_email($email, 'Signing up to "'.$action.'" at PledgeBank.com', "Thank you for submitting your signature to a pledge at PledgeBank. To confirm your email address, please click on this link:\n\n$link\n\n");
    if ($success) { ?>
<p>An email has been sent to the address you gave to confirm it is yours. <strong>Please check your email, and follow the link given there.</strong> <a href="<?=$ref ?>">Back to pledge page</a></p>
<?			return true;
    } else { ?>
<p>Unfortunately, something bad has gone wrong, and we couldn't send an email to the address you gave. Oh dear.</p>
<?			return false;
    }
}

# Pledgee has clicked on link in their email
function confirm_signatory() {
	$token = get_http_var('confirms');
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

    $q = db_query('SELECT * FROM pledges WHERE ref=?', array(get_http_var('pledge')));
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
        $detail = $r['detail'];

	$q = db_query('SELECT * FROM signers WHERE confirmed=1 AND pledge_id=? ORDER BY id', array($r['id']));
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
<form id="pledge" action="./" method="post"><input type="hidden" name="pledge_id" value="<?=htmlspecialchars(get_http_var('pledge')) ?>">
<input type="hidden" name="add_signatory" value="1">
<p style="margin-top: 0">&quot;I will <strong><?=htmlspecialchars($action) ?></strong> if <strong><?=htmlspecialchars($people) ?></strong> <?=htmlspecialchars($type) ?> <?=($signup=='sign up'?'will do the same':$signup) ?>&quot;</p>
<p>Deadline: <strong><?=prettify($date) ?></strong></p>

<p style="text-align: center; font-style: italic;"><?=$curr ?> <?=make_plural($curr,'person has','people have') ?> signed up<?=($left<0?' ('.(-$left).' over target :) )':', '.$left.' more needed') ?></p>

<? if (!$finished) { ?>
<div style="text-align: left; margin-left: 50%;">
<h2 style="margin-top: 1em; font-size: 120%">Sign me up</h2>
<p style="text-align: left">Name: <input type="text" size="20" name="name" value="<?=htmlspecialchars(get_http_var('name')) ?>">
<br>Email: <input type="text" size="30" name="email" value="<?=htmlspecialchars(get_http_var('email')) ?>">
<br><small>(we need this so we can tell you when the pledge is completed and let the pledge creator get in touch)</small>
<br>Show my name on this pledge: <input type="checkbox" name="showname" value="1" checked>
&nbsp;
<input type="submit" value="Submit"></p>
</div>
<? }

if ($detail) {
    print '<p style="text-align:left"><strong>More details</strong><br>' . htmlspecialchars($detail) . '</p>';
}

?>
</form>

<p style="text-align: center"><a href="./?pdf=<?=get_http_var('pledge') ?>" title="Stick them places!">Print out customised flyers</a> | <a href="" onclick="return false">Chat about this Pledge</a><? if (!$finished) { ?> | <a href="" onclick="return false">SMS this Pledge</a> | <a href="" onclick="return false">Email this Pledge</a><? } ?></p>
<!-- <p><em>Need some way for originator to view email addresses of everyone, needs countdown, etc.</em></p> -->

<h2>Current signatories</h2><?
		$out = '<li>'.htmlspecialchars($name).' (Pledge Author)</li>';
		$anon = 0;
		while ($r = db_fetch_array($q)) {
			$showname = $r['showname'];
			if ($showname) {
                            $out .= '<li>'.htmlspecialchars($r['signname']).' <small>(<a href="./?report='.$r['id'].'">Is this signature suspicious?</a>)</small></li>';
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
function create_new_pledge($action, $people, $type, $date, $name, $email, $ref, $signup, $detail, $open) {
	$isodate = $date['iso'];
	$token = str_replace('.', 'X', substr(crypt($id.' '.$email.microtime()), 12, 16));
	$add = db_query('INSERT INTO pledges (title, target, type, signup, date,
        name, email, ref, token, confirmed, creationtime, detail, open) VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP, ?, ?)', 
        array($action, $people, $type, $signup, $isodate, 
        $name, $email, $ref, $token, $detail, $open));
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
	$token = get_http_var('confirmp');
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

	db_query('UPDATE pledges SET confirmed=1,creationtime=CURRENT_TIMESTAMP WHERE token = ?', array($token));
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
        $q = db_query('SELECT title,target,date,name,ref FROM pledges WHERE confirmed=1 AND open=1');
        print '<table><tr><th>Title</th><th>Target</th><th>Deadline</th><th>Creator</th><th>Short name</th></tr>';
        while ($r = db_fetch_row($q)) {
                $r[0] = '<a href="'.$r[4].'">'.$r[0].'</a>';
                print '<tr><td>'.join('</td><td>',array_map('prettify',$r)).'</td></tr>';        }
        print '</table>';
}

function pdfs() {
        if (!get_http_var('pdf')) {
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

function search() {
    global $today;
    $id = db_getOne('SELECT id FROM pledges WHERE ref = ?', array(get_http_var('search')));
    if ($id) {
        Header("Location: get_http_var(search)"); # TODO: should be absolute?
        exit;
    }
    $q = db_query('SELECT date,ref,title FROM pledges WHERE title ILIKE \'%\' || ? || \'%\' ORDER BY date', array(get_http_var('search')));
    if (!db_num_rows($q)) {
        return '<p>Sorry, we could find nothing that matched "' . htmlspecialchars(get_http_var('search')) . '".</p>';
    } else {
        $closed = ''; $open = '';
        while ($r = db_fetch_array($q)) {
            $text = '<li><a href="' . $r['ref'] . '">' . $r['title'] . '</a></li>';
            if ($r['date']>$today) {
                $open .= $text;
            } else {
                $closed .= $text;
            }
        }
        $out = '';
        if ($open) {
            $out = '<p>The following currently open pledges matched your search term "' . htmlspecialchars(get_http_var('search')) . '":</p>';
            $out .= '<ul>' . $open . '</ul>';
        }
        if ($closed) {
            $out .= '<p>The following are closed pledges that match your search term:</p>';
            $out .= '<ul>' . $closed . '</ul>';
        }
        return $out;
    }
}
?>
