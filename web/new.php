<?
// new.php:
// New pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: new.php,v 1.60 2005-07-22 13:57:39 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../../phplib/person.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../phplib/alert.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/mapit.php';      # To test validity of postcodes
require_once '../../phplib/countries.php';
require_once '../../phplib/gaze.php';

$page_title = _('Create a New Pledge');
$page_params = array();
ob_start();
if (get_http_var('newpost')==1) {
    pledge_form_one_submitted();
} elseif (get_http_var('newpost')=='tw') {
    pledge_form_target_warning_submitted();
} elseif (get_http_var('newpost')==2) {
    pledge_form_two_submitted();
} elseif (get_http_var('newpost')==3) {
    pledge_form_three_submitted();
} else {
    pledge_form_one();
}
$contents = ob_get_contents();
ob_end_clean();
page_header($page_title, $page_params);
print $contents;
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
<h2><?=_('Top Tips for Successful Pledges') ?></h2>
<ol>

<li> <?=_('<strong>Keep your ambitions modest</strong> &mdash; why ask for 50 people
to do something when 5 would be enough? Every extra person makes your pledge
harder to meet.') ?></li>

<li> <?=_("<strong>Think about how your pledge reads.</strong> How will it look to
someone who picks up a flyer from their doormat? Read your pledge to the person
next to you, or to your mother, and see if they understand what you're talking
about. If they don't, you need to rewrite it.") ?></li>

<li> <?=_("<strong>Don't imagine that your pledge will sell itself.</strong> If
you've created something, tell the world! Email your friends, print leaflets
and stick them through your neighbours doors. Focus especially hard on breaking
outside your circle of friends &mdash; ask your co-workers, put a flyer through
the door of that neighbour whose name you've forgotten.") ?></li>

</ol>
</div>
<?
    }

    if (get_http_var('local') && $ref = get_http_var('ref')) {
        $p = new Pledge($ref);
        $data['title'] = $p->title();
        $data['target'] = 5;
        $data['type'] = _('people in my street');
    }

    if (get_http_var('streetparty')) {
        # Remember to change the error handling code down below if you change <MY STREET>
        $data['title'] = _("organise a street party for <MY STREET>");
        $data['target'] = 3;
        $data['type'] = _('other people on <MY STREET>');
        $data['signup'] = _('help organise it');
        $data['identity'] = _('resident of <MY STREET>');
    }

    if (get_http_var('picnic')) {
        $data['title'] = _("organise a picnic this summer");
        $data['target'] = 5;
        $data['type'] = _('friends');
        $data['signup'] = _('pledge to come along and bring food or drink');
        $data['identity'] = _('picnic lover');
    }

    global $pb_time;
    $P = person_if_signed_on();
    if (!is_null($P)) {
        if (!array_key_exists('email', $data))
            $data['email'] = $P->email();
        if (!array_key_exists('name', $data))
            $data['name'] = $P->name_or_blank();
    }
?>

<form accept-charset="utf-8" class="pledge" name="pledge" method="post" action="/new"><input type="hidden" name="newpost" value="1">
<h2><?=_('New Pledge &#8211; Step 1 of 3') ?></h2>
<div class="c">
<p><strong><?=_('I will') ?></strong> <input<? if (array_key_exists('title', $errors)) print ' class="error"' ?> onblur="fadeout(this)" onfocus="fadein(this)" title="Pledge" type="text" name="title" id="title" value="<? if (isset($data['title'])) print htmlspecialchars($data['title']) ?>" size="72"></p>

<p><strong><?=_('but only if') ?></strong> <input<? if (array_key_exists('target', $errors)) print ' class="error"' ?> onchange="pluralize(this.value)" title="Target number of people" size="5" type="text" id="target" name="target" value="<?=(isset($data['target'])?htmlspecialchars($data['target']):'10') ?>">
<input<? if (array_key_exists('type', $errors)) print ' class="error"' ?> type="text" id="type" name="type" size="50" value="<?=(isset($data['type'])?htmlspecialchars($data['type']):_('other local people')) ?>"></p>

<p><strong><?=_('will') ?></strong> <input type="text" id="signup" name="signup"
size="74" value="<?=(isset($data['signup'])?htmlspecialchars($data['signup']):'do the same') ?>">.</p>

<p><?=_('The other people must sign up before') ?> <input<? if (array_key_exists('date', $errors)) print ' class="error"' ?> title="Deadline date" type="text" id="date" name="date" onfocus="fadein(this)" onblur="fadeout(this)" value="<? if (isset($data['date'])) print htmlspecialchars($data['date']) ?>"> <small>(e.g. "<?=date('jS F', $pb_time+60*60*24*28) // 28 days ?>")</small></p>

<p><?=_('Choose a short name for your pledge (6 to 16 letters):') ?>
<input<? if (array_key_exists('ref', $errors) || array_key_exists('ref2', $errors)) print ' class="error"' ?> onkeyup="checklength(this)" type="text" size="16" maxlength="16" id="ref" name="ref" value="<? if (isset($data['ref'])) print htmlspecialchars($data['ref']) ?>"> 
<br><small><?=_('This gives your pledge an easy web address. e.g. www.pledgebank.com/tidyupthepark') ?></small>
</p>

<p style="margin-bottom: 1em;"><strong><?=_('Your name:') ?></strong> <input<? if (array_key_exists('name', $errors)) print ' class="error"' ?> type="text" size="20" name="name" value="<? if (isset($data['name'])) print htmlspecialchars($data['name']) ?>">
<strong><?=_('Email:') ?></strong> <input<? if (array_key_exists('email', $errors)) print ' class="error"' ?> type="text" size="30" name="email" value="<? if (isset($data['email'])) print htmlspecialchars($data['email']) ?>">
<br><small><?=_('(we need your email so we can get in touch with you when your pledge completes, and so on)') ?></small>

<p><?=_('On flyers and elsewhere, after your name, how would you like to be described? (optional)') ?>
<br><small><?=_('(e.g. "resident of Tamilda Road")') ?></small>
<input<? if (array_key_exists('identity', $errors)) print ' class="error"' ?> type="text" name="identity" value="<? if (isset($data['identity'])) print htmlspecialchars($data['identity']) ?>" size="40" maxlength="40"></p>

<p id="moreinfo"><?=_('More details about your pledge: (optional)') ?><br> <small><?=_('(links and email addresses will be automatically spotted, no markup needed)') ?></small>
<br><textarea name="detail" rows="10" cols="60"><? if (isset($data['detail'])) print htmlspecialchars($data['detail']) ?></textarea>

</div>
<p style="text-align: right">
<?=_("Did you read the tips at the top of the page? They'll help you make a successful pledge") ?> <input type="submit" name="submit" value="<?=_('Next') ?> &gt;&gt;"></p>
<? if (sizeof($data)) {
    print '<input type="hidden" name="data" value="' . base64_encode(serialize($data)) . '">';
} ?>
</form>
<? 
}

function pledge_form_target_warning($data, $errors) {

	if (sizeof($errors)) {
		print '<div id="errors"><ul><li>';
		print join ('</li><li>', array_values($errors));
		print '</li></ul></div>';
    }

    print '<p>' . _('Your pledge looks like this so far:') . '</p>';
    $isodate = $data['parseddate']['iso'];
    $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    $partial_pledge = new Pledge($row);
    $partial_pledge->render_box(array('showdetails' => true));
    
?>

<form accept-charset="utf-8" id="pledgeaction" name="pledge" method="post" action="/new"><input type="hidden" name="newpost" value="tw">

<?  print h2(_('Rethink your target'));
    printf(p(_("Hello - we've noticed that your pledge is aiming to recruit more than
%d people.")), OPTION_PB_TARGET_WARNING);
    printf(p(_("Recruiting more than %d people to a pledge is a
lot of work, and many people who have set up pledges larger than this have not
succeeded.  You should only set a large target if you are preprared to do some
serious marketing of your pledge.")), OPTION_PB_TARGET_WARNING);
    print p(_('Please take advantage of this box to change your target.  There is <a
href="/faq#targets">more advice</a> about choosing a target in the FAQ.'));
?>
<p><?=_('<strong>My target</strong> is ') ?>
<input<? if (array_key_exists('target', $errors)) print ' class="error"' ?> onchange="pluralize(this.value)" title="<?=_('Target number of people') ?>" size="5" type="text" id="target" name="target" value="10">
<strong><?=$data['type']?></strong></p>

<p><?=_('Remember, a small but successful pledge can be the perfect preparation
for a larger and more ambitious one.') ?></p>

<p style="text-align: right;">
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<input type="submit" name="newback" value="&lt;&lt; <?=_('Back to step 1') ?>">
<input type="submit" name="submit" value="<?=_('Next') ?> &gt;&gt;">
</p>

</form>

<?
}


function pledge_form_two($data, $errors = array()) {
    global $countries_name_to_code, $countries_code_to_name;

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

    $country = gaze_get_country_from_ip($_SERVER['REMOTE_ADDR']);
    # $country = gaze_get_country_from_ip(""); # Ukraine: "194.44.201.2" # France: "213.228.0.42"

    if (!$country) $country = '';
    if (isset($data['country'])) $country = $data['country'];

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    } else {
?>

<p><?=_('Your pledge looks like this so far:') ?></p>
<?  }
    $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    $partial_pledge = new Pledge($row);
    $partial_pledge->render_box(array('showdetails' => true));
?>

<form accept-charset="utf-8" id="pledgeaction" name="pledge" method="post" action="/new"><input type="hidden" name="newpost" value="2">
<h2><?=_('New Pledge &#8211; Step 2 of 3') ?></h2>

<input type="hidden" name="comparison" value="atleast">
<? /* <p>Should the pledge stop accepting new subscribers when it
is fulfilled?
<input type="radio" name="comparison" value="exactly"<?=($comparison == 'exactly') ? ' checked' : '' ?>> Yes
<input type="radio" name="comparison" value="atleast"<?=($comparison == 'atleast') ? ' checked' : '' ?>> No
</p> */?>

<p><?=_('Which country does your pledge apply to?') ?>
<select <? if (array_key_exists('country', $errors)) print ' class="error"' ?> id="country" name="country" onchange="update_postcode_local(this, true)">
  <option value="(choose one)"><?=_('(choose one)') ?></option>
  <!-- needs explicit values for IE Javascript -->
<?
    if ($country and array_key_exists($country, $countries_code_to_name)) {
        print "<option value=\"$country\" >";
        print $countries_code_to_name[$country];
        print "</option>";
    }
?>
  <option value="Global" <? if ($country=='Global') print ' selected'; ?> ><?=_('Global') ?></option>
  <option value="(seperator)"><?=_('---------------------------------------------------') ?></option>
<?
    foreach ($countries_name_to_code as $opt_country => $opt_code) {
        print "<option value=\"$opt_code\" ";
        if ($opt_country == $opt_code) 
            print ' selected'; 
        print ">";
        print "$opt_country</option>";
    }
?>
</select>
</p>

<p><span id="local_line"><?=_('Within the UK, is your pledge specific to a local area?') ?></span>
<br><input <? if (array_key_exists('local', $errors)) print ' class="error"' ?> onclick="update_postcode_local(this, true)" type="radio" id="local1" name="local" value="1"<?=($local?' checked':'') ?>> <?=_('Yes') ?>
<input <? if (array_key_exists('local', $errors)) print ' class="error"' ?> onclick="update_postcode_local(this, true)" type="radio" id="local0" name="local" value="0"<?=($notlocal?' checked':'') ?>> <?=_('No') ?>
<br>
<span id="postcode_line">
<!-- If yes, enter your postcode so that local people can find your pledge: -->
<?=_('If yes, enter any postcode that is in the local area:') ?>
<input <? if (array_key_exists('postcode', $errors)) print ' class="error"' ?> type="text" name="postcode" id="postcode" value="<? if (isset($data['postcode'])) print htmlspecialchars($data['postcode']) ?>">
<small><?=_('(This will be used so people who live nearby can find your pledge.  You can enter
just the start of the postcode, such as WC1, if you like.)') ?></small>
</span>
</p>

<p><?=_('Which category does your pledge best fit into?') ?>
<select name="category">
<option value="-1"><?=_('(choose one)') ?></option>
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
<br><small><?=_('(this will be used in future to help more people find your pledge)') ?></small>
</p>

<p><?=_('Who do you want to be able to see your pledge?') ?>
<br><input onclick="grey_pin(true)" type="radio" name="visibility" value="all"<?=($v=='all'?' checked':'') ?>> <?=_('Anyone') ?>
<input onclick="grey_pin(false)" type="radio" name="visibility" value="pin"<?=($v=='pin'?' checked':'') ?>> <?=_('Only people to whom I give this PIN:') ?>
<input <? if (array_key_exists('pin', $errors)) print ' class="error"' ?> type="text" id="pin" name="pin" value="">
</p>

<p style="text-align: right;">
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<input type="submit" name="newback" value="&lt;&lt; <?=_('Back to step 1') ?>">
<input type="submit" name="submit" value="<?=_('Preview') ?> &gt;&gt;">
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
    $data['signup'] = preg_replace('#\.$#', '', $data['signup']);

    $stepdata = unserialize(base64_decode($data['data']));
    if ($stepdata && !is_array($stepdata)) $errors[] = _('Transferring the data between steps failed!');
    unset($data['data']);
    if ($stepdata)
        $data = array_merge($stepdata, $data);

    $errors = step1_error_check($data);
    if (sizeof($errors)) {
        pledge_form_one($data, $errors);
        return;
    } 
    $errors = target_warning_error_check($data);
    if (sizeof($errors) || $data['target'] > OPTION_PB_TARGET_WARNING) {
        pledge_form_target_warning($data, $errors);
        return;
    }
    pledge_form_two($data);
}

function pledge_form_target_warning_submitted() {
    $data = array();
    $fields = array('data','target');
    foreach ($fields as $field) {
        $data[$field] = get_http_var($field);
    }
    $steptwdata = unserialize(base64_decode($data['data']));
    if (!$steptwdata) $errors[] = _('Transferring the data from target warning failed!');
    unset($data['data']);
    $data = array_merge($steptwdata, $data);

    $errors = target_warning_error_check($data);
    if (sizeof($errors) && !get_http_var('newback')) {
        pledge_form_target_warning($data, $errors);
        return;
    }

    $errors = step1_error_check($data);
    if (sizeof($errors) || get_http_var('newback')) {
        pledge_form_one($data, $errors);
    } else {
        pledge_form_two($data);
    }
}

function step1_error_check($data) {
    global $pb_today;

    $errors = array();
    if (!$data['target']) $errors['target'] = _('Please enter a target');
    elseif (!ctype_digit($data['target']) || $data['target'] < 1) $errors['target'] = _('The target must be a positive number');

    $disallowed_refs = array('contact', 'translate');
    if (!$data['ref']) $errors['ref'] = _('Please enter a short name for your pledge');
    elseif (strlen($data['ref'])<6) $errors['ref'] = _('The short name must be at least six characters long');
    elseif (strlen($data['ref'])>16) $errors['ref'] = _('The short name can be at most 20 characters long');
    elseif (in_array($data['ref'], $disallowed_refs)) $errors['ref'] = _('That short name is not allowed.');
    if (preg_match('/[^a-z0-9-]/i',$data['ref'])) $errors['ref2'] = _('The short name must only contain letters, numbers, or a hyphen.  Spaces are not allowed.');
    if (!preg_match('/[a-z]/i',$data['ref'])) $errors['ref2'] = _('The short name must contain at least one letter.');

    $dupe = db_getOne('SELECT id FROM pledges WHERE ref ILIKE ?', array($data['ref']));
    if ($dupe) $errors['ref'] = _('That short name is already taken!');
    if (!$data['title']) $errors['title'] = _('Please enter a pledge');

    $pb_today_arr = explode('-', $pb_today);
    $deadline_limit = 2; # in months
    $deadline_limit = date('Y-m-d', mktime(12, 0, 0, $pb_today_arr[1] + $deadline_limit, $pb_today_arr[2], $pb_today_arr[0]));
    if (!$data['date'] || !$data['parseddate']) $errors['date'] = _('Please enter a deadline');
    if ($data['parseddate']['iso'] < $pb_today) $errors['date'] = _('The deadline must be in the future');
    if ($data['parseddate']['error']) $errors['date'] = _('Please enter a valid date');
    if ($deadline_limit < $data['parseddate']['iso'])
        $errors['date'] = _('We have imposed a limit of two months for pledges. This is
        not a hard limit, just a way of encouraging people to aim at smaller, more
        achievable targets. If you want a longer pledge, we\'d be glad to set it
        up for you. Just drop us a quick email to <a href="mailto:team@pledgebank.com">team@pledgebank.com</a>
        letting us know who you are and what you are aiming to do.');

    if (!$data['name']) $errors['name'] = _('Please enter your name');
    if (!$data['email']) $errors['email'] = _('Please enter your email address');
    if (!validate_email($data['email'])) $errors['email'] = _('Please enter a valid email address');

    $mystreetmessage = htmlspecialchars(_('Please change <MY STREET> to the name of your street'));
    if (stristr($data['title'], "<MY STREET>")) $errors['title'] = $mystreetmessage;
    if (stristr($data['type'], "<MY STREET>")) $errors['type'] = $mystreetmessage;
    if (stristr($data['identity'], "<MY STREET>")) $errors['identity'] = $mystreetmessage;

    return $errors;
}

function target_warning_error_check($data) {
    global $pb_today;

    $errors = array();
    if (!$data['target'] || !ctype_digit($data['target']))
        $errors['target'] = _('Please enter a target');
    elseif ($data['target'] > OPTION_PB_TARGET_CAP) {
        $errors['target'] = sprintf(_('We have imposed a cap of
            %d people maximum on each pledge. This is not a
            hard limit, just a way of encouraging people to aim at smaller and more
            achievable targets. If you want a target higher than 
            %d people, we\'d be glad to set it up for you.
            Just drop us a quick email to <a
            href="mailto:team@pledgebank.com">team@pledgebank.com</a> letting us
            know who you are and what you are aiming to do.'), OPTION_PB_TARGET_CAP, OPTION_PB_TARGET_CAP);
    }

    return $errors;
}
 
function step2_error_check($data) {
    global $countries_name_to_code;

    $errors = array();
    if ($data['comparison'] != 'atleast' && $data['comparison'] != 'exactly') {
        $errors[] = _('Please select either "at least" or "exactly" number of people');
    }
    if ($data['country'] == "(choose one)") 
        $errors['country'] = _('Please choose which country your pledge applies to');
    elseif ($data['country'] == 'GB') { 
        if ($data['local'] != '1' && $data['local'] != '0')
            $errors['local'] = _('Please choose whether the pledge is local or not');
        if ($data['local']) {
            if (!$data['postcode']) 
                $errors['postcode'] = _('For local pledges, please enter a postcode or the first part of a postcode');
            else if (!validate_postcode($data['postcode']) && !validate_partial_postcode($data['postcode'])) 
                $errors['postcode'] = _('Please enter a valid postcode or first part of a postcode.  For example OX1 3DR or WC1.');
            else if (mapit_get_error(mapit_get_location($data['postcode'], 1)))
                $errors['postcode'] = _("We couldn't recognise that postcode or part of a postcode; please re-check it");
        }
    } elseif ($data['country'] == 'Global') {
        // OK
    } elseif (array_key_exists($data['country'], $countries_name_to_code)) {
        // OK
    } else {
        $errors['country'] = _('Please choose a country, or \'Global\' if your pledge applies across the world');
    }
    if ($data['visibility'] == 'pin' && !$data['pin']) 
        $errors['pin'] = _('Please enter a pin');
    return $errors;
}

function step3_error_check($data) {
    $errors = array();
    if (!$data['confirmconditions']) {
        $errors['confirmconditions'] = _('Please read the terms and conditions paragraph, and check the box to confirm that you have');
    }
    return $errors;
}

function pledge_form_two_submitted() {
    $errors = array();
    $data = array();
    $fields = array('comparison', 'category', 'country', 'local', 'postcode', 'visibility', 'pin', 'data');
    foreach ($fields as $field) {
        $data[$field] = trim(get_http_var($field));
    }

    $step1data = unserialize(base64_decode($data['data']));
    if (!$step1data) $errors[] = _('Transferring the data from Step 1 to Step 2 failed :(');
    unset($data['data']);
    $data = array_merge($step1data, $data);

    if (!$data['local']) $data['postcode'] = '';
    if ($data['country'] != 'GB') {
        $data['local'] = ''; $data['postcode'] = '';
    }
    if ($data['visibility'] != 'pin') { $data['visibility'] = 'all'; $data['pin'] = ''; }

    $errors = step1_error_check($data);
    if (sizeof($errors) || get_http_var('newback')) {
        pledge_form_one($data, $errors);
        return;
    }
    $errors = target_warning_error_check($data);
    if (sizeof($errors)) {
        pledge_form_target_warning($data, $errors);
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
    if (!$alldata) $errors[] = _('Transferring the data from Preview page failed :(');
    unset($data['data']);
    $data = array_merge($alldata, $data);

    $errors = step1_error_check($data);
    if (sizeof($errors) || get_http_var('newback1')) {
        pledge_form_one($data, $errors);
        return;
    }
    $errors = target_warning_error_check($data);
    if (sizeof($errors)) {
        pledge_form_target_warning($data, $errors);
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
    $data['reason_web'] = _('Before creating your new pledge, we need to check that your email is working.');
    $data['template'] = 'pledge-confirm';
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

    print '<p>';
    printf(_('Your pledge, with short name <em>%s</em>, will look like this:'), $data['ref']);
    print '</p>';
    $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    $partial_pledge = new Pledge($row);
    $partial_pledge->render_box(array('showdetails' => true));
    /* <p><img border="0" vspace="5" src="<?=$png_flyers1_url ?>" width="298" height="211" alt="Example of a PDF flyer"></p> */ 
?>

<form accept-charset="utf-8" id="pledgeaction" name="pledge" method="post" action="/new"><input type="hidden" name="newpost" value="3">
<?  print h2(_('New Pledge &#8211; Step 3 of 3'));
    print p(_('Please check the details you have entered, both the pledge itself (see left)
and other details below.  Click one of the two "Back" buttons if you would like
to go back and edit your data.  
<strong>Check carefully, as you cannot edit your pledge after you have
created it.</strong>
(<a href="/faq#editpledge">why not?</a>)'));
?>
<ul>

<li><?=_('Which country does your pledge apply to?') ?> <em><?=
htmlspecialchars($data['country']) ?></em>
</li>

<? if ($data['country'] == "GB") { ?>
<li><?=_('Within the UK, is your pledge specific to a local area?') ?> <em><?=
$local ? _('Yes') . ' (' . htmlspecialchars($data['postcode']) . ')' : _('No') ?></em>
</li>
<? } ?>

<li><?=_('Does your pledge fit into a specific topic or category?') ?> <em><?=
    $data['category'] == -1
        ? _('No')
        : _('Yes') . ': "'
            . htmlspecialchars(db_getOne('select name from category where id = ?', $data['category'])) // XXX show enclosing cat?
            . '"'
?></em></li>

<li><?=_('Who do you want to be able to see your pledge?') ?> <em><?
if ($v=='all') print _('Anyone');
if ($v=='pin') print _('Only people to whom I give a PIN I have specified');
?></em></li>
</ul>

<?
    print h2(_('Terms and Conditions'));
    print '<p><strong>' . _('Click "Create" to confirm that you wish PledgeBank.com to display the
pledge at the top of this page in your name.') . '</strong> ';
    if ($v == 'pin') { ?>
<!-- no special terms for private pledge -->
<?  } else {
        print _('You also consent to the syndication of your pledge to other sites &mdash; this means that they will be able to display your pledge and your name');
        if ($data['country'] == "GB" && $local) {
            print _(', and <strong>use (but not display) your postcode</strong> to locate your pledge in the right geographic area');
        }
        print '.';
        print _('The purpose of this is simply to give your pledge
greater publicity and a greater chance of succeeding.');
        print ' ';
    }
    print _("Rest assured that we won't ever give or sell anyone your email address."); ?>
<br><input type="checkbox" name="confirmconditions" id="confirmconditions" value="1"><label for="confirmconditions"><?=_('Tick this box to confirm you have read the Terms and Conditions') ?>.</label>
</p>


<p style="text-align: right;">
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<input type="submit" name="newback1" value="&lt;&lt; <?=_('Back to step 1') ?>">
<input type="submit" name="newback2" value="&lt;&lt; <?=_('Back to step 2') ?>">
<input type="submit" name="submit" value="<?=_('Create') ?> &gt;&gt;">
</p>

</form>
<?
    
}

# Someone has submitted a new pledge
function create_new_pledge($P, $data) {
    $isodate = $data['parseddate']['iso'];
    if ($data['visibility'] == 'all')
        $data['pin'] = null;

    /* Guard against double-insertion. */
    db_query('lock table pledges in share mode');
        /* Can't just use SELECT ... FOR UPDATE since that wouldn't prevent an
         * insert on the table. */
    if (is_null(db_getOne('select id from pledges where ref = ?', $data['ref']))) {
        $data['id'] = db_getOne("select nextval('pledges_id_seq')");
        if ($data['postcode'] == '') {
            $data['postcode'] = null;
        }
        $latitude = null;
        $longitude = null;
        if ($data['postcode']) {
            $location = mapit_get_location($data['postcode'], 1);
            if (mapit_get_error($location)) {
                /* This error should never happen, as earlier postcode validation in form will stop it */
                err('Invalid postcode while setting alert, please check and try again.');
            }
            $latitude = $location['wgs84_lat'];
            $longitude = $location['wgs84_lon'];
        }
        db_query('
                insert into pledges (
                    id, title, target,
                    type, signup, date, datetext,
                    person_id, name, ref, 
                    creationtime,
                    detail,
                    comparison,
                    country, postcode, latitude, longitude,
                    pin, identity
                ) values (
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, 
                    pb_current_timestamp(),
                    ?,
                    ?,
                    ?, ?, ?, ?,
                    ?, ?
                )', array(
                    $data['id'], $data['title'], $data['target'],
                    $data['type'], $data['signup'], $isodate, $data['date'],
                    $P->id(), $data['name'], $data['ref'], 
                    $data['detail'],
                    $data['comparison'],
                    $data['country'], $data['postcode'], $latitude, $longitude,
                    $data['pin'] ? sha1($data['pin']) : null, $data['identity']
                ));

        if ($data['category'] != -1)
            db_query('
                insert into pledge_category (pledge_id, category_id)
                values (?, ?)',
                array($data['id'], $data['category']));

    }

    db_commit();
    $p = new Pledge($data['ref']); // Reselect full data set from DB

    // Comment alerts are no longer sent specially to pledge creators, so we
    // add them in for alerts explicitly here.
    alert_signup($P->id(), "comments/ref", array('pledge_id' => $p->id()));
    db_commit();

    global $page_title, $page_params;
    $page_title = _('Pledge Created');
    $page_params['noprint'] = true;

    $url = htmlspecialchars(OPTION_BASE_URL . "/" . urlencode($p->data['ref']));
?>
    <p class="noprint loudmessage"><?=_('Thank you for creating your pledge.') ?></p>
    <p class="noprint loudmessage" align="center"><? printf(_('It is now live at %s<br>and people can sign up to it there.'), '<a href="'.$url.'">'.$url.'</a>') ?></p>
    <p class="noprint loudmessage" align="center"><?=_('Your pledge will <strong>not be publicised</strong> elsewhere on the site until a few people have signed it.  So get out there and tell your friends and neighbours about your pledge.') ?></p>
<?  post_confirm_advertise($p);
}

?>

