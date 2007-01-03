<?
/*
 * person.csv.php:
 * Admin page for LiveSimply, to output CSV of promise creators
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: person.csv.php,v 1.2 2007-01-03 15:49:08 matthew Exp $
 * 
 */

require_once "../conf/general";
require_once "../phplib/pb.php";

// Originally (at least) for Live Simply Promise
function escape_csv($v) {
    $v = str_replace('"', '\"', $v);
    return '"'.$v.'"';
}

header("Content-Type: application/csv; charset=utf-8");

// XXX pledge creators only for now
$q = db_query("SELECT distinct(person.*) FROM person, pledges 
    WHERE pledges.person_id = person.id ORDER BY id");
print "name,email,address_1,address_2,address_3,town,county,postcode,country\n";
while ($r = db_fetch_array($q)) {
    print escape_csv($r['name']) . ",";
    print escape_csv($r['email']) . ",";
    print escape_csv($r['address_1']) . ",";
    print escape_csv($r['address_2']) . ",";
    print escape_csv($r['address_3']) . ",";
    print escape_csv($r['address_town']) . ",";
    print escape_csv($r['address_county']) . ",";
    print escape_csv($r['address_postcode']) . ",";
    print escape_csv($r['address_country']);
    print "\n";
}


?>
