<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.51 2005-03-04 18:49:45 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/db.php';
require_once '../phplib/fns.php';

require_once 'contact.php';

db_connect();

$today = date('Y-m-d');

if (get_http_var('search')) {
    $search_results = search();
}

$title = '';
ob_start();
if (get_http_var('report') && ctype_digit(get_http_var('report'))) {
    if (get_http_var('reason')) send_report();
    else report_form();
}
elseif (get_http_var('confirmp')) {
    $title = 'Pledge Confirmation';
    confirm_pledge();
} elseif (get_http_var('confirms')) {
    $title = 'Signature Confirmation';
    confirm_signatory();
} elseif (get_http_var('add_signatory')) {
    $title = 'Signature addition';
    add_signatory();
} elseif (get_http_var('pledge') == 'new') {
    $title = 'Create a New Pledge';
    pledge_form();
} elseif (get_http_var('pledge') == 'faq') {
    $title = 'Frequently Asked Questions';
    view_faq();
} elseif (get_http_var('pledge') == 'all') {
    $title = 'All Pledges';
    list_all_pledges();
} elseif (get_http_var('pledge') == 'contact') {
    $title = 'Contact Us';
    contact_form();
} elseif (get_http_var('pledge')) view_pledge();
elseif (get_http_var('newpost')==1) pledge_form_submitted();
elseif (get_http_var('newpost')==2) pledge_form_two_submitted();
elseif (get_http_var('contactpost')) {
    $title = 'Contact Us';
    contact_form_submitted();
} elseif (get_http_var('admin')=='pledgebank') {
    $title = 'Admin';
    admin();
} elseif (get_http_var('pdf')) {
    $title = 'Pledge Flyers';
    pdfs();
} elseif (get_http_var('search')) {
    $title = 'Search Results';
    print $search_results;
} else
    front_page();
$body = ob_get_contents();
ob_end_clean();

page_header($title);
print $body;
page_footer();

# --------------------------------------------------------------------

function report_form() {
    $q = db_query('SELECT * FROM signers,pledges WHERE signers.pledge_id=pledges.id AND signers.confirmed AND signers.id=?', array(get_http_var('report')));
    if (!db_num_rows($q)) {
        print '<p>Illegal PledgeBank id!</p>';
	return false;
    } else {
        $r = db_fetch_array($q);
        print '<form action="./" method="post"><input type="hidden" name="report" value="' . htmlspecialchars(get_http_var('report')) . '">';
        print '<h2>Signature reporting</h2>';
        print '<p>You are reporting the signature "' . htmlspecialchars($r['name']) . '" on the pledge "' . htmlspecialchars($r['title']) . '"</p>';
        print '<p>Please give a (short) reason for reporting this signature:</p>';
        print '<textarea name="reason" rows="5" cols="50"></textarea>';
        print '<p><input type="submit" value="Submit"></p>';
        print '</form>';
    }
}

function send_report() {
    $q = db_query('SELECT * FROM signers,pledges WHERE signers.pledge_id=pledges.id AND signers.confirmed AND signers.id=?', array(get_http_var('report')));
    if (!db_num_rows($q)) {
        print '<p>Illegal PledgeBank id!</p>';
	return false;
    } else {
        $r = db_fetch_array($q);
        $reason = get_http_var('reason');
        pb_send_email(OPTION_CONTACT_EMAIL, "Signature reporting", "Reporting of '$r[name]' in pledge '$r[title]'\n\nReason given: $reason\n\n");
        db_query('UPDATE signers SET showname=false,reported=true WHERE id=?', array(get_http_var('report')));
        print '<p>Thank you for reporting that signature; it will be looked at asap.</p>';
    }
}

