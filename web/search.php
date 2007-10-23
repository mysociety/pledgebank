<?
// search.php:
// Search for a pledge.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: search.php,v 1.67 2007-10-23 14:48:03 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/comments.php';
require_once '../../phplib/mapit.php';
require_once "../../phplib/votingarea.php";
require_once "../../phplib/countries.php";

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
    array_unique($rss_items);
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
        $location = mapit_get_location($search, $is_partial_postcode ? 1 : 0);
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
        $key = OPTION_GOOGLE_MAPS_API_KEY;
        $url = 'http://maps.google.com/maps/geo?key=' . $key . '&q=' . $search;
        #$url = 'http://ws.geonames.org/postalCodeSearch?country=US&postalcode=' . $search;
        $f = @file_get_contents($url);
        #if (preg_match('#<lat>(.*?)</lat>\s*<lng>(.*?)</lng>#', $f, $m)) {
        if (preg_match('#"coordinates":\[(.*?),(.*?),#', $f, $m)) {
            #$lat = $m[1]; $lon = $m[2];
            $lon = $m[1]; $lat = $m[2];
            list($location_results, $radius) = get_location_results($pledge_select, $lat, $lon);
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
    global $countries_code_to_name, $countries_name_to_statecode;
    $change_country = pb_get_change_country_link(false);
    if (microsites_site_country()) {
        preg_match('#^(.*?)(?:, (.*?))?(?:, (.*?))?(?: \((.*?)\))?$#', $search, $m);
        $parts = array(
            'name' => $m[1],
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
        $all_places = gaze_find_places(microsites_site_country(), null, $parts['name'], 5, 70);
        gaze_check_error($all_places);

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
	    if (!isset($statecounts[$state])) $statecounts[$state] = 0;
            if ($state)
                $statecounts[$state]++;
        }

        if (!$rss && count($places) > 1) {
            $success = 1;
            $out = "";
            if (microsites_local_alerts()) {
                print '<div id="local_alert_search">';
                pb_view_local_alert_quick_signup("localsignupsearchpage", 
                    array('newflash'=>false, 'place'=>$parts['name'])); # XXX Should be whole thing, but /alert can't yet cope
                $shown_alert_box = true;
                print '</div>';
            }
            usort($places, 'by_state');
            foreach ($places as $p) {
                list($name, $in, $near, $lat, $lon, $state, $score) = $p;
                list($desc, $url) = display_place_name($name, $in, $near, $state, $statecounts[$state]);
                $out .= '<li><a href="/search?q=' . urlencode($url) . '">' . $desc . '</a></li>';
            }
            echo p(sprintf(_("We found more than one location matching <strong>%s</strong>, %s:"),
                htmlspecialchars($search), $countries_code_to_name[microsites_site_country()]));
            echo '<ul>' . $out . '</ul>';
        } elseif (count($places) == 1) {
            $success = 1;
            $out = '';
            if (!$rss) {
                if (microsites_local_alerts()) {
                    print '<div id="local_alert_search">';
                    pb_view_local_alert_quick_signup("localsignupsearchpage", 
                        array('newflash'=>false, 'place'=>$parts['name']));
                    $shown_alert_box = true;
                    print '</div>';
                }
            }
            list($name, $in, $near, $lat, $lon, $state, $score) = $places[0];
	    list($desc, $url) = display_place_name($name, $in, $near, $state, $statecounts[$state]);
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
	    usort($alt_places, 'by_state');
            foreach ($alt_places as $p) {
                list($name, $in, $near, $lat, $lon, $state, $score) = $p;
		list($desc, $url) = display_place_name($name, $in, $near, $state, $statecounts[$state]);
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
                        AND NOT ishidden 
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
    global $microsite; # XXX
    if ($microsite == 'o2') {
        $q = db_query("SELECT ref, title, pledges.name FROM pledges, person
        WHERE pledges.person_id = person.id $backpage_clause
          AND (pledges.name ILIKE '%' || ? || '%'
          OR person.email ILIKE '%' || ? || '%') ORDER BY pledges.name", $search, $search);
        while ($r = db_fetch_array($q)) {
            $people[$r['name']][] = array($r['ref'], $r['title'], 'creator');
        }
        $q = db_query("SELECT ref, title, signers.name FROM signers,pledges, person
        WHERE showname AND signers.pledge_id = pledges.id
        AND signers.person_id = person.id
        $backpage_clause AND (signers.name ILIKE '%' || ? || '%'
        OR person.email ILIKE '%' || ? || '%') ORDER BY signers.name",
        $search, $search);
        while ($r = db_fetch_array($q)) {
            $people[$r['name']][] = array($r['ref'], $r['title'], 'signer');
        }
    } else {
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

function display_place_name($name, $in, $near, $state, $statecount) {
    global $countries_statecode_to_name;
    $desc = $name;
    if ($in && $statecount>1) $desc .= ", $in";
    if ($state) {
        $desc .= ', ';
        if (isset($countries_statecode_to_name[microsites_site_country()][$state]))
            $desc .= $countries_statecode_to_name[microsites_site_country()][$state];
        else
            $desc .= $state;
    }
    $url = $desc;
    if ($near) {
        $desc .= " (" . _('near') . " " . htmlspecialchars($near) . ")";
        $url .= " ($near)";
    }
    return array($desc, $url);
}

function by_state($a, $b) {
    if ($a[5] > $b[5]) return 1;
    elseif ($a[5] < $b[5]) return -1;
    return 0;
}

