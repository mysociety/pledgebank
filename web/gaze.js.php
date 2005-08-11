<?
// gaze.js.php:
// 
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: gaze.js.php,v 1.2 2005-08-11 22:49:33 matthew Exp $

require_once '../phplib/pb.php';
require_once '../../phplib/gaze.php';

header('Content-Type: text/javascript');

?>
// gaze.js
//
// Countries which Gaze has local placenames for.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org

var gaze_countries = new Array()
<?

$countries_with_gazetteer = gaze_get_find_places_countries();
gaze_check_error($countries_with_gazetteer);
foreach ($countries_with_gazetteer as $country) {
    print "gaze_countries['$country'] = 1\n";
}

