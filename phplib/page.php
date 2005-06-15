<?
// page.php:
// Header, footer and other layout parts for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.41 2005-06-15 15:46:01 francis Exp $

/* page_header TITLE [PARAMS]
 * Print top part of HTML page, with the given TITLE. This prints up to the
 * start of the "content" <div>.  If PARAMS['nonav'] is true then the top 
 * title and navigation are not displayed, or if PARAMS['noprint'] is true
 * then they are not there if the page is printed.  */
function page_header($title, $params = array()) {
    static $header_outputted = 0;
    if ($header_outputted) {
        return;
    }
    
    $P = person_if_signed_on(true); /* Don't renew any login cookie. */

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
?> PledgeBank - Tell the world "I'll do it, but only if you'll help"</title>
<style type="text/css" media="all">@import url('/pb.css');</style>
<link rel="stylesheet" type="text/css" media="print" href="/pbprint.css">
<?

    $category = '';
    if (array_key_exists('ref', $params)) {
        $category = db_getOne('SELECT name FROM category,pledge_category WHERE category_id=id AND pledge_id=(SELECT id FROM pledges WHERE ref=?)', substr($params['ref'],1) );
        if ($category && is_file('../web/css/' . strtolower($category) . '.css'))
            print '<style type="text/css" media="all">@import url(\'/css/' . rawurlencode(strtolower($category)) . '.css\');</style>';
    }

    if (array_key_exists('rss', $params))
        print '<link rel="alternate" type="application/rss+xml" title="New Pledges at PledgeBank.com" href="/rss">';
?>
<script type="text/javascript" src="/pb.js"></script>
</head>
<body<? if (array_key_exists('id', $params)) print ' id="' . $params['id'] . '"'; ?>>
<?
    // Display title bar
    if (!array_key_exists('nonav', $params) or !$params['nonav']) {
        if (array_key_exists('noprint', $params) and $params['noprint'])
            print '<div class="noprint">';
?>
<h1><a href="/"><!-- <img src="http://very.unfortu.net/~tom/tspblogo.png" alt="PledgeBank - beta"></a> -->
<span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></a> <span id="beta">Beta</span></h1>
<hr class="v"><?
        if (array_key_exists('noprint', $params) and $params['noprint'])
            print '</div> <!-- noprint -->';
    }

        if (array_key_exists('ref', $params)) {
            $url = $params['ref'];
            print '<p id="reference">This pledge\'s permanent location: ';
            if (!array_key_exists('noreflink', $params))
                print '<a href="' . $url . '">';
            print '<strong>'. str_replace('http://', '', $url) . '</strong>';
            if (!array_key_exists('noreflink', $params))
                print '</a>';
/*            if ($category) {
                print '<br>This pledge is in the <strong>' . $category . '</strong> category';
            } */
            print '</p>';
        }
        if ($P) {
            print '<p id="signedon">Hello, ';
            if ($P->has_name())
                print htmlspecialchars($P->name);
            else 
                print htmlspecialchars($P->email);
            print ' <small>(<a href="/logout">this isn\'t you?  click here</a>)</small>';
            print '</p>';
        }
?>
<div id="content"><?    

    // Warn that we are on a testing site
    $devwarning = array();
    if (OPTION_PB_STAGING) {
        $devwarning[] = 'This is a test site for developers only. You probably want
<a href="http://www.pledgebank.com/">the real site</a>.';
    }
    global $pb_today;
    if ($pb_today != date('Y-m-d')) {
        $devwarning[] = "Note: On this test site, the date is faked to be $pb_today";
    }
    if (count($devwarning) > 0) {
        ?><p class="noprint" align="center" style="color: #cc0000; background-color: #ffffff; margin-top: 0;"><?
        print join('<br>', $devwarning);
        ?></p><?
    }
}

/* page_footer PARAMS
 * Print bottom of HTML page. This closes the "content" <div>.  If
 * PARAMS['nonav'] is true then the footer navigation is not displayed. */
function page_footer($params = array()) {
    static $footer_outputted = 0; 
    if (!$footer_outputted && (!array_key_exists('nonav', $params) or !$params['nonav'])) {
        $footer_outputted = 1;
?>
</div>
<hr class="v"><h2 class="v">Navigation</h2>
<form id="search" accept-charset="utf-8" action="/search" method="get">
<p><label for="s">Search:</label>
<input type="text" id="s" name="q" size="10" value=""> <input type="submit" value="Go"></p>
</form>
<!-- remove all extraneous whitespace to avoid IE bug -->
<ul id="nav"><li><a href="/">Home</a></li><li><a href="/new">Start a Pledge</a></li><li><a href="/all">All Pledges</a></li><li><a href="/faq"><acronym title="Frequently Asked Questions">FAQ</acronym></a></li><li><a href="/contact">Contact</a></li><?
        $P = person_if_signed_on(true); /* Don't renew any login cookie. */
        if ($P) {
?><li><a href="/logout">Logout</a></li><?
        } else {
?><li><a href="/login">Login</a></li><?
        }
?></ul>
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

/* page_check_ref REFERENCE
 * Given a pledge REFERENCE, check whether it uniquely identifies a pledge. If
 * it does, return. Otherwise, fuzzily find possibly matching pledges and
 * show the user a set of possible pages. */
function page_check_ref($ref) {
    if (!is_null(db_getOne('select ref from pledges where ref ilike ?', $ref)))
        return;
    page_header("We couldn't find that pledge");
    $s = db_query('select pledge_id from pledge_find_fuzzily(?) limit 5', $ref);
    if (db_num_rows($s) == 0) {
        print "<p>We couldn't find any pledge with a reference like \"" . htmlspecialchars($ref) . "\". ";
        print "Try the following: </p>";
    } else {
        print "<p>We couldn't find the pledge with reference \"" . htmlspecialchars($ref) . "\". ";
        print "Did you mean one of these pledges?</p><dl>";
        while ($r = db_fetch_array($s)) {
            $p = new Pledge((int)$r['pledge_id']);
            print "<dt><a href=\"/"
                        /* XXX for the moment, just link to pledge index page,
                         * but we should figure out which page the user
                         * actually wanted and link to that instead. */
                        . htmlspecialchars($p->ref()) . "\">"
                        . htmlspecialchars($p->ref()) . "</a>"
                    . "</dt><dd>"
                    . $p->h_sentence()
                    . "</dd>";
        }
        print "</dl>";
        print "<p>If none of those look like what you want, try the following:</p>";
    }

    print "<p> <ul>
        <li>If you typed in the location, check it carefully and try typing it again.</li>
        <li>Look for the pledge on <a href=\"/all\">the list of all pledges</a>.</li>
        <li>Search for the pledge you want by entering words below.</ul></p>";
    ?>
<form accept-charset="utf-8" action="/search" method="get" class="pledge">
<label for="s">Search for a pledge:</label>
<input type="text" id="s" name="q" size="15" value=""> <input type="submit" value="Go"></p>
</form>
<?
    
    page_footer();

    exit();
}

?>
