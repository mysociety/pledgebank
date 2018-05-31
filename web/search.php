<?
// search.php:
// Search for a pledge.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: search.php,v 1.74 2007-10-24 22:39:44 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/comments.php';
require_once '../commonlib/phplib/mapit.php';
require_once "../commonlib/phplib/votingarea.php";
require_once "../commonlib/phplib/countries.php";

$search = trim(get_http_var('q', true));
if (!$search) $search = trim(get_http_var('s', true));
$backpage_clause = " AND (".microsites_normal_prominences()." OR cached_prominence = 'frontpage') ";
if (get_http_var('backpage'))
    $backpage_clause = '';
$rss = get_http_var('rss') ? true : false;
$rss_items = array();
$pledges_output = array();

# XXX: Header stuff will need changing
$heading = sprintf(_("Search results for '%s'"), htmlspecialchars($search));
if ($rss) 
    rss_header($heading, $heading, array());
else 
    page_header($heading,
            array('rss'=> array(
                    $heading => pb_domain_url(array('explicit'=>true, 'path'=>'/rss'.$_SERVER['REQUEST_URI']))
                    ))
    );

$shown_alert_box = search($search);

if ($rss) {
    function compare_creationtime($a, $b) {
        return strcmp($b['creationtime'], $a['creationtime']);
    }
    $rss_items = array_unique($rss_items, SORT_REGULAR);
    usort($rss_items, "compare_creationtime");
    rss_footer($rss_items);
}
else {
    $params = array();
    if ($shown_alert_box)
        $params['nolocalsignup'] = true;
    page_footer($params);
}

# ---

