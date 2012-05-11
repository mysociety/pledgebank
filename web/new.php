<?
// new.php:
// New pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: new.php,v 1.212 2009-01-05 16:34:00 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pbperson.php';
require_once '../phplib/pledge.php';
require_once '../phplib/alert.php';
require_once '../phplib/gaze-controls.php';
require_once '../phplib/pbfacebook.php';
require_once '../commonlib/phplib/utility.php';
require_once '../commonlib/phplib/mapit.php';      # To test validity of postcodes
require_once "../commonlib/phplib/votingarea.php";
require_once '../commonlib/phplib/countries.php';
require_once '../commonlib/phplib/gaze.php';

/* currently: Barnet microsite redirects rather than letting this page be displayed */
if ($redirect_url = microsites_denied_access_redirect_url()) {
    header("Location: $redirect_url");
    exit;
}

# Whether the pledge location step is shown
function has_step_2() {
    return microsites_location_allowed();
}

# Whether the category/privacy step is shown
function has_step_3() {
    return (microsites_categories_page3() || microsites_private_allowed());
}
# Whether the creator's extra address step is shown
function has_step_addr() {
    return microsites_postal_address_allowed();
}

$number_of_steps = 2;
if (has_step_2()) $number_of_steps++;
if (has_step_3()) $number_of_steps++;
if (has_step_addr()) $number_of_steps++;

$page_title = _('Create a New Pledge');
$page_params = array('id'=>'new');
ob_start();
if (get_http_var('tostep1') || get_http_var('tostep2') || get_http_var('tostep3') || get_http_var('tostepaddr') || get_http_var('topreview') || get_http_var('tocreate') || get_http_var('donetargetwarning')) {
    pledge_form_submitted();
} else {
    pledge_form_one();
}
$contents = ob_get_contents();
ob_end_clean();
page_header($page_title, $page_params);
print $contents;
page_footer(array('nolocalsignup'=>true));

function check_facebook_params($data) {
    if ($data && array_key_exists('facebook_id', $data) && array_key_exists('facebook_name', $data) && array_key_exists('facebook_id_sig', $data)) {
        $facebook_id = $data['facebook_id'];
        $facebook_name = $data['facebook_name'];
        $facebook_id_sig = $data['facebook_id_sig'];
    } else {
        $facebook_id = intval(get_http_var('facebook_id'));
        $facebook_name = get_http_var('facebook_name');
        $facebook_id_sig = get_http_var("facebook_id_sig");
    }
    if ($facebook_id) { 
        $verified = auth_verify_with_shared_secret($facebook_id.":".$facebook_name, OPTION_CSRF_SECRET, $facebook_id_sig);
        if ($verified) {
            return array($facebook_id, $facebook_id_sig, $facebook_name);
        }
    }
    return null;
}

function pledge_form_one($data = array(), $errors = array()) {
    global $lang, $langs;

    microsites_new_breadcrumbs(1);

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', array_values($errors));
        print '</li></ul></div>';
    }
    microsites_new_pledges_toptips();

    if (get_http_var('local') && $ref = get_http_var('ref')) {
        $p = new Pledge($ref);
        $data['title'] = $p->title();
        $data['target'] = 5;
        $data['type'] = _('people in my street');
    }

    if (get_http_var('residents')) {
        # Remember to change the error handling code down below if you change <MY STREET>
        $data['title'] = "hold a meeting to set up a Residents' Association and provide tea and cake";
        $data['target'] = 5;
        $data['type'] = 'other people in <MY AREA>';
        $data['signup'] = 'come along';
        $data['identity'] = 'resident of <MY AREA>';
    }

    if (get_http_var('football')) {
        # Remember to change the error handling code down below if you change <MY STREET>
        $data['title'] = "arrange a weekly kickabout at <MY LOCAL PARK>";
        $data['target'] = 9;
        $data['type'] = 'other footie fans (or more)';
        $data['signup'] = 'come along when they can';
        $data['identity'] = 'resident of <MY AREA>';
    }

    if (get_http_var('streetparty')) {
        # Remember to change the error handling code down below if you change <MY STREET>
        $data['title'] = "organise a street party for <MY STREET>";
        $data['target'] = 3;
        $data['type'] = 'other people on <MY STREET>';
        $data['signup'] = 'help organise it';
        $data['identity'] = 'resident of <MY STREET>';
    }

    if (get_http_var('picnic')) {
        $data['title'] = "organise a picnic this summer";
        $data['target'] = 5;
        $data['type'] = 'friends';
        $data['signup'] = 'pledge to come along and bring food or drink';
        $data['identity'] = 'picnic lover';
    }

    # new_pledge_type is for initial creation only: can boldy load data without checking if it's set already
    if (get_http_var('new_pledge_type')) {
        $data['pledge_type'] = microsites_valid_custom_pledge_type(get_http_var('new_pledge_type'));
        if (!$data['pledge_type']) {
            $data['pledge_type'] = get_http_var('new_pledge_type'); # set back to invalid value to precipitate error
        } else {
            $preloaded_data = microsites_get_pledge_type_details($data['pledge_type'], 'preloaded_data');
            if ($preloaded_data) {
                $ref_in_pledge_type = get_http_var('ref', "");
                $data['ref_in_pledge_type'] = $ref_in_pledge_type;
                if ($ref_in_pledge_type == "") $ref_in_pledge_type = '???'; # for placeholders expecting it
                foreach ($preloaded_data as $key => $value) {
                    $data[$key] = preg_replace("/%s/", $ref_in_pledge_type, $value); # i18n here? e.g., _($value)
                }            
            }
        }
    } else {
        if (!array_key_exists('pledge_type', $data)) $data['pledge_type'] = '';
    }
    
    // Can introduce initial tags via URL parameters
    if (get_http_var('tags')) {
        $data['tags'] = get_http_var('tags');
    }

    global $pb_time;
    $P = pb_person_if_signed_on();
    if (!is_null($P)) {
        if (!array_key_exists('email', $data))
            $data['email'] = $P->email();
        if (!array_key_exists('name', $data))
            $data['name'] = $P->name_or_blank();
    }
    
    global $number_of_steps;
?>

<form accept-charset="utf-8" name="pledge" method="post" action="/new">

<h2><?=_('New Pledge &ndash; Basics')?></h2>
<?  if (microsites_show_translate_blurb() && !count($_POST)) { ?>
<p><small><?=sprintf(_('(<a href="%s">Want your pledge in a language other than %s?</a>)'),
    "/lang?r=/new", $langs[$lang]) ?></small></p>
<? } ?>

