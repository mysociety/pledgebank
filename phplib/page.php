<?
// page.php:
// Header, footer and other layout parts for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.182 2008-08-08 17:00:01 matthew Exp $

require_once '../../phplib/conditional.php';
require_once '../../phplib/db.php';
require_once '../../phplib/tracking.php';
require_once 'pbperson.php';
require_once 'pledge.php'; # XXX: I don't think this is needed?

// Do NOT include microsites.php here, or it won't get translated.
// This may cause funny errors, but you'll just have to deal with it :)
//require_once 'microsites.php';

$page_vary_header_sent = false;
$page_plain_headers = false;

/* page_send_vary_header
 * Emit an appropriate Vary: header for PledgeBank. */
function page_send_vary_header() {
    global $page_vary_header_sent;
    if ($page_vary_header_sent)
        return;
    /* We must tell caches what headers alter the behaviour of the pages.
     * This list is conservative (it may contain headers which don't affect a
     * particular page), and we may wish to optimise this later. */
    header('Vary: Cookie, Accept-Encoding, Accept-Language, X-GeoIP-Country');
    $etag = '';
    foreach (array('COOKIE', 'ACCEPT_ENCODING', 'ACCEPT_LANGUAGE', 'X_GEOIP_COUNTRY') as $h) {
        if (isset($_SERVER['HTTP_'.$h])) $etag .= $_SERVER['HTTP_'.$h];
        $etag .= '|';
    }
    $page_vary_header_sent = true;
    return $etag;
}

// Internal
function strip_title($title) {
    // Live Simply Promise, the title needs italics in it removing.
    $title = str_replace(array('<em>', '</em>'), '', $title);
    return $title;
}

/* page_header TITLE [PARAMS]
 * Print top part of HTML page, with the given TITLE, which should be in HTML
 * with special characters encoded as entities. This prints up to the
 * start of the "pbcontent" <div>. Optionally, PARAMS specifies other featurs
 * of the page; possible keys in PARAMS are:
 *  nonav
 *      If true, suppresses display of the top title and navigation.
 *  noprint
 *      If true, suppresses printing of the top title and navication
 *  noreflink
 *      If true, suppresses display of link to "this pledge's permanent
 *      location".
 *  last-modified
 *      Optionally gives the last-modified time of the page as seconds since
 *      the epoch.
 *  etag
 *      Optionally gives an etag (assumed weak) for the current page.
 *  cache-max-age
 *      Optionally gives the maximum age of the page in seconds, for use in
 *      a Cache-Control: header.
 *  id
 *      Optionally specify an id for the <body> tag of the page.
 *  ref
 *      Optional pledge ref which will be saved for use in the contact link
 *      output by page_footer 
 *  robots
 *      Optional content for a robots meta-tag.
 *  rss
 *      Optional array of feed title to feed URL to be output in link tags.
 *  override
 *      If true, ouput the page header even if it appears that one has already
 *      been output.
 */
