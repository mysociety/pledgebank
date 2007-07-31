<?
// ref-sign.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-sign.php,v 1.58 2007-07-31 16:36:56 matthew Exp $

require_once '../phplib/pb.php';
require_once '../phplib/pledge.php';
require_once '../phplib/pbperson.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';

page_check_ref(get_http_var('ref'));
$p = new Pledge(get_http_var('ref'));
microsites_redirect($p);

$title = _('Signature addition');
page_header($title, array('ref'=>$p->ref(),'pref'=>$p->url_typein()));
$location = array();
if ($p->byarea())
    $location = gaze_controls_get_location(array('townonly'=>true));
$errors = do_sign($location);

if (is_array($errors) && !array_key_exists('location_choice', $errors)) {
    $disambiguate_form = false;
    if ($errors == array('gaze_place' => 'NOTICE')) {
        $disambiguate_form = true;
        unset($errors['gaze_place']); # remove NOTICE
    }

    if ($errors) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    }
    $p->render_box(array('showdetails'=>false));

    if ($disambiguate_form) {
        // Display location disambiguate form
        sign_location_choose($p, $errors, $location);
    } else {
        // Display email/name form
        $p->sign_box($errors);
    }
}

$params = array();
$params['nolocalsignup'] = true;
page_footer($params);

