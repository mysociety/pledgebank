<?
// gaze.js.php:
// 
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: gaze.js.php,v 1.1 2005-08-09 15:55:41 francis Exp $

require_once '../phplib/pb.php';
require_once '../../phplib/gaze.php';


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

