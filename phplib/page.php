<?
//
// page.php:
// Header and footer for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.7 2005-03-16 09:13:10 sandpit Exp $

/* page_header TITLE [PARAMS]
 * Print top part of HTML page, with the given TITLE. This prints up to the
 * start of the "content" <div>.  If PARAMS['nonav'] is true then the top 
 * title and navigation are not displayed.  */
function page_header($title, $params = array()) {
?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en"><head><title><?

    if ($title) print htmlspecialchars($title) . " - ";
    
?> PledgeBank - Not Finished Yet</title>
<style type="text/css" media="all">@import url("/pb.css");</style>
<link rel="stylesheet" type="text/css" media="print" href="/pbprint.css" />
<script type="text/javascript" src="/pb.js"></script>
</head>
<body>
<?
    if (!array_key_exists('nonav', $params) or !$params['nonav']) {
?>
<h1>Pledge<span>Bank</span> &mdash; Not Finished Yet</h1>
<hr class="v"><div id="content"><?
    }
    if (OPTION_PB_STAGING) {
?><div class="noprint"><p align="center" style="color: #cc0000; background-color: #ffffff">
<i>This is a test site for developers only. You probably want
<a href="http://www.pledgebank.com/">the real site</a>.</i></p></div><?
    }
}

/* page_footer PARAMS
 * Print bottom of HTML page. This closes the "content" <div>. 
 * If PARAMS['nonav'] is true then the footer navigation is not displayed.
 */
function page_footer($params = array()) { 
    if (!array_key_exists('nonav', $params) or !$params['nonav']) {
?>
</div>
<hr class="v"><h2 class="v">Navigation</h2>
<ul id="nav">
<li><a href="/">Home</a>
<li><a href="/new">New Pledge</a>
<li><a href="/faq"><acronym title="Frequently Asked Questions">FAQ</acronym></a>
<li><a href="/contact">Contact</a>
</ul>
<hr class="v">
<div id="footer"><a href="http://www.mysociety.org/">Built by mySociety</a>.</div>
<?
    }
?>
</body></html>
<? }

?>
