<?
// page.php:
// Header, footer and other layout parts for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.81 2005-10-10 12:30:18 francis Exp $

require_once '../../phplib/person.php';
require_once '../../phplib/db.php';
require_once '../../phplib/countries.php';
require_once 'pledge.php';

/* page_header TITLE [PARAMS]
 * Print top part of HTML page, with the given TITLE. This prints up to the
 * start of the "content" <div>.  If PARAMS['nonav'] is true then the top 
 * title and navigation are not displayed, or if PARAMS['noprint'] is true
 * then they are not there if the page is printed.  */
function page_header($title, $params = array()) {
    global $lang, $microsite;

    static $header_outputted = 0;
    if ($header_outputted && !array_key_exists('override', $params)) {
        return;
    }
    
    // The http-equiv in the HTML below doesn't always seem to override HTTP
    // header, so we say that we are UTF-8 in the HTTP header as well (Case
    // where this was required: On my laptop, Apache wasn't setting UTF-8 in
    // header by default as on live server, and FireFox was defaulting to
    // latin-1 -- Francis)
    header('Content-Type: text/html; charset=utf-8');

    $P = person_if_signed_on(true); /* Don't renew any login cookie. */

    $header_outputted = 1;
?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="<?=$lang ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?
    if ($title) 
        print htmlspecialchars($title) . " - ";
        /* XXX @import url('...') uses single-quotes to hide the style-sheet
         * from Mac IE. Ugly, but it works. */
?> PledgeBank<?if (!$title) print " - " . _("Tell the world \"I'll do it, but only if you'll help\"") ?></title>
<style type="text/css" media="all">@import url('/pb.css');</style>
<link rel="stylesheet" type="text/css" media="print" href="/pbprint.css">
<?

    $category = '';
    if (array_key_exists('ref', $params)) {
        $category = db_getOne('SELECT name FROM category,pledge_category WHERE category_id=id AND pledge_id=(SELECT id FROM pledges WHERE ref=?)', substr($params['ref'],1) );
        if ($category && is_file('../web/css/' . strtolower($category) . '.css'))
            print '<style type="text/css" media="all">@import url(\'/css/' . rawurlencode(strtolower($category)) . '.css\');</style>';
    }
    if ($microsite && is_file('../web/' . $microsite . '.css')) {
        print '<style type="text/css" media="all">@import url(\'/' . rawurlencode($microsite) . '.css\');</style>';
    }

    if (array_key_exists('rss', $params))
        print '<link rel="alternate" type="application/rss+xml" title="' . $params['rss'][1] . '" href="'.$params['rss'][0].'">';
?>
<script type="text/javascript" src="/pb.js"></script>
<?  //this was conditional, but now we need it nearly always for bottom of page local alert signups
    //if (array_key_exists('gazejs', $params)) { ?>
<script type="text/javascript" src="/gaze.js"></script>
<? //} ?>
</head>
<body<? if (array_key_exists('id', $params)) print ' id="' . $params['id'] . '"'; ?>>
<?
    // On the "print flyers from in-page image" page, these top parts are hidden from printing
    if (array_key_exists('noprint', $params) and $params['noprint'])
        print '<div class="noprint">';

    // Display title bar
    if (!array_key_exists('nonav', $params) or !$params['nonav']) {
?>
<h1><a href="/"><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></a>
<span id="beta">Beta</span>
<span id="countrytitle"><?=pb_site_country_name() ?>
 <a href="/where">(change)</a></span>
</h1>
<hr class="v"><?
    }

    // Display link to main pledge page
    if (array_key_exists('ref', $params)) {
        $url = $params['ref'];
        print '<p id="reference">';
        print _('This pledge\'s permanent location: ');
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

    // Start flyers-printing again
    if (array_key_exists('noprint', $params) and $params['noprint'])
        print '</div> <!-- noprint -->';

    // Display who is logged in 
    if ($P) {
        print '<p id="signedon" class="noprint">';
        print _('Hello, ');
        if ($P->has_name())
            print htmlspecialchars($P->name);
        else 
            print htmlspecialchars($P->email);
        print ' <small>(<a href="/logout">';
        print _('this isn\'t you?  click here');
        print '</a>)</small></p>';
    }

?>
<div id="content"><?    

    // Warn that we are on a testing site
    $devwarning = array();
    if (OPTION_PB_STAGING) {
        $devwarning[] = _('This is a test site for developers only. You probably want
<a href="http://www.pledgebank.com/">the real site</a>.');
    }
    global $pb_today;
    if ($pb_today != date('Y-m-d')) {
        $devwarning[] = _("Note: On this test site, the date is faked to be") . " $pb_today";
    }
    if (count($devwarning) > 0) {
        ?><p class="noprint" align="center" style="color: #cc0000; background-color: #ffffff; margin-top: 0;"><?
        print join('<br>', $devwarning);
        ?></p><?
    }
}

/* page_footer PARAMS
 * Print bottom of HTML page. This closes the "content" <div>.  
 * If PARAMS['nonav'] is true then the footer navigation is not displayed. 
 * If PARAMS['nolocalsignup'] is true then no local signup form is showed.
 */
function page_footer($params = array()) {
?></div><? # id="content"
    static $footer_outputted = 0; 
    if (!$footer_outputted && (!array_key_exists('nonav', $params) or !$params['nonav'])) {
        $footer_outputted = 1;
?>
<hr class="v"><h2 class="v"><?=_('Navigation') ?></h2>
<form id="search" accept-charset="utf-8" action="/search" method="get">
<p><label for="q"><?=_('Search') ?>:</label>
<input type="text" id="q" name="q" size="25" value="" onblur="fadeout(this)" onfocus="fadein(this)"> <input type="submit" value="<?=_('Go') ?>"></p>
</form>
<!-- remove all extraneous whitespace to avoid IE bug -->
<ul id="nav"><li><a href="/"><?=_('Home') ?></a></li><li><a href="/list"><?=_('All Pledges') ?></a></li><li><a href="/your"><?=_('Your Pledges') ?></a></li><li><a href="/new"><?=_('Start a Pledge') ?></a></li><li><a href="/faq"><acronym title="<?=_('Frequently Asked Questions') ?>">FAQ</acronym></a></li><li><a href="/contact"><?=_('Contact') ?></a></li><?
        $P = person_if_signed_on(true); /* Don't renew any login cookie. */
        if ($P) {
?><li><a href="/logout"><?=_('Logout') ?></a></li><?
        } else {
?><li><a href="/login"><?=_('Login') ?></a></li><?
        }
?></ul>
<?  if (!array_key_exists('nolocalsignup', $params) or !$params['nolocalsignup']) 
        pb_view_local_alert_quick_signup("localsignupeverypage"); ?>
<hr class="v">
<div id="footer"><? pb_print_change_language_links(); ?> <br> <a href="http://www.mysociety.org/"><?=_('Built by mySociety') ?></a>.</div>
<?
    }
?>
</body></html>
<?  }

function print_this_link($link_text, $after_text) {
    return <<<EOF
<script type="text/javascript">
    document.write('<a href="javascript:window.print();">$link_text<\/a>$after_text');
</script>
<noscript>
$link_text$after_text
</noscript> 
EOF;
}

/* page_check_ref REFERENCE
 * Given a pledge REFERENCE, check whether it uniquely identifies a pledge. If
 * it does, return. Otherwise, fuzzily find possibly matching pledges and
 * show the user a set of possible pages. */
function page_check_ref($ref) {
    if (!is_null(db_getOne('select ref from pledges where ref ilike ?', $ref)))
        return;
    page_header(_("We couldn't find that pledge"));
    $s = db_query('select pledge_id from pledge_find_fuzzily(?) limit 5', $ref);
    if (db_num_rows($s) == 0) {
        printf(p(_("We couldn't find any pledge with a reference like \"%s\". Try the following: ")), htmlspecialchars($ref) );
    } else {
        printf(p(_("We couldn't find the pledge with reference \"%s\". Did you mean one of these pledges?")), htmlspecialchars($ref) );
        print '<dl>';
        while ($r = db_fetch_array($s)) {
            $p = new Pledge((int)$r['pledge_id']);
            print '<dt><a href="/'
                        /* XXX for the moment, just link to pledge index page,
                         * but we should figure out which page the user
                         * actually wanted and link to that instead. */
                        . htmlspecialchars($p->ref()) . '">'
                        . htmlspecialchars($p->ref()) . '</a>'
                    . '</dt><dd>'
                    . $p->h_sentence()
                    . '</dd>';
        }
        print '</dl>';
        print p(_('If none of those look like what you want, try the following:'));
    }

    print '<ul>
        <li>' . _('If you typed in the location, check it carefully and try typing it again.') . '</li>
        <li>' . _('Look for the pledge on <a href="/all">the list of all pledges</a>.') . '</li>
        <li>' . _('Search for the pledge you want by entering words below.') . '</ul>';
    ?>
<form accept-charset="utf-8" action="/search" method="get" class="pledge">
<label for="s"><?=_('Search for a pledge') ?>:</label>
<input type="text" id="s" name="q" size="15" value=""> <input type="submit" value=<?=_('Go') ?>></p>
</form>
<?
    
    page_footer();
    exit();
}

?>