function page_header($title, $params = array()) {
    global $lang, $microsite, $page_plain_headers;

    if ($page_plain_headers) {
        # No need to print anything, body is enough.
        #print "Title: $title\n\n";
        #print "Body: ";
        return;
    }

    if (!is_array($params))
        err("PARAMS must be an array in page_header");
    foreach ($params as $k => $v) {
        if (!preg_match('/^(nonav|noprint|noreflink|last-modified|etag|cache-max-age|id|pref|ref|robots|rss|css|override|banner)$/', $k))
            err("bad key '$k' with value '$v' in PARAMS argument to page_header");
    }

    static $header_outputted = 0;
    if ($header_outputted && !array_key_exists('override', $params)) {
        return;
    }

    global $contact_ref;
    $contact_ref = '';
    if (array_key_exists('ref', $params)) {
        $contact_ref = "?ref=" . htmlspecialchars($params['ref']);
    }

    // The http-equiv in the HTML below doesn't always seem to override HTTP
    // header, so we say that we are UTF-8 in the HTTP header as well (Case
    // where this was required: On my laptop, Apache wasn't setting UTF-8 in
    // header by default as on live server, and FireFox was defaulting to
    // latin-1 -- Francis)
    header('Content-Type: text/html; charset=utf-8');
    page_cache_headers($params);

    /* On an error page don't do anything complicated like check login */
    global $err_handling_error;
    $P = null;
    if (!$err_handling_error) 
        $P = pb_person_if_signed_on(true); /* Don't renew any login cookie. */

    $header_outputted = 1;
?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="<?=$lang ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?  if (array_key_exists('robots', $params)) { ?>
<meta name="robots" content="<?=$params['robots']?>">
<?  }

    echo '<title>';
    if ($title) 
        print strip_title($title) . " - ";
    echo strip_title(_('PledgeBank'));
    if (!$title) print ' - ' . microsites_html_title_slogan();
    echo '</title>';

    /* XXX @import url('...') uses single-quotes to hide the style-sheet
     * from Mac IE. Ugly, but it works. */
    foreach (microsites_css_files() as $microsite_file) {
        echo '<style type="text/css" media="all">@import url(\'' . $microsite_file . '\');</style>';
    }
    echo '<link rel="stylesheet" type="text/css" media="print" href="/pbprint.css">';
    echo '<!--[if LT IE 7]><style type="text/css">@import url("/css/ie6.css");</style><![endif]-->';
    if (array_key_exists('css', $params)) {
	echo '<style type="text/css" media="all">@import url(\'' . $params['css'] . '\');</style>';
    }
    if ($lang == 'zh' || $lang == 'eo' || $lang == 'fr') {
	echo '<style type="text/css" media="all">@import url(\'/css/pb.' . $lang . '.css\');</style>';
    }

    if (array_key_exists('rss', $params)) {
        foreach ($params['rss'] as $rss_title => $rss_url) {
            print '<link rel="alternate" type="application/rss+xml" title="' . $rss_title . '" href="'.$rss_url.'">' . "\n";
        }
    }
    // Find appropriate translated Javascript file
    $js_file = "js/pb.$lang.js";
    if ($microsite) {
        $microsite_js_file = "js/pb.$lang.$microsite.js";
        if (file_exists($microsite_js_file)) {
            $js_file = $microsite_js_file;
        }
    }
    if (!file_exists($js_file)) 
        $js_file = null;
    if ($js_file) { ?>
<script type="text/javascript" src="/<?=$js_file?>"></script>
<?  } ?>
<script type="text/javascript" src="/pb.js"></script>
<script type="text/javascript" src="/jslib/utils.js"></script>
<script type="text/javascript" src="/jquery.js"></script>
<?  //this was conditional, but now we need it nearly always for bottom of page local alert signups
    //if (array_key_exists('gazejs', $params)) { ?>
<script type="text/javascript" src="/gaze.js"></script>
<? //}
    microsites_display_favicon();
    if (!$microsite || $microsite != 'global-cool') { ?>
</head>
<body<? if (array_key_exists('id', $params)) print ' id="' . $params['id'] . '"'; ?>>
<?
        // On the "print flyers from in-page image" page, these top parts are hidden from printing
        if (array_key_exists('noprint', $params) and $params['noprint'])
            print '<div class="noprint">';

        // Display title bar
        if (!array_key_exists('nonav', $params) or !$params['nonav']) {
        ?>
        <?=microsites_logo()?>
        <hr class="v"><?
        }
    } else {
        // TODO: factor this if and include out into phplib/microsites.php.
        // Not clear how best to structure this stuff - e.g. here the include
        // has tohappen instead of </head><body> which is perhaps eccentrically
        // particular to global-cool's template html files.
        include "microsites/autogen/global-cool/thirdPartyHeader.html";
        include "microsites/autogen/global-cool/thirdPartyLeftNav.html";
    }

?><div id="pballheader"><? 

    // Start flyers-printing again
    if (array_key_exists('noprint', $params) and $params['noprint'])
        print '</div> <!-- noprint -->';

    // Display who is logged in 
    if (microsites_display_login() && $P) {
        print '<p id="signedon" class="noprint">';
        if ($P->has_name())
		printf(_('Hello, %s'), htmlspecialchars($P->name));
	else
		printf(_('Hello, %s'), htmlspecialchars($P->email));
        print ' <small>(<a href="/logout">';
        print _('this isn\'t you?  click here');
        print '</a>)</small></p>';
    }

    echo '</div>'; # id="pballheader"
    echo '<form id="nav_search" accept-charset="utf-8" action="/search" method="get">';
    echo _('Search for pledges:') . ' <input type="text" id="q" name="q" size="25" value="'
        . htmlspecialchars(get_http_var('q', true)) . '"><input type="submit" value="'
        . _('Search') . '">