<? if ($data['pledge_type']) {
     $canonical_pledge_type = microsites_valid_custom_pledge_type($data['pledge_type']);
     if (microsites_get_pledge_type_details($canonical_pledge_type, 'is_valid')) { ?>
       <h2>
          <?= sprintf(_('This is a custom "%s" pledge.'), htmlspecialchars($data['pledge_type'])); ?>
       </h2>
       <div class="ms-pledge-list-icon" style="background-image:url(<?= microsites_get_pledge_type_details( $canonical_pledge_type, 'default_image_url')?>);margin:1em 0 1em 0;width:400px;"></div>
       <input type="hidden" name="pledge_type" value="<?= $canonical_pledge_type ?>" />
       <p>
         <?= microsites_get_pledge_type_details( $canonical_pledge_type, 'ref_label') .":" ?>
         <input type="text" id="ref_in_pledge_type" name="ref_in_pledge_type" <? if (array_key_exists('ref_in_pledge_type', $errors)) print 'class="error"' ?> value="<? if (isset($data['ref_in_pledge_type'])) print htmlspecialchars($data['ref_in_pledge_type']) ?>"> 
         <small>
             <?=sprintf(_('(This is the reference for this pledge amongst other <b>%s</b> pledges '
              . '&mdash; for example, a street name for a street party pledge) '), htmlspecialchars($data['pledge_type'])) ?>
         </small>
       </p>
  <?   } else { ?>
      <p class="error">
        <?= sprintf(_('Invalid pledge type for this PledgeBank: "%s"'), htmlspecialchars($data['pledge_type'])) ?>
      </p> 
  <? } 
} ?>

<p><strong><?=_('I will') ?></strong> 
	<input<? if (array_key_exists('title', $errors)) print ' class="error"' ?> title="<?=_('Pledge') ?>" type="text" name="title" id="title" value="<? if (isset($data['title'])) print htmlspecialchars($data['title']) ?>" size="40"></p>

<p><strong><?=_('but only if') ?></strong> <input<? if (array_key_exists('target', $errors)) print ' class="error"' ?> onchange="pluralize(this.value)" title="<?=_('Target number of people') ?>" size="2" type="text" id="target" name="target" value="<?=(isset($data['target'])?htmlspecialchars($data['target']):'10') ?>">
<input<? if (array_key_exists('type', $errors)) print ' class="error"' ?> type="text" id="type" name="type" size="24" value="<?=(isset($data['type'])?htmlspecialchars($data['type']):microsites_other_people()) ?>"></p>

<p><? if ($lang=='de' || $lang=='nl') { ?>
<input type="text" id="signup" name="signup"
size="48" value="<?=(isset($data['signup'])?htmlspecialchars($data['signup']):_('do the same')) ?>"> <strong><?=_('will') ?></strong>.
<? } else { ?>
<strong><?=_('will') ?></strong> <input type="text" id="signup" name="signup"
size="40" value="<?=(isset($data['signup'])?htmlspecialchars($data['signup']):_('do the same')) ?>">.
<? }
?></p>

<p><?=_('The other people must sign up before') ?>:
<br><input<?
    if (array_key_exists('date', $errors)) print ' class="error"';
?> title="<?=_('Deadline date') ?>" type="text" id="date" name="date" value="<?
    if (isset($data['date'])) print htmlspecialchars($data['date']) ?>">
<small>(<?=_('e.g.') ?> <? microsites_example_date(); ?>)</small></p>

<p><?=_('Choose a short name for your pledge (6 to 16 letters):') ?>
<input<? if (array_key_exists('ref', $errors) || array_key_exists('ref2', $errors)) print ' class="error"' ?> onkeyup="checklength(this)" type="text" size="16" maxlength="16" id="ref" name="ref" value="<? if (isset($data['ref'])) print htmlspecialchars($data['ref']) ?>"> 
<small>(<?=sprintf(_('This gives your pledge an easy web address. e.g. %stidyupthepark'), pb_domain_url()) ?>)</small>
</p>

<p id="moreinfo"><?=_('More details about your pledge:') . ' <small>' . _('(optional)') . '</small>' ?>
<br><?=microsites_new_pledges_detail_textarea($data) ?>
<br><small><?=_('(links and email addresses will be automatically made clickable, no "markup" needed)') ?></small>

<h3><?=_('About You') ?></h3>
<p style="margin-bottom: 1em;">
<? 
    list ($facebook_id, $facebook_id_sig, $facebook_name) = check_facebook_params($data);
    if ($facebook_id) { 
?>
<strong><?=_('Your Facebook account:')?></strong> <a href="http://www.facebook.com/profile.php?id=<?=$facebook_id?>"><?=htmlspecialchars($facebook_name)?></a> 
<input type="hidden" name="facebook_id" value="<?=htmlspecialchars($facebook_id)?>">
<input type="hidden" name="facebook_id_sig" value="<?=htmlspecialchars($facebook_id_sig)?>">
<input type="hidden" name="facebook_name" value="<?=htmlspecialchars($facebook_name)?>">
<input type="hidden" name="name" value="<?=htmlspecialchars($facebook_name)?>">
<?   } else { ?>
<label for="name"><?=_('Your name:') ?></label> <input<? if (array_key_exists('name', $errors)) print ' class="error"' ?> type="text" size="30" name="name" id="name" value="<? if (isset($data['name'])) print htmlspecialchars($data['name']) ?>">
<?   } ?>
<br><label for="email"><?=_('Your email:') ?></label> <input<? if (array_key_exists('email', $errors)) print ' class="error"' ?> type="text" size="30" name="email" id="email" value="<? if (isset($data['email'])) print htmlspecialchars($data['email']) ?>">
<br><small><?=_('(we need your email so we can get in touch with you when your pledge completes, and so on)') ?></small>

<p><?=_('On flyers and elsewhere, after your name, how would you like to be described?') . ' <small>' . _('(optional)') . '</small>' ?>
<br><input<? if (array_key_exists('identity', $errors)) print ' class="error"' ?> type="text" name="identity" value="<? if (isset($data['identity'])) print htmlspecialchars($data['identity']) ?>" size="40" maxlength="40">
<small><?=_('(e.g. "resident of Tamilda Road")') ?></small>
</p>

<?  
  if (sizeof($data)) {
        print '<input type="hidden" name="data" value="' . base64_encode(serialize($data)) . '">';
    }
    print p(_("Did you read the tips on the right of the page? They'll help you make a successful pledge."));
