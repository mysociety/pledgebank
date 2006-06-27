<?php
// gaze-controls.php:
// Let someone enter the name of a town (or postcode) and country,
// and select a choice of matching towns from Gaze, the gazetteer.
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: gaze-controls.php,v 1.2 2006-06-27 17:27:27 francis Exp $

// TODO: 
// - Alter new.php to call these functions, rather than have its own,
// slightly differently behaving, copy of the code.
// - Probably remove the get_http_var calls for prev_country and prev_place
// in pb_gaze_validate_location
// - Rename functions to be gaze_controls_ or something as a prefix
// - Adapt this so it can be in global phplib for use on other sites

function pb_gaze_find_places($country, $state, $query, $maxresults = null, $minscore = null) {
    $ret = gaze_find_places($country, $state, $query, $maxresults, $minscore);
    gaze_check_error($ret);
    return $ret;
}

// Given a row returned from gaze, returns a list of (description, radio button value)
function pb_get_gaze_place_details($p) {
    list($name, $in, $near, $lat, $lon, $st, $score) = $p;
    $desc = $name;
    if ($in) $desc .= ", $in";
    if ($st) $desc .= ", $st";
    if ($near) $desc .= " (" . _('near') . " " . htmlspecialchars($near) . ")";
    locale_push('en-gb');
    $t = htmlspecialchars("$lat|$lon|$desc");
    locale_pop();
    if ($score) $desc .= "<!--($score%)-->";
    return array($desc, $t);
}

// Prints HTML for radio buttons to select a list of places drawn from Gaze
function pb_view_gaze_places_choice($places, $place, $selected_gaze_place) {
    print "<strong>" . sprintf(_("There are several possible places which match '%s'. Please choose one:"),$place) . "</strong><br>";
    $nn = 0;
    foreach ($places as $p) {
        list($desc, $t) = pb_get_gaze_place_details($p);
        $checked = '';
        if ($t == $selected_gaze_place) {
            $checked = 'checked';
        }
        $nn++;
        print "<input type=\"radio\" name=\"gaze_place\" value=\"$t\" id=\"gaze_place_$nn\" $checked>\n<label for=\"gaze_place_$nn\">$desc</label><br>\n";
    }
    print "<strong>"._("If it isn't any of those, try a different spelling, or the name of another nearby town:")."</strong>";
}

function country_sort($a, $b) {
    global $countries_code_to_name;
    return strcoll($countries_code_to_name[$a], $countries_code_to_name[$b]);
}

/* pb_view_gaze_country_choice
 * Draws a drop down box for choice of country. $selected_country and $selected_state are
 * which items to select by default. $errors array is used to highlight in red
 * if countains key 'country'.  params can contain
 *      'noglobal' - don't offer "any country" choice
 *      'gazeonly' - only list countries for which we have local gaze place
 */
