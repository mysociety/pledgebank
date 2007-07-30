<?
// lang.php:
// Showing choice of languages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: lang.php,v 1.3 2007-07-30 14:50:23 matthew Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';

$r = get_http_var('r');
if (!$r)
    $r = "/";

$l = get_http_var('lang');
if ($l) {
    $params = array('lang'=>$l, 'country'=>$site_country, 'path'=>$r);
    if ($l == 'translate')
        $url = '/translate';
    else
        $url = pb_domain_url($params);
    header("Location: $url");
    exit;
}

page_header(_('Choose your language'));
print h2(_('Choose your language'));
print '<p>';
$out = array();
foreach ($langs as $l => $pretty) {
    $params = array('lang'=>$l, 'country'=>$site_country);
    if ($r)
        $params['path'] = $r;
    $url = pb_domain_url($params);
    if ($l == $lang) $o = '<strong>';
    else $o = '<a href="'.$url.'" lang="' . $l . '" hreflang="' . $l . '">';
    $o .= $pretty;
    if ($l == $lang) $o .= '</strong>';
    else $o .= '</a>';
    $out[] = $o;
}
$first = array_splice($out, 0, -2);
if (count($first)) print ' ' . join(', ', $first) . ',';
if (count($out) >= 2)
    print ' ' . $out[count($out)-2] . ' ' . _('and') . ' ' . $out[count($out)-1];
elseif (count($out) == 1)
    print ' ' . $out[0];
print '.</p> <p><a href="/translate/">'._('Translate PledgeBank into your language').'</a>.</p>';

page_footer();