function pledge_form($data = array(), $errors = array()) {
# <!-- <p><big><strong>Before people can create pledges we should have a stiff warning page, with few, select, bold words about what makes for good &amp; bad pledges (we want to try to get people to keep their target numbers down).</strong></big></p> -->
	if (sizeof($errors)) {
		print '<div id="errors"><ul><li>';
		print join ('</li><li>', $errors);
		print '</li></ul></div>';
	}
?>
<!-- <p>To create a new pledge, please fill in the form below.</p> -->
<form class="pledge" name="pledge" method="post" action="./"><input type="hidden" name="newpost" value="1">
<h2>New Pledge &#8211; Step 1</h2>
<p>I will <input onblur="fadeout(this)" onfocus="fadein(this)" title="Pledge" type="text" name="action" id="action" value="<? if (isset($data['action'])) print htmlspecialchars($data['action']) ?>" size="82"></p>
<p>if <select name="comparison"><option value="atleast">at least</option><option value="exactly">exactly</option></select>
<input onchange="pluralize(this.value)" title="Target number of people" size="5" type="text" id="people" name="people" value="<?=(isset($data['people'])?htmlspecialchars($data['people']):'3') ?>">
other <input type="text" id="type" name="type" size="30" value="people"> <input type="text" id="signup" name="signup" size="10" value="sign up"> before
<input title="Deadline date" type="text" id="date" name="date" onfocus="fadein(this)" onblur="fadeout(this)" value="<? if (isset($data['date'])) print htmlspecialchars($data['date']['iso']) ?>">.</p>

<p>Choose a short name for your pledge (e.g. mySocPledge) :<br>www.pledgebank.com/<input onkeyup="checklength(this)" type="text" size="20" id="ref" name="ref" value="<? if (isset($data['ref'])) print htmlspecialchars($data['ref']) ?>"> <small>(letters, numbers, -; minimum 6 characters)</small></p>
<p style="margin-bottom: 1em;">Name: <input type="text" size="20" name="name" value="<? if (isset($data['name'])) print htmlspecialchars($data['name']) ?>">
Email: <input type="text" size="30" name="email" value="<? if (isset($data['email'])) print htmlspecialchars($data['email']) ?>">
<p style="text-align: right"><input type="submit" name="submit" value="Next &gt;&gt;"></p>
<hr style="color: #522994; background-color: #522994; height: 1px; border: none;" >
<h3>Optional Information</h3>
<p id="moreinfo" style="text-align: left">More details about your pledge:
<br><textarea name="detail" rows="10" cols="60"><? if (isset($data['detail'])) print htmlspecialchars($data['detail']) ?></textarea>
<p style="text-align: right;">
<? if (sizeof($data)) {
    print '<input type="hidden" name="data" value="' . base64_encode(serialize($data)) . '">';
} ?>
<input type="submit" name="submit" value="Next &gt;&gt;"></p>
</form>
<? }

function pledge_form_two($data, $errors = array()) {
    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    }

    $v = 'all';
    if (isset($data['visibility'])) {
        $v = $data['visibility']; if ($v!='password') $v = 'all';
    }
    $local = (isset($data['local'])) ? $data['local'] : 0;
    $isodate = $data['date']['iso'];
?>
<p>You entered the following:</p>
<div class="pledge">
<p style="margin-top: 0">&quot;I will <strong><?=htmlspecialchars($data['action']) ?></strong> if <strong><?=htmlspecialchars($data['people']) ?></strong> <?=htmlspecialchars($data['type']) ?> <?=($data['signup']=='sign up'?'will do the same':$data['signup']) ?>&quot;</p>
<p>Deadline: <strong><?=prettify($isodate) ?></strong></p>
<p style="text-align: right">&mdash; <?=htmlspecialchars($data['name']) ?></p>
</div>

<form class="pledge" name="pledge" method="post" action="./"><input type="hidden" name="newpost" value="2">
<h2>New Pledge &#8211; Step 2 (optional)</h2>

<p>Where does your pledge apply?
<select name="country"><option>Global<option>UK</select>
</p>

<p>Is your pledge specific to a local area?
<input onclick="grey_postcode(false)" type="radio" name="local" value="1"<?=($local?' checked':'') ?>> Yes
<input onclick="grey_postcode(true)" type="radio" name="local" value="0"<?=(!$local?' checked':'') ?>> No
<br>
<span id="postcode_line">
If yes, enter your postcode so that local people can find your pledge:
<input type="text" name="postcode" id="postcode" value="<? if (isset($data['postcode'])) print htmlspecialchars($data['postcode']) ?>">
</span>
</p>

