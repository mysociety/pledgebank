<?
// search.php:
// Search for a pledge.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: search.php,v 1.49 2006-04-05 08:56:44 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/comments.php';
require_once '../../phplib/mapit.php';
require_once "../../phplib/votingarea.php";

$search = trim(get_http_var('q', true));
if (!$search) $search = trim(get_http_var('s', true));
$rss = get_http_var('rss') ? true : false;
$rss_items = array();
$pledges_output = array();
$heading = sprintf(_("Search results for '%s'"), htmlspecialchars($search));
if ($rss) 
    rss_header($heading, $heading, array());
else 
    page_header($heading,
            array('rss'=> array(
                    $heading => pb_domain_url(array('explicit'=>true, 'path'=>'/rss'.$_SERVER['REQUEST_URI']))
                    ))
    );
search($search);
if ($rss) {
    function compare_creationtime($a, $b) {
        return strcmp($b['creationtime'], $a['creationtime']);
    }
    array_unique($rss_items);
    usort($rss_items, "compare_creationtime");
    rss_footer($rss_items);
}
else
    page_footer();

function get_location_results($pledge_select, $lat, $lon) {
    global $pb_today, $rss_items, $rss, $pledges_output;
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
                    pin IS NULL AND
                    pledges.cached_prominence <> 'backpage' AND 
                    '$pb_today' <= pledges.date 
                ORDER BY distance", array($lat, $lon, $radius)); 
    locale_pop();
    $ret = "";
    if (db_num_rows($q)) {
        $success = 1;
        $ret .= '<ul>';
        while ($r = db_fetch_array($q)) {
            $ret .= '<li>';
            $distance_line = pb_pretty_distance($r['distance'], microsites_site_country());
            $ret .= preg_replace('#^(.*)( away)$#', '<strong>$1</strong>$2: ', $distance_line);
            #$ret .= "<a href=\"/".$r['ref']."\">".htmlspecialchars($r['title'])."</a>"; # shorter version?
            $ret .= pledge_summary($r, array('html'=>true, 'href'=>$r['ref']));
            $pledges_output[$r['ref']] = 1;

            if ($rss) {
                $pledge = new Pledge($r['ref']);
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

    if (!$rss) {
        // Blank searches
        if ($search == _('<Enter town or keyword>'))
            $search = "";
        if (!$search) {
            print p(_('You can search for:'));
            print "<ul>";
            print li(_("The name of a <strong>town or city</strong> near you, to find pledges in your area"));
            print li(_("A <strong>postcode</strong> or postcode area, if you are in the United Kingdom"));
            print li(_("<strong>Any words</strong>, to find pledges and comments containing those words"));
            print li(_("The name of <strong>a person</strong>, to find pledges they made or signed publically"));
            print "</ul>";
            return;
        }
    }
       
    // Link to RSS feed
    if (!$rss) {
?><a href="<?=pb_domain_url(array('explicit'=>true, 'path'=>"/rss".$_SERVER['REQUEST_URI']))?>"><img align="right" border="0" src="/rss.gif" alt="<?=_('RSS feed of search for \'') . htmlspecialchars($search) ."'" ?>"></a><?
    }

    // General query
    $pledge_select = "SELECT pledges.*, '$pb_today' <= pledges.date as open,
                (SELECT count(*) FROM signers WHERE pledge_id=pledges.id) AS signers,
                date - '$pb_today' AS daysleft, latitude, longitude";

    // Exact pledge reference match
    if (!$rss) {
        $q = db_query("$pledge_select FROM pledges LEFT JOIN location ON location.id = pledges.location_id 
                    WHERE pin is NULL AND ref ILIKE ?", $search);
        if (db_num_rows($q)) {
            $success = 1;
            $r = db_fetch_array($q);
            print sprintf(p(_('Result <strong>exactly matching</strong> pledge <strong>%s</strong>:')), htmlspecialchars($search) );
            print '<ul><li>';
            print pledge_summary($r, array('html'=>true, 'href'=>$r['ref']));
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
                print sprintf(p(_('Results for <strong>open pledges</strong> within %s %s of UK postcode <strong>%s</strong>:')), pb_pretty_distance($radius, 'GB', false), get_change_radius_link($search, $radius), htmlspecialchars(strtoupper($search)) );
                if ($location_results) {
                    print $location_results;
                } else {
                    print "<ul><li>". _("No nearby open pledges. Why not <a href=\"/new\">make one</a>?")."</li></ul>";
                }
            }
        }
    }


    // Places
    global $countries_code_to_name;
    $change_country = pb_get_change_country_link();
    if (microsites_site_country()) {
        $places = pb_gaze_find_places(microsites_site_country(), null, $search, 5, 70);
        if (count($places) > 0) {
            $success = 1;
            $out = "";
            $max_radius = -1;
            foreach ($places as $p) {
                list($name, $in, $near, $lat, $lon, $st, $score) = $p;
                $desc = $name;
                if ($in) $desc .= ", $in";
                if ($st) $desc .= ", $st";
                if ($near) $desc .= " (" . _('near') . " " . htmlspecialchars($near) . ")";
                list ($location_results, $radius) = get_location_results($pledge_select, $lat, $lon);
                $max_radius = max($radius, $max_radius);
                if (!$rss) {
                    if (count($places) > 1) 
                        $out .= "<li>$desc";
                    else
                        # TRANS: For example: "Results for <strong>open pledges</strong> near places matching <strong>Bolton</strong>, United Kingdom (<a href="....">change country</a>):"
			$out .= p(sprintf(_("Results for <strong>open pledges</strong> within %s %s of <strong>%s</strong>, %s (%s):"), pb_pretty_distance($radius,microsites_site_country(), false), get_change_radius_link($search, $radius), htmlspecialchars($desc), $countries_code_to_name[microsites_site_country()], $change_country));
                    if ($location_results) {
                        $out .= $location_results;
                    } else {
                        $out .= "<ul><li>". _("No nearby open pledges. Why not <a href=\"/new\">make one</a>?")."</li></ul>";
                    }
                    if (count($places) > 1) $out .= "</li>";
                }
            }
            if (!$rss && count($places) > 1) {
		print p(sprintf(_("Results for <strong>open pledges near</strong> %s places matching <strong>%s</strong>, %s (%s):"), get_change_radius_link($search, $max_radius), htmlspecialchars($search), $countries_code_to_name[microsites_site_country()], $change_country));
                print "<ul>";
            }
            print $out;
            if (!$rss) {
                if (count($places) > 1) print "</ul>";
                pb_view_local_alert_quick_signup("localsignupsearchpage", 
                    array('newflash'=>false,
                          'place'=>$search));
            }
        }
    } else {
        if (!$rss)
            print p(sprintf(_("To search for a town, please first %s."), $change_country));
    }

    // Searching for text in pledges - stored in strings $open, $closed printed later
    $q = db_query($pledge_select . ' FROM pledges 
                LEFT JOIN location ON location.id = pledges.location_id 
                WHERE pin IS NULL 
                    AND pledges.cached_prominence <> \'backpage\'
                    AND (title ILIKE \'%\' || ? || \'%\' OR 
                         detail ILIKE \'%\' || ? || \'%\' OR 
                         identity ILIKE \'%\' || ? || \'%\' OR 
                         type ILIKE \'%\' || ? || \'%\' OR 
                         ref ILIKE \'%\' || ? || \'%\')
                    AND ref NOT ILIKE ?
                ORDER BY date DESC', array($search, $search, $search, $search, $search, $search));
    $closed = ''; $open = '';
    if (db_num_rows($q)) {
        $success = 1;
        while ($r = db_fetch_array($q)) {
            if (array_key_exists($r['ref'], $pledges_output))
                continue;
        
            $text = '<li>';
            $text .= pledge_summary($r, array('html'=>true, 'href'=>$r['ref']));
            $pledges_output[$r['ref']] = 1;
            $text .= '</li>';
            if ($r['open']=='t') {
                $open .= $text;
            } else {
                $closed .= $text;
            }
            if ($rss && $r['open'] == 't') {
                $pledge = new Pledge($r);
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
        print '<ul>' . $open . '</ul>';
    }

    // Closed pledges
    if ($closed) {
        print sprintf(p(_('Results for <strong>closed pledges</strong> matching <strong>%s</strong>:')), htmlspecialchars($search) );
        print '<ul>' . $closed . '</ul>';
    }

    // Comments
    $comments_to_show = 10;
    $q = db_query('SELECT comment.id,
                          extract(epoch from whenposted) as whenposted,
                          text,comment.name,website,ref 
                   FROM comment,pledges 
                   WHERE pin IS NULL
                        AND comment.pledge_id = pledges.id 
                        AND NOT ishidden 
                        AND text ILIKE \'%\' || ? || \'%\'
                   ORDER BY whenposted DESC', array($search));
    if (db_num_rows($q)) {
        $success = 1;
        print sprintf(p(_("Results for <strong>comments</strong> matching <strong>%s</strong>:")), htmlspecialchars($search) );
        print '<ul>';
        while($r = db_fetch_array($q)) {
            print '<li>';
            print comments_summary($r);
            print '</li>';
        }
        print '</ul>';
    }

    // Signers and creators (NOT person table, as we only search for publically visible names)
    $people = array();
    $q = db_query('SELECT ref, title, name FROM pledges WHERE pin IS NULL AND name ILIKE \'%\' || ? || \'%\' ORDER BY name', $search);
    while ($r = db_fetch_array($q)) {
        $people[$r['name']][] = array($r['ref'], $r['title'], 'creator');
    }
    $q = db_query('SELECT ref, title, signers.name FROM signers,pledges WHERE showname AND pin IS NULL AND signers.pledge_id = pledges.id AND signers.name ILIKE \'%\' || ? || \'%\' ORDER BY name', $search);
    while ($r = db_fetch_array($q)) {
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
}

