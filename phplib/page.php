<?
// page.php:
// Header, footer and other layout parts for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.23 2005-05-20 13:37:12 matthew Exp $

/* page_header TITLE [PARAMS]
 * Print top part of HTML page, with the given TITLE. This prints up to the
 * start of the "content" <div>.  If PARAMS['nonav'] is true then the top 
 * title and navigation are not displayed, or if PARAMS['noprint'] is true
 * then they are not there if the page is printed.  */
function page_header($title, $params = array()) {
    static $header_outputted = 0;
    if (!$header_outputted) {
        $header_outputted = 1;
?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?
    if ($title) 
        print htmlspecialchars($title) . " - ";
        /* XXX @import url('...') uses single-quotes to hide the style-sheet
         * from Mac IE. Ugly, but it works. */
?> PledgeBank - Not Finished Yet</title>
<style type="text/css" media="all">@import url('/pb.css');</style>
<link rel="stylesheet" type="text/css" media="print" href="/pbprint.css">
<?  if (array_key_exists('rss', $params))
        print '<link rel="alternate" type="application/rss+xml" title="New Pledges at PledgeBank.com" href="/rss">';
?>
<script type="text/javascript" src="/pb.js"></script>
</head>
<body>
<?
    // Display title bar
    if (!array_key_exists('nonav', $params) or !$params['nonav']) {
        if (array_key_exists('noprint', $params) and $params['noprint'])
            print '<div class="noprint">';
?>
<h1><a href="/"><span class="logo_pledge">Pledge</span><span class="logo_bank">Bank</span></a> &mdash; Not Finished Yet</h1>
<hr class="v"><?
        if (array_key_exists('noprint', $params) and $params['noprint'])
            print '</div> <!-- noprint -->';
    }

        if (array_key_exists('ref', $params)) {
            $url = OPTION_BASE_URL . $params['ref'];
            print '<p id="reference">This pledge\'s permanent location: ';
            if (!array_key_exists('noreflink', $params))
                print '<a href="' . $url . '">';
            print '<strong>'. str_replace('http://', '', $url) . '</strong>';
            if (!array_key_exists('noreflink', $params))
                print '</a>';
            print '</p>';
        }
?>
<div id="content"><?    

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

    }
}

/* page_footer PARAMS
 * Print bottom of HTML page. This closes the "content" <div>. 
 * If PARAMS['nonav'] is true then the footer navigation is not displayed.
 */
function page_footer($params = array()) {
    static $footer_outputted = 0; 
    if (!$footer_outputted && (!array_key_exists('nonav', $params) or !$params['nonav'])) {
        $footer_outputted = 1;
?>
</div>
<hr class="v"><h2 class="v">Navigation</h2>
<ul id="nav">
<li><a href="/">Home</a></li>
<li><a href="/new">New Pledge</a></li>
<li><a href="/all">All Pledges</a></li>
<li><a href="/faq"><acronym title="Frequently Asked Questions">FAQ</acronym></a></li>
<li><a href="/contact">Contact</a></li>
</ul>
<hr class="v">
<div id="footer"><a href="http://www.mysociety.org/">Built by mySociety</a>.</div>
<?
    }
?>
</body></html>
<?  }

function print_this_link($link_text, $after_text) {
?>
<script type="text/javascript">
    document.write('<a href="javascript:window.print();"><?=$link_text?><\/a><?=$after_text?>');
</script>
<noscript>
<?=$link_text?><?=$after_text?>
</noscript> 
<?
}
?>