<p>Who do you want to be able to see your pledge?
<br><input onclick="grey_password(true)" type="radio" name="visibility" value="all"<?=($v=='all'?' checked':'') ?>> Anyone
<input onclick="grey_password(false)" type="radio" name="visibility" value="password"<?=($v=='password'?' checked':'') ?>> Only people to whom I give this password:
<input type="text" id="password" name="password" value="">
</p>

<p style="text-align: right;">
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<input type="submit" value="Submit">
</p>

<?
}

function pledge_form_submitted() {
    $data = array();
    $fields = array('action', 'people', 'name', 'email', 'ref', 'detail', 'comparison', 'type', 'date', 'signup', 'data');
    foreach ($fields as $field) {
        $data[$field] = get_http_var($field);
    }
    if ($data['action']=='<Enter your pledge>') $data['action'] = '';
    if (!$data['type']) $data['type'] = 'people';
    $data['date'] = parse_date($data['date']);
    if (!$data['signup']) $data['signup'] = 'sign up';

    $errors = step1_error_check($data);

    $stepdata = unserialize(base64_decode($data['data']));
    if ($stepdata && !is_array($stepdata)) $errors[] = 'Transferring the data between steps failed!';
    unset($data['data']);
    $data = array_merge($stepdata, $data);
    if (sizeof($errors)) {
        pledge_form($data, $errors);
    } else {
        pledge_form_two($data);
    }
}

function step1_error_check($data) {
    global $today;
    $errors = array();
    if (!$data['people']) $errors[] = 'Please enter a target';
    elseif (!ctype_digit($data['people']) || $data['people'] < 1) $errors[] = 'The target must be a positive number';
    elseif ($data['people'] > 100) {
        $errors[] = 'Unfortunately, we do not allow pledges with a target of more than 100 people to be created. Blah, blah, blah. Contact details. Have this on a special box/page?'; # TODO
    }
    if ($data['comparison'] != 'atleast' && $data['comparison'] != 'exactly') {
        $errors[] = 'Please select either "at least" or "exactly" number of people';
    }
    if (!$data['ref']) $errors[] = 'Please enter a PledgeBank reference';
    elseif (strlen($data['ref'])<6) $errors[] = 'The reference must be at least six characters long';
    if (preg_match('/[^a-z0-9-]/i',$data['ref'])) $errors[] = 'The reference must only contain letters, numbers, -';
    $disallowed_refs = array('contact');
    if (in_array($data['ref'], $disallowed_refs)) $errors[] = 'That reference is not allowed.';
    $dupe = db_getOne('SELECT id FROM pledges WHERE ref=?', array($data['ref']));
    if ($dupe) $errors[] = 'That reference is already taken!';
    if (!$data['action']) $errors[] = 'Please enter a pledge';
    if (!$data['date']) $errors[] = 'Please enter a deadline';
    if ($data['date']['iso'] < $today) $errors[] = 'The deadline must be in the future';
    if ($data['date']['error']) $errors[] = 'Please enter a valid date';
    if (!$data['name']) $errors[] = 'Please enter your name';
    if (!$data['email']) $errors[] = 'Please enter your email address';
    return $errors;
}

