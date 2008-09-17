<?
// blog.php:
// Simple skin of main blog for PledgeBank
//
// Copyright (c) 2008 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: blog.php,v 1.3 2008-09-17 23:01:47 matthew Exp $

require_once "../phplib/pb.php";

$ref = isset($_GET['ref']) ? $_GET['ref'] : '';
if (!preg_match('#\d{4}/\d\d/\d\d/[^/]*/$#', $ref)) $ref = '';

if (!$ref)
    err('No blog reference given', E_USER_NOTICE);

$f = file_get_contents('http://www.mysociety.org/' . $ref);
preg_match('#<h1>(.*?)</h1>.*?<div class="entry"[^>]*>(.*?)<p class="postmetadata[^>]*>\s*<small>\s*(.*?)and is filed#s', $f, $m);
$title = $m[1];
$content = $m[2];
$meta = $m[3];

page_header(_("PledgeBank blog") . ": $title", array('cache-max-age' => 600));

print h2($title);
print "<p><i>$meta</i></p>";
print $content;

page_footer();
