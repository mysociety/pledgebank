<?php
// gaze-controls.php:
// Let someone enter the name of a town (or postcode) and country,
// and select a choice of matching towns from Gaze, the gazetteer.
// Also uses MaPit, and Google Maps API for zipcodes, so gaze_controls
// probably a bit of a misnomer.
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: gaze-controls.php,v 1.22 2008-10-28 14:59:29 matthew Exp $

// TODO: 
// - Adapt this so it can be in global phplib for use on other sites
// - All the NOTICE stuff in other files is a bit messy - replace at least with 
//   gaze_control_ functions here which check $errors arrays

// The parameter "townonly" indicates that a location must be entered by
// the name of a town/place rather than a postcode.

$gaze_controls_nearby_distance = 10;

function gaze_controls_find_places($country, $state, $query, $maxresults = null, $minscore = null) {
    $ret = gaze_find_places($country, $state, $query, $maxresults, $minscore);
    gaze_check_error($ret);
    $wales = -1;
    foreach ($ret as $k => $p) {
        if ($ret[$k][0] == 'Wales') $wales = $k;
        $ret[$k][7] = $country;
    }
    if ($wales >= 0) unset($ret[$wales]);
    return $ret;
}

// Given a row returned from gaze, returns a list of (description, radio button value)
function gaze_controls_get_place_details($p, $statecounts = array(), $justdesc = false) {
    global $countries_statecode_to_name;
    list($name, $in, $near, $lat, $lon, $state, $score, $country) = $p;
    $desc = $name;
    if ($in && (!$state || $statecounts[$state][$name]>1)) $desc .= ", $in";
    if ($state) {
        $desc .= ', ';
        if (isset($countries_statecode_to_name[$country][$state]))
            $desc .= $countries_statecode_to_name[$country][$state];
        else
            $desc .= $state;
    }
    $url = $desc;
    if ($near) {
        $desc .= " (" . _('near') . " " . htmlspecialchars($near) . ")";
        $url .= " ($near)";
    }
    if ($justdesc)
        return array($desc, $url);
    locale_push('en-gb');
    $t = htmlspecialchars("$lat|$lon|$desc");
    locale_pop();
    if ($score) $desc .= "<!--($score%)-->";
    return array($desc, $t);
}

function gaze_controls_sort_places($a, $b) {
    # Score
    if ($a[6] > $b[6]) return -1;
    elseif ($a[6] < $b[6]) return 1;
    # State
    if ($a[5] > $b[5]) return 1;
    elseif ($a[5] < $b[5]) return -1;
    return 0;
}

// Prints HTML for radio buttons to select a list of places drawn from Gaze
function gaze_controls_print_places_choice($places, $place, $selected_gaze_place) {
    // TODO: Maybe pass country through to here (or all of $location) and display 
    // country name in this sentence

    list($have_exact, $anymatches) = _gaze_controls_exact_match($places, $place);
    print "<strong>";
    if ($anymatches)
        printf(_("There is more than one place called '%s'. Please choose which one you mean, or which place with a similar name:"), $place);
    else 
        printf(_("Sorry, we don't know where '%s' is. We know about these places with similar names, please choose one if it is right:"), $place);
    print "</strong><br>";
    $nn = 0;
    $statecounts = array();
    foreach ($places as $p) {
        $name = $p[0]; $state = $p[5];
        if (!isset($statecounts[$state])) $statecounts[$state] = array();
        if (!isset($statecounts[$state][$name])) $statecounts[$state][$name] = 0;
        if ($state)
            $statecounts[$state][$name]++;
    }
    usort($places, 'gaze_controls_sort_places');
    foreach ($places as $p) {
        list($desc, $t) = gaze_controls_get_place_details($p, $statecounts);
        $checked = '';
        if ($t == $selected_gaze_place) {
            $checked = 'checked';
        }
        $nn++;
        print "<input type=\"radio\" name=\"gaze_place\" value=\"$t\" id=\"gaze_place_$nn\" $checked>\n<label for=\"gaze_place_$nn\">$desc</label><br>\n";
    }
    print "<strong>"._("If it isn't any of those, try a different spelling, or the name of another nearby town:")."</strong>";
}