</form>';
    if (isset($params['banner'])) {
        echo $params['banner'];
    }
    echo '<div id="pbcontent">';

    // Warn that we are on a testing site
    $devwarning = array();
    if (OPTION_PB_STAGING) {
        $devwarning[] = _('This is a test site for developers only. You probably want
<a href="http://www.pledgebank.com/">the real site</a>.');
    }
    global $pb_today;
    if ($pb_today && $pb_today != date('Y-m-d')) {
        $devwarning[] = _("Note: On this test site, the date is faked to be") . " $pb_today";
    }
    if (count($devwarning) > 0) {
        ?><p class="noprint" align="center" style="color: #cc0000; background-color: #ffffff; margin-top: 0;"><?
        print join('<br>', $devwarning);
        ?></p><?
    }
}

/* page_footer [PARAMS]
 * Print bottom of HTML page. This closes the "pbcontent" <div>. Possible keys in
 * PARAMS are:
 *  nonav
 *      If true, don't display footer navigation.
 *  nolocalsignup
 *      If true, don't display the local alerts signup form.
 *  extra
 *      Supplies extra user-tracking information associated with this page view
 *      to pass to track_event.
 */
function page_footer($params = array()) {
    global $contact_ref, $microsite, $page_plain_headers;

    if ($page_plain_headers) {
        return;
    }

    // Just logged in, so show password set box if they don't have one
    global $P;
    $P = pb_person_if_signed_on();
    global $stash_in_stashpost;
    if ($P && $stash_in_stashpost && !$P->has_password()) {
        change_personal_details();
        $params['nolocalsignup'] = true; // don't show local signup form as well
    }

    echo '</div> <div id="pballfooter">';
    if (!$microsite || $microsite != 'global-cool') {
        static $footer_outputted = 0; 
        if (!$footer_outputted) {
            $footer_outputted = 1;
            debug_timestamp(true, "begin footer");
?>
<hr class="v"><h2 class="v"><?=_('Navigation') ?></h2>
<div id="navforms">
<a href="http://www.mysociety.org/"><img id="ms_logo" align="top" alt="Visit mySociety.org" src="/i/mysociety-dark+50.png"><span id="ms_logo_ie"></span></a>
<?
            if (microsites_show_translate_blurb()) {
                global $lang, $langs, $site_country;
                print '<form action="/lang" method="get" name="language">
<input type="hidden" name="r" value="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '">
<select name="lang" id="language">';
                foreach ($langs as $l => $pretty) {
                    $o = '<option value="' . $l . '"';
                    if ($l == $lang) $o .= ' selected';
                    $o .= ">$pretty</option>";
                    print $o;
                }
                print '<option value="translate">'._('Translate into your language...').'</option>
        </select> <input type="submit" value="' . _('Change') . '"></form>';
            }
            print '</div>'; # navforms
            $menu = microsites_navigation_menu($contact_ref);
            # remove all extraneous whitespace to avoid IE bug
            print '<ul id="nav">';
            foreach ($menu as $text => $link) {
                print "<li>";
                print '<a href="'.$link.'">';
                print $text;
                print "</a>";
                print "</li>";
            }
            print '</ul>';
            if (!array_key_exists('nonav', $params) or !$params['nonav']) {
?>
<div class="noprint">
<?              if (microsites_local_alerts() && (!array_key_exists('nolocalsignup', $params) || !$params['nolocalsignup']))
                    pb_view_local_alert_quick_signup("localsignupeverypage");
                debug_timestamp(true, "local alert quick timestamp");
        ?>
<hr class="v">
<div id="pbfooter">
<a href="/translate/"><?=_('Translate PledgeBank into your language') ?></a>.
<br><a href="http://www.mysociety.org/"><?=_('Built by mySociety') ?></a>.
<a href="http://www.easynet.net/publicsector/"><?=_('Powered by Easynet')?></a>.</div>
</div>
<?
                debug_timestamp(true, "change language links");
            }
        }
    }

    /* User tracking */
    if ($track = microsites_user_tracking()) {
        if (is_bool($track)) {
            // Temporarily use google analytics
            // Hopefully permanently use piwik :)
            if (true) {
?>
<!-- Piwik -->
<script src="http://piwik.mysociety.org/piwik.js" type="text/javascript"></script>
<script type="text/javascript">
<!--
piwik_action_name = '';
piwik_idsite = 1;
piwik_url = 'http://piwik.mysociety.org/piwik.php';
piwik_log(piwik_action_name, piwik_idsite, piwik_url);
//-->
</script>
<noscript><img src="http://piwik.mysociety.org/piwik.php?i=1" style="border:0" alt="piwik"></noscript>
<!-- /Piwik --> 
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "UA-2712333-1";
urchinTracker();
</script>
<?
            } else {
                // Our own tracking - mostly broken
                $extra = null;
                if (array_key_exists('extra', $params) && $params['extra'])
                    $extra = $params['extra'];
                track_event($extra);
            }
        } elseif (is_string($track)) {
            print $track;
        }
    }

    if (!$microsite || $microsite != 'global-cool') {
?></div><? # id="pballfooter"
?>
</body></html>
<?  
    } else {
        include "microsites/autogen/global-cool/thirdPartyFooter.html";
    }
    header('Content-Length: ' . ob_get_length());
}

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

