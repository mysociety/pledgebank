<?
// ajax-gaze.php
// 
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ajax-gaze.php,v 1.1 2006-08-24 11:11:15 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/gaze-controls.php';

#page_check_ref(get_http_var('ref'));
#$p = new Pledge(get_http_var('ref'));

$location = gaze_controls_get_location(array('townonly'=>true));
if ($location['country'] && $location['place']) {
    $location['places'] = gaze_controls_find_places($location['country'], $location['state'], $location['place'], $gaze_controls_nearby_distance, 0);
    gaze_controls_print_places_choice($location['places'], $location['place'], $location['gaze_place']);
} else {
    print "ERROR: no country / place";
}

