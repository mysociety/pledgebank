<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.86 2005-03-16 15:26:02 chris Exp $

require_once "../phplib/pb.php";
require_once '../phplib/db.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/utility.php';

require_once 'contact.php';

$today = date('Y-m-d');

if (get_http_var('search')) {
    $search_results = search();
}

$title = '';
ob_start();
if (get_http_var('report') && ctype_digit(get_http_var('report'))) {
    if (get_http_var('reason')) {
        send_report();
    } else {
        report_form();
    }
} elseif (get_http_var('add_signatory')) {
    $title = 'Signature addition';
    $errors = add_signatory();
    if (is_array($errors))
        view_pledge($errors);
} elseif (get_http_var('pledge') == 'new') {
    $title = 'Create a New Pledge';
    pledge_form_one();
} elseif (get_http_var('pledge') == 'faq') {
    $title = 'Frequently Asked Questions';
    view_faq();
} elseif (get_http_var('pledge') == 'all') {
    $title = 'All Pledges';
    list_all_pledges();
} elseif (get_http_var('pledge') == 'contact') {
    $title = 'Contact Us';
    contact_form();
} elseif (get_http_var('pledge')) {
    view_pledge();
} elseif (get_http_var('newpost')==1) {
    $title = 'Create a New Pledge';
    pledge_form_submitted();
} elseif (get_http_var('newpost')==2) {
    $title = 'Create a New Pledge';
    pledge_form_two_submitted();
} elseif (get_http_var('contactpost')) {
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
} else {
    $title = null;
    front_page();
}
$body = ob_get_contents();
ob_end_clean();

page_header($title);
print $body;
page_footer();

# --------------------------------------------------------------------

