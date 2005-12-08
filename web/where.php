<?
// ref-alert.php:
// Signing up for alerts.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: where.php,v 1.8 2005-12-08 19:51:15 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/pledge.php';
require_once '../phplib/alert.php';
require_once '../../phplib/person.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/gaze.php';

$r = get_http_var('r');
if (!$r)
    $r = "/";

page_header(_('Choose your country'));

print h2(_('Choose your country ...'));
$lastchar = "*";
print "<p>";
uksort($countries_name_to_code, 'strcoll');
$column = 0;
$total_countries = count($countries_name_to_code);
$n = 0;
$last_col_n = 0;
print "<div class=\"wherecolumn\">";
foreach ($countries_name_to_code as $name => $code) {
    $firstchar = mb_substr($name, 0, 1, "UTF-8");
    # Tricksy test to see if we are on the next "letter" by collation.
    # i.e. We have reached the next letter if it sufficiently different from
    # this on that when put before "a" and "b", you end up with "Xa" >= "Xb"
    $charcomp = strcoll($firstchar."a", $lastchar."b");
    if ($charcomp >= 0 || $lastchar == "*") {
        print "</p>";
        if (($n > $total_countries / 3 && $column == 0) ||
           ($n > 2 * $total_countries / 3 && $column == 1)) {
           $column++;
           print "</div><div class=\"wherecolumn\">";
           $last_col_n = $n;
        }
        print "<h3>$firstchar</h3><p>";
    }
    $url = pb_domain_url(array('country'=>$code, 'path'=>$r));
    print "<a href=\"".$url."\">".$name."</a>";
    print "<br>";
    $n++;
    $lastchar = $firstchar;
}
print "</div></p>";

print "<div class=\"wherespecial\">";
print h2(_('... or choose a special site'));
foreach ($microsites_public_list as $code => $name) {
    $url = pb_domain_url(array('microsite'=>$code, 'path'=>$r));
    print "<a href=\"".$url."\">".$name."</a>";
    print "<br>";
}
print "</div>";

page_footer();

?>
