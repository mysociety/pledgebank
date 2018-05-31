<?
// ref-alert.php:
// Signing up for alerts.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: where.php,v 1.16 2007-11-14 18:31:11 matthew Exp $

require_once '../phplib/pb.php';
require_once '../phplib/pledge.php';
require_once '../phplib/alert.php';
require_once '../phplib/pbperson.php';
require_once '../commonlib/phplib/utility.php';
require_once '../commonlib/phplib/gaze.php';

$r = get_http_var('r');
if (!$r)
    $r = "/";

page_header(_('Choose your country'));

print h2(_('Choose your country ...'));

// Link to all pledges in all countries near the top (for search engines to
// find easily, and for the curious)
print "<p>";
$code = 'everywhere';
$name = $microsites_public_list[$code];
$url = pb_domain_url(array('microsite'=>$code, 'path'=>$r));
print "<a href=\"".$url."\">".$name."</a>";
print "</p>";

// Count how many pledges there are for each country
$query = "SELECT count(*) as c, location.country as country
        FROM pledges LEFT JOIN location ON location.id = pledges.location_id
        WHERE cached_prominence <> 'backpage' AND country IS NOT NULL
        GROUP BY location.country";
$q = db_query($query);
$country_count = array();
while ($row = db_fetch_array($q)) {
    $country_count[$row['country']] = $row['c'];
}

// Display countries which have at least one pledge
$lastchar = "*";
uksort($countries_name_to_code, 'strcoll');
$column = 0;
$total_countries = count($country_count);
$n = 0;
$last_col_n = 0;
print '<div id="wherecolumns">';
print "<div><p>\n";
foreach ($countries_name_to_code as $name => $code) {
    if (!array_key_exists($code, $country_count))
        continue;
    $firstchar = mb_substr($name, 0, 1, "UTF-8");
    # Tricksy test to see if we are on the next "letter" by collation.
    # i.e. We have reached the next letter if it sufficiently different from
    # this on that when put before "a" and "b", you end up with "Xa" >= "Xb"
    $charcomp = strcoll($firstchar."a", $lastchar."b");
    if ($charcomp >= 0 || $lastchar == "*") {
        print "\n</p>";
        if (($n > $total_countries / 3 && $column == 0) ||
           ($n > 2 * $total_countries / 3 && $column == 1)) {
           $column++;
           print "</div><div>";
           $last_col_n = $n;
        }
        print "<h3>$firstchar</h3><p>\n";
    }
    $url = pb_domain_url(array('country'=>$code, 'path'=>$r));
    print '<a href="' . $url . '">' . $countries_code_to_name[$code] . '</a>';
    print " <!-- pledges: " . $country_count[$code] . " --> ";
    print "<br>\n";
    $n++;
    $lastchar = $firstchar;
}
print "\n</p></div></div>";

// Show special sites
print '<div id="wherespecial">';
print h2(_('... or choose a special site'));
foreach ($microsites_public_list as $code => $name) {
    if ($code == 'everywhere') // this shown at top
        continue;
    $url = pb_domain_url(array('microsite'=>$code, 'path'=>$r));
    print "<a href=\"".$url."\">".$name."</a>";
    print "<br>";
}
print p(_('If you would like your own special PledgeBank site, then <a href="/contact">email us</a> for details.'));
print "</div>";

page_footer();

?>
