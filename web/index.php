<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.140 2005-04-14 10:17:23 francis Exp $

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

function report_form() {
    err("Report form not working");
    // TODO: Add password checking here
    return;
    $q = db_query('SELECT title, signers.name AS name FROM signers,pledges WHERE signers.pledge_id=pledges.id AND signers.id=?', array(get_http_var('report')));
    if (!db_num_rows($q)) {
        err('<p>PledgeBank id not known');
        return false;
    } else {
        $r = db_fetch_array($q);
        print '<form accept-charset="utf-8" action="./" method="post"><input type="hidden" name="report" value="' . htmlspecialchars(get_http_var('report')) . '">';
        print '<h2>Signature reporting</h2>';
        print '<p>You are reporting the signature "' . htmlspecialchars($r['name']) . '" on the pledge "' . htmlspecialchars($r['title']) . '"</p>';
        print '<p>Please give a (short) reason for reporting this signature:</p>';
        print '<textarea name="reason" rows="5" cols="50"></textarea>';
        print '<p><input name="submit" type="submit" value="Submit"></p>';
        print '</form>';
    }
}

function send_report() {
    err("Report form not working");
    // TODO: Add password checking here
    return;
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

<form accept-charset="utf-8" class="pledge" name="pledge" method="post" action="./"><input type="hidden" name="newpost" value="1">
<h2>New Pledge &#8211; Step 1</h2>
<div class="c">
<p><strong>I will</strong> <input onblur="fadeout(this)" onfocus="fadein(this)" title="Pledge" type="text" name="title" id="title" value="<? if (isset($data['title'])) print htmlspecialchars($data['title']) ?>" size="72"></p>

<p><strong>but only if</strong> <input onchange="pluralize(this.value)" title="Target number of people" size="5" type="text" id="target" name="target" value="<?=(isset($data['target'])?htmlspecialchars($data['target']):'3') ?>">
<input type="text" id="type" name="type" size="50" value="<?=(isset($data['type'])?htmlspecialchars($data['type']):'other local people') ?>"></p>

<p><strong>will</strong> <input type="text" id="signup" name="signup"
size="74" value="<?=(isset($data['signup'])?htmlspecialchars($data['signup']):'do the same') ?>">.</p>

<p>The other people must sign up before <input title="Deadline date" type="text" id="date" name="date" onfocus="fadein(this)" onblur="fadeout(this)" value="<? if (isset($data['date'])) print htmlspecialchars($data['date']['iso']) ?>"> <small>(e.g. "5th May")</small></p>

<p>Choose a short name for your pledge (6 to 12 letters):
<input onkeyup="checklength(this)" type="text" size="20" id="ref" name="ref" value="<? if (isset($data['ref'])) print htmlspecialchars($data['ref']) ?>"> 
<br><small>This gives your pledge an easy web address. e.g. www.pledgebank.com/tidyupthepark</small>
</p>

<p style="margin-bottom: 1em;">Your name: <input type="text" size="20" name="name" value="<? if (isset($data['name'])) print htmlspecialchars($data['name']) ?>">
Email: <input type="text" size="30" name="email" value="<? if (isset($data['email'])) print htmlspecialchars($data['email']) ?>">
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

<form accept-charset="utf-8" class="pledge" name="pledge" method="post" action="./"><input type="hidden" name="newpost" value="2">
<p style="float: right"><input type="submit" name="submit" value="Next &gt;&gt;"></p>

<h2>New Pledge &#8211; Step 2 (optional details)</h2>

<p id="moreinfo">More details about your pledge:<br> <small>(links and email addresses will be automatically spotted, no markup needed)</small>
<br><textarea name="detail" rows="10" cols="60"><? if (isset($data['detail'])) print htmlspecialchars($data['detail']) ?></textarea>

<p>On flyers and elsewhere, how would you like to be described?
<br><small>(e.g. "resident of Tamilda Road")</small>
<input type="text" name="identity" value="<? if (isset($data['identity'])) print htmlspecialchars($data['identity']) ?>" size="40" maxlength="40"></p>

<input type="hidden" name="comparison" value="atleast">
<? /* <p>Should the pledge stop accepting new subscribers when it
is fulfilled?
<input type="radio" name="comparison" value="exactly"<?=($comparison == 'exactly') ? ' checked' : '' ?>> Yes
<input type="radio" name="comparison" value="atleast"<?=($comparison == 'atleast') ? ' checked' : '' ?>> No
</p> */?>

<p>Which country does your pledge apply to?
<select name="country"><option>Global<option>UK</select>
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
    global $pb_today;

    $errors = array();
    if (!$data['target']) $errors[] = 'Please enter a target';
    elseif (!ctype_digit($data['target']) || $data['target'] < 1) $errors[] = 'The target must be a positive number';
    elseif ($data['target'] > 100) {
        $errors[] = 'We have imposed a cap of 100 people maximum on each
        pledge. This is not a hard limit, just a way of encouraging people to
        aim at smaller and more achievable targets. If you want a target higher
        than 100 people, we\'d be glad to set it up for you. Just drop us a
        quick email to <a href="mailto:team@pledgebank.com">team@pledgebank.com</a> 
        letting us know who you are and what
        you are aiming to do.';
    }
    if (!$data['ref']) $errors[] = 'Please enter a PledgeBank reference';
    elseif (strlen($data['ref'])<6) $errors[] = 'The reference must be at least six characters long';
    if (preg_match('/[^a-z0-9-]/i',$data['ref'])) $errors[] = 'The reference must only contain letters, numbers, or a hyphen';
    $disallowed_refs = array('contact');
    if (in_array($data['ref'], $disallowed_refs)) $errors[] = 'That reference is not allowed.';
    $dupe = db_getOne('SELECT id FROM pledges WHERE ref ILIKE ?', array($data['ref']));
    if ($dupe) $errors[] = 'That reference is already taken!';
    if (!$data['title']) $errors[] = 'Please enter a pledge';
    if (!$data['date']) $errors[] = 'Please enter a deadline';
    if ($data['date']['iso'] < $pb_today) $errors[] = 'The deadline must be in the future';
    if ($data['date']['error']) $errors[] = 'Please enter a valid date';
    if (!$data['name']) $errors[] = 'Please enter your name';
    if (!$data['email']) $errors[] = 'Please enter your email address';
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
<p style="margin-top: 1em; text-align: right"><input type="submit" value="Go"></p>
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

    if (!$finished) { ?>
<h2 class="v">Spread the word</h2>
<ul id="spread">
   <li> <? print_link_with_password("./$h_ref/flyers", "Stick them places!", "Print out customised flyers") ?></li>
   <li> <? print_link_with_password("./$h_ref/email", "", "Email pledge to your friends") ?></li>
   <li> <? print_link_with_password("ical.php?ref=$h_ref", "", "Add deadline to your calendar") ?> </li>
</ul>
<br clear="both">
<?
 } ?>
<div class="tips" style="text-align: center">
<p style="margin-top: 0">&quot;<?=pledge_sentence($r, array('firstperson'=>true, 'html'=>true)) ?>&quot;</p>
<p align="right">&mdash; <?=$r['name'].($r['identity']?', '.$r['identity']:'') ?></p>
<p>Deadline: <strong><?=prettify($r['date']) ?></strong></p>
<p style="font-style: italic;"><?=prettify($curr) ?> <?=make_plural($curr,'person has','people have') ?> signed up<?=($left<0?' ('.prettify(-$left).' over target)':', '.prettify($left).' more needed') ?></p>
<?
if ($r['detail']) {
    $det = $r['detail'];
    $det = htmlspecialchars($det);
    # regexs here borrowed from TWFY
    preg_match_all("/((http(s?):\/\/)|(www\.))([a-zA-Z\d\_\.\+\,\;\?\%\~\-\/\#\='\*\$\!\(\)\&]+)([a-zA-Z\d\_\?\%\~\-\/\#\='\*\$\!\(\)\&])/", $det, $matches);
    foreach ($matches[0] as $match) {
        $newmatch = $match;
        if (substr($match,0,3)=='www') $newmatch = "http://$match";
        $det = str_replace($match, '<a href="'.$newmatch.'">'.$match.'</a>', $det);
    }
    $det = preg_replace("/([\w\.]+)(@)([\w\.\-]+)/i", "<a href=\"mailto:$0\">$0</a>", $det);
    $det = nl2br($det);
    print '<p align = left><strong>More details</strong><br>' . $det . '</p>';
}
?>
</div>
<? if (!$finished) { ?>
<form accept-charset="utf-8" class="pledge" name="pledge" action="./" method="post">
<input type="hidden" name="add_signatory" value="1">
<input type="hidden" name="pledge" value="<?=htmlspecialchars(get_http_var('pledge')) ?>">
<input type="hidden" name="ref" value="<?=htmlspecialchars(get_http_var('pledge')) ?>">
<h2>Sign up now</h2>
<? if (get_http_var('pw')) print '<input type="hidden" name="pw" value="'.htmlspecialchars(get_http_var('pw')).'">'; ?>
<p><span style="font-weight: bold">
I, <input onblur="fadeout(this)" onfocus="fadein(this)" type="text" name="name" id="name" value="<?=htmlspecialchars(get_http_var('name'))?>">,
sign up to the pledge. Your email: <input type="text" size="30" name="email" value="<?=htmlspecialchars(get_http_var('email')) ?>"></span>
<br><small>(we need this so we can tell you when the pledge is completed and let the pledge creator get in touch)</small>
</p>
<p> <input type="checkbox" name="showname" value="1"<?=$showname?>> Show my name on this pledge </p>
<p><input type="submit" name="submit" value="Sign Pledge"> </p>
<p>Or you can text <strong>pledge <?=htmlspecialchars(get_http_var('pledge'))?></strong> to
<strong><?=OPTION_PB_SMS_DISPLAY_NUMBER?></strong> (cost 25p + normal SMS rate)
            <small>
            <br><b>Small print:</b> SMS operated by charity UKCOD. Sign-up message
            costs 25p + your normal text rate. Further messages from us are free.
            </small>
</p>
</form>

<? }

?>

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
                            #.' <small>(<a href="./?report='
                            #    . htmlspecialchars($r['id'])
                            #. '">Is this signature suspicious?</a>)</small>'
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
    
    # Commented out for now
    #    print "<h2>Comments on this pledge</h2>";
    #    comments_show($pledge_id);
}

