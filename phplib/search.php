<?
// search.php:
// Search for a pledge.
//
// Copyright (c) 2008 UK Citizens Online Democracy. All rights reserved.
// Email: angie@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: search.php,v 1.1 2008-01-28 13:32:01 angie Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/comments.php';
require_once '../commonlib/phplib/mapit.php';
require_once "../commonlib/phplib/votingarea.php";
require_once "../commonlib/phplib/countries.php";

global $pb_today;
global $pledge_select;
global $backpage_clause;
global $founditems;
global $subset_clause;

$pledge_select = "SELECT pledges.*, '$pb_today' <= pledges.date as open,
            (SELECT count(*) FROM signers WHERE pledge_id=pledges.id) AS signers,
            latitude, longitude";
$backpage_clause = " AND (".microsites_normal_prominences()." OR cached_prominence = 'frontpage') ";

if (get_http_var('backpage')) {
    $backpage_clause = '';
}

$founditems = array();

function search($params) {
    global $subset_clause, $pb_today;
    $search = (isset($params['search'])) ? trim($params['search']) : '';
    $rss = (isset($params['rss']) && $params['rss']) ? 1 : 0;
    $pledgestatus = (isset($params['pledgestatus'])) ? trim($params['pledgestatus']) : '';

    if ($pledgestatus == 'open') {$subset_clause = " AND pledges.date >= '$pb_today' ";}
    if ($pledgestatus == 'closed') {$subset_clause = " AND pledges.date <= '$pb_today' ";}
    
    $postcodepledges = array();
    $retary = array();

    $exacts = array();
    if (!$rss) {
        $exacts = search_pledge_by_ref($search);
    }    
    $postcodes = search_pledge_by_postcode($search);
    $zipcodes = search_pledge_by_zipcode($search);
    $stringsearches = search_pledge_by_string($search, $pledgestatus);
    
    $retary = array(
        'exact' => $exacts['pledges'],
        'exact_message' => $exacts['message'],
        'exact_errors' => $exacts['errors'],
        'postcode' => $postcodes['pledges'],
        'postcode_message' => $postcodes['message'],
        'postcode_errors' => $postcodes['errors'],
        'zipcode' => $zipcodes['pledges'],
        'zipcode_message' => $zipcodes['message'],
        'zipcode_errors' => $zipcodes['errors'],
        'bystring_pledges_open' => $stringsearches['openpledges'],
        'bystring_message_open' => $stringsearches['openmessage'],
        'bystring_pledges_closed' => $stringsearches['closedpledges'],
        'bystring_message_closed' => $stringsearches['closedmessage'],
        'notfound_message' => '',
    );
    
    if (!$exacts['pledges'] && !$postcodes['pledges'] && !$zipcodes['pledges'] && !$stringsearches['openpledges'] && !$stringsearches['closedpledges']) {
        $retary['notfound_message'] = _('Sorry, we could find anything that matched "' . $search . '".');
    }
    return $retary;
}

