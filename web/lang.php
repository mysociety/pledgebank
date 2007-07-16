<?
// lang.php:
// Showing choice of languages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: lang.php,v 1.2 2007-07-16 08:09:19 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';

$r = get_http_var('r');
if (!$r)
    $r = "/";

page_header(_('Choose your language'));
print h2(_('Choose your language'));
pb_print_change_language_links($r);
#$url = pb_domain_url(array('country'=>$code, 'path'=>$r));
page_footer();

?>
