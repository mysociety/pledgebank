<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.9 2005-03-04 13:16:41 matthew Exp $

function page_header($title) { ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en"><head><title><?=$title?> - PledgeBank - Not Finished Yet</title>
<style type="text/css">@import url("pb.css");</style>
<script type="text/javascript" src="pb.js"></script>
</head>
<body>
<?
    if (OPTION_PB_STAGING) {
        print "<i>This is a test site for developers only.  You
        probably want <a href=\"http://www.pledgebank.com\">the real site</a>.</i>";
    }
?>
<h1>Pledge<span>Bank</span> &mdash; Not Finished Yet</h1>
<hr class="v"><div id="content">
<? 
}

function page_footer() { ?>
</div>
<hr class="v"><h2 class="v">Navigation</h2>
<ul id="nav"><li><a href="./">Home</a><li><a href="./all">Pledges</a><li><a href="./faq"><acronym title="Frequently Asked Questions">FAQ</acronym></a><li><a href="./?contact=1">Contact</a></ul>
<hr class="v"><div id="footer">Built by <a href="http://www.mysociety.org/">mySociety</a></div>
</body></html>
<? }

?>