function search_pledge_by_ref($search) {
    global $pb_today, $pledge_select, $founditems;
    $output = array(
        'pledges' => array(), 
        'message' => '', 
        'errors' => '',
    );
        $pledges = array();
        $q = db_query("$pledge_select FROM pledges LEFT JOIN location ON location.id = pledges.location_id 
                    WHERE pin is NULL AND ref ILIKE ?", $search);
        if (db_num_rows($q)) {
            $success = 1;
            $r = db_fetch_array($q);
            $pledge = new Pledge($r);
            $pledges[] = $pledge;
            $output['message'] = sprintf(_('Result <strong>exactly matching</strong> pledge <strong>%s</strong>:'), htmlspecialchars($search) );
            $founditems[$r['ref']] = 1;
        }
        $output['pledges'] = $pledges;
        $output['errors'] = '';
    return $output;
}

function search_pledge_by_string($search) {
    global $pledge_select, $backpage_clause, $subset_clause, $founditems;

    $stringoutput = array(
        'closedpledges' => array(), 
        'openpledges' => array(), 
        'closedmessage' => '', 
        'openmessage' => '', 
        'errors' => '',
    );
    $pledges = array();
                    
    $q = db_query($pledge_select . ' FROM pledges 
                LEFT JOIN location ON location.id = pledges.location_id 
                WHERE pin IS NULL 
                    '. $backpage_clause . $subset_clause .'
                    AND (title ILIKE \'%\' || ? || \'%\' OR 
                         detail ILIKE \'%\' || ? || \'%\' OR 
                         identity ILIKE \'%\' || ? || \'%\' OR 
                         type ILIKE \'%\' || ? || \'%\' OR 
                         ref ILIKE \'%\' || ? || \'%\')
                    AND lower(ref) <> ?
                ORDER BY date DESC', array($search, $search, $search, $search, $search, $search));
    
    $closed = array(); 
    $open = array(); 

    if (db_num_rows($q)) {
        while ($r = db_fetch_array($q)) {
            if (array_key_exists($r['ref'], $founditems))
                continue;
            $pledge = new Pledge($r);
            $founditems[$r['ref']] = 1;
            if ($r['open']=='t') {
                $open[] = $pledge;
            } else {
                $closed[] = $pledge;
            }
        }
    }

    if ($open) {
        $stringoutput['openmessage'] = sprintf(_('Results for <strong>open pledges</strong> matching <strong>%s</strong>:'), htmlspecialchars($search) );
        $stringoutput['openpledges'] = $open;
    }
    if ($closed) {
        $stringoutput['closedmessage'] = sprintf(_('Results for <strong>closed pledges</strong> matching <strong>%s</strong>:'), htmlspecialchars($search) );    
        $stringoutput['closedpledges'] = $closed;
    }

  return $stringoutput;
}

function search_pledge_by_postcode($search) {
    global $pledge_select;
    $pcoutput = array(
        'pledges' => array(), 
        'message' => '', 
        'errors' => '',
    );
    $pledges = array();

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
               $pcoutput['errors'] = "We couldn't find that postcode, please check it again.";
        } else {
            //list($location_results, $radius) = get_location_results($pledge_select, $location['wgs84_lat'], $location['wgs84_lon']);
            $locationret = get_location_results($pledge_select, $location['wgs84_lat'], $location['wgs84_lon']);
            $radius = $locationret['radius'];
            if ($locationret['pledges']) {
                $pcmessage = sprintf(_('Results for <strong>open pledges</strong> within %s %s of UK postcode <strong>%s</strong>:'), pb_pretty_distance($radius, microsites_site_country(), false), get_change_radius_link($search, $radius), htmlspecialchars(strtoupper($search)));
                $pcoutput['message'] = $pcmessage;
                $pcoutput['pledges'] = $locationret['pledges'];
            } else {
                $pcoutput['message'] =  _("No nearby open pledges. Why not <a href=\"/new\">make one</a>?");
            }
        }
    }
  return $pcoutput;
}

function search_pledge_by_zipcode($search) {
    global $pledge_select;
    $zipoutput = array(
        'pledges' => array(), 
        'message' => '', 
        'errors' => '',
    );
    $pledges = array();

    if (preg_match('#^\d{5}$#', $search) && microsites_site_country() == 'US') {
        $zip = lookup_zipcode($search);
        if (isset($zip['lon'])) {
            $locationret = get_location_results($pledge_select, $zip['lat'], $zip['lon']);
            $radius = $locationret['radius'];
            if ($locationret) {
                $zipoutput['message'] = sprintf(_('Results for <strong>open pledges</strong> within %s %s of US zipcode <strong>%s</strong>:'), pb_pretty_distance($radius, microsites_site_country(), false), get_change_radius_link($search, $radius), htmlspecialchars($search) );
                $zipoutput['pledges'] = $locationret['pledges'];                                
            } else {
                $zipoutput['errors'] = "We couldn't find that zipcode, please try again.";
            }
        }
    }
  return $zipoutput;
}


function get_location_results($pledge_select, $lat, $lon) {
    global $pb_today, $backpage_clause, $subset_clause, $founditems; // $rss_items, $rss, $pledges_output ;
    
    $pledges = array();
    $locres = array();
    
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
                    ". $backpage_clause . $subset_clause ."
                    AND '$pb_today' <= pledges.date 
                ORDER BY distance", array($lat, $lon, $radius)); 
    locale_pop();
    //$ret = '';
    if (db_num_rows($q)) {
        while ($r = db_fetch_array($q)) {
            $pledge = new Pledge($r);
            $pledges[] = $pledge;
            $founditems[$r['ref']] = 1;          
            /*$ret .= '<li>';
            $distance_line = pb_pretty_distance($r['distance'], microsites_site_country());
            $ret .= preg_replace('#^(.*)( away)$#', '<strong>$1</strong>$2: ', $distance_line);
            $ret .= "<a href=\"/".$r['ref']."\">".htmlspecialchars($r['title'])."</a>"; # shorter version?
            $ret .= $pledge->new_summary(array('firstperson'=>'includename'));
            $pledges_output[$r['ref']] = 1;

            if ($rss) {
                $rss_items[] = $pledge->rss_entry();
            }

            $ret .= '</li>';
            */
        }
        $locres['pledges'] = $pledges;
        $locres['radius'] = $radius;
        $locres['errors'] = '';
        //$ret .= '</ul>';
    }
    
    return $locres;
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