# Someone has submitted a new pledge
function create_new_pledge($data) {
    # 'title', 'people', 'name', 'email', 'ref', 'detail', 'comparison', 'type', 'date', 'signup', 'country', 'postcode', 'password'
	$isodate = $data['date']['iso'];
    $token = pledge_email_token($data['email'], $data['ref']);
    if ($data['visibility'] == 'all')
        $data['password'] = null;
    $data['id'] = db_getOne("select nextval('pledges_id_seq')");
	$add = db_query('INSERT INTO pledges (id, title, target, type, signup, date,
        name, email, ref, token, confirmed, creationtime, detail,
        comparison, country, postcode, password, identity) VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, false, pb_current_timestamp(), ?, ?, ?, ?, ?, ?)', 
        array($data['id'], $data['title'], $data['target'], $data['type'], $data['signup'], $isodate, $data['name'], $data['email'], $data['ref'], $token, $data['detail'], $data['comparison'], $data['country'], $data['postcode'], $data['password'] ? sha1($data['password']) : null, $data['identity']));
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

<p><a href="<?=$png_flyers8_url?>"><img src="<?=$png_flyers8_url?>" border="0" alt="Graphic of flyers for printing"></a></p>
<?  }

    return true;
}

function search() {
    $id = db_getOne('SELECT id FROM pledges WHERE ref ILIKE ?', array(get_http_var('search')));
    if ($id) {
        Header("Location: " . get_http_var('search')); # TODO: should be absolute?
        exit;
    }
    $q = db_query('SELECT date,ref,title, pb_current_date() <= date as open FROM pledges WHERE password IS NULL AND title ILIKE \'%\' || ? || \'%\' ORDER BY date', array(get_http_var('search')));
    if (!db_num_rows($q)) {
        return '<p>Sorry, we could find nothing that matched "' . htmlspecialchars(get_http_var('search')) . '".</p>';
    } else {
        $closed = ''; $open = '';
        while ($r = db_fetch_array($q)) {
            $text = '<li><a href="' . $r['ref'] . '">' . $r['title'] . '</a></li>';
            if ($r['open']=='t') {
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
