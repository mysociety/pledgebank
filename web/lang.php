<?
// lang.php:
// Showing choice of languages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: lang.php,v 1.6 2007-08-01 09:22:54 matthew Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';

$r = get_http_var('r');
if (!$r)
    $r = '/';

$l = get_http_var('lang');
if ($l) {
    if ($l == 'translate') {
        $url = '/translate';
    } else {
        $params = array('lang' => $l, 'country' => $site_country, 'path'=>$r);
        $url = pb_domain_url($params);
    }
    header("Location: $url");
    exit;
}

page_header(_('Choose your language'));
print h2(_('Choose your language'));
print '<ul>';
$out = array();
foreach ($langs as $l => $pretty) {
    $params = array('lang'=>$l, 'country'=>$site_country, 'path'=>$r);
    $url = pb_domain_url($params);
    if ($l == $lang) $o = '<strong>';
    else $o = '<a href="'.$url.'" lang="' . $l . '" hreflang="' . $l . '">';
    $o .= $pretty;
    if ($l == $lang) $o .= '</strong>';
    else $o .= '</a>';
    print "<li>$o</li>";
}
print '</ul>
<p><a href="/translate/">'._('Translate PledgeBank into your language').'</a>.</p>';

page_footer();
