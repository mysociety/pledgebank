<?
// page.php:
// Header, footer and other layout parts for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.11 2005-03-29 07:39:56 francis Exp $

/* page_header TITLE [PARAMS]
 * Print top part of HTML page, with the given TITLE. This prints up to the
 * start of the "content" <div>.  If PARAMS['nonav'] is true then the top 
 * title and navigation are not displayed, or if PARAMS['noprint'] is true
 * then they are not there if the page is printed.  */
function page_header($title, $params = array()) {
?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en"><head><title><?
    if ($title) 
        print htmlspecialchars($title) . " - ";
?> PledgeBank - Not Finished Yet</title>
<style type="text/css" media="all">@import url("/pb.css");</style>
<link rel="stylesheet" type="text/css" media="print" href="/pbprint.css">
<script type="text/javascript" src="/pb.js"></script>
</head>
<body>
<?
    // Display title bar
    if (!array_key_exists('nonav', $params) or !$params['nonav']) {
        if (array_key_exists('noprint', $params) and $params['noprint'])
            print '<div class="noprint">';
?>
<h1>Pledge<span>Bank</span> &mdash; Not Finished Yet</h1>
<hr class="v"><?
        if (array_key_exists('noprint', $params) and $params['noprint'])
            print '</div> <!-- noprint -->';
    }

    // Warn that we are on a testing site
    if (OPTION_PB_STAGING) {
?><p class="noprint" align="center" style="color: #cc0000; background-color: #ffffff">
<em>This is a test site for developers only. You probably want
<a href="http://www.pledgebank.com/">the real site</a>.</em></p><?
    }

    // Warn that date has been set for debugging
    global $pb_today;
    if ($pb_today != date('Y-m-d')) {
?><p class="noprint" align="center" style="color: #cc0000; background-color: #ffffff">
<em>Note: On this test site, the date is faked to be <?=$pb_today?></em></p><?
    }

    // Begin content
?><div id="content"><?
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
<li><a href="/">Home</a></li>
<li><a href="/new">New Pledge</a></li>
<li><a href="/faq"><acronym title="Frequently Asked Questions">FAQ</acronym></a></li>
<li><a href="/contact">Contact</a></li>
</ul>
<hr class="v">
<div id="footer"><a href="http://www.mysociety.org/">Built by mySociety</a>.</div>
<?
    }
?>
</body></html>
<? }

function print_this_link($link_text, $after_text) {
?>
<script type="text/javascript">
    document.write('<a href="javascript: window.print()"><?=$link_text?></a><?=$after_text?>');
</script>
<noscript>
<?=$link_text?><?=$after_text?>
</noscript> 
<?
}
?>