function get_location_results($pledge_select, $lat, $lon) {
    global $pb_today, $rss_items, $rss, $pledges_output, $backpage_clause;
    if (get_http_var("far")) {
        $radius = intval(get_http_var("far"));
    } else {
        $radius = gaze_get_radius_containing_population($lat, $lon, OPTION_PB_SEARCH_POPULATION);
        gaze_check_error($radius);
    }
    locale_push('en-gb');
    $q = db_query($pledge_select . ", distance
                FROM pledge_find_nearby(?,?,?) AS nearby 
                LEFT JOIN pledges ON nearby.pledge_id = pledges.id
                LEFT JOIN location ON location.id = pledges.location_id 
                WHERE 
                    pin IS NULL 
                    $backpage_clause
                    AND '$pb_today' <= pledges.date 
                ORDER BY distance", array($lat, $lon, $radius)); 
    locale_pop();
    $ret = "";
    if (db_num_rows($q)) {
        $success = 1;
        $ret .= '<ul class="search_results">';
        while ($r = db_fetch_array($q)) {
            $pledge = new Pledge($r['ref']);
            $ret .= '<li>';
            $distance_line = pb_pretty_distance($r['distance'], microsites_site_country());
            $ret .= preg_replace('#^(.*)( away)$#', '<strong>$1</strong>$2: ', $distance_line);
            #$ret .= "<a href=\"/".$r['ref']."\">".htmlspecialchars($r['title'])."</a>"; # shorter version?
            $ret .= $pledge->new_summary(array('firstperson'=>'includename'));
            $pledges_output[$r['ref']] = 1;

            if ($rss) {
                $rss_items[] = $pledge->rss_entry();
            }

            $ret .= '</li>';
        }
        $ret .= '</ul>';
    }
    return array($ret, $radius);
}

function get_change_radius_link($search, $radius) {
    // Link for changing radius of search
    if (get_http_var("far")) {
        $change_radius_link = "(<a href=\"search?q=".htmlspecialchars($search)."\">"._("decrease distance")."</a>)";
    } else {
        $far_radius = 50;
        if ($radius > $far_radius / 2)
            $far_radius = 100;
        if ($radius > $far_radius / 2)
            $far_radius = 200;
        if ($radius > $far_radius / 2)
            $far_radius = 300;
        $change_radius_link = "(<a href=\"search?q=".htmlspecialchars($search)."&far=$far_radius\">"._("increase distance")."</a>)";
    }
    return $change_radius_link;
}
 
function search($search) {
    global $pb_today, $rss, $rss_items, $pledges_output;
    $success = 0;
    $shown_alert_box = 0;

    if (!$rss) {
        // Blank searches
        if ($search == _('<Enter town or keyword>'))
            $search = "";
        if (!$search) {
            microsites_search_help();
            return;
        }
        $rss_title = sprintf(_("RSS feed of pledges matching '%s'"), htmlspecialchars($search));
       
?><a href="<?=pb_domain_url(array('explicit'=>true, 'path'=>"/rss".$_SERVER['REQUEST_URI']))?>"><img
vspace="5" align="right" border="0" src="/rss.gif" alt="<?=$rss_title ?>" title="<?=$rss_title ?>"></a><?
    }

    // General query
    $pledge_select = "SELECT pledges.*, '$pb_today' <= pledges.date as open,
                (SELECT count(*) FROM signers WHERE pledge_id=pledges.id) AS signers,
                latitude, longitude";

    // Exact pledge reference match
    if (!$rss) {
        $q = db_query("$pledge_select FROM pledges LEFT JOIN location ON location.id = pledges.location_id 
                    WHERE pin is NULL AND ref ILIKE ?", $search);
        if (db_num_rows($q)) {
            $success = 1;
            $r = db_fetch_array($q);
            $pledge = new Pledge($r);
            print sprintf(p(_('Result <strong>exactly matching</strong> pledge <strong>%s</strong>:')), htmlspecialchars($search) );
            print '<ul class="search_results"><li>';
            print $pledge->new_summary(array('firstperson'=>'includename'));
            $pledges_output[$r['ref']] = 1;
            print '</li></ul>';
        }
    }

    // Postcodes
    $location = null;
    $location_description = null;
    $is_postcode = validate_postcode($search);
    $is_partial_postcode = validate_partial_postcode($search);
    if ($is_postcode || $is_partial_postcode)  {
        $success = 1;
        if ($is_partial_postcode) {
            $location = mapit_call("postcode/$search", 'partial');
        } else {
            $location = mapit_call('postcode', $search);
        }
        if (mapit_get_error($location)) {
            if (!$rss)
                print p(_("We couldn't find that postcode, please check it again."));
        } else {
            list($location_results, $radius) = get_location_results($pledge_select, $location['wgs84_lat'], $location['wgs84_lon']);
            if (!$rss) {
                print sprintf(p(_('Results for <strong>open pledges</strong> within %s %s of UK postcode <strong>%s</strong>:')), pb_pretty_distance($radius, microsites_site_country(), false), get_change_radius_link($search, $radius), htmlspecialchars(strtoupper($search)) );
                if ($location_results) {
                    print $location_results;
                } else {
                    print "<ul><li>". _("No nearby open pledges. Why not <a href=\"/new\">make one</a>?")."</li></ul>";
                }
            }
        }
    }

    // Zipcodes
    if (preg_match('#^\d{5}$#', $search) && microsites_site_country() == 'US') {
        $success = 1;
        $zip = lookup_zipcode($search);
        if (isset($zip['lon'])) {
            list($location_results, $radius) = get_location_results($pledge_select, $zip['lat'], $zip['lon']);
            if (!$rss) {
                print sprintf(p(_('Results for <strong>open pledges</strong> within %s %s of US zipcode <strong>%s</strong>:')), pb_pretty_distance($radius, microsites_site_country(), false), get_change_radius_link($search, $radius), htmlspecialchars($search) );
                if ($location_results) {
                    print $location_results;
                } else {
                    print "<ul><li>". _("No nearby open pledges. Why not <a href=\"/new\">make one</a>?")."</li></ul>";
                }
            }
        } elseif (!$rss) {
            print p(_("We couldn't find that zipcode, please try again."));
        }
    }

    // Places
    global $countries_code_to_name;
    $change_country = pb_get_change_country_link(false);
    if (microsites_site_country()) {
        $parts = gaze_controls_split_name($search);
        $all_places = gaze_controls_find_places(microsites_site_country(), null, $parts['name'], 10, 70);

        # If exact matches, just take them!
        $places = array(); $alt_places = array();
        $statecounts = array();
        foreach ($all_places as $p) {
            list($name, $in, $near, $lat, $lon, $state, $score) = $p;
            $match_name = strtolower($name) == strtolower($parts['name']);
            $match = $match_name;
            if ($parts['in']) {
                $match_in = strtolower($in) == strtolower($parts['in']);
                $match &= $match_in;
            }
            if ($parts['near']) {
                $match_near = strtolower($near) == strtolower($parts['near']);
                $match &= $match_near;
            }
            if ($parts['state']) {
                $match_state = strtolower($state) == strtolower($parts['state']);
                $match &= $match_state;
            }
            if ($match) {
                $places[] = $p;
            } elseif (!$match_name || ($parts['in'] && !$match_in) || ($parts['near'] && !$match_near) || ($parts['state'] && !$match_state)) {
                $alt_places[] = $p;
            }
            if (!isset($statecounts[$state])) $statecounts[$state] = array();
            if (!isset($statecounts[$state][$name])) $statecounts[$state][$name] = 0;
            if ($state)
                $statecounts[$state][$name]++;
        }

        if (!$rss && count($places) > 1) {
            $success = 1;
            $out = "";
            usort($places, 'gaze_controls_sort_places');
            foreach ($places as $p) {
                list($name, $in, $near, $lat, $lon, $state, $score) = $p;
                list($desc, $url) = gaze_controls_get_place_details($p, $statecounts, true);
                $out .= '<li><a href="/search?q=' . urlencode($url) . '">' . $desc . '</a></li>';
            }
            echo p(sprintf(_("We found more than one location matching <strong>%s</strong>, %s. Pick your town from the list to see pledges in your area:"),
                htmlspecialchars($search), $countries_code_to_name[microsites_site_country()]));
            echo '<ul>' . $out . '</ul>';
        } elseif (count($places) == 1) {
            $success = 1;
            $out = '';
            list($name, $in, $near, $lat, $lon, $state, $score) = $places[0];
            list($desc, $url) = gaze_controls_get_place_details($places[0], $statecounts, true);
            list($location_results, $radius) = get_location_results($pledge_select, $lat, $lon);
            if (!$rss) {
                # TRANS: For example: "Results for <strong>open pledges</strong> near places matching <strong>Bolton</strong>, United Kingdom:"
                $out .= p(sprintf(_("Results for <strong>open pledges</strong> within %s %s of <strong>%s</strong>, %s:"), pb_pretty_distance($radius, microsites_site_country(), false), get_change_radius_link($search, $radius), htmlspecialchars($desc), $countries_code_to_name[microsites_site_country()]));
                if ($location_results) {
                    $out .= $location_results;
                } else {
                    $out .= "<ul><li>". _("No nearby open pledges. Why not <a href=\"/new\">make one</a>?")."</li></ul>";
                }
            }
            print $out;
        }
        if (!$rss && $alt_places) {
            if ($places)
                print '<p><small>' . sprintf(_('Other similar places to %s'), htmlspecialchars($search)) . ': ';
            else
                print '<p>' . _('Did you mean:') . ' ';
            $descs = array();
            usort($alt_places, 'gaze_controls_sort_places');
            foreach ($alt_places as $p) {
                list($name, $in, $near, $lat, $lon, $state, $score) = $p;
                list($desc, $url) = gaze_controls_get_place_details($p, $statecounts, true);
                $descs[] = '<a href="/search?q=' . urlencode($url) . '">' . $desc . '</a>';
            }
            print join('; ', $descs);
            if ($places)
                print '</small></p>';
            else
                print '?</p>';
        }
    } elseif ($change_country) {
        if (!$rss)
            print p(sprintf(_("To search for a town, please first %s."), $change_country));
    }

    // Searching for text in pledges - stored in strings $open, $closed printed later
    global $backpage_clause;
    $q = db_query($pledge_select . ' FROM pledges 
                LEFT JOIN location ON location.id = pledges.location_id 
                WHERE pin IS NULL 
                    '.$backpage_clause.'
                    AND (title ILIKE \'%\' || ? || \'%\' OR 
                         detail ILIKE \'%\' || ? || \'%\' OR 
                         identity ILIKE \'%\' || ? || \'%\' OR 
                         type ILIKE \'%\' || ? || \'%\' OR 
                         ref ILIKE \'%\' || ? || \'%\')
                    AND lower(ref) <> ?
                ORDER BY date DESC', array($search, $search, $search, $search, $search, $search));
    $closed = ''; $open = '';
    if (db_num_rows($q)) {
        $success = 1;
        while ($r = db_fetch_array($q)) {
            if (array_key_exists($r['ref'], $pledges_output))
                continue;
            $pledge = new Pledge($r);
            $text = '<li>' . $pledge->new_summary(array('firstperson'=>'includename')) . '</li>';
            $pledges_output[$r['ref']] = 1;
            if ($r['open']=='t') {
                $open .= $text;
            } else {
                $closed .= $text;
            }
            if ($rss && $r['open'] == 't') {
                $rss_items[] = $pledge->rss_entry();
            }
        }
    }

    // No more search types that go into RSS (only pledges do that for now)
    if ($rss)
        return;

    // Open pledges
    if ($open) {
        print sprintf(p(_('Results for <strong>open pledges</strong> matching <strong>%s</strong>:')), htmlspecialchars($search) );
        print '<ul class="search_results">' . $open . '</ul>';
    }

    // Closed pledges
    if ($closed) {
        print sprintf(p(_('Results for <strong>closed pledges</strong> matching <strong>%s</strong>:')), htmlspecialchars($search) );
        print '<ul class="search_results">' . $closed . '</ul>';
    }

    // Comments
    $q = db_query('SELECT comment.id,
                          extract(epoch from ms_current_timestamp()-whenposted) as whenposted,
                          text,comment.name,website,ref 
                   FROM comment,pledges 
                   WHERE pin IS NULL
                    '.$backpage_clause.'
                        AND comment.pledge_id = pledges.id 
                        AND NOT comment.ishidden 
                        AND text ILIKE \'%\' || ? || \'%\'
                   ORDER BY whenposted', array($search));
    if (db_num_rows($q)) {
        $success = 1;
        print sprintf(p(_("Results for <strong>comments</strong> matching <strong>%s</strong>:")), htmlspecialchars($search) );
        print '<ul class="search_results">';
        while($r = db_fetch_array($q)) {
            print '<li>';
            print comments_summary($r, $search);
            print '</li>';
        }
        print '</ul>';
    }

    // Signers and creators (NOT person table, as we only search for publicly visible names)
    $people = array();
    $q = db_query('SELECT ref, title, name FROM pledges
    WHERE pin IS NULL ' . $backpage_clause .
    ' AND name ILIKE \'%\' || ? || \'%\' ORDER BY name', $search);
    while ($r = db_fetch_array($q)) {
        if (preg_match("#\b$search\b#i", $r['name']))
            $people[$r['name']][] = array($r['ref'], $r['title'], 'creator');
    }
    $q = db_query('SELECT ref, title, signers.name FROM signers,pledges
    WHERE showname AND pin IS NULL AND signers.pledge_id = pledges.id
    ' . $backpage_clause . ' AND signers.name ILIKE \'%\' || ? || \'%\' ORDER BY name',
    $search);
    while ($r = db_fetch_array($q)) {
        if (preg_match("#\b$search\b#i", $r['name']))
            $people[$r['name']][] = array($r['ref'], $r['title'], 'signer');
    }
    if (sizeof($people)) {
        $success = 1;
        print sprintf(p(_('Results for <strong>people</strong> matching <strong>%s</strong>:')), htmlspecialchars($search) );
        print '<dl>';
        uksort($people, 'strcoll');
        foreach ($people as $name => $array) {
            print '<dt><b>'.htmlspecialchars($name). '</b></dt> <dd>';
            foreach ($array as $item) {
                print '<dd>';
                print '<a href="/' . $item[0] . '">' . $item[1] . '</a>';
                if ($item[2] == 'creator') print _(" (creator)");
                print '</dd>';
            }
        }
        print '</dl>';
    }

    if (!$success) {
        print sprintf(p(_('Sorry, we could find nothing that matched "%s".')), htmlspecialchars($search) );
    }
?>
<form id="search" accept-charset="utf-8" action="/search" method="get" style="text-align:center; padding: 1em 0; margin-top: 2em">
<label for="q"><?=_('Search for pledges:') ?></label>
<input type="text" id="q" name="q" size="25" value="<?=htmlspecialchars($search)?>">
<input type="submit" value="<?=_('Search') ?>">
</form>
<?
    return $shown_alert_box;
}

