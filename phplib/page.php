<?
//
// page.php:
// Header and footer for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.5 2005-03-14 14:14:18 francis Exp $

/* page_header TITLE
 * Print top part of HTML page, with the given TITLE. This prints up to the
 * start of the "content" <div>. */
function page_header($title) {
?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en"><head><title><?

    if ($title) print htmlspecialchars($title) . " - ";
    
?> PledgeBank - Not Finished Yet</title>
<style type="text/css">@import url("/pb.css");</style>
<script type="text/javascript" src="/pb.js"></script>
</head>
<body>
<h1>Pledge<span>Bank</span> &mdash; Not Finished Yet</h1>
<hr class="v"><div id="content"><?

    if (OPTION_PB_STAGING) {
?><p align="center" style="color: #cc0000; background-color: #ffffff">
<i>This is a test site for developers only. You probably want
<a href="http://www.pledgebank.com/">the real site</a>.</i></p><?
    }
}

/* page_footer
 * Print bottom of HTML page. This closes the "content" <div>. */
function page_footer() { ?>
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
</body></html>
<? }

?>
