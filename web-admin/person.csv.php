<?
/*
 * person.csv.php:
 * Admin page for LiveSimply, to output CSV of promise creators
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: person.csv.php,v 1.9 2007-06-12 20:49:16 matthew Exp $
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

print "Creators\n\n";

$q = db_query("SELECT pledges.name, person.email, address_1, address_2, address_3,
    address_town, address_county, address_postcode, address_country, ref, title
    FROM person, pledges 
    WHERE pledges.person_id = person.id ORDER BY creationtime");
print "name,email,address_1,address_2,address_3,town,county,postcode,country,ref,title\n";
while ($r = db_fetch_row($q)) {
    print join(',', array_map('escape_csv', $r)) . "\n";
}

print "\n\nSigners\n\n";

$q = db_query("SELECT ref, signers.name, person.email
    FROM person, signers, pledges
    WHERE signers.person_id = person.id AND pledge_id = pledges.id ORDER BY creationtime, signtime");
print "ref,name,email\n";
while ($r = db_fetch_row($q)) {
    print join(',', array_map('escape_csv', $r)) . "\n";
}

?>
