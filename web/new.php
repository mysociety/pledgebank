<?
// new.php:
// New pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: new.php,v 1.2 2005-04-30 15:54:30 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/auth.php';
require_once '../phplib/comments.php';
require_once '../../phplib/utility.php';

page_header('Create a New Pledge');

if (get_http_var('newpost')==1) {
    pledge_form_submitted();
} elseif (get_http_var('newpost')==2) {
    pledge_form_two_submitted();
} elseif (get_http_var('newpost')==3) {
    pledge_form_three_submitted();
} else {
    pledge_form_one();
}

page_footer();

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

<form accept-charset="utf-8" class="pledge" name="pledge" method="post" action="/new"><input type="hidden" name="newpost" value="1">
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

<form accept-charset="utf-8" class="pledge" name="pledge" method="post" action="/new">
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
    elseif ($data['target'] > 400) {
        $errors['target'] = 'We have imposed a cap of 400 people maximum on each
        pledge. This is not a hard limit, just a way of encouraging people to
        aim at smaller and more achievable targets. If you want a target higher
        than 400 people, we\'d be glad to set it up for you. Just drop us a
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

<form accept-charset="utf-8" class="pledge" name="pledge" method="post" action="/new"><input type="hidden" name="newpost" value="3">
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

# Someone has submitted a new pledge
function create_new_pledge($data) {
    # 'title', 'people', 'name', 'email', 'ref', 'detail', 'comparison', 'type', 'parseddate', 'date', 'signup', 'country', 'postcode', 'password'
    $isodate = $data['parseddate']['iso'];
    $token = auth_random_token();
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

?>