function pledge_form_two_submitted() {
    $errors = array();
    $data = array();
    $fields = array('country', 'local', 'postcode', 'visibility', 'password', 'data');
    foreach ($fields as $field) {
        $data[$field] = get_http_var($field);
    }

    $step1data = unserialize(base64_decode($data['data']));
    if (!$step1data) $errors[] = 'Transferring the data from Step 1 to Step 2 failed :(';
    unset($data['data']);
    $data = array_merge($step1data, $data);
    
    if (!$data['local']) $data['postcode'] = '';
    if ($data['visibility'] != 'password') { $data['visibility'] = 'all'; $data['password'] = ''; }
    if ($data['local'] && !$data['postcode']) $errors[] = 'Please enter a postcode';
    if ($data['visibility'] == 'password' && !$data['password']) $errors[] = 'Please enter a password';
    if (sizeof($errors)) {
        pledge_form_two($data, $errors);
        return;
    }

    $errors = step1_error_check($data);
    if (sizeof($errors)) {
        pledge_form($data, $errors);
        return;
    }

    create_new_pledge($data);
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
<p id="start"><a href="./new"><strong>Start your own pledge &raquo;</strong></a></p>
<form id="search" action="./" method="get">
<h2>Search</h2>
<p><label for="s">Enter a PledgeBank Reference, or a search string:</label>
<input type="text" id="s" name="search" size="10" value=""></p>
<p style="margin-top: 1em; text-align: right"><input type="submit" value="Go"></p>
</form>
<h2>Sign up to one of our five newest pledges</h2>
<?	$q = db_query('SELECT *, date - CURRENT_DATE AS daysleft FROM pledges WHERE date >= CURRENT_DATE AND confirmed AND password=\'\' ORDER BY id DESC LIMIT 10');
    $new = '';
    $k = 5;
    while ($k && $r = db_fetch_array($q)) {
        $signatures = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ? AND confirmed', array($r['id']));
        $days = $r['daysleft'];
        $new .= '<li>' . htmlspecialchars($r['name']) . ' will <a href="' . $r['ref'] . '">' . htmlspecialchars($r['title']) . '</a> if ';
        $new .= comparison_nice($r['comparison']) . ' ' . htmlspecialchars($r['target']) . ' other ' . $r['type'] . ' ';
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
pledges.signup, pledges.date, pledges.target, pledges.type, pledges.ref, pledges.comparison,
COUNT(signers.id) AS count, max(date)-CURRENT_DATE
AS daysleft FROM pledges, signers WHERE pledges.id=signers.pledge_id AND
pledges.date>=CURRENT_DATE AND pledges.confirmed AND signers.confirmed AND pledges.password=\'\' GROUP
BY pledges.id,pledges.name,pledges.title,pledges.date,pledges.target,pledges.type,pledges.signup,pledges.ref,pledges.comparison ORDER BY count DESC');
    $new = '';
    $k = 5;
    while ($k && $r = db_fetch_array($q)) {
        $days = $r['daysleft'];
        $new .= '<li>'.$r['count'].' '.make_plural($r['count'],'pledge').' : '.htmlspecialchars($r['name']).' will <a href="'.$r['ref'].'">';
        $new .= htmlspecialchars($r['title']).'</a> if ';
        $new .= comparison_nice($r['comparison']) . ' ' . htmlspecialchars($r['target']).' other ' . htmlspecialchars($r['type']) . ' ';
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
    $showname = get_http_var('showname') ? 't' : 'f';
    $ref = get_http_var('pledge_id');

    $q = db_query('SELECT id,target,title,email,confirmed,date,password,comparison FROM pledges WHERE ref=?', array($ref));
    if (!$q) {
        print '<p>Illegal PledgeBank reference!</p>';
        return false;
    }

    $r = db_fetch_array($q);
    $action = $r['title'];
    $target = $r['target'];
    $id = $r['id'];
    $password = $r['password'];
    $comparison = comparison_nice($r['comparison']);

    if ($r['confirmed'] != 't') {
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

    if ($password) {
        if ($pw = get_http_var('pw')) {
            if ($pw != $password) {
                print '<p>Incorrect password!</p>';
                return false;
            }
        } else {
            print '<p>Something went wrong; please <a href="./'.$ref.'">try again</a>.</p>';
            return false;
        }
    }
    if ($comparison=='exactly') {
        $q = db_query('SELECT * FROM signers WHERE pledge_id=? AND confirmed', array($id));
        if ($q) {
            $r = db_fetch_array($q);
            $signedup = db_num_rows($q);
            if ($signedup >= $target) {
                print '<p>That pledge has already reached its target, sorry.</p>';
                return false;
            }
        }
    }
	
    $q = db_query('SELECT email FROM signers WHERE pledge_id = ? AND email= ?', array($id, $email));
    if (db_num_rows($q)) {
        print '<p>You have already signed this pledge!</p>';
        return false;
    }

    $token = str_replace('.', 'X', substr(crypt($id.' '.$email.microtime()), 12, 16));
    $add = db_query('INSERT INTO signers (pledge_id, name,
        email, showname, signtime, token, confirmed) VALUES
        (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, false)', 
        array($id, get_http_var('name'), $email, $showname, $token));
    $link = str_replace('index.php', '', 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . '?confirms=' . $token);
    $success = pb_send_email($email, 'Signing up to "'.$action.'" at PledgeBank.com', "Thank you for submitting your signature to a pledge at PledgeBank. To confirm your email address, please click on this link:\n\n$link\n\n");
    if ($success) { ?>
<p>An email has been sent to the address you gave to confirm it is yours. <strong>Please check your email, and follow the link given there.</strong> <a href="<?=$ref ?>">Back to pledge page</a></p>
<?      return true;
    } else { ?>
<p>Unfortunately, something bad has gone wrong, and we couldn't send an email to the address you gave. Oh dear.</p>
<?      return false;
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
    $confirmed = ($row['confirmed'] == 't');
    if ($confirmed) {
        print '<p>You have already confirmed your signature for this pledge!</p>';
#        return false;
    }

    db_query('UPDATE signers SET confirmed = true WHERE token = ?', array($token));
    $success = db_affected_rows();
    if ($success) {
        # 
        $q = db_query('SELECT * FROM pledges,signers WHERE pledges.id=? AND pledges.id=signers.pledge_id AND pledges.confirmed AND signers.confirmed', array($pledge_id));
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
        } ?>
<p>Thank you for confirming your signature. <a href="<?=$r['ref'] ?>">View pledge page</a>.</p>

<p><a href="<?=$r['ref'] ?>/flyers">View and print Customised Flyers for this pledge</a></p>

<p align="center"><big>Why not <strong>
<script type="text/javascript">
    document.write('<a href="javascript: window.print()">HIT PRINT</a>');
</script>
<noscript>
HIT PRINT
</noscript>
</strong> now and get these example cards below, for you to cut out and give to your friends and neighbours?</big></p>

<style type="text/css">
table {
    border: none;
    margin: 0 auto;
    max-width: 90%;
}
td {
    border: solid 2px black;
}
</style>
<table border="1" cellpadding="10" cellspacing="20"><?

for ($rows = 0; $rows<4; $rows++) {
    print '<tr align="center">';
    for ($cols=0; $cols<2; $cols++) {
        print '<td>';
        print '<strong>"I will ' . $r['title'] . '"</strong>';
        print '<br>Deadline: ' . prettify($r['date']);
        print '<br>www.pledgebank.com/' . $r['ref'];
        print '</td>';
    }
    print '</tr>';
}
?>
</table>
<?  } else { ?>
<p>Something went wrong confirming your signature, hmm.</p>
<?  }
}

function send_success_email($pledge_id) {
    $q = db_query('SELECT * FROM pledges WHERE pledges.id=? AND pledges.confirmed', array($pledge_id));
    $r = db_fetch_array($q);
    $globalsuccess = 1;
    $action = $r['title'];
    $body = 'Congratulations! You said "I will '.$r['title'].' if '.comparison_nice($r['comparison']).' '.$r['target'].' '.$r['type'].' '.($r['signup']=='sign up'?'will do the same':$r['signup']).'", and they have!'."\n\nTo see who else signed up, please follow this link:\n\n".OPTION_BASE_URL.$r['ref']."\n\nYou should also visit this page to be reminded what the pledge was about.\n\nMany thanks,\n\nPledgeBank";
    $success = pb_send_email($r['email'], 'PledgeBank pledge success!', $body);
    if ($success==0) 
        $globalsuccess = 0;
    $q = db_query('SELECT * FROM signers WHERE pledge_id=? AND signers.confirmed', array($pledge_id));
    $s = db_fetch_array($q);
    while ($s) {
        $success = pb_send_email($s['email'], 'PledgeBank pledge success!', $body);
        if ($success==0) 
            $globalsuccess = 0;
        $s = db_fetch_array($q);
    }
    return $globalsuccess;
}

# Individual pledge page
function view_pledge() {
    global $today, $title;

    $ref = get_http_var('pledge');
    $q = db_query('SELECT * FROM pledges WHERE ref=?', array($ref));
    if (!db_num_rows($q)) {
        print '<p>Illegal PledgeBank reference!</p>';
	return false;
    } else {
        $r = db_fetch_array($q);
	$confirmed = ($r['confirmed'] == 't');
	if (!$confirmed) {
	    print '<p>Illegal PledgeBank reference!</p>';
	    return false;
	}
        $password = $r['password'];
        $pw = '';
        if ($password) {
            if ($pw = get_http_var('pw')) {
                if ($pw != $password) {
                    print '<p class="finished">Incorrect password!</p>';
                    print '<form class="pledge" action="./" method="post"><input type="hidden" name="pledge" value="'.htmlspecialchars($ref).'"><h2>Password Protected Pledge</h2><p>This pledge is password protected: please enter the password to proceed:</p>';
                    print '<p><input type="password" name="pw" value=""><input type="submit" value="Submit"></p>';
                    print '</form>';
                    return true;
                }
            } else {
                print '<form class="pledge" action="./" method="post"><input type="hidden" name="pledge" value="'.htmlspecialchars($ref).'"><h2>Password Protected Pledge</h2><p>This pledge is password protected: please enter the password to proceed:</p>';
                print '<p><input type="password" name="pw" value=""><input type="submit" value="Submit"></p>';
                print '</form>';
                return true;
            }
        }

	$action = $r['title'];
        $title = 'I will ' . $action;
	$people = $r['target'];
	$type = $r['type'];
	$date = $r['date'];
	$name = $r['name'];
	$email = $r['email'];
        $signup = $r['signup'];
        $detail = $r['detail'];
        $comparison = comparison_nice($r['comparison']);
	$q = db_query('SELECT * FROM signers WHERE confirmed AND pledge_id=? ORDER BY id', array($r['id']));
	$curr = db_num_rows($q);
	$left = $people - $curr;

	$finished = 0;
	if ($r['date']<$today) {
            $finished = 1;
	    print '<p class="finished">This pledge is now closed, its deadline has passed.</p>';
        }
	if ($left<=0) {
            if ($comparison == 'exactly') {
                $finished = 1;
                print '<p class="finished">This pledge is now closed, its target has been reached.</p>';
            }
	    print '<p class="success">This pledge has been successful!</p>';
	}
?>
<p>Here is the pledge:</p>
<form class="pledge" name="pledge" action="./" method="post"><input type="hidden" name="pledge_id" value="<?=htmlspecialchars(get_http_var('pledge')) ?>">
<input type="hidden" name="pw" value="<?=htmlspecialchars($pw) ?>">
<input type="hidden" name="add_signatory" value="1">
<p style="margin-top: 0">&quot;I will <strong><?=htmlspecialchars($action) ?></strong> if <strong><?=htmlspecialchars($comparison) ?></strong> <strong><?=htmlspecialchars($people) ?></strong> <?=htmlspecialchars($type) ?> <?=($signup=='sign up'?'will do the same':$signup) ?>&quot;</p>
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
<input type="submit" name="submit" value="Submit"></p>
</div>
<? }

if ($detail) {
    print '<p style="text-align:left"><strong>More details</strong><br>' . htmlspecialchars($detail) . '</p>';
}

?>
</form>

<p style="text-align: center"><a href="./<?=get_http_var('pledge') ?>/flyers" title="Stick them places!">Print out customised flyers</a> | <a href="" onclick="return false">Chat about this Pledge</a><? if (!$finished) { ?> | <a href="" onclick="return false">SMS this Pledge</a> | <a href="" onclick="return false">Email this Pledge</a><? } ?></p>
<!-- <p><em>Need some way for originator to view email addresses of everyone, needs countdown, etc.</em></p> -->

<h2>Current signatories</h2><?
		$out = '<li>'.htmlspecialchars($name).' (Pledge Author)</li>';
		$anon = 0;
		while ($r = db_fetch_array($q)) {
			$showname = ($r['showname'] == 't');
			if ($showname) {
                            $out .= '<li>'.htmlspecialchars($r['name']).' <small>(<a href="./?report='.$r['id'].'">Is this signature suspicious?</a>)</small></li>';
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
function create_new_pledge($data) {
    # 'action', 'people', 'name', 'email', 'ref', 'detail', 'comparison', 'type', 'date', 'signup', 'country', 'postcode', 'password'
	$isodate = $data['date']['iso'];
	$token = str_replace('.', 'X', substr(crypt($data['ref'] . ' ' . $data['email'] . microtime()), 12, 16));
	$add = db_query('INSERT INTO pledges (title, target, type, signup, date,
        name, email, ref, token, confirmed, creationtime, detail, comparison, country, postcode, password) VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, false, CURRENT_TIMESTAMP, ?, ?, ?, ?, ?)', 
        array($data['action'], $data['people'], $data['type'], $data['signup'], $isodate, $data['name'], $data['email'], $data['ref'], $token, $data['detail'], $data['comparison'], $data['country'], $data['postcode'], $data['password']));
?>
<p>Thank you very much for submitting your pledge:</p>
<div class="pledge">
<p style="margin-top: 0">&quot;I will <strong><?=htmlspecialchars($data['action']) ?></strong> if <strong><?=htmlspecialchars($data['people']) ?></strong> <?=htmlspecialchars($data['type']) ?> <?=($data['signup']=='sign up'?'will do the same':$data['signup']) ?>&quot;</p>
<p>Deadline: <strong><?=prettify($isodate) ?></strong></p>
<p style="text-align: right">&mdash; <?=htmlspecialchars($data['name']) ?></p>
</div>
<?
	$link = str_replace('index.php', '', 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . '?confirmp=' . $token);
	$success = pb_send_email($data['email'], 'New pledge at PledgeBank.com : '.$data['action'], "Thank you for submitting your pledge to PledgeBank. To confirm your email address, please click on this link:\n\n$link\n\n");
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

	$confirmed = ($row['confirmed'] == 't');
	if ($confirmed) {
		print '<p>You have already confirmed this pledge!</p>';
		return false;
	}

	db_query('UPDATE pledges SET confirmed=true,creationtime=CURRENT_TIMESTAMP WHERE token = ?', array($token));
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
    $q = db_query('SELECT title,target,date,name,ref FROM pledges WHERE confirmed AND password=\'\'');
    print '<table><tr><th>Title</th><th>Target</th><th>Deadline</th><th>Creator</th><th>Short name</th></tr>';
    while ($r = db_fetch_row($q)) {
            $r[0] = '<a href="'.$r[4].'">'.$r[0].'</a>';
            print '<tr><td>'.join('</td><td>',array_map('prettify',$r)).'</td></tr>';        
    }
    print '</table>';
}

function pdfs() {
    $ref = get_http_var('pdf');
	$q = db_query('SELECT * FROM pledges WHERE ref = ?', array($ref));
	$row = db_fetch_array($q);
        if (!$row) {
            print '<p>Illegal PledgeBank reference!</p>';
            return false;
        }
        $pdf_cards_url = new_url("../flyers/{$ref}_A4_cards.pdf", false);
        $pdf_tearoff_url = new_url("../flyers/{$ref}_A4_tearoff.pdf", false);
    ?>
<h2>Customised Flyers</h2>
<p>Below you can generate <acronym title="Portable Document Format">PDF</acronym>s containing your pledge data, to print out, display, hand out, or whatever.</p>
<ul>
<li><a href="<?=$pdf_tearoff_url?>">Tear-off format (like accommodation rental ones) (A4)</a></li>
<li><a href="<?=$pdf_cards_url?>">Sheet of little pledge cards (A4)</a></li>
</ul>

<?  return true;
}

function search() {
    global $today;
    $id = db_getOne('SELECT id FROM pledges WHERE ref = ?', array(get_http_var('search')));
    if ($id) {
        Header("Location: " . get_http_var('search')); # TODO: should be absolute?
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

function comparison_nice($comparison) {
    if ($comparison == 'atleast') 
        return 'at least';
    else if ($comparison == 'exactly')
        return 'exactly';
    else
        err("Unknown comparison type '$comparison'");
}

?>