function pb_view_gaze_country_choice($selected_country, $selected_state, $errors, $params = array()) {
    global $countries_name_to_code, $countries_code_to_name, $countries_statecode_to_name, $ip_country;

    /* Save previous value of country, so that we can detect if it's changed after
     * one of a list of placenames is selected. */
    if ($selected_country)
        printf("<input type=\"hidden\" name=\"prev_country\" value=\"%s\">", htmlspecialchars($selected_country));

?>
<select <? if (array_key_exists('country', $errors)) print ' class="error"' ?> name="country" onchange="update_place_local(this, true)">
  <option value="(choose one)"><?=_('(choose one)') ?></option>
<? if (!array_key_exists('noglobal', $params)) { ?>
  <option value="Global"<? if ($selected_country=='Global') print ' selected'; ?>><?=_('None &mdash; applies anywhere') ?></option>
<? } ?>
  <!-- needs explicit values for IE Javascript -->
<?
    if ($selected_country and array_key_exists($selected_country, $countries_code_to_name)) {
        print "<option value=\"$selected_country\"";
/* Disabled with below
        if (!$selected_state) { */
            print " selected";
/*        } */
        print ">"
                . htmlspecialchars($countries_code_to_name[$selected_country])
                . "</option>";
/* Disabled for now, as not necessary
        if (array_key_exists($selected_country, $countries_statecode_to_name)) {
            foreach ($countries_statecode_to_name[$selected_country] as $opt_statecode => $opt_statename) {
                print "<option value=\"$selected_country,$opt_statecode\"";
                if ($selected_state && "$opt_statecode" == $selected_state)
                    print ' selected';
                print "> &raquo; "
                        . htmlspecialchars($opt_statename)
                        . "</option>";
                
            }
        }
*/
    }
    if ($selected_country != microsites_site_country() && microsites_site_country()) {
        print "<option value=\"".microsites_site_country()."\">";
        print htmlspecialchars($countries_code_to_name[microsites_site_country()]);
        print "</option>";
    }
    if ($selected_country != $ip_country && $ip_country && $ip_country != microsites_site_country()) {
        print "<option value=\"$ip_country\">";
        print htmlspecialchars($countries_code_to_name[$ip_country]);
        print "</option>";
    }
?>
  <option value="(separator)"><?=_('---------------------------------------------------') ?></option>
<?
    if (array_key_exists('gazeonly', $params)) {
        $countries_list = gaze_get_find_places_countries();
        # Ignore errors, so outages in gaze don't stop every page rendering
        if (rabx_is_error($countries_list))
            $countries_list = array();
        usort($countries_list, "country_sort");
    } else {
        $countries_list = array_values($countries_name_to_code);
        usort($countries_list, "country_sort");
    }

    foreach ($countries_list as $opt_code) {
        $opt_country = $countries_code_to_name[$opt_code];
        print "<option value=\"$opt_code\">"
                . htmlspecialchars($opt_country)
                . "</option>";
/* Disabled for now, as not necessary
        if (array_key_exists($opt_code, $countries_statecode_to_name)) {
            foreach ($countries_statecode_to_name[$opt_code] as $opt_statecode => $opt_statename) {
                print "<option value=\"$opt_code,$opt_statecode\">"
                        . "&raquo; "
                        . htmlspecialchars($opt_statename)
                        . "</option>";
            }
        }
*/
    }
?>
</select>
<?
}

/* pb_view_gaze_place_choice
 * Display options for choosing a local place
 */
function pb_view_gaze_place_choice($selected_place, $selected_gaze_place, $places, $errors, $postcode) {

    $select_place = false;
    if (!(!$selected_place || array_key_exists('place', $errors) || count($places) == 0)) {
        print '<p></p><div id="formnote">';
        print _('Now please select one of the possible places; if none of them is right, please type the name of another nearby place');
        print '</div>';
        $select_place = true;
    }

    print "<ul>";

    ?> 
    <li><p id="place_line"> <?

    /* Save previous value of 'place' so we can show a new selection list in the
     * case where the user types a different place name after clicking on one of
     * the selections. */
    if ($selected_place)
        printf("<input type=\"hidden\" name=\"prev_place\" value=\"%s\">", htmlspecialchars($selected_place));

    /* If the user has already typed a place name, then we need to grab the
     * possible places from Gaze. */
    if (!$select_place) {
        ?>
           <?=_('Place name:') ?>
        <?
    } else {
        pb_view_gaze_places_choice($places, $selected_place, $selected_gaze_place);
    }

    ?>
    <input <? if (array_key_exists('place', $errors)) print ' class="error"' ?> type="text" name="place" id="place" value="<? if ($selected_place) print htmlspecialchars($selected_place) ?>">
</p></li>

    <li><p id="postcode_line">
    <?=_('Or, UK only, you can give a postcode area:') ?>
    <input <? if (array_key_exists('postcode', $errors)) print ' class="error"' ?> type="text" name="postcode" id="postcode" value="<? if ($postcode) print htmlspecialchars($postcode) ?>">
    <br><small><?=_('(just the start of the postcode, such as WC1)') ?></small>
    </p></li>
    </ul>
    <?
}

