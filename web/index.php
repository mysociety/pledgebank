<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.155 2005-04-28 16:19:55 chris Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/utility.php';

require_once 'contact.php';

if (get_http_var('search')) {
    $search_results = search();
}

$title = '';
$header_params = array();
ob_start();
if (get_http_var('abusive')) {
    report_abusive_thing();
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
} elseif (get_http_var('newpost')==3) {
    $title = 'Create a New Pledge';
    pledge_form_three_submitted();
} elseif (get_http_var('contactpost')) {
    $title = 'Contact Us';
    contact_form_submitted();
} elseif (get_http_var('admin')=='pledgebank') {
    $title = 'Admin';
    admin();
} elseif (get_http_var('pdf')) {
    $title = 'Pledge Flyers';
    $header_params['noprint'] = true;
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

page_header($title, $header_params);
print $body;
page_footer();

# --------------------------------------------------------------------

/* report_abusive_thing
 * Reporting of abusive comments, signatures, and pledges. */
function report_abusive_thing() {
    $what = get_http_var('what');
    global $q_what, $q_id, $q_reason;
    global $q_h_what, $q_h_id, $q_h_reason;
    if (!is_null(importparams(
                array('what',       '/^(comment|pledge|signer)$/',  ''),
                array('id',         '/^[1-9]\d*$/',                 ''),
                array('reason',     '//',                           '', null)
            )))
        err("A required parameter was missing");

    /* Find information about the associated pledge. */
    $w = $q_what;
    if ($q_what == 'pledge')
        $pledge_id = $q_id;
    else if ($q_what == 'comment')
        $pledge_id = db_getOne('select pledge_id from comment where id = ?', $q_id);
    else if ($q_what == 'signer') {
        $w = 'signature';
        $pledge_id = db_getOne('select pledge_id from signers where id = ?', $q_id);
    }

    if (is_null($pledge_id))
        err("Bad ID value");

    if (!is_null($q_reason)) {
        db_query('insert into abusereport (what, what_id, reason) values (?, ?, ?)', array($q_what, $q_id, $q_reason));
        db_commit();
        print <<<EOF
<p><strong>Thank you!</strong> One of our team will investigate that $w as soon
as possible. <a href="./">Return to the home page</a>.</p>
EOF;
        return;
    }

    $title = htmlspecialchars(db_getOne('select title from pledges where id = ?', $q_id));

    print <<<EOF
<form accept-charset="utf-8" action="./" method="post">
<h2>Report abusive $w</h2>
EOF;

    if ($q_what == 'pledge') {
        print <<<EOF
<p>You are reporting the pledge</p>
<blockquote>$title</blockquote>
EOF;
    } else if ($q_what == 'signer') {
        $name = htmlspecialchars(db_getOne('select name from signers where id = ?', $q_id));
        print <<<EOF
<p>You are reporting the signer</p>
<blockquote>$name</blockquote>
<p>on the pledge <strong>$title</strong>.</p>
EOF;
    } else if ($q_what == 'comment') {
        print <<<EOF
<p>You are reporting the comment</p>
EOF;
        comments_show_one(db_getRow('select * from comment where id = ?', $q_id));
        print <<<EOF
<p>on the pledge <strong>$title</strong>.</p>
EOF;
    }

    print <<<EOF
<input type="hidden" name="abusive" value="1">
<input type="hidden" name="what" value="$q_h_what">
<input type="hidden" name="id" value="$q_h_id">
<p>Please give a short reason for reporting this $w</p>
<textarea name="reason" rows="5" cols="50"></textarea>
<p><input name="submit" type="submit" value="Submit"></p>
</form>
EOF;

}

function pledge_form_one($data = array(), $errors = array()) {
# <!-- <p><big><strong>Before people can create pledges we should have a stiff warning page, with few, select, bold words about what makes for good &amp; bad pledges (we want to try to get people to keep their target numbers down).</strong></big></p> -->
	if (sizeof($errors)) {
		print '<div id="errors"><ul><li>';
		print join ('</li><li>', array_values($errors));
		print '</li></ul></div>';
	} else {
?>
<div class="tips" style="text-align: left">
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

<form accept-charset="utf-8" class="pledge" name="pledge" method="post" action="./"><input type="hidden" name="newpost" value="1">
<h2>New Pledge &#8211; Step 1 of 3</h2>
<div class="c">
<p><strong>I will</strong> <input<? if (array_key_exists('title', $errors)) print ' class="error"' ?> onblur="fadeout(this)" onfocus="fadein(this)" title="Pledge" type="text" name="title" id="title" value="<? if (isset($data['title'])) print htmlspecialchars($data['title']) ?>" size="72"></p>

<p><strong>but only if</strong> <input<? if (array_key_exists('target', $errors)) print ' class="error"' ?> onchange="pluralize(this.value)" title="Target number of people" size="5" type="text" id="target" name="target" value="<?=(isset($data['target'])?htmlspecialchars($data['target']):'3') ?>">
<input type="text" id="type" name="type" size="50" value="<?=(isset($data['type'])?htmlspecialchars($data['type']):'other local people') ?>"></p>

<p><strong>will</strong> <input type="text" id="signup" name="signup"
size="74" value="<?=(isset($data['signup'])?htmlspecialchars($data['signup']):'do the same') ?>">.</p>

<p>The other people must sign up before <input<? if (array_key_exists('date', $errors)) print ' class="error"' ?> title="Deadline date" type="text" id="date" name="date" onfocus="fadein(this)" onblur="fadeout(this)" value="<? if (isset($data['date'])) print htmlspecialchars($data['date']) ?>"> <small>(e.g. "5th May")</small></p>

<p>Choose a short name for your pledge (6 to 13 letters):
<input<? if (array_key_exists('ref', $errors)) print ' class="error"' ?> onkeyup="checklength(this)" type="text" size="20" id="ref" name="ref" value="<? if (isset($data['ref'])) print htmlspecialchars($data['ref']) ?>"> 
<br><small>This gives your pledge an easy web address. e.g. www.pledgebank.com/tidyupthepark</small>
</p>

<p style="margin-bottom: 1em;">Your name: <input<? if (array_key_exists('name', $errors)) print ' class="error"' ?> type="text" size="20" name="name" value="<? if (isset($data['name'])) print htmlspecialchars($data['name']) ?>">
Email: <input<? if (array_key_exists('email', $errors)) print ' class="error"' ?> type="text" size="30" name="email" value="<? if (isset($data['email'])) print htmlspecialchars($data['email']) ?>">
<br><small>(we need your email so we can get in touch with you when your pledge completes, and so on)</small>
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
    $local = (isset($data['local'])) ? $data['local'] : '0';
    $isodate = $data['parseddate']['iso'];
    if (!isset($data['comparison']))
        $comparison = "atleast";
    else
        $comparison = $data['comparison'];

    $country = '';
    if (isset($data['country'])) $country = $data['country'];
?>

<p>Your pledge looks like this so far:</p>
<?  $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    pledge_box($row);
?>

<form accept-charset="utf-8" class="pledge" name="pledge" method="post" action="./">
<input type="hidden" name="newpost" value="2">
<p style="float: right">
<input type="submit" name="newback" value="&lt;&lt; Back to step 1">
<input type="submit" name="submit" value="Preview &gt;&gt;">
</p>

<h2>New Pledge &#8211; Step 2 of 3</h2>

<p id="moreinfo">More details about your pledge: (optional)<br> <small>(links and email addresses will be automatically spotted, no markup needed)</small>
<br><textarea name="detail" rows="10" cols="60"><? if (isset($data['detail'])) print htmlspecialchars($data['detail']) ?></textarea>

<p>On flyers and elsewhere, after your name, how would you like to be described? (optional)
<br><small>(e.g. "resident of Tamilda Road")</small>
<input type="text" name="identity" value="<? if (isset($data['identity'])) print htmlspecialchars($data['identity']) ?>" size="40" maxlength="40"></p>

<input type="hidden" name="comparison" value="atleast">
<? /* <p>Should the pledge stop accepting new subscribers when it
is fulfilled?
<input type="radio" name="comparison" value="exactly"<?=($comparison == 'exactly') ? ' checked' : '' ?>> Yes
<input type="radio" name="comparison" value="atleast"<?=($comparison == 'atleast') ? ' checked' : '' ?>> No
</p> */?>

<p>Which country does your pledge apply to?
<select name="country">
  <option<? if ($country=='Global') print ' selected'; ?>>Global</option>
  <option<? if ($country=='UK') print ' selected'; ?>>UK</option>
</select>
</p>

<p>Within your country, is your pledge specific to a local area?
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
<input type="submit" name="newback" value="&lt;&lt; Back to step 1">
<input type="submit" name="submit" value="Preview &gt;&gt;">
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
    $data['parseddate'] = parse_date($data['date']);
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
    global $pb_today;

    $errors = array();
    if (!$data['target']) $errors['target'] = 'Please enter a target';
    elseif (!ctype_digit($data['target']) || $data['target'] < 1) $errors['target'] = 'The target must be a positive number';
    elseif ($data['target'] > 100) {
        $errors['target'] = 'We have imposed a cap of 100 people maximum on each
        pledge. This is not a hard limit, just a way of encouraging people to
        aim at smaller and more achievable targets. If you want a target higher
        than 100 people, we\'d be glad to set it up for you. Just drop us a
        quick email to <a href="mailto:team@pledgebank.com">team@pledgebank.com</a> 
        letting us know who you are and what
        you are aiming to do.';
    }

    $disallowed_refs = array('contact');
    if (!$data['ref']) $errors['ref'] = 'Please enter a PledgeBank reference';
    elseif (strlen($data['ref'])<6) $errors['ref'] = 'The reference must be at least six characters long';
    elseif (strlen($data['ref'])>13) $errors['ref'] = 'The reference must be at most 13 characters long';
    elseif (in_array($data['ref'], $disallowed_refs)) $errors['ref'] = 'That reference is not allowed.';
    if (preg_match('/[^a-z0-9-]/i',$data['ref'])) $errors['ref2'] = 'The reference must only contain letters, numbers, or a hyphen';

    $dupe = db_getOne('SELECT id FROM pledges WHERE ref ILIKE ?', array($data['ref']));
    if ($dupe) $errors['ref'] = 'That reference is already taken!';
    if (!$data['title']) $errors['title'] = 'Please enter a pledge';

    $pb_today_arr = explode('-', $pb_today);
    $deadline_limit = 2; # in months
    $deadline_limit = date('Y-m-d', mktime(12, 0, 0, $pb_today_arr[1] + $deadline_limit, $pb_today_arr[2], $pb_today_arr[0]));
    if (!$data['date'] || !$data['parseddate']) $errors['date'] = 'Please enter a deadline';
    if ($data['parseddate']['iso'] < $pb_today) $errors['date'] = 'The deadline must be in the future';
    if ($data['parseddate']['error']) $errors['date'] = 'Please enter a valid date';
    if ($deadline_limit < $data['parseddate']['iso'])
        $errors['date'] = 'We have imposed a limit of two months for pledges. This is
        not a hard limit, just a way of encouraging people to aim at smaller, more
        achievable targets. If you want a longer pledge, we\'d be glad to set it
        up for you. Just drop us a quick email to <a href="mailto:team@pledgebank.com">team@pledgebank.com</a>
        letting us know who you are and what you are aiming to do.';

    if (!$data['name']) $errors['name'] = 'Please enter your name';
    if (!$data['email']) $errors['email'] = 'Please enter your email address';
    return $errors;
}

function step2_error_check($data) {
    $errors = array();
    if ($data['comparison'] != 'atleast' && $data['comparison'] != 'exactly') {
        $errors[] = 'Please select either "at least" or "exactly" number of people';
    }
    if ($data['local'] && !$data['postcode']) $errors[] = 'Please enter a postcode';
    if ($data['visibility'] == 'password' && !$data['password']) $errors[] = 'Please enter a password';
    return $errors;
}

function pledge_form_two_submitted() {
    $errors = array();
    $data = array();
    $fields = array('detail', 'identity', 'comparison', 'country', 'local', 'postcode', 'visibility', 'password', 'data');
    foreach ($fields as $field) {
        $data[$field] = get_http_var($field);
    }

    $step1data = unserialize(base64_decode($data['data']));
    if (!$step1data) $errors[] = 'Transferring the data from Step 1 to Step 2 failed :(';
    unset($data['data']);
    $data = array_merge($step1data, $data);
    if (!$data['local']) $data['postcode'] = '';
    if ($data['visibility'] != 'password') { $data['visibility'] = 'all'; $data['password'] = ''; }

    $errors = step2_error_check($data);
    if (sizeof($errors)) {
        pledge_form_two($data, $errors);
        return;
    }

    $errors = step1_error_check($data);
    if (sizeof($errors) || get_http_var('newback')) {
        pledge_form_one($data, $errors);
        return;
    }
    preview_pledge($data);
}

function pledge_form_three_submitted() {
    $errors = array();
    $data = array();
    $fields = array('data');
    foreach ($fields as $field) {
        $data[$field] = get_http_var($field);
    }

    $alldata = unserialize(base64_decode($data['data']));
    if (!$alldata) $errors[] = 'Transferring the data from Preview page failed :(';
    unset($data['data']);
    $data = $alldata;
    if (get_http_var('newback1')) {
        pledge_form_one($data, $errors);
        return;
    }
    if (get_http_var('newback2')) {
        pledge_form_two($data, $errors);
    }
    create_new_pledge($data);
}

function preview_pledge($data) {
    $v = 'all';
    if (isset($data['visibility'])) {
        $v = $data['visibility']; if ($v!='password') $v = 'all';
    }
    $local = (isset($data['local'])) ? $data['local'] : '0';
    $isodate = $data['parseddate']['iso'];
    if (!isset($data['comparison']))
        $comparison = "atleast";
    else
        $comparison = $data['comparison'];

#    $png_flyers1_url = new_url("../flyers/{$ref}_A7_flyers1.png", false);
?>
<p>Your pledge, with reference <em><?=$data['ref'] ?></em>, will look like this:</p>
<?  $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    pledge_box($row);
    /* <p><img border="0" vspace="5" src="<?=$png_flyers1_url ?>" width="298" height="211" alt="Example of a PDF flyer"></p> */ ?>

<form accept-charset="utf-8" class="pledge" name="pledge" method="post" action="./"><input type="hidden" name="newpost" value="3">
<h2>New Pledge &#8211; Step 3 of 3</h2>

<p>Please check the details you have entered, both the pledge above and other details below, and then either click "Create" to create your pledge, or one of the two "Back" buttons to go back and edit your data.</p>

<ul>
<li>Which country does your pledge apply to?
<em><?=htmlspecialchars($data['country']) ?></em>
</li>

<li>Within your country, is your pledge specific to a local area? <em><?=
$local ? 'Yes (' . htmlspecialchars($data['postcode']) . ')' : 'No' ?></em>
</li>

<li>Who do you want to be able to see your pledge? <em><?
if ($v=='all') print 'Anyone';
if ($v=='password') print ' Only people to whom I give a password I have specified';
?></em></li>
</ul>

<p style="text-align: right;">
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<input type="submit" name="newback1" value="&lt;&lt; Back to step 1">
<input type="submit" name="newback2" value="&lt;&lt; Back to step 2">
<input type="submit" name="submit" value="Create &gt;&gt;">
</p>

</form>
<?
    
}

function front_page() {
?>
<p>Welcome to PledgeBank, the site that helps you get things done that
you couldn't do on your own.</p>

<p>PledgeBank works by letting people set up pledges like "I'll organise
a residents' association, but only if 5 people on my street pledge to
come to my house to talk about it". We've only just entered our
testing phase, and if you want to set up a pledge please
<a href="mailto:team@pledgebank.com">contact us</a> first - we're only
accepting certain kinds at the moment.</p>

<p id="start"><a href="./new"><strong>Start your own pledge &raquo;</strong></a></p>
<form accept-charset="utf-8" id="search" action="./" method="get">
<h2>Search</h2>
<p><label for="s">Enter a PledgeBank Reference, or a search string:</label>
<input type="text" id="s" name="search" size="10" value=""></p>
<p style="margin-top: 0.5em; text-align: right"><input type="submit" value="Go"></p>
</form>
<?
    //list_newest_pledges();
    //list_highest_signup_pledges();
    list_frontpage_pledges();
}

function view_faq() {
    include 'faq.php';
}

# Someone wishes to sign a pledge
function add_signatory() {
    global $q_email, $q_name, $q_showname, $q_ref, $q_pw;
    $errors = importparams(
            array('email',      '/^[^@]+@.+/',     'Please give your email'),
            array('name',       '/^[a-z]/i',        'Please give your name'),
            array('ref',        '/^[a-z0-9-]+$/i', ''),
            array('showname',   '//',              'Please enter showname', 0),
            array('pw',         '//',              '', null)
            );
    if ($q_email=='<Enter your name>') $q_email='';
    if (!is_null($errors))
        return $errors;

    $r = db_getRow('select * from pledges where ref ILIKE ?', $q_ref);
    if (!check_password($q_ref, $r['password']))
        err("Permission denied");

    /* The exact mail we send depends on whether we're already signed up to
     * this pledge. */
    $id = db_getOne('select id from signers where pledge_id = ? and email = ?', array($r['id'], $q_email));
    if (isset($id)) {
        $success = pb_send_email_template($q_email, 'signature-confirm-already', $r);
    } else {
        /* Generate a secure URL to send to the user. */
        $data = array('email' => $q_email, 'name' => $q_name, 
                'showname' => $q_showname, 'pledge_id' => $r['id']);
        $token = pledge_token_store('signup-web', $data);

        $url = OPTION_BASE_URL . "/I/" . $token;
        $success = pb_send_email_template($q_email, 'signature-confirm-ok',
                array_merge($r, array('url'=>$url)));
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
    global $title;

	if (sizeof($errors)) {
		print '<div id="errors"><ul><li>';
		print join ('</li><li>', $errors);
		print '</li></ul></div>';
    }

    $ref = get_http_var('pledge'); 
    $h_ref = htmlspecialchars($ref);
    $q = db_query('SELECT *, pb_current_date() <= date as open FROM pledges WHERE ref ILIKE ?', array($ref));
    if (!db_num_rows($q)) {
        err('PledgeBank reference not known');
        return false;
    } 
    
    $r = db_fetch_array($q);
    $confirmed = ($r['confirmed'] == 't');
    if (!$confirmed) {
        err('PledgeBank reference not known');
	return false;
    }
    if (!deal_with_password("/$h_ref", $ref, $r['password']))
        return false;

    $pledge_id = $r['id'];

    $title = "'I will " . $r['title'] . "'";
    $q = db_query('SELECT * FROM signers WHERE pledge_id=? ORDER BY id', array($pledge_id));
    $curr = db_num_rows($q);
    $left = $r['target'] - $curr;

    $finished = 0;
    if ($r['open'] == 'f') {
        $finished = 1;
        print '<p class="finished">This pledge is now closed, as its deadline has passed.</p>';
    }
    if ($left <= 0) {
        if ($r['comparison'] == 'exactly') {
            $finished = 1;
            print '<p class="finished">This pledge is now closed, as its target has been reached.</p>';
        } else {
            print '<p class="success">This pledge has been successful!';
            if (!$finished) {
                print '<br><strong>You can still add your name to it</strong>, because the deadline hasn\'t been reached yet.';
            }
            print '</p>';
        }
    }

    if (get_http_var('add_signatory'))
        $showname = get_http_var('showname') ? ' checked' : '';
    else
        $showname = ' checked';

    $png_flyers1_url = new_url("../flyers/{$ref}_A7_flyers1.png", false);
    pledge_box($r, $curr, $left);

    if (!$finished) { ?>
<form accept-charset="utf-8" class="pledgesign" name="pledge" action="./" method="post">
<input type="hidden" name="add_signatory" value="1">
<input type="hidden" name="pledge" value="<?=htmlspecialchars(get_http_var('pledge')) ?>">
<input type="hidden" name="ref" value="<?=htmlspecialchars(get_http_var('pledge')) ?>">
<h2>Sign up now</h2>
<? if (get_http_var('pw')) print '<input type="hidden" name="pw" value="'.htmlspecialchars(get_http_var('pw')).'">'; ?>
<p><b>
I, <input onblur="fadeout(this)" onfocus="fadein(this)" type="text" name="name" id="name" value="<?=htmlspecialchars(get_http_var('name'))?>">,
sign up to the pledge.<br>Your email: <input type="text" size="30" name="email" value="<?=htmlspecialchars(get_http_var('email')) ?>"></b>
<br><small>(we need this so we can tell you when the pledge is completed and let the pledge creator get in touch)</small>
</p>
<p><input type="checkbox" name="showname" value="1"<?=$showname?>> Show my name on this pledge </p>
<p><input type="submit" name="submit" value="Sign Pledge"> </p>
</form>

<? }

?>
<div id="flyeremail">
<?    if (!$finished) { ?>
<h2>Spread the word</h2>
<ul id="spread">
   <li> <? print_link_with_password("./$h_ref/email", "", "Email pledge to your friends") ?></li>
   <li> <? print_link_with_password("ical.php?ref=$h_ref", "", "Add deadline to your calendar") ?> </li>
   <li> <? print_link_with_password("./$h_ref/flyers", "Stick them places!", "Print out customised flyers") ?>
<!-- <a href="/<?=$h_ref ?>/flyers"><img border="0" vspace="5" align="right" src="<?=$png_flyers1_url ?>" width="298" height="211" alt="PDF flyers to download"></a> -->
</li>
</ul>
<br clear="all">
<?
 } ?>
</div>

<div id="signatories">
<h2><a name="signers">Current signatories</a></h2><?
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
                            .' <small>(<a href="./?abusive=1&what=signer&id='
                                . htmlspecialchars($r['id'])
                            . '">Is this signature suspicious?</a>)</small>'
                            . '</li>';
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
    print '</div>';

    print '<div id="comments"><h2><a name="comments">Comments on this pledge</a></h2>';
    comments_show($pledge_id); 
    comments_form($pledge_id, 1);
    print '</div>';
}

# Someone has submitted a new pledge
function create_new_pledge($data) {
    # 'title', 'people', 'name', 'email', 'ref', 'detail', 'comparison', 'type', 'parseddate', 'date', 'signup', 'country', 'postcode', 'password'
    $isodate = $data['parseddate']['iso'];
    $token = pledge_random_token();
    if ($data['visibility'] == 'all')
        $data['password'] = null;
    $data['id'] = db_getOne("select nextval('pledges_id_seq')");
    $add = db_query('INSERT INTO pledges (id, title, target, type, signup, date, datetext,
        name, email, ref, token, confirmed, creationtime, detail,
        comparison, country, postcode, password, identity) VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, false, pb_current_timestamp(), ?, ?, ?, ?, ?, ?)', 
        array($data['id'], $data['title'], $data['target'], $data['type'], $data['signup'], $isodate, $data['date'], $data['name'], $data['email'], $data['ref'], $token, $data['detail'], $data['comparison'], $data['country'], $data['postcode'], $data['password'] ? sha1($data['password']) : null, $data['identity']));
?>
<h2>Now check your email...</h2>
<p>You must now click on the link within the email we've just sent you. <strong>Please check your email, and follow the link given there.</strong>  You can start getting other
people to sign up to your pledge after you have clicked the link in the email.</p>
<?
    $url = OPTION_BASE_URL . '/C/' . urlencode($token);
    $success = pb_send_email_template($data['email'], 'pledge-confirm',
    array_merge($data, array('url'=>$url, 'date'=>$isodate)));
    if ($success) {
        db_commit();
        global $title;
        $title = 'Now Check Your Email';
        return true;
    } else {
        db_rollback();
?>
<p>Unfortunately, something bad has gone wrong, and we couldn't send an email to the address you gave. Oh dear.</p>
<?      return false;
    }
}

function list_all_pledges() {
    $type = $_SERVER['REQUEST_URI'];
    $type = substr($type, strpos($type, '?')+1);
    $order = 'id DESC';
    if ($type == 'title') {
        $order = 'title';
    } elseif ($type =='target') {
        $order = 'target';
    } elseif ($type =='deadline') {
        $order = 'date';
    } elseif ($type =='creator') {
        $order = 'name';
    } elseif ($type =='ref') {
        $order = 'ref';
    }
    $q = db_query('SELECT title,target,date,name,ref FROM pledges WHERE confirmed AND date>=pb_current_date() AND password IS NULL ORDER BY '.$order.' LIMIT 50');
    $out = '<table width="100%"><tr><th><a href="./all?title">Title</a></th><th><a href="./all?target">Target</a></th><th><a href="./all?deadline">Deadline</a></th><th><a href="./all?creator">Creator</a></th><th><a href="./all?ref">Short name</a></th></tr>';
    while ($r = db_fetch_row($q)) {
            $r[0] = '<a href="'.$r[4].'">'.$r[0].'</a>';
            $out .= '<tr><td>'.join('</td><td align="center">',array_map('prettify',$r)).'</td></tr>';
    }
    $out .= '</table>';
    print '<h2>Open Pledges 1-'.db_num_rows($q).':</h2>';
    print $out;
}

function list_newest_pledges() {
?>
<h2>Sign up to one of our five newest pledges</h2><?

    $q = db_query("
                SELECT *, date - pb_current_date() AS daysleft
                FROM pledges
                WHERE date >= pb_current_date() AND 
                password is NULL AND confirmed
                ORDER BY id
                DESC LIMIT 5");
    $new = '';
    while ($r = db_fetch_array($q)) {
        $signatures = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
        $new .= '<li>' . pledge_sentence($r, array('html'=>true, 'href'=>$r['ref'])) . ' ';
        if ($r['target'] - $signatures <= 0) {
            $new .= 'Target met, pledge still open for ' . $r['daysleft'] . ' ' . make_plural($r['daysleft'], 'day', 'days');
        } else {
            $new .= "(${r['daysleft']} "
                        . make_plural($r['daysleft'], 'day', 'days') /* XXX i18n */
                        . " left), "
                    . prettify($r['target'] - $signatures)
                    . " more needed";
        }
        $new .= '</li>';
    }
    if (!$new) {
        print '<p>There are no new pledges at the moment.</p>';
    } else {
        print '<ol>'.$new.'</ol>';
    }
}

function list_highest_signup_pledges() {
?>
<h2>&hellip; or sign a pledge with many signatures</h2><?

    $q = db_query("
            SELECT pledges.id, pledges.name, pledges.title, pledges.signup,
                pledges.date, pledges.target, pledges.type, pledges.ref,
                pledges.comparison, COUNT(signers.id) AS count,
                max(date) - pb_current_date() AS daysleft,
                pledges.identity
            FROM pledges, signers
            WHERE pledges.id = signers.pledge_id
                AND pledges.date >= pb_current_date() AND pledges.confirmed
                AND pledges.password is NULL
            GROUP BY pledges.id, pledges.name, pledges.title, pledges.date,
                pledges.target, pledges.type, pledges.signup, pledges.ref,
                pledges.comparison, pledges.identity
            ORDER BY count DESC
            limit 5");
    $new = '';
    while ($r = db_fetch_array($q)) {
        $signatures = $r['count'];
        $new .= '<li>' . pledge_sentence($r, array('html'=>true, 'href'=>$r['ref'])) . ' ';
        if ($r['target'] - $signatures <= 0) {
            $new .= 'Target met, pledge still open for ' . $r['daysleft'] . ' ' . make_plural($r['daysleft'], 'day', 'days');
        } else {
            $new .= "(${r['daysleft']} "
                        . make_plural($r['daysleft'], 'day', 'days') /* XXX i18n */
                        . " left), "
                    . prettify($r['target'] - $signatures)
                    . " more needed";
        }
        $new .= '</li>';
    }
    if (!$new) {
        print '<p>There are currently no active pledges.</p>';
    } else {
        print '<ol>'.$new.'</ol>';
    }

}

function list_frontpage_pledges() {
?>
<h2>Some current pledges</h2><?

    $q = db_query("
                SELECT *, date - pb_current_date() AS daysleft
                FROM pledges, frontpage_pledges
                WHERE 
                pledges.id = frontpage_pledges.pledge_id AND
                date >= pb_current_date() AND 
                password is NULL AND confirmed
                ORDER BY id");
    $pledges = '';
    while ($r = db_fetch_array($q)) {
        $signatures = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
        $pledges .= '<li>' . pledge_sentence($r, array('html'=>true, 'href'=>$r['ref'])) . ' ';
        if ($r['target'] - $signatures <= 0) {
            $pledges .= 'Target met, pledge still open for ' . $r['daysleft'] . ' ' . make_plural($r['daysleft'], 'day', 'days');
        } else {
            $pledges .= "(${r['daysleft']} "
                        . make_plural($r['daysleft'], 'day', 'days') /* XXX i18n */
                        . " left), "
                    . prettify($r['target'] - $signatures)
                    . " more needed";
        }
        $pledges .= '</li>';
    }
    if (!$pledges) {
        print '<p>There are no featured pledges at the moment.</p>';
    } else {
        print '<ol>'.$pledges.'</ol>';
    }
}


function pdfs() {
    $ref = get_http_var('pdf');
    $h_ref = htmlspecialchars($ref);
    $q = db_query('SELECT * FROM pledges WHERE ref ILIKE ?', array($ref));
    $row = db_fetch_array($q);
    if (!deal_with_password("/$h_ref/flyers", $ref, $row['password']))
        return false;

    if (!$row) {
        err('PledgeBank not known');
        return false;
    }
    $pdf_cards_url = new_url("../flyers/{$ref}_A4_cards.pdf", false);
    $pdf_tearoff_url = new_url("../flyers/{$ref}_A4_tearoff.pdf", false);
    $pdf_flyers16_url = new_url("../flyers/{$ref}_A4_flyers16.pdf", false);
    $pdf_flyers8_url = new_url("../flyers/{$ref}_A4_flyers8.pdf", false);
    $pdf_flyers4_url = new_url("../flyers/{$ref}_A4_flyers4.pdf", false);
    $pdf_flyers1_url = new_url("../flyers/{$ref}_A4_flyers1.pdf", false);
    $png_flyers8_url = new_url("../flyers/{$ref}_A4_flyers8.png", false);
    ?>
<div class="noprint">
<h2>Customised Flyers</h2>
<p>Here you can get <acronym title="Portable Document Format">PDF</acronym>s containing your pledge data, to print out, display, hand out, or whatever.</p>
<ul>
<!--
<li><? print_link_with_password($pdf_flyers4_url, "", "Flyers for handing out, 4 per page (A4, PDF)") ?> </li>
-->
<li><? print_link_with_password($pdf_flyers8_url, "", "Flyers for handing out, 8 per page (A4, PDF" . (get_http_var("pw") ? "" : ", like picture below") . ")") ?> </li>
<li><? print_link_with_password($pdf_flyers1_url, "", "Big poster" . 
    ($row['detail'] ? ', including more details' : ''). " (A4, PDF)") ?> </li>
<!--
<li><? print_link_with_password($pdf_flyers16_url, "", "Loads of little flyers, 16 per page (A4, PDF)") ?> </li>
<li><? print_link_with_password($pdf_tearoff_url, "", "Tear-off format (like accommodation rental ones) (A4)") ?> </li>
-->
</ul>
</div>
<?
    // Show inline graphics only for passwordless pledges (as PNG doesn't
    // work for the password protected ones, you can't POST a password
    // into an IMG SRC= link)
    if (!get_http_var('pw')) {
?>
<p class="noprint">Alternatively, simply 
<?print_this_link("print this page out", "")?>
to get these flyers.
</p>

<p><a href="<?=$png_flyers8_url?>"><img width="595" height="842" src="<?=$png_flyers8_url?>" border="0" alt="Graphic of flyers for printing"></a></p>
<?  }

    return true;
}

function search() {
    $out = ''; $success = 0;
    $search = get_http_var('search');
    $id = db_getOne('SELECT id FROM pledges WHERE ref ILIKE ?', $search);
    if ($id) {
        Header("Location: " . OPTION_BASE_URL . '/' . $search);
        exit;
    }

    $q = db_query('SELECT date,ref,title, pb_current_date() <= date as open FROM pledges WHERE password IS NULL AND (title ILIKE \'%\' || ? || \'%\' OR detail ILIKE \'%\' || ? || \'%\') ORDER BY date', array($search, $search));
    if (!db_num_rows($q)) {
    } else {
        $success = 1;
        $closed = ''; $open = '';
        while ($r = db_fetch_array($q)) {
            $text = '<li><a href="' . $r['ref'] . '">' . htmlspecialchars($r['title']) . '</a></li>';
            if ($r['open']=='t') {
                $open .= $text;
            } else {
                $closed .= $text;
            }
        }
        if ($open) {
            $out .= '<p>The following currently open pledges matched your search term "' . htmlspecialchars($search) . '" in either their title or More Details:</p>';
            $out .= '<ul>' . $open . '</ul>';
        }
        if ($closed) {
            $out .= '<p>The following are closed pledges that match your search term:</p>';
            $out .= '<ul>' . $closed . '</ul>';
        }
    }

    $people = array();
    $q = db_query('SELECT ref, title, name FROM pledges WHERE confirmed AND name ILIKE \'%\' || ? || \'%\' ORDER BY name', $search);
    while ($r = db_fetch_array($q)) {
        $people[$r['name']][] = array($r['ref'], $r['title'], 'creator');
    }
    $q = db_query('SELECT ref, title, signers.name FROM signers,pledges WHERE showname AND NOT reported AND signers.pledge_id = pledges.id AND signers.name ILIKE \'%\' || ? || \'%\' ORDER BY name', $search);
    while ($r = db_fetch_array($q)) {
        $people[$r['name']][] = array($r['ref'], $r['title'], 'signer');
    }
    if (sizeof($people)) {
        $success = 1;
        $out .= '<p>The following creators or signatures matched your search term "'.htmlspecialchars($search).'":</p> <dl>';
        ksort($people);
        foreach ($people as $name => $array) {
            $out .= '<dt><b>'.htmlspecialchars($name). '</b></dt> <dd>';
            foreach ($array as $item) {
                $out .= '<dd>';
                $out .= '<a href="' . $item[0] . '">' . $item[1] . '</a>';
                if ($item[2] == 'creator') $out .= " (creator)";
                $out .= '</dd>';
            }
        }
        $out .= '</dl>';
    }

    if (!$success) {
        $out .= '<p>Sorry, we could find nothing that matched "' . htmlspecialchars($search) . '".</p>';
    }
    return $out;
}

?>
