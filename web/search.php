<?
// search.php:
// Search for a pledge.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: search.php,v 1.23 2005-09-10 12:32:25 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/comments.php';
require_once '../../phplib/mapit.php';

page_header(_("Search Results"));
search();
page_footer();

function get_location_results($pledge_select, $lat, $lon) {
    global $pb_today;
    $q = db_query($pledge_select . ", distance
                FROM pledge_find_nearby(?,?,?) AS nearby 
                LEFT JOIN pledges ON nearby.pledge_id = pledges.id
                WHERE 
                    pin IS NULL AND
                    pb_pledge_prominence(pledges.id) <> 'backpage' AND 
                    '$pb_today' <= pledges.date 
                ORDER BY distance", array($lat, $lon, 50)); // 50 miles. XXX Should be indexed with wgs84_lat, wgs84_lon
    $ret = "";
    if (db_num_rows($q)) {
        $success = 1;
        $ret .= '<ul>';
        while ($r = db_fetch_array($q)) {
            $ret .= '<li>';
            if (round($r['distance'],0) < 1) 
                $ret .= '<strong>under 1 km</strong> away: ';
            else
                $ret .= '<strong>' . round($r['distance'],0) . " km</strong> away: ";
            #$ret .= "<a href=\"/".$r['ref']."\">".htmlspecialchars($r['title'])."</a>"; # shorter version?
            $ret .= pledge_summary($r, array('html'=>true, 'href'=>$r['ref']));
            $ret .= '</li>';
        }
        $ret .= '</ul>';
    }
    return $ret;
}

function search() {
    global $pb_today;
    $search = trim(get_http_var('q'));
    $success = 0;

    // Exact pledge reference match
    $pledge_select = "SELECT pledges.*, '$pb_today' <= pledges.date as open,
                (SELECT count(*) FROM signers WHERE pledge_id=pledges.id) AS signers,
                date - '$pb_today' AS daysleft";

    $q = db_query("$pledge_select FROM pledges WHERE pin is NULL AND ref ILIKE ?", $search);
    if (db_num_rows($q)) {
        $success = 1;
        $r = db_fetch_array($q);
        print sprintf(p(_('Result <strong>exactly matching</strong> pledge <strong>%s</strong>:')), htmlspecialchars($search) );
        print '<ul><li>';
        print pledge_summary($r, array('html'=>true, 'href'=>$r['ref']));
        print '</li></ul>';
    }

    // Postcodes
    $location = null;
    $location_description = null;
    if (validate_postcode($search))  {
        $success = 1;
        $location = mapit_get_location($search);
        if (mapit_get_error($location)) {
            print p(_("We couldn't find that postcode, please check it again."));
        } else {
            $location_results = get_location_results($pledge_select, $location['wgs84_lat'], $location['wgs84_lon']);
            print sprintf(p(_('Results for <strong>open pledges near</strong> UK postcode <strong>%s</strong>:')), htmlspecialchars(strtoupper($search)) );
            if ($location_results) {
                print $location_results;
            } else {
                print "<ul><li>". _("No nearby open pledges. Why not <a href=\"/new\">make one</a>?")."</li></ul>";
            }
        }
    }
 
    // Searching for text in pledges - stored in strings $open, $closed printed later
    $q = db_query($pledge_select . ' FROM pledges 
                WHERE pin IS NULL 
                    AND pb_pledge_prominence(pledges.id) <> \'backpage\'
                    AND (title ILIKE \'%\' || ? || \'%\' OR 
                         detail ILIKE \'%\' || ? || \'%\' OR 
                         ref ILIKE \'%\' || ? || \'%\')
                    AND ref NOT ILIKE ?
                ORDER BY date DESC', array($search, $search, $search, $search));
    $closed = ''; $open = '';
    if (db_num_rows($q)) {
        $success = 1;
        while ($r = db_fetch_array($q)) {
            $text = '<li>';
            $text .= pledge_summary($r, array('html'=>true, 'href'=>$r['ref']));
            $text .= '</li>';
            if ($r['open']=='t') {
                $open .= $text;
            } else {
                $closed .= $text;
            }
        }
    }

    // Places
    global $site_country, $countries_code_to_name;
    $change_country = pb_get_change_country_link();
    if ($site_country) {
        $places = gaze_find_places($site_country, null, $search, 5, 70);
        if (gaze_check_error($places))
            err('Error doing place search');
        if (count($places) > 0) {
            $success = 1;
            if (count($places) > 1) {
                print p(sprintf(_("Results for <strong>open pledges near</strong> places matching <strong>%s</strong>, %s (%s):"), htmlspecialchars($search), $countries_code_to_name[$site_country], $change_country));
                print "<ul>";
            }
            foreach ($places as $p) {
                list($name, $in, $near, $lat, $lon, $st, $score) = $p;
                $desc = $name;
                if ($in) $desc .= ", $in";
                if ($st) $desc .= ", $st";
                if ($near) $desc .= " (" . _('near') . " " . htmlspecialchars($near) . ")";
                $location_results = get_location_results($pledge_select, $lat, $lon);
                if (count($places) > 1) 
                    print "<li>$desc";
                else
                    print p(sprintf(_("Results for <strong>open pledges near %s</strong>, %s (%s):"), htmlspecialchars($desc), $countries_code_to_name[$site_country], $change_country));
                if ($location_results) {
                    print $location_results;
                } else {
                    print "<ul><li>". _("No nearby open pledges. Why not <a href=\"/new\">make one</a>?")."</li></ul>";
                }
                if (count($places) > 1) print "</li>";
            }
            if (count($places) > 1) print "</ul>";
        }
    }

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
        ksort($people);
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

