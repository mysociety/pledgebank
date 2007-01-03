<?
/*
 * person.csv.php:
 * Admin page for LiveSimply, to output CSV of promise creators
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: person.csv.php,v 1.5 2007-01-03 19:13:19 matthew Exp $
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
$q = db_query("SELECT person.name, person.email, address_1, address_2, address_3,
    address_town, address_county, address_postcode, address_country, ref, title
    FROM person, pledges 
    WHERE pledges.person_id = person.id ORDER BY person.id");
print "name,email,address_1,address_2,address_3,town,county,postcode,country,ref,title\n";
while ($r = db_fetch_row($q)) {
    print join(',', array_map('escape_csv', $r)) . "\n";
}

?>