function report_form() {
    $q = db_query('SELECT title, signers.name AS name FROM signers,pledges WHERE signers.pledge_id=pledges.id AND signers.id=?', array(get_http_var('report')));
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
    if (!is_null(importparams(
                array('report',     '/^\d+$/',  ''),
                array('reason',     '//',       '')
            )))
        err("A required parameter was missing");
    $r = db_getRow('
                select name as name, title
                from signers, pledges
                where signers.id = ?
                    and signers.pledge_id = pledges.id
                for update
            ', array($q_report));
    if (is_null($r))
        err("Bad signer ID in report parameter");
    else {
        pb_send_email(
                OPTION_CONTACT_EMAIL,
                "Signature reporting",
                "Reporting '$r[name]' in pledge '$r[title]'\n\nReason given: $q_reason\n\n");
        db_query('
                update signers
                set showname = false, reported = true
                where id = ?', array($q_report));
        db_commit();
        print '<p>Thank you for reporting that signature; it will be looked at asap.</p>';
    }
}

function pledge_form_one($data = array(), $errors = array()) {
# <!-- <p><big><strong>Before people can create pledges we should have a stiff warning page, with few, select, bold words about what makes for good &amp; bad pledges (we want to try to get people to keep their target numbers down).</strong></big></p> -->
	if (sizeof($errors)) {
		print '<div id="errors"><ul><li>';
		print join ('</li><li>', $errors);
		print '</li></ul></div>';
	} else {
?>
<div class="tips">
<h2>Top Tips for Successful Pledges</h2>
<ol>

<li> <strong>Keep your ambitions modest</strong> &mdash; why ask for 50 people
to do something when 5 would be enough? Every extra person makes your pledge
harder to meet.</li>

<li> <strong>Think about how your pledge reads.</strong> How will it look to
someone who picks up a flyer from their doormat? Read your pledge to the person
next to you, or to your mother, and see if they understand what you're talking
about. If they don't, you need to rewrite it.</li>

<li> <strong>Don't imagine that your pledge will sell itself.</strong> If
you've created something, tell the world! Email your friends, print leaflets
and stick them through your neighbours doors. Focus especially hard on breaking
outside your circle of friends &mdash; ask your co-workers, put a flyer through
the door of that neighbour whose name you've forgotten.</li>

</ol>
</div>
<?
    }
?>

<form class="pledge" name="pledge" method="post" action="./"><input type="hidden" name="newpost" value="1">
<h2>New Pledge &#8211; Step 1</h2>
<div class="c">
<p><strong>I will</strong> <input onblur="fadeout(this)" onfocus="fadein(this)" title="Pledge" type="text" name="title" id="title" value="<? if (isset($data['title'])) print htmlspecialchars($data['title']) ?>" size="72"></p>

<p><strong>if</strong> <input onchange="pluralize(this.value)" title="Target number of people" size="5" type="text" id="target" name="target" value="<?=(isset($data['target'])?htmlspecialchars($data['target']):'3') ?>">
<input type="text" id="type" name="type" size="67" value="other local people"></p>

<p><strong>will</strong> <input type="text" id="signup" name="signup"
size="74" value="do the same">.</p>

<p>The other people must sign up before <input title="Deadline date" type="text" id="date" name="date" onfocus="fadein(this)" onblur="fadeout(this)" value="<? if (isset($data['date'])) print htmlspecialchars($data['date']['iso']) ?>"></p>

<p>Choose a short name for your pledge (6 to 12 letters):
<input onkeyup="checklength(this)" type="text" size="20" id="ref" name="ref" value="<? if (isset($data['ref'])) print htmlspecialchars($data['ref']) ?>"> 
<br><small>This gives your pledge an easy web address. e.g. www.pledgebank.com/tidyupthepark</small>
</p>

<p style="margin-bottom: 1em;">Your name: <input type="text" size="20" name="name" value="<? if (isset($data['name'])) print htmlspecialchars($data['name']) ?>">
Email: <input type="text" size="30" name="email" value="<? if (isset($data['email'])) print htmlspecialchars($data['email']) ?>">
</div>
<p style="text-align: right"><input type="submit" name="submit" value="Next &gt;&gt;"></p>
<? if (sizeof($data)) {
    print '<input type="hidden" name="data" value="' . base64_encode(serialize($data)) . '">';
} ?>
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
    if (!isset($data['comparison']))
        $comparison = "atleast";
    else
        $comparison = $data['comparison'];
?>

<p style="text-align: center">Your pledge looks like this so far:</p>
<div class="tips" style="text-align: center">
<p style="margin-top: 0">&quot;<? $row = $data; unset($row['date']); print pledge_sentence($row, array('firstperson'=>true, 'html'=>true)) ?>&quot;</p>
<p>Deadline: <strong><?=prettify($isodate) ?></strong></p>
<p style="text-align: right">&mdash; <?=htmlspecialchars($data['name']) ?></p>
</div>

<form class="pledge" name="pledge" method="post" action="./"><input type="hidden" name="newpost" value="2">
<p style="float: right"><input type="submit" name="submit" value="Next &gt;&gt;"></p>

<h2>New Pledge &#8211; Step 2 (optional details)</h2>

<p id="moreinfo">More details about your pledge:
<br><textarea name="detail" rows="10" cols="60"><? if (isset($data['detail'])) print htmlspecialchars($data['detail']) ?></textarea>

<p>Should the pledge stop accepting new subscribers when it
is fulfilled?
<input type="radio" name="comparison" value="exactly"<?=($comparison == 'exactly') ? ' checked' : '' ?>> Yes
<input type="radio" name="comparison" value="atleast"<?=($comparison == 'atleast') ? ' checked' : '' ?>> No
</p>

<p>Which country does your pledge apply to?
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
<input type="submit" name="submit" value="Next &gt;&gt;">
</p>

</form>

<?
}

function pledge_form_submitted() {
    $data = array();
    $fields = array('title', 'target', 'name', 'email', 'ref', 'type', 'date', 'signup', 'data');
    foreach ($fields as $field) {
        $data[$field] = get_http_var($field);
    }
    if ($data['title']=='<Enter your pledge>') $data['title'] = '';
    if (!$data['type']) $data['type'] = 'other local people';
    $data['date'] = parse_date($data['date']);
    if (!$data['signup']) $data['signup'] = 'sign up';

    $errors = step1_error_check($data);

    $stepdata = unserialize(base64_decode($data['data']));
    if ($stepdata && !is_array($stepdata)) $errors[] = 'Transferring the data between steps failed!';
    unset($data['data']);
    if ($stepdata)
        $data = array_merge($stepdata, $data);
    if (sizeof($errors)) {
        pledge_form_one($data, $errors);
    } else {
        pledge_form_two($data);
    }
}

function step1_error_check($data) {
    global $today;
    $errors = array();
    if (!$data['target']) $errors[] = 'Please enter a target';
    elseif (!ctype_digit($data['target']) || $data['target'] < 1) $errors[] = 'The target must be a positive number';
    elseif ($data['target'] > 100) {
        $errors[] = 'Unfortunately, we do not allow pledges with a target of more than 100 people to be created. Blah, blah, blah. Contact details. Have this on a special box/page?'; # TODO
    }
    if (!$data['ref']) $errors[] = 'Please enter a PledgeBank reference';
    elseif (strlen($data['ref'])<6) $errors[] = 'The reference must be at least six characters long';
    if (preg_match('/[^a-z0-9-]/i',$data['ref'])) $errors[] = 'The reference must only contain letters, numbers, or a hyphen';
    $disallowed_refs = array('contact');
    if (in_array($data['ref'], $disallowed_refs)) $errors[] = 'That reference is not allowed.';
    $dupe = db_getOne('SELECT id FROM pledges WHERE ref=?', array($data['ref']));
    if ($dupe) $errors[] = 'That reference is already taken!';
    if (!$data['title']) $errors[] = 'Please enter a pledge';
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
    $fields = array('detail', 'comparison', 'country', 'local', 'postcode', 'visibility', 'password', 'data');
    foreach ($fields as $field) {
        $data[$field] = get_http_var($field);
    }

    $step1data = unserialize(base64_decode($data['data']));
    if (!$step1data) $errors[] = 'Transferring the data from Step 1 to Step 2 failed :(';
    unset($data['data']);
    $data = array_merge($step1data, $data);
    if (!$data['local']) $data['postcode'] = '';
    if ($data['visibility'] != 'password') { $data['visibility'] = 'all'; $data['password'] = ''; }
    if ($data['comparison'] != 'atleast' && $data['comparison'] != 'exactly') {
        $errors[] = 'Please select either "at least" or "exactly" number of people';
    }
    if ($data['local'] && !$data['postcode']) $errors[] = 'Please enter a postcode';
    if ($data['visibility'] == 'password' && !$data['password']) $errors[] = 'Please enter a password';
    if (sizeof($errors)) {
        pledge_form_two($data, $errors);
        return;
    }

    $errors = step1_error_check($data);
    if (sizeof($errors)) {
        pledge_form_one($data, $errors);
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
href="mailto:team@pledgebank.com">email us</a> if it just
doesn't work, or you have any other suggests or comments. 
</p>
<p id="start"><a href="./new"><strong>Start your own pledge &raquo;</strong></a></p>
<form id="search" action="./" method="get">
<h2>Search</h2>
<p><label for="s">Enter a PledgeBank Reference, or a search string:</label>
<input type="text" id="s" name="search" size="10" value=""></p>
<p style="margin-top: 1em; text-align: right"><input type="submit" value="Go"></p>
</form>
<h2>Sign up to one of our five newest pledges</h2><?

    $q = db_query("
                SELECT *, date - CURRENT_DATE AS daysleft
                FROM pledges
                WHERE date >= CURRENT_DATE AND password is NULL AND confirmed
                ORDER BY id
                DESC LIMIT 5");
    $new = '';
    while ($r = db_fetch_array($q)) {
        $signatures = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
        $new .= '<li>'
                    . pledge_sentence($r, array('html'=>true, 'href'=>$r['ref']))
                    . " (${r['daysleft']} "
                        . make_plural($r['daysleft'], 'day', 'days') /* XXX i18n */
                        . " left), "
                    . prettify($r['target'] - $signatures)
                    . " more needed"
                    . "</li>";
    }
    if (!$new) {
        print '<p>No pledges yet!</p>';
    } else {
        print '<ol>'.$new.'</ol>';
    }
?>

<h2>Five Highest Signup Pledges</h2><?

    $q = db_query("
            SELECT pledges.id, pledges.name, pledges.title, pledges.signup,
                pledges.date, pledges.target, pledges.type, pledges.ref,
                pledges.comparison, COUNT(signers.id) AS count,
                max(date) - CURRENT_DATE AS daysleft
            FROM pledges, signers
            WHERE pledges.id = signers.pledge_id
                AND pledges.date >= CURRENT_DATE AND pledges.confirmed
                AND pledges.password is NULL
            GROUP BY pledges.id, pledges.name, pledges.title, pledges.date,
                pledges.target, pledges.type, pledges.signup, pledges.ref,
                pledges.comparison
            ORDER BY count DESC
            limit 5");
    $new = '';
    while ($r = db_fetch_array($q)) {
        $signatures = $r['count'];
        $new .= '<li>'
                    . pledge_sentence($r, array('html'=>true, 'href'=>$r['ref']))
                    . " (${r['daysleft']} "
                        . make_plural($r['daysleft'], 'day', 'days') /* XXX i18n */
                        . " left), "
                    . prettify($r['target'] - $signatures)
                    . " more needed"
                    . "</li>";
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

    global $q_email, $q_name, $q_showname, $q_ref, $q_pw;
    $errors = importparams(
            array('email',      '/^[^@]+@.+/',     'Please give your email'),
            array('name',       '/[a-z]/i',        'Please give your name'),
            array('ref',        '/^[a-z0-9-]+$/i', ''),
            array('showname',   '//',              'Please enter showname', 0),
            array('pw',         '//',              '', null)
            );
    if (!is_null($errors))
        return $errors;

    $r = db_getRow('select id, title, password from pledges where ref = ?', $q_ref);

    if (!is_null($r['password']) && (is_null($q_pw) || $q_pw != $r['password']))
        err("Permission denied");

    /* The exact mail we send depends on whether we're already signed up to
     * this pledge. */
    $id = db_getOne('select id from signers where pledge_id = ? and email = ?', array($r['id'], $q_email));
    if (defined($id)) {
        $success = pb_send_email(
                $q_email,
                "Already signed up to \"${r['title']}\" at PledgeBank.com",
                <<<EOF

Thanks for signing up to this pledge at PledgeBank, but
according to our records you have already signed it.

Good luck with your pledge!

-- 
PledgeBank.com
a mySociety project
EOF
            );
    } else {
        /* Generate a secure URL to send to the user. */
        $data = array('email' => $q_email, 'name' => $q_name, 
                'showname' => $q_showname, 'pledge_id' => $r['id']);
        $token = pledge_token_store('signup-web', $data);

        $url = OPTION_BASE_URL . "/I/" . $token;

        $success = pb_send_email(
                $q_email,
                "Signing up to \"${r['title']}\" at PledgeBank.com",
                <<<EOF
Thank you for signing a pledge at PledgeBank. To confirm your
email address, please click on this link:

 $url

-- 
PledgeBank.com
a mySociety project
EOF
            );
    }

    if ($success) {
    ?>
<p><strong>Now check your email</strong></p>
<p>We've sent you an email to confirm your address. Please follow the link
we've sent to you to finish signing this pledge.</p>
<?
        db_commit();
        return true;
    } else {
?>
<p>We seem to be having some technical problems. Please could try again in a
few minutes, making sure that you carefully check the email address you give.
</p>
<?
        return false;
    }
}

# Individual pledge page
function view_pledge($errors = array()) {
    global $today, $title;

	if (sizeof($errors)) {
		print '<div id="errors"><ul><li>';
		print join ('</li><li>', $errors);
		print '</li></ul></div>';
    }

    $ref = get_http_var('pledge'); 
    $h_ref = htmlspecialchars($ref);
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
        if (!deal_with_password('pledge', $ref, $r['password']))
            return false;

    $title = "'I will " . $r['title'] . "'";
	$q = db_query('SELECT * FROM signers WHERE pledge_id=? ORDER BY id', array($r['id']));
	$curr = db_num_rows($q);
	$left = $r['target'] - $curr;

	$finished = 0;
	if ($r['date'] < $today) {
        $finished = 1;
	    print '<p class="finished">This pledge is now closed, as its deadline has passed.</p>';
    }
	if ($left <= 0) {
            if ($r['comparison'] == 'exactly') {
                $finished = 1;
                print '<p class="finished">This pledge is now closed, as its target has been reached.</p>';
            } else {
                print '<p class="success">This pledge has been successful!</p>';
            }
	}

    if (get_http_var('add_signatory'))
        $showname = get_http_var('showname') ? 'checked' : '';
    else
        $showname = 'checked';

?>
<p></p>
<form class="pledge" name="pledge" action="./" method="post"><input type="hidden" name="pledge_id" value="<?=$h_ref ?>">
<? if (get_http_var('pw')) print '<input type="hidden" name="pw" value="'.htmlspecialchars(get_http_var('pw')).'">'; ?>
<div class="c">
<p style="margin-top: 0">&quot;<?=pledge_sentence($r, array('firstperson'=>true,
'html'=>true)) ?>&quot;</p>
<p>Deadline: <strong><?=prettify($r['date']) ?></strong></p>

<p style="font-style: italic;"><?=prettify($curr) ?> <?=make_plural($curr,'person has','people have') ?> signed up<?=($left<0?' ('.prettify(-$left).' over target)':', '.prettify($left).' more needed') ?></p>
</div>
<? if (!$finished) { ?>
<div style="margin-left: 50%;">
<h2 style="margin-top: 1em; font-size: 120%">Sign me up</h2>
<p>
<input type="hidden" name="add_signatory" value="1">
<input type="hidden" name="pledge" value="<?=htmlspecialchars(get_http_var('pledge')) ?>">
<input type="hidden" name="ref" value="<?=htmlspecialchars(get_http_var('pledge')) ?>">
Name: <input type="text" name="name" value="<?=htmlspecialchars(get_http_var('name'))?>">
<br /> Email: <input type="text" size="30" name="email" value="<?=htmlspecialchars(get_http_var('email')) ?>">
<br><small>(we need this so we can tell you when the pledge is completed and let the pledge creator get in touch)</small>
<br /> <input type="checkbox" name="showname" value="1" <?=$showname?>> Show my name on this pledge 
<br /><input type="submit" name="submit" value="Submit">
</p>
</div>
<? }

if ($r['detail']) {
    print '<p><strong>More details</strong><br>' . htmlspecialchars($r['detail']) . '</p>';
}

?>
</form>

<p style="text-align: center"><a href="./<?=$h_ref ?>/flyers" title="Stick them places!">Print out customised flyers</a> | <a href="" onclick="return false">Comment on this Pledge</a><? if (!$finished) { ?> | <a href="./<?=$h_ref ?>/email">Email this Pledge</a><? } ?></p>
<!-- <p><em>Need some way for originator to view email addresses of everyone, needs countdown, etc.</em></p> -->

<h2>Current signatories</h2><?
        $out = '<li>'
                . htmlspecialchars($r['name'])
                . ' (Pledge Author)</li>';
        $anon = 0;
        $unknownname = 0;
        while ($r = db_fetch_array($q)) {
            $showname = ($r['showname'] == 't');
            if ($showname) {
                if (isset($r['name'])) {
                    $out .= '<li>'
                            . htmlspecialchars($r['name'])
                            .' <small>(<a href="./?report='
                                . htmlspecialchars($r['id'])
                            . '">Is this signature suspicious?</a>)</small></li>';
                } else {
                    ++$unknownname;
                }
            } else {
                $anon++;
            }
        }
        print '<ul>'.$out;
        if ($anon || $unknownname) {
            /* XXX i18n-a-go-go */
            $extra = '';
            if ($anon)
                $extra .= "Plus $anon "
                            . make_plural($anon, 'other')
                            . ' who did not want to give their '
                            . make_plural($anon, 'name');
            if ($unknownname)
                $extra .= ($anon ? ', and' : 'Plus')
                            . " $unknownname "
                            . make_plural($unknownname, 'other')
                            . ' whose '
                            . make_plural($unknownname, 'name')
                            . " we don't know.";
            print "<li>$extra</li>";
        }
        print '</ul>';
    }
}

# Someone has submitted a new pledge
function create_new_pledge($data) {
    # 'title', 'people', 'name', 'email', 'ref', 'detail', 'comparison', 'type', 'date', 'signup', 'country', 'postcode', 'password'
	$isodate = $data['date']['iso'];
    $token = pledge_email_token($data['email'], $data['ref']);
    if ($data['visibility'] == 'all')
        $data['password'] = null;
	$add = db_query('INSERT INTO pledges (title, target, type, signup, date,
        name, email, ref, token, confirmed, creationtime, detail, comparison, country, postcode, password) VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, false, CURRENT_TIMESTAMP, ?, ?, ?, ?, ?)', 
        array($data['title'], $data['target'], $data['type'], $data['signup'], $isodate, $data['name'], $data['email'], $data['ref'], $token, $data['detail'], $data['comparison'], $data['country'], $data['postcode'], $data['password']));
?>
<h2>Now check your email...</h2>
<p>You must now click on the link within the email we've just sent you. <strong>Please check your email, and follow the link given there.</strong>  You can start getting other
people to sign up to your pledge after you have clicked the link in the email.</p>
<?
    $link = OPTION_BASE_URL . '/C/' . urlencode($token);
	$success = pb_send_email($data['email'], 'New pledge at PledgeBank.com : '.$data['title'], "Thank you for submitting your pledge to PledgeBank. To confirm your email address, please click on this link:\n\n$link\n\n");
	if ($success) {
            db_commit();
?>
<?		
        global $title;
        $title = 'Now Check Your Email';
        return true;
	} else {
            db_rollback();
?>
<p>Unfortunately, something bad has gone wrong, and we couldn't send an email to the address you gave. Oh dear.</p>
<?		return false;
	}
}

# Pledger has clicked on link in their email
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
    if (!deal_with_password('pdf', $ref, $row['password']))
        return false;

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

?>