# pb_gaze_get_location
function pb_gaze_get_location() {
    $location = array();
    $location['country'] = get_http_var('country');
    $location['state'] = null;
    if (is_string($location['country'])) {
        $a = array();
        if (preg_match('/^([A-Z]{2}),(.+)$/', $location['country'], $a))
            list($x, $location['country'], $location['state']) = $a;
    } else {
        $location['country'] = $site_country;
    }
    $location['place'] = get_http_var('place');
    if ($location['country'] && $location['country'] == 'Global')
        $location['place'] = null;
    else {
        # Check gaze has this country
        $countries_with_gazetteer = gaze_get_find_places_countries();
        gaze_check_error($countries_with_gazetteer);
        if (!in_array($location['country'], $countries_with_gazetteer)) {
            $location['place'] = null;
        }
    }
    $location['gaze_place'] = get_http_var('gaze_place');
    $location['postcode'] = get_http_var('postcode');
    if ($location['country'] && $location['country'] != 'GB') $location['postcode'] = '';
    if ($location['country'] && $location['country'] == '(choose one)') $location['country'] = null;
    if ($location['country'] && $location['country'] == '(separator)') $location['country'] = null;

    if ($location['place'] && (validate_partial_postcode($location['place']) || validate_postcode($location['place']))) {
        $location['postcode'] = $location['place'];
        $location['place'] = null;
    }
    return $location;
}

# pb_gaze_validate_location
# Validates a location entered for a form.
function pb_gaze_validate_location(&$location, &$errors) {
    if (!$location['country']) $errors['country'] = _("Please choose a country");
    if ($location['country'] == 'GB') {
        if ($location['postcode'] && $location['place'])
            $errors['place'] = _("Please enter either a place name or a postcode area, but not both");
    } else {
        if ($location['postcode'])
            $errors['postcode'] = _("You can only enter a postcode area if your pledge applies to the UK");
    }
    if ($location['postcode']) {
        if (!validate_partial_postcode($location['postcode']) && !validate_postcode($location['postcode']))
            $errors['postcode'] = _('Please enter a postcode, or just its first part; for example, OX1 3DR or WC1.');
        else if (mapit_get_error(mapit_get_location($location['postcode'], 1)))
            $errors['postcode'] = sprintf(_("We couldn't recognise the postcode '%s'; please re-check it"), htmlspecialchars($location['postcode']));
        else
            $location['postcode'] = canonicalise_partial_postcode($location['postcode']);
    } elseif ($location['place']) {
        if (!$location['gaze_place']) {
            $errors['gaze_place'] = "NOTICE";
        }
    } else {
        if ($location['country'] == 'GB') {
            $errors['place'] = _("Please enter either a place name or a postcode area");
        } else {
            $errors['place'] = _("Please enter a place name");
        }
    }
    if ($location['place'] && ($location['country'] != get_http_var('prev_country') || $location['place'] != get_http_var('prev_place'))) {
        $errors['gaze_place'] = "NOTICE";
    }
    if (array_key_exists('gaze_place', $errors) && $errors['gaze_place'] == "NOTICE") {
        $places = pb_gaze_find_places($location['country'], $location['state'], $location['place'], 10, 0);
        $have_exact = have_exact_gaze_match($places, $location['place']);
        if ($have_exact) {
            list($desc, $radio_name) = pb_get_gaze_place_details($have_exact);
            $location['gaze_place'] = $radio_name;
            unset($errors['gaze_place']);
            #print "have exact $desc $radio_name\n"; exit;
        }
    }

    global $countries_statecode_to_name;
    if (array_key_exists($location['country'], $countries_statecode_to_name)) {
        // Split out state in case where they picked US from dropdown, but place with state from gaze
        $a = array();
        if (preg_match('/^(.+), ([^,]+)$/', $location['gaze_place'], $a)) {
            list($x, $location['gaze_place'], $location['state']) = $a;
        }
    }
    
    // Create list of possibly matching places to choose from
    $location['places'] = null;
    if ($location['place']) {
        // Look up nearby places
        $location['places'] = pb_gaze_find_places($location['country'], $location['state'], $location['place'], 10, 0);
        if (array_key_exists('gaze_place', $errors)) {
            if (count($location['places']) > 0) {
                // message printed in pb_view_gaze_place_choice
            } else {
                $errors['place'] = sprintf(_("Unfortunately, we couldn't find anywhere with a name like '%s'.  Please try a different spelling, or another nearby village, town or city."),
                htmlspecialchars($location['place']));
            }
          #unset($errors['gaze_place']); # remove NOTICE
        } 
    }
}

// Is this match from gaze exact?
function have_exact_gaze_match($places, $typed_place) {
    if (count($places) < 1)
        return;
    $gotcount = 0;
    $got = null;
    foreach ($places as $place) {
        if (trim(strtolower($place['0'])) == trim(strtolower($typed_place))) {
            $got = $place;
            $gotcount++;
        }
    }
    if ($gotcount == 1)
        return $got;
    else
        return null;
}