?> <p align="right"><input type="submit" id="next_step" name="<?
    if (has_step_2()) print 'tostep2';
    elseif (has_step_3()) print 'tostep3';
    elseif (has_step_addr()) print 'tostepaddr';
    else print 'topreview';
?>" value="<?=_('Next step') ?>"></p>
</form>

<? 
    $custom_pledge_types = microsites_get_custom_pledge_types();
    if ($custom_pledge_types && ! $data['pledge_type']){
        ?>
        <div class="pledge-type-init">
            <h3>Alternatively: create a pledge by type</h3>
            <form action="" method="get">
                <label for="new_pledge_type">Pledge type:</label>
                <select name="new_pledge_type">
                    <option value="no type selected"></option> <!-- forces explanatory error on next page -->
                    <?
                        ksort($custom_pledge_types);
                        foreach ($custom_pledge_types as $type) { ?>
                            <option value="<?= $type ?>"><?= microsites_get_pledge_type_details($type, 'title'); ?></option>
                        <? }
                    ?>
                </select>
                <label for="ref">Ref (e.g., street name, etc)</label>
                <input name="ref" type="text" value="" style="width:250px;"/>
                <br/>
                <input type="submit" value="Create new pledge by type"/>
            </form>
        </div>
        <?
    }
} // end of pledge_form_one

function pledge_form_target_warning($data, $errors) {

    $must_gather = $data['target'] * 0.025; # 2.5%, as in pb_pledge_prominence_calculated in db
    if ($must_gather < 2)
        $must_gather = 2;
    $percent_successful_above_warn = percent_success_above(OPTION_PB_TARGET_WARNING);
    
    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', array_values($errors));
        print '</li></ul></div>';
    }

    print '<div id="preview"><p>' . _('Your pledge looks like this so far:') . '</p>';
    $isodate = $data['parseddate']['iso'];
    $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    $partial_pledge = new Pledge($row);
    $partial_pledge->render_box(array('showdetails' => true));

?>
</div>

<form accept-charset="utf-8" name="pledge" method="post" action="/new">