function do_sign(&$location) {
    global $q_email, $q_name, $q_showname, $q_ref, $q_pin;
    $errors = importparams(
                array(array('name',true),       '//',        '', null),
                array('email',      'importparams_validate_email'),
                array('ref',        '/^[a-z0-9-]+$/i',  ''),
                array('showname',   '//',               '', 0),
                array(array('pin',true),        '//',              '', null)
            );
    if (!$errors) $errors = array();
    if ($q_name==_('<Enter your name>')) {
        $q_name = null;
    }
    if (!$q_name) {
        $q_showname = false;
        $q_name = null;
    }
    if ($email_err = microsites_invalid_email_address($q_email))
        $errors['email'] = $email_err;
    microsites_signup_extra_fields_validate($errors);

    if (!$q_ref)
        /* I don't think this error is likely to occur with real users, (see
         * mysociety-developers email of 20060209) but the error message which
         * occurs when ref is null is confusing, so better to trap it
         * explicitly. */
        err(_("No pledge reference was specified"));

    $pledge = new Pledge($q_ref);
    if (!check_pin($q_ref, $pledge->pin()))
        err(_("Permission denied"));

    if ($pledge->byarea()) {
        gaze_controls_validate_location($location, $errors, array('townonly'=>true));
    }

    if ($errors)
        return $errors;

    /* Search for nearby existing signers, and offer the user the option
     * of joining them, rather than signing up in a new place/town. */
    if ($pledge->byarea()) {
        list($lat, $lon, $desc) = explode('|', $location['gaze_place'], 3);

        $location['wgs84_lat'] = $lat;
        $location['wgs84_lon'] = $lon;
        $location['description'] = $desc;
        $location['input'] = $location['place'];
        $location['method'] = "Gaze";

        // See if somebody has already signed for this place
        $already_id = db_getOne("select byarea_location_id from byarea_location 
                left join location on location.id = byarea_location.byarea_location_id
                where pledge_id = ? and
                    country = ? and latitude = ? and longitude = ?",
                array($pledge->id(),
                $location['country'], /* deliberately no state, as can be null */
                $location['wgs84_lat'], $location['wgs84_lon'],
                ));

        // See if we already have a specific place on the form
        $byarea_location_id = get_http_var('byarea_location_id');
        if (!$byarea_location_id) {
            $q = db_query('
                select location.*, byarea_location.byarea_location_id, nearby.distance,
                    (select count(*) from signers where signers.pledge_id = ? and 
                        signers.byarea_location_id = byarea_location.byarea_location_id) as count
                    from location_find_nearby(?, ?, ?) as nearby, byarea_location 
                    left join location on location.id = byarea_location.byarea_location_id
                    where nearby.location_id = byarea_location.byarea_location_id
                    and byarea_location.pledge_id = ? order by distance limit 8
            ', array($pledge->id(), $lat, $lon, 150,  /* 150 km is an arbitary large range here */
            $pledge->id()));
            if (db_num_rows($q) > 0) {
                // Display form with choice of signers
                $pledge->render_box(array('showdetails'=>false));
?>
    <form accept-charset="utf-8" id="pledgeaction" name="pledge" action="/<?=htmlspecialchars($pledge->ref()) ?>/sign" method="post">
    <input type="hidden" name="add_signatory" value="1">
    <input type="hidden" name="pledge" value="<?=htmlspecialchars($pledge->ref()) ?>">
    <input type="hidden" name="ref" value="<?=htmlspecialchars($pledge->ref()) ?>">
    <input type="hidden" name="name" value="<?=htmlspecialchars($q_name) ?>">
    <input type="hidden" name="email" value="<?=htmlspecialchars($q_email) ?>">
    <input type="hidden" name="showname" value="<?=htmlspecialchars($q_showname) ?>">
    <input type="hidden" name="country" value="<?=$location['country']?>">
    <input type="hidden" name="prev_country" value="<?=$location['country']?>">
    <input type="hidden" name="gaze_place" value="<?=$location['gaze_place']?>">
    <input type="hidden" name="place" value="<?=$location['place']?>">
    <input type="hidden" name="prev_place" value="<?=$location['place']?>">
    <h2><?=_('Which local pledge would you like to join?')?></h2>
    <?  if ($already_id) {
            print "<p>";
            printf(_('You can join other people making the pledge, either in %s, or another place nearby.'), $desc);
            print "</p><p>";
        } else {
            print "<p>";
            printf(_('You can be the first to sign in %s, or join other people making the pledge in a place nearby.'), $desc);
            print "</p><p>";
    ?>
                <input type="radio" name="byarea_location_id" value="new" id="byarea_location_id_0" checked>
                <label for="byarea_location_id_0"><?=$desc?> (<?=_('you will be the first')?>)</label><br>
<?
        }
                $c = 0;
                while ($r = db_fetch_array($q)) {
                    $c++;
?>
                <input type="radio" name="byarea_location_id" value="<?=$r['byarea_location_id']?>" id="byarea_location_id_<?=$c?>" <?=($already_id == $r['byarea_location_id']) ? 'checked' : ''?>>
                <label for="byarea_location_id_<?=$c?>"><?=$r['description']?><?
                    if ($r['country'] && ($location['country'] != $r['country'])) {
                        global $countries_code_to_name;
                        print ", ". $countries_code_to_name[$r['country']];
                }?>
 
                (<?=pb_pretty_distance($r['distance'], $location['country'])?>, <?=$r['count']?> <?=ngettext('person', 'people', $r['count'])?>)</label><br>
<?
                }
?>
    </p>
    <p><input type="submit" name="submit" value="<?=_('Sign Pledge')?>"></p>
    </form>
<?
                return array('location_choice' => true);
            } else {
                // No choice but to make a new place
            }
        } 
        if ($errors)
            return $errors;
    }

    /* Get the user to log in. */
    $r = $pledge->data;
    $r['reason_web'] = _('Before putting your signature on the pledge, we need to check that your email is working.');
    $r['template'] = 'signature-confirm';
    $P = pb_person_signon($r, $q_email, $q_name);
    
    $R = pledge_is_valid_to_sign($pledge->id(), $P->email());
    $f1 = $pledge->succeeded(true);

    if (!pledge_is_error($R)) {
        /* All OK, sign pledge. */

        if ($pledge->byarea()) {
            if ($byarea_location_id == 'new')
                $byarea_location_id = null;
            if ($byarea_location_id) {
                // check byarea_location is valid if already set from form choice
                $pledge->byarea_validate_location($byarea_location_id);
            } else if ($already_id) {
                $byarea_location_id = $already_id;
            } else {
                $byarea_location_id = db_getOne("select nextval('location_id_seq')");
                db_query("insert into location
                            (id, country, state, method, input, latitude, longitude, description)
                        values (?, ?, ?, ?, ?, ?, ?, ?)", array(
                            $byarea_location_id,
                            $location['country'], $location['state'],
                            $location['method'], $location['input'],
                            $location['wgs84_lat'], $location['wgs84_lon'],
                            $location['description']
                        ));
                db_query("insert into byarea_location
                            (pledge_id, byarea_location_id)
                            values (?, ?)", array(
                            $pledge->id(),
                            $byarea_location_id));
            }
        } else {
            $byarea_location_id = null;
        }
        
        db_query('insert into signers (pledge_id, name, person_id, showname, signtime, ipaddr, byarea_location_id) values (?, ?, ?, ?, ms_current_timestamp(), ?, ?)', array($pledge->id(), ($P->has_name() ? $P->name() : null), $P->id(), $q_showname ? 't' : 'f', $_SERVER['REMOTE_ADDR'], $byarea_location_id));
        db_commit();
        print '<p class="noprint loudmessage" align="center">';
        if ($byarea_location_id) {
            $byarea_location_description = db_getOne("select description from
                    location where location.id = ?", $byarea_location_id);
            printf(_('Thanks for signing up to this pledge in %s!'), $byarea_location_description);
        } else
            print _('Thanks for signing up to this pledge!');

        print '</p>';

        /* Grab the row again so the check is current. */
        $pledge = new Pledge($q_ref);
        if (!$f1 && $pledge->succeeded())
            print '<p><strong>' . _("Your signature has made this pledge reach its target! Woohoo!") . '</strong></p>';

        post_confirm_advertise();
    } else if ($R == PLEDGE_SIGNED) {
        /* Either has already signer, or is creator. */
        print '<p><strong>';
        if ($P->id() == $pledge->creator_id()) {
            print _('You cannot sign your own pledge!');
        } else {
            print _('You\'ve already signed this pledge!');
        }
        print '</strong></p>';
    } else {
        /* Something else has gone wrong. */
        print '<p><strong>' . _("Sorry &mdash; it wasn't possible to sign that pledge.") . ' '
                . htmlspecialchars(pledge_strerror($R))
                . ".</strong></p>";
    }
}

