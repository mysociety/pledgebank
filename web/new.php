<?
// new.php:
// New pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: new.php,v 1.20 2005-05-31 14:45:40 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/person.php';
require_once '../phplib/pledge.php';
require_once '../phplib/auth.php';
require_once '../phplib/comments.php';
require_once '../../phplib/utility.php';

page_header('Create a New Pledge');

if (get_http_var('newpost')==1) {
    pledge_form_one_submitted();
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
<div id="tips">
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
    global $pb_time;
    $P = person_if_signed_on();
    if (!is_null($P)) {
        if (!array_key_exists('email', $data))
            $data['email'] = $P->email();
        if (!array_key_exists('name', $data))
            $data['name'] = $P->name();
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

<p>The other people must sign up before <input<? if (array_key_exists('date', $errors)) print ' class="error"' ?> title="Deadline date" type="text" id="date" name="date" onfocus="fadein(this)" onblur="fadeout(this)" value="<? if (isset($data['date'])) print htmlspecialchars($data['date']) ?>"> <small>(e.g. "<?=date('jS F', $pb_time+60*60*24*28) // 28 days ?>")</small></p>

<p>Choose a short name for your pledge (6 to 16 letters):
<input<? if (array_key_exists('ref', $errors)) print ' class="error"' ?> onkeyup="checklength(this)" type="text" size="16" maxlength="16" id="ref" name="ref" value="<? if (isset($data['ref'])) print htmlspecialchars($data['ref']) ?>"> 
<br><small>This gives your pledge an easy web address. e.g. www.pledgebank.com/tidyupthepark</small>
</p>

<p style="margin-bottom: 1em;"><strong>Your name:</strong> <input<? if (array_key_exists('name', $errors)) print ' class="error"' ?> type="text" size="20" name="name" value="<? if (isset($data['name'])) print htmlspecialchars($data['name']) ?>">
<strong>Email:</strong> <input<? if (array_key_exists('email', $errors)) print ' class="error"' ?> type="text" size="30" name="email" value="<? if (isset($data['email'])) print htmlspecialchars($data['email']) ?>">
<br><small>(we need your email so we can get in touch with you when your pledge completes, and so on)</small>

<p>On flyers and elsewhere, after your name, how would you like to be described? (optional)
<br><small>(e.g. "resident of Tamilda Road")</small>
<input type="text" name="identity" value="<? if (isset($data['identity'])) print htmlspecialchars($data['identity']) ?>" size="40" maxlength="40"></p>

<p id="moreinfo">More details about your pledge: (optional)<br> <small>(links and email addresses will be automatically spotted, no markup needed)</small>
<br><textarea name="detail" rows="10" cols="60"><? if (isset($data['detail'])) print htmlspecialchars($data['detail']) ?></textarea>

</div>
<p style="text-align: right"><input type="submit" name="submit" value="Next &gt;&gt;"></p>
<? if (sizeof($data)) {
    print '<input type="hidden" name="data" value="' . base64_encode(serialize($data)) . '">';
} ?>
</form>
<? }

function pledge_form_two($data, $errors = array()) {
    $v = 'all';
    if (isset($data['visibility'])) {
        $v = $data['visibility']; if ($v!='pin') $v = 'all';
    }
    $local = (array_key_exists('local', $data)) && $data['local'] == '1';
    $notlocal = (array_key_exists('local', $data)) && $data['local'] == '0';
    $isodate = $data['parseddate']['iso'];
    if (!isset($data['comparison']))
        $comparison = "atleast";
    else
        $comparison = $data['comparison'];

    $country = '';
    if (isset($data['country'])) $country = $data['country'];

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    } else {
?>

<p>Your pledge looks like this so far:</p>
<?  }
    $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    $partial_pledge = new Pledge($row);
    $partial_pledge->render_box(array('showdetails' => true));
?>

<form accept-charset="utf-8" id="pledgenew" name="pledge" method="post" action="/new"><input type="hidden" name="newpost" value="2">
<h2>New Pledge &#8211; Step 2 of 3</h2>

<input type="hidden" name="comparison" value="atleast">
<? /* <p>Should the pledge stop accepting new subscribers when it
is fulfilled?
<input type="radio" name="comparison" value="exactly"<?=($comparison == 'exactly') ? ' checked' : '' ?>> Yes
<input type="radio" name="comparison" value="atleast"<?=($comparison == 'atleast') ? ' checked' : '' ?>> No
</p> */?>

<p>Which country does your pledge apply to?
<select <? if (array_key_exists('country', $errors)) print ' class="error"' ?> id="country" name="country" onchange="update_postcode_local(this, true)">
  <option>(choose one)</option>
  <!-- needs explicit values for IE Javascript -->
  <option value="UK" <? if ($country=='UK') print ' selected'; ?> >UK</option>
  <option value="Global" <? if ($country=='Global') print ' selected'; ?> >Global</option>
</select>
</p>

<p><span id="local_line">Within the UK, is your pledge specific to a local area?</span>
<br><input <? if (array_key_exists('local', $errors)) print ' class="error"' ?> onclick="update_postcode_local(this, true)" type="radio" id="local1" name="local" value="1"<?=($local?' checked':'') ?>> Yes
<input <? if (array_key_exists('local', $errors)) print ' class="error"' ?> onclick="update_postcode_local(this, true)" type="radio" id="local0" name="local" value="0"<?=($notlocal?' checked':'') ?>> No
<br>
<span id="postcode_line">
If yes, enter your postcode so that local people can find your pledge:
<input <? if (array_key_exists('postcode', $errors)) print ' class="error"' ?> type="text" name="postcode" id="postcode" value="<? if (isset($data['postcode'])) print htmlspecialchars($data['postcode']) ?>">
</span>
</p>

<p>Which category does your pledge best fit into?
<select name="category">
<option value="-1">(choose one)</option>
<?
    /* XXX should do multiple categories, but ignore that for now. */
    $s = db_query('select id, parent_category_id, name from category
        where parent_category_id is null
        order by id');
    while ($a = db_fetch_row($s)) {
        list($id, $parent_id, $name) = $a;
        printf("<option value=\"%s\">%s%s</option>",
                    $id,
                    (is_null($parent_id) ? '' : '&nbsp;-&nbsp;'),
                    htmlspecialchars($name));
    }

?>
</select>
<br><small>(this will help more people find your pledge)</small>
</p>

<p>Who do you want to be able to see your pledge?
<br><input onclick="grey_pin(true)" type="radio" name="visibility" value="all"<?=($v=='all'?' checked':'') ?>> Anyone
<input onclick="grey_pin(false)" type="radio" name="visibility" value="pin"<?=($v=='pin'?' checked':'') ?>> Only people to whom I give this PIN:
<input <? if (array_key_exists('pin', $errors)) print ' class="error"' ?> type="text" id="pin" name="pin" value="">
</p>

<p style="text-align: right;">
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<input type="submit" name="newback" value="&lt;&lt; Back to step 1">
<input type="submit" name="submit" value="Preview &gt;&gt;">
</p>

</form>

<?
}

function pledge_form_one_submitted() {
    $data = array();
    $fields = array('title', 'target', 'name', 'email', 'ref', 'type', 'date', 'signup', 'data','identity','detail');
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
    elseif (strlen($data['ref'])>16) $errors['ref'] = 'The reference can be at most 20 characters long';
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
    if ($data['country'] == "(choose one)") 
        $errors['country'] = 'Please choose which country your pledge applies to';
    elseif ($data['country'] == 'UK') { 
        if ($data['local'] != '1' && $data['local'] != '0')
            $errors['local'] = 'Please choose whether the pledge is local or not';
        if ($data['local'] && !$data['postcode']) 
            $errors['postcode'] = 'For local pledges, please enter a postcode';
        if ($data['local'] && !validate_postcode($data['postcode'])) 
            $errors['postcode'] = 'Please enter a valid postcode, such as OX1 3DR';
    }
    if ($data['visibility'] == 'pin' && !$data['pin']) 
        $errors['pin'] = 'Please enter a pin';
    return $errors;
}

function step3_error_check($data) {
    $errors = array();
    if (!$data['confirmconditions']) {
        $errors['confirmconditions'] = 'Please read the terms and conditions paragraph, and check the box to confirm that you have'; 
    }
    return $errors;
}

function pledge_form_two_submitted() {
    $errors = array();
    $data = array();
    $fields = array('comparison', 'category', 'country', 'local', 'postcode', 'visibility', 'pin', 'data');
    foreach ($fields as $field) {
        $data[$field] = get_http_var($field);
    }

    $step1data = unserialize(base64_decode($data['data']));
    if (!$step1data) $errors[] = 'Transferring the data from Step 1 to Step 2 failed :(';
    unset($data['data']);
    $data = array_merge($step1data, $data);

    if (!$data['local']) $data['postcode'] = '';
    if ($data['country'] != 'UK') {
        $data['local'] = ''; $data['postcode'] = '';
    }
    if ($data['visibility'] != 'pin') { $data['visibility'] = 'all'; $data['pin'] = ''; }

    $errors = step1_error_check($data);
    if (sizeof($errors) || get_http_var('newback')) {
        pledge_form_one($data, $errors);
        return;
    }
    $errors = step2_error_check($data);
    if (sizeof($errors)) {
        pledge_form_two($data, $errors);
        return;
    }

    preview_pledge($data, $errors);
}

function pledge_form_three_submitted() {
    $errors = array();
    $data = array();
    $fields = array('data', 'confirmconditions');
    foreach ($fields as $field) {
        $data[$field] = get_http_var($field);
    }
    
    $alldata = unserialize(base64_decode($data['data']));
    if (!$alldata) $errors[] = 'Transferring the data from Preview page failed :(';
    unset($data['data']);
    $data = array_merge($alldata, $data);

    $errors = step1_error_check($data);
    if (sizeof($errors) || get_http_var('newback1')) {
        pledge_form_one($data, $errors);
        return;
    }
    $errors = step2_error_check($data);
    if (sizeof($errors) || get_http_var('newback2')) {
        pledge_form_two($data, $errors);
        return;
    }
    $errors = step3_error_check($data);
    if (sizeof($errors)) {
        preview_pledge($data, $errors);
        return;
    }
 
    /* User must have an account to do this. */
    $data['template'] = 'pledge-confirm';
    $data['reason'] = 'create your new pledge';
    $P = person_signon($data, $data['email'], $data['name']);

    create_new_pledge($P, $data);
}

function preview_pledge($data, $errors) {
    $v = 'all';
    if (isset($data['visibility'])) {
        $v = $data['visibility']; if ($v!='pin') $v = 'all';
    }
    $local = (isset($data['local'])) ? $data['local'] : '0';
    $isodate = $data['parseddate']['iso'];
    if (!isset($data['comparison']))
        $comparison = "atleast";
    else
        $comparison = $data['comparison'];

	if (sizeof($errors)) {
		print '<div id="errors"><ul><li>';
		print join ('</li><li>', array_values($errors));
		print '</li></ul></div>';
	} #    $png_flyers1_url = new_url("../flyers/{$ref}_A7_flyers1.png", false);
?>
<p>Your pledge, with reference <em><?=$data['ref'] ?></em>, will look like this:</p>
<?  
    $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    $partial_pledge = new Pledge($row);
    $partial_pledge->render_box(array('showdetails' => true));
    /* <p><img border="0" vspace="5" src="<?=$png_flyers1_url ?>" width="298" height="211" alt="Example of a PDF flyer"></p> */ 
?>

<form accept-charset="utf-8" id="pledgenew" name="pledge" method="post" action="/new"><input type="hidden" name="newpost" value="3">
<h2>New Pledge &#8211; Step 3 of 3</h2>

<p>Please check the details you have entered, both the pledge above and other
details below.  Click one of the two "Back" buttons if you would like to go
back and edit your data.  
<strong>Check carefully, as you cannot edit your pledge after you have
created it.</strong>
(<a href="/faq#editpledge">why not?</a>)
</p>


<ul>

<li>Which country does your pledge apply to?
<em><?=htmlspecialchars($data['country']) ?></em>
</li>

<? if ($data['country'] == "UK") { ?>
<li>Within the UK, is your pledge specific to a local area? <em><?=
$local ? 'Yes (' . htmlspecialchars($data['postcode']) . ')' : 'No' ?></em>
</li>
<? } ?>

<li>Does your pledge fit into a specific topic or category?
<em><?=
    $data['category'] == -1
        ? 'No'
        : 'Yes: "'
            . htmlspecialchars(db_getOne('select name from category where id = ?', $data['category'])) // XXX show enclosing cat?
            . '"'
?></em></li>

<li>Who do you want to be able to see your pledge? <em><?
if ($v=='all') print 'Anyone';
if ($v=='pin') print ' Only people to whom I give a PIN I have specified';
?></em></li>
</ul>

<p>
<h2>Terms and Conditions</h2>
<strong>Click "Create" to confirm that you wish PledgeBank.com to display the
pledge at the top of this page in your name.</strong>  
<? if ($v == 'pin') { ?>
<!-- no special terms for private pledge -->
<? } else { ?>
You also consent to the syndication of your pledge to other sites &mdash; this means
that they will be able to display your pledge and your name<? if ($data['country'] == "UK" && $local) { ?>
, and <strong>use (but not display) your postcode</strong> to locate your pledge in the right geographic area<? } ?>.
The purpose of this is simply to give your pledge
greater publicity and a greater chance of succeeding. 
<? } ?>
Rest assured that we won't ever give or sell anyone your email address. 
<input type="checkbox" name="confirmconditions" id="confirmconditions" value="1"><label for="confirmconditions">Tick this box to confirm you have read this paragraph</a>.</input>
</p>


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
function create_new_pledge($P, $data) {
    $isodate = $data['parseddate']['iso'];
    $token = auth_random_token();
    if ($data['visibility'] == 'all')
        $data['pin'] = null;

    /* Guard against double-insertion. */
    db_query('lock table pledges in share mode');
        /* Can't just use SELECT ... FOR UPDATE since that wouldn't prevent an
         * insert on the table. */
    if (is_null(db_getOne('select id from pledges where ref = ?', $data['ref']))) {
        $data['id'] = db_getOne("select nextval('pledges_id_seq')");
        db_query('
                insert into pledges (
                    id, title, target,
                    type, signup, date, datetext,
                    person_id, name, ref, token,
                    confirmed,
                    creationtime,
                    detail,
                    comparison,
                    country, postcode,
                    pin, identity
                ) values (
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    true,
                    pb_current_timestamp(),
                    ?,
                    ?,
                    ?, ?,
                    ?, ?
                )', array(
                    $data['id'], $data['title'], $data['target'],
                    $data['type'], $data['signup'], $isodate, $data['date'],
                    $P->id(), $data['name'], $data['ref'], $token,
                    $data['detail'],
                    $data['comparison'],
                    $data['country'], $data['postcode'],
                    $data['pin'] ? sha1($data['pin']) : null, $data['identity']
                ));

        if ($data['category'] != -1)
            db_query('
                insert into pledge_category (pledge_id, category_id)
                values (?, ?)',
                array($data['id'], $data['category']));
    }

    db_commit();

    page_header('Pledge created');
    $p = new Pledge($data['ref']); // Reselect full data set from DB
    $url = htmlspecialchars(OPTION_BASE_URL . "/" . urlencode($p->data['ref']));
?>
    <p class="noprint" id="loudmessage">Thank you for creating your pledge.</p>
    <p class="noprint" id="loudmessage" align="center">It is now live at <strong><a href="<?=$url?>"><?=$url?></a></strong> <br>and people can sign up to it there.</p>
<?  post_confirm_advertise($p);
}

?>