/* gaze_controls_print_country_choice
 * Draws a drop down box for choice of country. $selected_country and $selected_state are
 * which items to select by default. $errors array is used to highlight in red
 * if countains key 'country'.  params can contain
 *      'noglobal' - don't offer "any country" choice
 *      'gazeonly' - only list countries for which we have local gaze place
 *      'fieldname' - HTML form field name to use, defaults to country
 */
function gaze_controls_print_country_choice($selected_country, $selected_state, $errors, $params = array()) {
    global $countries_name_to_code, $countries_code_to_name, $countries_statecode_to_name, $ip_country;

    $field_name = 'country';
    if (array_key_exists('fieldname', $params)) {
        $field_name = $params['fieldname'];
    }

    /* Save previous value of country, so that we can detect if it's changed after
     * one of a list of placenames is selected. */
    if ($selected_country)
        printf("<input type=\"hidden\" name=\"prev_$field_name\" value=\"%s\">", htmlspecialchars($selected_country));

?>
<select <? if (array_key_exists($field_name, $errors)) print ' class="error"' ?> name="<?=$field_name?>" onchange="update_place_local(this, true)">
  <option value="(choose one)"><?=_('(choose one)') ?></option>
<? if (!array_key_exists('noglobal', $params)) { ?>
  <option value="Global"<? if ($selected_country=='Global') print ' selected'; ?>><?=_('Not specific to any location') ?></option>
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
        usort($countries_list, "countries_sort");
    } else {
        $countries_list = array_values($countries_name_to_code);
        usort($countries_list, "countries_sort");
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
function gaze_controls_print_place_choice($selected_place, $selected_gaze_place, $places, $errors, $postcode, $params = array()) {

    $select_place = false;
    if (!(!$selected_place || array_key_exists('place', $errors) || count($places) == 0)) {
        if (array_key_exists('midformnote', $params) && $params['midformnote']) {
            print '<p></p><div id="formnote">';
            print _('Now please select one of the possible places; if none of them is right, please type the name of another nearby place');
            print '</div>';
        }
        $select_place = true;
    }

    print "<ul>";

    ?> 
    <li id="place_line"> <?

    /* Save previous value of 'place' so we can show a new selection list in the
     * case where the user types a different place name after clicking on one of
     * the selections. */
    if ($selected_place)
        printf("<input type=\"hidden\" name=\"prev_place\" value=\"%s\">", htmlspecialchars($selected_place));

    /* If the user has already typed a place name, then we need to grab the
     * possible places from Gaze. */
    if (!$select_place) {
        ?>
           <span id="place_name_label"><?=_('Place name:') ?></span>
        <?
    } else {
        gaze_controls_print_places_choice($places, $selected_place, $selected_gaze_place);
    }

    ?>
    <input <? if (array_key_exists('place', $errors)) print ' class="error"' ?> type="text" name="place" id="place" value="<? if ($selected_place) print htmlspecialchars($selected_place) ?>">
</li>

<? if (!array_key_exists('townonly', $params) || !$params['townonly']) { ?>
    <li id="postcode_line">
    <?=_('Or, UK only, you can give a postcode area:') ?>
    <input <? if (array_key_exists('postcode', $errors)) print ' class="error"' ?> type="text" name="postcode" id="postcode" value="<? if ($postcode) print htmlspecialchars($postcode) ?>">
    <br><small><?=_('(just the start of the postcode, such as WC1)') ?></small>
    </li>
<? } ?>

    </ul>
    <?
}

# gaze_controls_get_location
# Looks up the country, state, place, postcode etc. from HTTP variables,
# partially validates it and returns one $location data array.
function gaze_controls_get_location($params = array()) {
    global $site_country;
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
    if ($location['country'] && $location['country'] == '(choose one)') $location['country'] = null;
    if ($location['country'] && $location['country'] == '(separator)') $location['country'] = null;

    if ($location['place'] && (validate_partial_postcode($location['place']) || validate_postcode($location['place']))) {
        $location['postcode'] = $location['place'];
        $location['place'] = null;
    }

    $parts = gaze_controls_split_name($location['place']);
    if ($parts['state']) {
        $location['place'] = $parts['name'];
        $location['state'] = $parts['state'];
    }

    # Only allow postcode in the UK, and don't allow it if in townonly mode (for byarea pledges)
    if ($location['country'] && $location['country'] != 'GB') $location['postcode'] = '';
    if (array_key_exists('townonly', $params) && $params['townonly']) $location['postcode'] = '';

    $location['places'] = null;

    return $location;
}

# gaze_controls_validate_location &LOCATION &ERRORS [PARAMS]
# Validates a location entered for a form. The LOCATION associative
# array is updated, for example if an exact place match was found.
# Error messages are added to the ERRORS array. PARAMS can
# contain the following parameters:
#   'townonly' - if true only allows lookup by town name, not by postcode.
function gaze_controls_validate_location(&$location, &$errors, $params = array()) {
    global $gaze_controls_nearby_distance;

    if (!$location['country']) $errors['country'] = _("Please choose a country");
    if (array_key_exists('townonly', $params) && $params['townonly'] && $location['postcode'])
        $errors['postcode'] = _("Please enter a place rather than a postcode for location based pledge signups");
    if ($location['country'] == 'GB') {
        if ($location['postcode'] && $location['place'])
            $errors['place'] = _("Please enter either a place name or a postcode area, but not both");
    } else {
        if ($location['postcode'])
            $errors['postcode'] = _("You can only enter a postcode area if you have selected the UK");
    }
    if ($location['postcode']) {
        if (!validate_partial_postcode($location['postcode']) && !validate_postcode($location['postcode']))
            $errors['postcode'] = _('Please enter a postcode, or just its first part; for example, OX1 3DR or WC1.');
        elseif (mapit_get_error(mapit_get_location($location['postcode'], 1))) {
            if (preg_match('#^(IM|JE|GY)#i', $location['postcode'])) {
                $errors['postcode'] = _("I'm afraid we don't know about Isle of Man or Channel Island postcodes. Please try entering a local town instead.");
            } else {
                $errors['postcode'] = sprintf(_("We couldn't recognise the postcode '%s'; please re-check it"), htmlspecialchars($location['postcode']));
            }
        } else
            $location['postcode'] = canonicalise_partial_postcode($location['postcode']);
    } elseif ($location['country'] == 'US' && preg_match('#^\d{5}$#', $location['place'])) {
        $zip = lookup_zipcode($location['place']);
        if (isset($zip['lon'])) {
            $location['gaze_place'] = "$zip[lat]|$zip[lon]|$location[place]";
            return;
        }
        $errors['place'] = "I'm afraid we did not recognise that zip code.";
    } elseif ($location['place']) {
        if (!$location['gaze_place']) {
            $errors['gaze_place'] = "NOTICE";
        }
    } else {
        if ($location['country'] == 'GB') {
            if (array_key_exists('townonly', $params) && $params['townonly'])
                $errors['place'] = _("Please enter a place name. Choose a specific town where you will carry out the pledge, rather than a postcode.");
            else
                $errors['place'] = _("Please enter either a place name or a postcode area");
        } else {
            $errors['place'] = _("Please enter a place name");
        }
    }

    $prev_country = isset($location['prev_country']) ? $location['prev_country'] : get_http_var('prev_country');
    $prev_place = isset($location['prev_place']) ? $location['prev_place'] : get_http_var('prev_place');
    if ($location['place'] && ($location['country'] != $prev_country || $location['place'] != $prev_place)) {
        $errors['gaze_place'] = "NOTICE";
    }
    if (array_key_exists('gaze_place', $errors) && $errors['gaze_place'] == "NOTICE") {
        $places = gaze_controls_find_places($location['country'], $location['state'], $location['place'], $gaze_controls_nearby_distance, 0);
        list ($have_exact, $anymatches) = _gaze_controls_exact_match($places, $location['place']);
        if ($have_exact) {
            $statecounts = array($have_exact[5] => array($have_exact[0] => 1));
            list($desc, $radio_name) = gaze_controls_get_place_details($have_exact, $statecounts);
            $location['gaze_place'] = $radio_name;
            unset($errors['gaze_place']);
            #print "have exact $desc $radio_name\n"; exit;
        }
    }

    global $countries_statecode_to_name, $countries_name_to_statecode;
    if (array_key_exists($location['country'], $countries_statecode_to_name)) {
        // Split out state in case where they picked US from dropdown, but place with state from gaze
        $a = array();
        if (preg_match('/^(.+), ([^,]+)$/', $location['gaze_place'], $a)) {
            list($x, $location['gaze_place'], $location['state']) = $a;
            if (isset($countries_name_to_statecode[$location['country']][strtolower($location['state'])]))
                $location['state'] = $countries_name_to_statecode[$location['country']][strtolower($location['state'])];
        }
    }
    
    // Create list of possibly matching places to choose from
    $location['places'] = null;
    if ($location['place']) {
        // Look up nearby places
        $location['places'] = gaze_controls_find_places($location['country'], $location['state'], $location['place'], $gaze_controls_nearby_distance, 0);
        if (array_key_exists('gaze_place', $errors)) {
            if (count($location['places']) > 0) {
                // message printed in gaze_controls_print_place_choice
            } else {
                $errors['place'] = sprintf(_("Unfortunately, we couldn't find anywhere with a name like '%s'.  Please try a different spelling, or another nearby village, town or city."),
                htmlspecialchars($location['place']));
            }
          #unset($errors['gaze_place']); # remove NOTICE
        } 
    }
}

// Is this match from gaze exact?
function _gaze_controls_exact_match($places, $typed_place) {
    if (count($places) < 1)
        return;
    $gotcount = 0;
    $got = null;
    $anymatches = false;
    foreach ($places as $place) {
        if (trim(strtolower($place[0])) == trim(strtolower($typed_place))) {
            $got = $place;
            $gotcount++;
            $anymatches = true;
        }
    }
    if ($gotcount == 1)
        return array($got, $anymatches);
    else
        return array(null, $anymatches);
}

function gaze_controls_split_name($s) {
    global $countries_name_to_statecode;
    preg_match('#^(.*?)(?:,\s*(.*?))?(?:,\s*(.*?))?(?:\s*\((.*?)\))?$#', $s, $m);
    $parts = array(
        'name' => isset($m[1]) ? $m[1] : '',
        'in' => isset($m[2]) ? $m[2] : '',
        'state' => isset($m[3]) ? $m[3]: '',
        'near' => isset($m[4]) ? $m[4] : '',
    );
    if (isset($countries_name_to_statecode[microsites_site_country()][strtolower($parts['state'])]))
        $parts['state'] = $countries_name_to_statecode[microsites_site_country()][strtolower($parts['state'])];
    if (isset($countries_name_to_statecode[microsites_site_country()][strtolower($parts['in'])])) {
        $parts['state'] = $countries_name_to_statecode[microsites_site_country()][strtolower($parts['in'])];
        $parts['in'] = '';
    }
    if (strlen($parts['in'])==2) {
        $parts['state'] = $parts['in'];
        $parts['in'] = '';
    }
    return $parts;
}

function lookup_zipcode($zip) {
    $key = OPTION_GOOGLE_MAPS_API_KEY;
    $url = 'http://maps.google.com/maps/geo?key=' . $key . '&q=' . $zip . ',+US';
    #$url = 'http://ws.geonames.org/postalCodeSearch?country=US&postalcode=' . $search;
    $f = @file_get_contents($url);
    #if (preg_match('#<lat>(.*?)</lat>\s*<lng>(.*?)</lng>#', $f, $m)) {
    if (preg_match('#"coordinates":\[(.*?),(.*?),#', $f, $m)) {
        #$lat = $m[1]; $lon = $m[2];
        $lon = $m[1]; $lat = $m[2];
        return array('lon' => $lon, 'lat' => $lat);
    } else {
        return array();
    }
}