function sign_location_choose($p, $errors = array(), $location = array()) {
    if (get_http_var('add_signatory'))
        $showname = get_http_var('showname') ? ' checked' : '';
    else
        $showname = ' checked';

    $email = get_http_var('email');
    $name = get_http_var('name', true);

    // error_log("$email $name");
?>
<form accept-charset="utf-8" id="pledgeaction" name="pledge" action="/<?=htmlspecialchars($p->ref()) ?>/sign" method="post">
<input type="hidden" name="add_signatory" value="1">
<input type="hidden" name="pledge" value="<?=htmlspecialchars($p->ref()) ?>">
<input type="hidden" name="ref" value="<?=htmlspecialchars($p->ref()) ?>">
<input type="hidden" name="name" value="<?=htmlspecialchars($name) ?>">
<input type="hidden" name="email" value="<?=htmlspecialchars($email) ?>">
<input type="hidden" name="showname" value="<?=htmlspecialchars($showname) ?>">
<?  if (get_http_var('pin', true)) print '<input type="hidden" name="pin" value="'.htmlspecialchars(get_http_var('pin', true)).'">'; 
    if ($p->byarea()) {
        // Pledges where target is per town, rather than overall
        if ($p->is_global()) {
?>     <p><input type="hidden" name="prev_country" value="<?=$location['country']?>"> 
          <input type="hidden" name="country" value="<?=$location['country']?>"> <?
        } else {
?>     <p><input type="hidden" name="prev_country" value="<?=$p->country_code()?>"> 
          <input type="hidden" name="country" value="<?=$p->country_code()?>"> <?
        }
?></p>

<div id="ifyes_line">
<?              gaze_controls_print_place_choice($location['place'], $location['gaze_place'], $location['places'], $errors, $location['postcode'], array('townonly'=>true)); ?>
</div>
<?
    }
    print '<p><input type="submit" name="submit" value="' . ($p->byarea() ? _('Sign Pledge') : _('Sign Pledge')) . '"></p>
</form>';
}



?>
