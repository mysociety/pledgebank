<?
// ref-index.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-sign.php,v 1.41 2006-06-29 13:36:31 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../../phplib/person.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';

page_check_ref(get_http_var('ref'));
$p = new Pledge(get_http_var('ref'));
$location = array();
if ($p->byarea())
    $location = pb_gaze_get_location();

$title = _('Signature addition');
$extra = null;
page_header($title, array('ref'=>$p->ref(),'pref'=>$p->url_typein()));
$errors = do_sign($location);
if (is_array($errors)) {
    if (array_key_exists('gaze_place', $errors) && $errors['gaze_place'] == 'NOTICE') {
        unset($errors['gaze_place']); # remove NOTICE
    }
    if ($errors) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    }
    $p->render_box(array('showdetails'=>false));
    $p->sign_box($errors, $location);
}
$params = array('extra'=>$extra);
# if ($extra=='signer-confirm-advert=local-alerts')
$params['nolocalsignup'] = true;
page_footer($params);

function do_sign(&$location) {
    global $q_email, $q_name, $q_showname, $q_ref, $q_pin, $extra;
    $errors = importparams(
                array(array('name',true),       '//',        '', null),
                array('email',      'importparams_validate_email'),
                array('ref',        '/^[a-z0-9-]+$/i',  ''),
                array('showname',   '//',               '', 0),
                array(array('pin',true),        '//',              '', null)
            );
    if ($q_name==_('<Enter your name>')) {
        $q_name = null;
    }
    if (!$q_name) {
        $q_showname = false;
        $q_name = null;
    }

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
        if (!$errors)
            $errors = array();
        pb_gaze_validate_location($location, $errors);
    }

    if ($errors)
        return $errors;

    /* Get the user to log in. */
    $r = $pledge->data;
    $r['reason_web'] = _('Before putting your signature on the pledge, we need to check that your email is working.');
    $r['template'] = 'signature-confirm';
    $P = person_signon($r, $q_email, $q_name);
    
    $R = pledge_is_valid_to_sign($pledge->id(), $P->email());
    $f1 = $pledge->succeeded(true);

    if (!pledge_is_error($R)) {
        /* All OK, sign pledge. */

        $byarea_location_id = null;
        if ($pledge->byarea()) {
            list($lat, $lon, $desc) = explode('|', $location['gaze_place'], 3);
            $location['wgs84_lat'] = $lat;
            $location['wgs84_lon'] = $lon;
            $location['description'] = $desc;
            $location['input'] = $location['place'];
            $location['method'] = "Gaze";

            $already_id = db_getOne("select byarea_location_id from byarea_location 
                    left join location on location.id = byarea_location.byarea_location_id
                    where pledge_id = ? and
                        country = ? and latitude = ? and longitude = ?",
                    array($pledge->id(),
                    $location['country'], /* deliberately no state, as can be null */
                    $location['wgs84_lat'], $location['wgs84_lon'],
                    ));
            if ($already_id) {
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
        }
        
        db_query('insert into signers (pledge_id, name, person_id, showname, signtime, ipaddr, byarea_location_id) values (?, ?, ?, ?, ms_current_timestamp(), ?, ?)', array($pledge->id(), ($P->has_name() ? $P->name() : null), $P->id(), $q_showname ? 't' : 'f', $_SERVER['REMOTE_ADDR'], $byarea_location_id));
        db_commit();
        print '<p class="noprint loudmessage" align="center">' . _('Thanks for signing up to this pledge!') . '</p>';

        /* Grab the row again so the check is current. */
        $pledge = new Pledge($q_ref);
        if (!$f1 && $pledge->succeeded())
            print '<p><strong>' . _("Your signature has made this pledge reach its target! Woohoo!") . '</strong></p>';

        $extra = post_confirm_advertise($pledge, 'signer-confirm');
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

?>