/* rss_header TITLE DESCRIPTION
 * Display header for RSS versions of page  
 */
function rss_header($title, $description, $params) {
    global $lang;
    $country_name = pb_site_country_name();
    $main_page = pb_domain_url(array("explicit"=>true, 'path'=>str_replace('rss/', '', $_SERVER['REQUEST_URI'])));
    header('Content-Type: application/xml; charset=utf-8');
    page_cache_headers($params);
    print '<?xml version="1.0" encoding="UTF-8"?>';
?>

<rdf:RDF
 xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
 xmlns="http://purl.org/rss/1.0/"
 xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"
 xmlns:taxo="http://purl.org/rss/1.0/modules/taxonomy/"
 xmlns:dc="http://purl.org/dc/elements/1.1/"
 xmlns:syn="http://purl.org/rss/1.0/modules/syndication/"
 xmlns:admin="http://webns.net/mvcb/"
>

<channel rdf:about="<?=$main_page?>">
<? # TRANS: 'PledgeBank' here is the second part of the title used in the RSS files ?>
<title><?=strip_title($title . ' - ' . _('PledgeBank') . ' ' . $country_name) ?></title>
<link><?=$main_page?></link>
<description><?=$description?></description>
<dc:language><?=$lang?></dc:language>

<?
}

/* rss_footer ITEMS
 * Display items and footer for RSS versions of page. The items
 * is an array of entries. Each entry is an associative array
 * containing title, link and description
 */
function rss_footer($items) {
?> <items> <rdf:Seq>
<?  foreach ($items as $item) { ?>
  <rdf:li rdf:resource="<?=$item['link']?>" />
<? } ?>
 </rdf:Seq>
</items>
</channel>
<? foreach ($items as $item) { ?>
<item rdf:about="<?=$item['link']?>">
<title><?=$item['title']?></title>
<link><?=$item['link']?></link>
<description><?=$item['description']?></description>
<? if (array_key_exists('latitude', $item) && array_key_exists('longitude', $item) && $item['latitude'] && $item['longitude']) { ?>
<geo:lat><?=$item['latitude']?></geo:lat>
<geo:long><?=$item['longitude']?></geo:long>
<? } ?>
</item>
<? } ?>
</rdf:RDF>
<?
}

function page_cache_headers($params) {
    page_send_vary_header();
    if (OPTION_PB_CACHE_HEADERS) {
        /* Send Last-Modified: and ETag: headers, if we have enough information to
         * do so. */
        $lm = null;
        $etag = null;
        if (array_key_exists('last-modified', $params))
            $lm = $params['last-modified'];
        if (array_key_exists('etag', $params))
            $etag = $params['etag'];
        if (isset($lm) || isset($etag)) {
            locale_push('en-gb');
            cond_headers($lm, $etag);
            locale_pop();
        }

        /* Ditto a max-age if specified. */
        if (array_key_exists('cache-max-age', $params))
            header('Cache-Control: max-age=' . $params['cache-max-age']);
    }
}


/* page_check_ref REFERENCE
 * Given a pledge REFERENCE, check whether it uniquely identifies a pledge. If
 * it does, return. Otherwise, fuzzily find possibly matching pledges and
 * show the user a set of possible pages. */
function page_check_ref($ref) {
    # XXX now we have a lower case index, not really sure this needs to do two separate checks
    if (!is_null(db_getOne('select ref from pledges where ref = ?', $ref))) {
        return;
    } else if (!is_null(db_getOne('select ref from pledges where lower(ref) = ?', strtolower($ref)))) {
        /* Note that this lower case match will only happen when the
         * alternative case is first typed in , as ref-index.php does a
         * redirect to the URL with the correct case. */
        return;
    }
    header('Location: /fuzzyref?ref=' . urlencode($ref));
    exit();
}

?>
