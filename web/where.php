<?
// ref-alert.php:
// Signing up for alerts.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: where.php,v 1.3 2005-11-26 14:29:21 matthew Exp $

require_once '../phplib/pb.php';
require_once '../phplib/pledge.php';
require_once '../phplib/alert.php';
require_once '../../phplib/person.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/gaze.php';

$r = get_http_var('r');
if (!$r)
    $r = "/";

page_header(_('Choose your country'));
print h2(_('Choose your country'));
$last = "*";
print "<p>";
ksort($countries_name_to_code);
foreach ($countries_name_to_code as $name => $code) {
    $name = $countries_code_to_name[$code];
    $firstchar = mb_substr($name, 0, 1, "UTF-8");
    if (str_replace("Å", "A", $firstchar) != str_replace("Å", "A",$last)) {
        print "</p><p>";
    }
    $url = pb_domain_url(array('country'=>$code, 'path'=>$r));
    print "<a href=\"".$url."\">".$name."</a>";
    print " ";
    $last = $firstchar;
}
print "</p>";
page_footer();

?>