<?  print h2(_('Please lower your target!'));

    printf(p(_("Recruiting more than %d people to a pledge is much harder than
    most people think. <strong>Only %0.0f%%</strong> of people who have set
    pledge targets at %d or above have succeeded - why risk it? Does
    your pledge really need so many people to be worthwhile?")),
    OPTION_PB_TARGET_WARNING, $percent_successful_above_warn, OPTION_PB_TARGET_WARNING);

    printf(p(_("In order to encourage people to aim at achievable targets, we don't
    automatically show pledges with less than 2.5%% of signers on the
    PledgeBank site. In the case of your pledge target, that would mean
    you would need to sign up %d signers before your pledge would show up
    on our 'All Pledges' page, in our search listings, or before it would
    be automatically mailed to local PledgeBank alert subscribers (if it
    is a local pledge).")), $must_gather);

    print(p(_("We've <strong>reduced your target to 10</strong> for now. Please
    use this box if you want to change back to another target.  There is 
    <a href=\"/faq#targets\">more advice</a> about choosing a target in the
    FAQ.")));

?>
<p><?=_('<strong>My target</strong> is ') ?>
<input<? if (array_key_exists('target', $errors)) print ' class="error"' ?> onchange="pluralize(this.value)" title="<?=_('Target number of people') ?>" size="5" type="text" id="target" name="target" value="10">
<strong><?=$data['type']?></strong></p>

<p><?=_('Remember, a small but successful pledge can be the perfect preparation
for a larger and more ambitious one. You can mail your subscribers, and ask
them to help you with something bigger.') ?></p>

<p>
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<input style="float:right" id="next_step" class="topbutton" type="submit" name="donetargetwarning" value="<?=_('Next step') ?>">
<br><input type="submit" name="tostep1" value="<?=_('Back to step 1') ?>" style="float:left">
</p>

</form>

<?
}


function pledge_form_two($data, $errors = array()) {
        
    $local = (array_key_exists('local', $data)) && $data['local'] == '1';
    $notlocal = (array_key_exists('local', $data)) && $data['local'] == '0';
        
    $isodate = $data['parseddate']['iso'];
    if (array_key_exists('gaze_place', $errors) && $errors['gaze_place'] == 'NOTICE') {
        unset($errors['gaze_place']); # remove NOTICE
    }

    microsites_new_breadcrumbs(2);

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
        echo '<div id="preview">';
    } else {
        echo '<div id="preview">';
?>

<p><?=_('Your pledge looks like this so far:') ?></p>
<?  }
    $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    $partial_pledge = new Pledge($row);
    $partial_pledge->render_box(array('showdetails' => true));

    global $number_of_steps;
?>
</div>

<form accept-charset="utf-8" name="pledge" method="post" action="/new">
<h2><?=_('New Pledge &ndash; Location')?></h2>

<p><?=_('If your pledge applies to a particular country,
or place within a country, providing that information
will help people who live nearby find your pledge, through
our email alerts and location search.') ?></p>

<p><?=_('Which country does your pledge apply to?') ?>
<? gaze_controls_print_country_choice($data['country'], $data['state'], $errors); ?>
</p>

<p id="local_line"><?=_('Within that country, is your pledge specific to a local area or specific place?') ?>
<br><input <? if (array_key_exists('local', $errors)) print ' class="error"' ?> onclick="update_place_local(this, true)" type="radio" id="local1" name="local" value="1"<?=($local?' checked':'') ?>> <label onclick="this.form.elements['local1'].click()" for="local1"><?=_('Yes') ?></label>
<input <? if (array_key_exists('local', $errors)) print ' class="error"' ?> onclick="update_place_local(this, true)" type="radio" id="local0" name="local" value="0"<?=($notlocal?' checked':'') ?>> <label onclick="this.form.elements['local0'].click()" for="local0"><?=_('No') ?></label>
</p>

<p id="ifyes_line"><?
print _('If yes, choose where.');

$gaze_with_state = $data['gaze_place'];
if ($data['state']) {
    global $countries_statecode_to_name;
    $gaze_with_state .= ', ';
    if (isset($countries_statecode_to_name[$data['country']][$data['state']]))
        $gaze_with_state .= $countries_statecode_to_name[$data['country']][$data['state']];
    else
        $gaze_with_state .= $data['state'];
}
gaze_controls_print_place_choice($data['place'], $gaze_with_state, $data['places'], $errors, array_key_exists('postcode', $data) ? $data['postcode'] : null, array('midformnote'=>true)); 
?>

<p>
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<input style="float:right" id="next_step" class="topbutton" type="submit" name="<?=has_step_3() ? "tostep3" : (has_step_addr() ? "tostepaddr" : "topreview") ?>" value="<?=_('Next step') ?>">
<input type="submit" name="tostep1" value="<?=_('Back to step 1') ?>" style="float:left">
</p>

</form>
<?
}

function pledge_form_three($data, $errors = array()) {
    $v = 'all';
    if (isset($data['visibility'])) {
        $v = $data['visibility']; if ($v!='pin') $v = 'all';
    }
    $isodate = $data['parseddate']['iso'];

    microsites_new_breadcrumbs(3);

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
        print '<div id="preview">';
    } else {
?>

<div id="preview">
<p><?=_('Your pledge looks like this so far:') ?></p>
<?  }
    $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    $partial_pledge = new Pledge($row);
    $partial_pledge->render_box(array('showdetails' => true));

    global $number_of_steps;
?>
</div>

<form accept-charset="utf-8" name="pledge" method="post" action="/new">
<h2><?=_('New Pledge &ndash; Category / Privacy')?></h2>

<?  if (microsites_categories_allowed()) { ?>
<p><?=_('Which category does your pledge best fit into?') ?>
<?      display_categories($data); ?>
<br><small><?=_('(this will be used in future to help more people find your pledge)') ?></small>
</p>
<?  } ?>

<?  if (microsites_private_allowed()) { ?>
<p><?=_('Who do you want to be able to see your pledge?') ?>
<br><input onclick="grey_pin(true)" type="radio" name="visibility" value="all"<?=($v=='all'?' checked':'') ?>> <?=_('Anyone') ?>
<input onclick="grey_pin(false)" type="radio" name="visibility" value="pin"<?=($v=='pin'?' checked':'') ?>> <?=_('Only people to whom I give this PIN:') ?>
<input <? if (array_key_exists('pin', $errors)) print ' class="error"' ?> type="text" id="pin" name="pin" value="">
</p>
<? } else { ?>
<input type="hidden" name="visibility" value="all">
<? } ?>

<p>
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<input style="float:right" id="next_step" class="topbutton" type="submit" name="topreview" value="<?=_('Preview') ?>">
<input style="float:left" type="submit" name="tostep2" value="<?=_('Back to step 2') ?>">
</p>

</form>

<?
}


# Live Simply only for now, will need updating if using for something else
function pledge_form_addr($data = array(), $errors = array()) {
    global $lang, $langs, $number_of_steps;

    $curr_step = has_step_2() ? (has_step_3() ? 4 : 3) : 2;
    microsites_new_breadcrumbs($curr_step);

    $isodate = $data['parseddate']['iso'];
    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', array_values($errors));
        print '</li></ul></div>';
        print '<div id="preview">';
    } else {
?>

<div id="preview">
<p><?=_('Your pledge looks like this so far:') ?></p>
<?  }
    $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    $partial_pledge = new Pledge($row);
    $partial_pledge->render_box(array('showdetails' => true));

?>
</div>

<form accept-charset="utf-8" name="pledge" method="post" action="/new">
<h2><?=sprintf(_('New Pledge &#8211; Step %s of %s'),
    $curr_step, $number_of_steps)?></h2>

<?
    microsites_new_pledges_stepaddr($data, $errors);
    if (sizeof($data)) {
        print '<input type="hidden" name="data" value="' . base64_encode(serialize($data)) . '">';
    } ?>
<p>
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<input style="float:right" id="next_step" class="topbutton" type="submit" name="topreview" value="<?=_('Preview') ?>">
<br><input style="float:left" type="submit" name="<?=(has_step_3() ? "tostep3" : (has_step_2() ? "tostep2" : 'tostep1')) ?>" value="<?=sprintf(_('Back to step %s'), (has_step_3() ? 3 : (has_step_2() ? 2 : 1))) ?>">
</p>
</form>
<? 
}

function pledge_form_submitted() {
    global $lang, $microsite;
    $errors = array();
    $data = array();
    foreach (array_keys($_POST) as $field) {
        if ($field == 'ref' || $field == 'data' || $field == 'email')
            $data[$field] = get_http_var($field);
        else
            $data[$field] = get_http_var($field, true);
    }
    if (array_key_exists('title', $data)) {
        $data['lang'] = $lang;
    }
    
    if (array_key_exists('data', $data)) {
        $alldata = unserialize(base64_decode($data['data']));
        if (!$alldata) err(_('Transferring the data from previous page failed :('));        
        unset($data['data']);
        $data = array_merge($alldata, $data);
    }

    # Step 1 fixes
    foreach (array('title', 'target', 'type', 'signup', 'date', 'ref', 'detail', 'name', 'email', 'identity', 'pledge_type', 'ref_in_pledge_type') as $v) {
        if (!isset($data[$v])) $data[$v] = '';
    }
    $data['microsite'] = $microsite;
    if ($data['title']==_('<Enter your pledge>')) $data['title'] = '';
    if ($data['name']==_('<Enter your name>')) $data['name'] = '';
    if (!$data['type']) $data['type'] = microsites_other_people();
    $data['parseddate'] = parse_date($data['date']);
    if (!$data['signup']) $data['signup'] = 'sign up';
    $data['signup'] = preg_replace('#\.$#', '', $data['signup']);
    $locale_info = localeconv();
    $data['target'] = str_replace($locale_info['thousands_sep'], '', $data['target']);
    $data['title'] = preg_replace('#^' . _('I will') . ' #i', '', $data['title']);
    $data['title'] = preg_replace('#^' . _('will') . ' #i', '', $data['title']);
    if (!array_key_exists('tags', $data))
        $data['tags'] = "";
    if (microsites_no_target()) { $data['target'] = 0; }
    list ($facebook_id, $facebook_id_sig, $facebook_name) = check_facebook_params($data);
    if (!$facebook_id) {
        $data['facebook_id'] = null;
        $data['facebook_id_sig'] = null;
    }

    # Step 2 fixes
        
    if (array_key_exists('local', $data) && !$data['local']) { 
        $data['gaze_place'] = ''; 
        $data['postcode'] = ''; 
        $data['place'] = ''; 
    }
    if (!array_key_exists('local', $data)){
        $data['local'] = '';
        $data = array_merge($data, microsites_default_location());        
    } 
    $location = gaze_controls_get_location();
    if ($location['country'] || !array_key_exists('country', $data))
        $data = array_merge($data, $location);
    
    # Step 1, main pledge details
    if (get_http_var('tostep1')) {
        pledge_form_one($data, $errors);
        return;
    }
    $errors = step1_error_check($data);
    if (sizeof($errors)) {
        pledge_form_one($data, $errors);
        return;
    }

    # Target warning
    if (get_http_var('donetargetwarning') || OPTION_PB_TARGET_WARNING == 0) {
        $data['skiptargetwarning'] = 1;
    }
    if (!array_key_exists('skiptargetwarning', $data)) {
        if ($data['target'] > OPTION_PB_TARGET_WARNING) {
            pledge_form_target_warning($data, $errors);
            return;
        }
        $errors = target_warning_error_check($data);
        if (sizeof($errors)) {
            pledge_form_target_warning($data, $errors);
            return;
        }
    }
    
    # Step 2, location
    
    if (get_http_var('tostep2') || get_http_var('donetargetwarning')) {
        pledge_form_two($data, $errors);
        return;
    }
    if (!has_step_2()) {
        $data['country'] = 'Global';
    }
    $errors = step2_error_check($data);
    if (sizeof($errors)) {
        pledge_form_two($data, $errors);
        return;
    }
    if ($data['local'] == 1 && $data['country'] != 'Global' && 
            array_key_exists('gaze_place', $errors) && $errors['gaze_place'] == 'NOTICE') {
        $data['gaze_place'] = ''; 
        pledge_form_two($data, $errors);
        return;
    }

    # Step 3, category, privacy
    if (get_http_var('tostep3')) {
        pledge_form_three($data, $errors);
        return;
    }
    if (!array_key_exists('visibility', $data) || $data['visibility'] != 'pin') { 
        $data['visibility'] = 'all'; 
        $data['pin'] = ''; 
    }
    if (!array_key_exists('category', $data)) { 
        $data['category'] = -1;
    }
    $errors = step3_error_check($data);
    if (sizeof($errors)) {
        pledge_form_three($data, $errors);
        return;
    }

    # Step postal address
    if (get_http_var('tostepaddr')) {
        pledge_form_addr($data, $errors);
        return;
    }
    $errors = stepaddr_error_check($data);
    if (sizeof($errors)) {
        pledge_form_addr($data, $errors);
        return;
    }

    # Step 4, preview
    if (get_http_var('topreview')) {
        preview_pledge($data, $errors);
        return;
    }
    $errors = preview_error_check($data);
    if (sizeof($errors)) {
        preview_pledge($data, $errors);
        return;
    }
 
    /* User must have an account to do this. */
    $data['reason_web'] = _('Before creating your new pledge, we need to check that your email is working.');
    $data['template'] = 'pledge-confirm';
    $P = pb_person_signon_without_redirect($data, $data['email'], $data['name'], get_http_var('password'));

    if ($data['facebook_id']) {
        $existing_facebook_person = db_getOne("select id from person where facebook_id = ?", $data['facebook_id']);
        $session_key = null;
        if ($existing_facebook_person && $existing_facebook_person != $P->id()) {
            /* Merge the user accounts, if say they already signed a pledge from in Facebook */
            $session_key = db_getOne("select session_key from facebook where facebook_id = ?", $data['facebook_id']);
            db_query("delete from facebook where facebook_id = ?", $data['facebook_id']);
            db_query("update signers set person_id = ? where person_id = ?", $P->id, $existing_facebook_person);
            db_query("delete from person where id = ?", $existing_facebook_person);
        }
        db_query('update person set facebook_id = ? where id = ?', array($data['facebook_id'], $P->id()));
        if ($session_key)
            db_query("insert into facebook (facebook_id, session_key) values (?, ?)", $data['facebook_id'], $session_key);
    }

    stepaddr_fillin_address($P, $data);
    create_new_pledge($P, $data);
}

function step1_error_check($data) {
    global $pb_today;

    $errors = array();

    if (microsites_no_target()) { /* Allow no target */ }
    elseif (!$data['target']) $errors['target'] = _('Please enter a target');
    elseif (!ctype_digit($data['target']) || $data['target'] < 1) $errors['target'] = _('The target must be a positive number');

    $disallowed_refs = array('contact', 'translate', 'posters', 'graphs', 'sportsclubpatrons', 'microsites', 'offline', 'explain', 'facebook', 'success');
    if (!$data['ref']) $errors['ref'] = _('Please enter a short name for your pledge');
    elseif (strlen($data['ref'])<6) $errors['ref'] = _('The short name must be at least six characters long');
    elseif (strlen($data['ref'])>16) $errors['ref'] = _('The short name can be at most 20 characters long');
    elseif (in_array(strtolower($data['ref']), $disallowed_refs)) $errors['ref'] = _('That short name is not allowed.');
    elseif (preg_match('/[^a-z0-9-]/i',$data['ref'])) $errors['ref2'] = _('The short name must only contain letters, numbers, or a hyphen.  Spaces are not allowed.');
    elseif (!preg_match('/[a-z]/i',$data['ref'])) $errors['ref2'] = _('The short name must contain at least one letter.');

    list ($dupe, $existing_creator_email) = db_getRow('SELECT pledges.id, person.email FROM pledges 
        LEFT JOIN person on person.id = pledges.person_id
        WHERE ref ILIKE ?', array($data['ref']));
    if ($dupe) {
        $P = pb_person_if_signed_on();
        // Note that for privacy reasons, we check against the logged in email
        // (rather than the email that they have entered in the form, which
        // could be anyone)
        if ($P && $P->email() == $existing_creator_email)  {
            // If somebody clicks the pledge confirmation URL a second time, they end up here
            $errors['ref'] = sprintf(_("You've already made a pledge with short name '%s'. Either go to your <a href=\"/%s\">existing pledge</a>, or edit the short name below to make a new pledge."), 
                htmlspecialchars($data['ref']), htmlspecialchars($data['ref']));
        } else 
            $errors['ref'] = _('That short name is already taken!');
    }
    if (!$data['title']) $errors['title'] = _('Please enter a pledge');

    if ($data['pledge_type']) {
      $canonical_pledge_type = microsites_valid_custom_pledge_type($data['pledge_type']);
      if (is_null($canonical_pledge_type)) {
        $errors['pledge_type'] = _('That is not a valid custom pledge type for this PledgeBank');
      } else {
        if (! $data['ref_in_pledge_type']) {
          $errors['ref_in_pledge_type'] = _('Please provide a reference to be used to identify this pledge within its type.');
        }
      }  
    }
    
    $pb_today_arr = explode('-', $pb_today);
    $deadline_limit_years = 5; # in years
    $deadline_limit = date('Y-m-d', mktime(12, 0, 0, $pb_today_arr[1], $pb_today_arr[2], $pb_today_arr[0] + $deadline_limit_years));
    if (!$data['date'] || !$data['parseddate']) $errors['date'] = _('Please enter a deadline');
    if ($data['parseddate']['iso'] < $pb_today) $errors['date'] = _('The deadline must be in the future');
    if ($data['parseddate']['error']) $errors['date'] = _('Please enter a valid date');
    if ($deadline_limit < $data['parseddate']['iso'])
        $errors['date'] = sprintf(_('Please change your deadline so it is less than %d years into the
        future. You must change the deadline in order to proceed with creating
        your pledge. If you want a longer deadline, please create your pledge
        with a short deadline, and drop us an email to <a href="mailto:%s">%s</a>
        asking for an alteration.'), $deadline_limit_years, str_replace("@", "&#64;", OPTION_CONTACT_EMAIL),
        str_replace("@", "&#64;", OPTION_CONTACT_EMAIL));

    if (!$data['name']) $errors['name'] = _('Please enter your name');
    if (!$data['email']) $errors['email'] = _('Please enter your email address');
    if (!validate_email($data['email'])) $errors['email'] = _('Please enter a valid email address');

    # These strings deliberately not localised
    $mystreetmessage = htmlspecialchars('Please change <MY STREET> to the name of your street');
    if (stristr($data['title'], "<MY STREET>")) $errors['title'] = $mystreetmessage;
    if (stristr($data['type'], "<MY STREET>")) $errors['type'] = $mystreetmessage;
    if (stristr($data['identity'], "<MY STREET>")) $errors['identity'] = $mystreetmessage;

    global $langs;
    if (!array_key_exists($data['lang'], $langs)) {
        $errors['lang'] = _('Unknown language code:') . ' ' . htmlspecialchars($data['lang']);
    }

    if ($data['facebook_id']) {
        $facebook_email = db_getOne("select email from person where facebook_id = ?", $data['facebook_id']);
        if ($facebook_email && $facebook_email != $data['email']) {
            $errors['email'] = _('You already have Facebook set up on PledgeBank with a different email address. Please use that address, it is ') . htmlspecialchars($facebook_email) . ".";
        }
    }

    $errors = array_merge($errors, microsites_step1_error_check($data));
    return $errors;
}

function target_warning_error_check($data) {
    global $pb_today;

    $errors = array();
    if (!$data['target'] || !ctype_digit($data['target']))
        $errors['target'] = _('Please enter a target');
/*  Higher target warning disabled, as was just annoying
    elseif ($data['target'] > OPTION_PB_TARGET_CAP) {
        $errors['target'] = sprintf(_('We have imposed a cap of
            %d people maximum on each pledge. This is not a
            hard limit, just a way of encouraging people to aim at smaller and more
            achievable targets. If you want a target higher than 
            %d people, we\'d be glad to set it up for you.
            Just drop us a quick email to <a
            href="mailto:team@pledgebank.com">team@pledgebank.com</a> letting us
            know who you are and what you are aiming to do.'), OPTION_PB_TARGET_CAP, OPTION_PB_TARGET_CAP);
    } */

    return $errors;
}
 
function step2_error_check(&$data) {
    global $countries_code_to_name, $countries_statecode_to_name;

    $errors = array();

    if (!array_key_exists('country', $data) || !$data['country'])
        $errors['country'] = _('Please choose which country your pledge applies to');
    elseif ($data['country'] != 'Global') {
        $a = array();
        $country = $data['country'];
        $state = null;
        if (preg_match('/^([A-Z]{2}),(.+)$/', $data['country'], $a))
            list($x, $country, $state) = $a;

        /* Validate country and/or state. */
        if (!array_key_exists($country, $countries_code_to_name))
            # TRANS: Ideally "none" will be translated here, but I can't see it as an entry in this .po file. How is this resolved? (Tim Morley, 2005-11-23)
            $errors['country'] = _('Please choose a country, or "not specific to any location" if your pledge applies everywhere');
        elseif ($state && !array_key_exists($state, $countries_statecode_to_name[$country]))
            $errors['country'] = _('Please choose a valid state within that country, or the country name itself');
        else {
            # Check gaze has this country
            $countries_with_gazetteer = gaze_get_find_places_countries();
            gaze_check_error($countries_with_gazetteer);
            if (!in_array($country, $countries_with_gazetteer)) {
                $data['local'] = 0;
            }

            /* Can only check local stuff if a valid country is selected. */
            if (!array_key_exists('local', $data) || ($data['local'] != '1' && $data['local'] != '0'))
                $errors['local'] = _('Please choose whether the pledge is local or not');
            elseif ($data['local']) {
                gaze_controls_validate_location($data, $errors);
            }
        }
    }
    return $errors;
}

function step3_error_check(&$data) {
    $errors = array();
    if ($data['visibility'] == 'pin' && !$data['pin']) 
        $errors['pin'] = _('Please enter a pin');
    return $errors;
}

function stepaddr_error_check(&$data) {
    $errors = array();
    if (array_key_exists('address_postcode', $data) && $data['address_postcode']
        && array_key_exists('address_country', $data) && $data['address_country'] == 'GB'
        && !validate_postcode($data['address_postcode'])) {
        $errors['address_postcode'] = _('Please enter a valid postcode');
    }

    if (array_key_exists('address_postcode_override', $data)) {
        # This means it's O2 for now!
        if ($data['address_postcode_override'])
            $data['address_postcode'] = $data['address_postcode_override'];
        if (!array_key_exists('address_postcode', $data) || !$data['address_postcode']) {
            $errors['address_postcode'] = 'Please pick a location or enter a postcode';
        } elseif (!validate_postcode($data['address_postcode'])) {
            $errors['address_postcode'] = 'Please enter a valid postcode';
        }
        if (!array_key_exists('address_1', $data) || !$data['address_1']) {
            $errors['address_1'] = 'Please choose a directorate';
        }
    }

    return $errors;
}

function preview_error_check($data) {
    $errors = array();
    // Note that for microsites where there isn't an explicit checkbox,
    // the entry is there but checked by default.
    if (!get_http_var('agreeterms')) {
        $errors['agreeterms'] = "Please confirm you agree to the terms and conditions.";
    }
    return $errors;
}

function preview_pledge($data, $errors) {
    global $number_of_steps;

    $v = 'all';
    if (isset($data['visibility'])) {
        $v = $data['visibility']; if ($v!='pin') $v = 'all';
    }
    $local = (isset($data['local'])) ? $data['local'] : '0';
    $isodate = $data['parseddate']['iso'];

    microsites_new_breadcrumbs($number_of_steps);

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', array_values($errors));
        print '</li></ul></div>';
    } #    $png_flyers1_url = url_new("../flyers/{$ref}_A7_flyers1.png", false);

    print '<div id="preview"><p>';
    printf(_('Your pledge, with short name <em>%s</em>, will look like this:'), $data['ref']);
    print '</p>';

    ?>

<?
    $row = $data; unset($row['parseddate']); $row['date'] = $isodate;
    $partial_pledge = new Pledge($row);
    $partial_pledge->render_box(array('showdetails' => true));
?>

<?
    $otherdetails_li_html = "";

    if (microsites_categories_allowed() && microsites_categories_page3()) {
        $otherdetails_li_html .= "<li>" .  _('Category') . ": <strong>";
        if ($data['category'] == -1)
            $otherdetails_li_html .= _('None');
        else
            $otherdetails_li_html .= htmlspecialchars(_(db_getOne('select name from category where id = ?', $data['category']))); // XXX show enclosing cat?
        $otherdetails_li_html .= "</strong></li>";
    }
    if ($data['tags']) {
        $tag_array = make_web20_tags($data['tags']);
        $otherdetails_li_html .= "<li>Tags: <strong>";
        foreach ($tag_array as $tag) {
           $otherdetails_li_html .= $tag . " "; 
        }
        $otherdetails_li_html .= "</strong></li>";
    }
    if (microsites_private_allowed()) {
        $otherdetails_li_html .= "<li>" . _('Privacy status') . ": <strong>";
        if ($v=='all') $otherdetails_li_html .= _('Public');
        if ($v=='pin') $otherdetails_li_html .= _('Pledge can only be seen by people who I give the PIN to');
        $otherdetails_li_html .= "</strong></li>";
    }
    $otherdetails_li_html .= microsites_new_pledges_preview_extras($data);
    
    if ($otherdetails_li_html) {
?>
    <div id="otherdetails">
        <?=h3(_("Other Details"))?>
        <ul>
            <?= $otherdetails_li_html ?>
        </ul>
    </div>
<? } ?>              

</div>

<form accept-charset="utf-8" method="post" action="/new">
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<h2><?=_('Preview')?></h2>
<?  print p(sprintf(_('
Now please read your pledge (on the right) and check the details thoroughly.
<strong>Read carefully</strong> - we can\'t ethically let you %schange the wording%s of your pledge once people have
started to sign up to it.    
'), '<a href="/faq#editpledge" id="changethewording" onclick="return toggleNewModifyFAQ()">', '</a>')
);
?>

<div id="modifyfaq">
<?=h3(_("Why can't I modify my pledge after I've made it?"))?>

<?=p(_("People who sign up to a pledge are signing up to the specific wording of
the pledge. If you change the wording, then their signatures would no
longer be valid."))?>

</div>

<p>
<input class="topbutton" type="submit" name="tostep1" value="<?=_('Change pledge text') ?>">
<? if (has_step_2()) { ?>
 <input class="topbutton" type="submit" name="tostep2" value="<?=_('Change location') ?>">
<? }
   if (has_step_3()) { ?>
 <input class="topbutton" type="submit" name="tostep3" value="<?=_('Change category/privacy') ?>">
<? }
   if (has_step_addr()) { ?>
 <input type="submit" name="tostepaddr" value="<?=_('Change your postal address') ?>">
<? } ?>
</p>
</form>

<form accept-charset="utf-8" name="pledge" method="post" action="/new">
<input type="hidden" name="data" value="<?=base64_encode(serialize($data)) ?>">
<?
    microsites_new_pledges_terms_and_conditions($data, $v, $local, $errors);
?>
</p>

</form>

<?
    
}

# Put extra address step information into person record
# (this originally for Live Simply Promise)
function stepaddr_fillin_address($P, $data) {
    foreach (array('1','2','3','town','county','postcode','country') as $item) {
        if (!array_key_exists("address_$item", $data)) {
            $data["address_$item"] = null;
        }
    }
    db_query("update person set 
            address_1 = ?,
            address_2 = ?,
            address_3 = ?,
            address_town = ?,
            address_county = ?,
            address_postcode = ?,
            address_country = ?
            where id = ?",
        array(
            $data['address_1'],
            $data['address_2'],
            $data['address_3'],
            $data['address_town'],
            $data['address_county'],
            $data['address_postcode'],
            $data['address_country'],
            $P->id()
        ));
}

# Someone has submitted a new pledge
function create_new_pledge($P, $data) {
    global $site_country;

    $isodate = $data['parseddate']['iso'];
    if ($data['visibility'] == 'all')
        $data['pin'] = null;

    /* Guard against double-insertion. */
    db_query('lock table pledges in share mode');
        /* Can't just use SELECT ... FOR UPDATE since that wouldn't prevent an
         * insert on the table. */
    if (is_null(db_getOne('select id from pledges where ref = ?', $data['ref']))) {
        $data['id'] = db_getOne("select nextval('pledges_id_seq')");

        /* Optionally add a pledge location. */
        $location_id = null;
        if ($data['postcode']) {
            $location = mapit_get_location($data['postcode'], 1);
            if (mapit_get_error($location))
                /* This error should never happen, as earlier postcode validation in form will stop it */
                err('Invalid postcode while creating pledge; please check and try again.');
            $location_id = db_getOne("select nextval('location_id_seq')");
            locale_push('en-gb');
            db_query("insert into location (id, country, method, input, latitude, longitude, description) values (?, 'GB', 'MaPit', ?, ?, ?, ?)", array($location_id, $data['postcode'], $location['wgs84_lat'], $location['wgs84_lon'], $data['postcode']));
            locale_pop();
        } elseif ($data['gaze_place']) {
            list($lat, $lon, $desc) = explode('|', $data['gaze_place'], 3);
            $location_id = db_getOne("select nextval('location_id_seq')");
            $a = array();
            $country = $data['country'];
            $state = null;
            if (preg_match('/^([A-Z]{2}),(.+)$/', $country, $a))
                list($x, $country, $state) = $a;
            db_query("insert into location (id, country, state, method, input, latitude, longitude, description) values (?, ?, ?, 'Gaze', ?, ?, ?, ?)", array($location_id, $country, $state, $data['place'], $lat, $lon, $desc));
        } elseif ($data['country'] <> 'Global') {
            $location_id = db_getOne("select nextval('location_id_seq')");
            $a = array();
            $country = $data['country'];
            $state = null;
            if (preg_match('/^([A-Z]{2}),(.+)$/', $country, $a))
                list($x, $country, $state) = $a;
            db_query("insert into location (id, country, state) values (?, ?, ?)", array($location_id, $country, $state));
        }
        $prominence = microsites_new_pledges_prominence();
        if ($data['lang']=='eo') {
            $prominence = 'frontpage';
        }
        if ($prominence == 'calculated')
            $cached_prominence = 'backpage';
        else
            $cached_prominence = $prominence;

        global $microsites_no_pledge_field;
        if (in_array($data['microsite'], $microsites_no_pledge_field)) {
            $data['microsite'] = null;
        }
        
        $pledge_type = microsites_valid_custom_pledge_type($data['pledge_type']);
        if (microsites_get_pledge_type_details($pledge_type, 'is_valid')) {
            $picture = microsites_get_pledge_type_details($pledge_type, 'default_image_url');
            if ($picture == "") $picture = null; 
        } else {
            $picture = null;
        }
        
        db_query('
                insert into pledges (
                    id, title, target,
                    type, signup, date, datetext,
                    person_id, name, ref, 
                    creationtime,
                    detail,
                    lang, location_id, microsite,
                    pin, identity, 
                    prominence, cached_prominence,
                    via_facebook,
                    pledge_type, ref_in_pledge_type,
                    picture
                ) values (
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, 
                    ms_current_timestamp(),
                    ?,
                    ?, ?, ?,
                    ?, ?,
                    ?, ?,
                    ?,
                    ?, ?,
                    ?
                )', array(
                    $data['id'], $data['title'], $data['target'],
                    $data['type'], $data['signup'], $isodate, $data['date'],
                    $P->id(), $data['name'], $data['ref'], 
                    $data['detail'],
                    $data['lang'], $location_id, $data['microsite'],
                    $data['pin'] ? sha1($data['pin']) : null, $data['identity'],
                    $prominence, $cached_prominence,
                    $data['facebook_id'] ? 't' : 'f',
                    $pledge_type, $data['ref_in_pledge_type'],
                    $picture
                ));

        if ($data['category'] != -1) {
            db_query('
                insert into pledge_category (pledge_id, category_id)
                values (?, ?)',
                array($data['id'], $data['category']));
        }

        if ($data['tags']) {
           $tag_array = make_web20_tags($data['tags']);
           foreach ($tag_array as $tag) {
                db_query('insert into pledge_tag (pledge_id, tag) values (?, ?)', $data['id'], $tag);
           }
        }

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
    unset($page_params['id']);

    if ($data['facebook_id']) {
        pbfacebook_init_cron(OPTION_FACEBOOK_ROBOT_ID);
        if (!pbfacebook_update_profile_box($data['facebook_id'])) {
            # profile not updated, not much can do about it really
        }
    }

    $url = htmlspecialchars(pb_domain_url() . urlencode($p->data['ref']));
    $facebook_url = htmlspecialchars($p->url_facebook());
?>
    <p class="loudmessage">
        <?=_('Thank you for creating your pledge.') ?>
        <?= microsites_pledge_created_message($p) ?>
    </p>
<? if ($data['facebook_id']) { ?>
    <p class="loudmessage"><? printf(_('It is now live on Facebook at %s<br>and your friends can sign up to it there.'), '<a href="'.$facebook_url.'">'.$facebook_url.'</a>') ?></p>
    <p class="loudmessage"><? printf(_('Or sign up by email at %s'), '<a href="'.$url.'">'.$url.'</a>') ?></p>
<? } else { ?>
    <p class="loudmessage"><? printf(_('It is now live at %s<br>and people can sign up to it there.'), '<a href="'.$url.'">'.$url.'</a>') ?></p>
<? } ?>
<?  if (microsites_new_pledges_prominence() != 'backpage') { ?>
    <p class="noisymessage"><? printf(_('Your pledge needs <strong>your support</strong> if it is to succeed, so <br>print some %s now and hand them out today.<br>Put a %s up in the canteen.<br>%s straightaway!'),
        '<a href="/flyers/'.$data['ref'].'_A4_flyers8.pdf">'._('flyers').'</a>',
        '<a href="/flyers/'.$data['ref'].'_A4_flyers1.pdf">'._('poster').'</a>',
        '<a href="'.$url.'/share">'._('Spread the word online').'</a>') ?></p>
<?  } else { ?>
    <p class="noisymessage"><? printf(_('Your pledge will <strong>not</strong> appear on the All Pledges page until <strong>you</strong> have recruited the first few signers.<br>Print some %s now and hand them out today.<br>Put a %s up in the canteen.<br>%s straightaway!'),
        '<a href="/flyers/'.$data['ref'].'_A4_flyers8.pdf">'._('flyers').'</a>',
        '<a href="/flyers/'.$data['ref'].'_A4_flyers1.pdf">'._('poster').'</a>',
        '<a href="'.$url.'/share">'._('Spread the word online').'</a>') ?></p>
<?  }

    if ($site_country == 'US') {
?><p class="noisymessage" style="margin-bottom:0">
If your pledge is about raising money and you want people to be able to donate straight away, why not use
<a href="http://www.changingthepresent.org/PledgeBank">ChangingThePresent</a> if you're giving to a registered non-profit
or <a href="http://www.chipin.com/">ChipIn</a> if you're raising money for something else?</p>
<p align="center">(If you do that, <a href="/contact">email us</a> and we'll add a link to your pledge)</p>
<?
    } else {
        post_confirm_advertise();
    }

    microsites_google_conversion_tracking("default");
}

function display_categories($data) { ?>
<select name="category">
<option value="-1"><?=_('(choose one)') ?></option>
<?
    $s = db_query('select id, parent_category_id, name from category
        where parent_category_id is null
        order by id');
    $out = array();
    while ($a = db_fetch_row($s)) {
        list($id, $parent_id, $name) = $a;
        $out[_($name)] = sprintf("<option value=\"%s\"%s>%s%s</option>",
                    $id,
                    (array_key_exists('category', $data) && $id == $data['category'] ? ' selected' : ''),
                    (is_null($parent_id) ? '' : '&nbsp;-&nbsp;'),
                    htmlspecialchars(_($name)));
    }
    uksort($out, 'strcoll');
    foreach ($out as $n => $s) {
        print $s;
    } ?>
</select>
<?
}
?>
