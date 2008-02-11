<?
// blog.php:
// Simple skin of main blog for PledgeBank
//
// Copyright (c) 2008 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: blog.php,v 1.1 2008-02-11 19:12:41 matthew Exp $

require_once "../phplib/pb.php";

$ref = isset($_GET['ref']) ? $_GET['ref'] : '';
if (!preg_match('#\d{4}/\d\d/\d\d/[^/]*/$#', $ref)) $ref = '';

if (!$ref)
    err('No blog reference given', E_USER_NOTICE);

$f = file_get_contents('http://www.mysociety.org/' . $ref);
preg_match('#<div class="item_head".*?<a.*?>(.*?)</a>.*?<div class="meta">(.*?)</div>.*?<div class="item">(.*?)</div>#s', $f, $m);
$title = $m[1];
$meta = $m[2];
$content = $m[3];

page_header(_("PledgeBank blog") . ": $title", array('cache-max-age' => 600));

print h2($title);
print "<p><i>$meta</i></p>";
print $content;

page_footer();
