<?
// page.php:
// Header, footer and other layout parts for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.187 2009-09-24 16:49:11 matthew Exp $

require_once '../commonlib/phplib/conditional.php';
require_once '../commonlib/phplib/db.php';
require_once '../commonlib/phplib/tracking.php';
require_once 'pbperson.php';
require_once 'pledge.php'; # XXX: I don't think this is needed?

// Do NOT include microsites.php here, or it won't get translated.
// This may cause funny errors, but you'll just have to deal with it :)
//require_once 'microsites.php';

$page_vary_header_sent = false;
$page_plain_headers = false;

/* page_send_vary_header
 * Emit an appropriate Vary: header for PledgeBank. */
function page_send_vary_header($params = array()) {
    global $page_vary_header_sent;
    if ($page_vary_header_sent)
        return;
    /* We must tell caches what headers alter the behaviour of the pages.
     * This list is conservative (it may contain headers which don't affect a
     * particular page), and we may wish to optimise this later. */
    if (isset($params['id']) && $params['id'] == 'front') {
        header('Vary: *');
    } else {
        header('Vary: Cookie, Accept-Encoding, Accept-Language');
    }

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
 *  noprint
 *      If true, suppresses printing of the top title and navication
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
    $default_params = array(
        'noprint' => '',
        'body_id' => '',
        'body_class' => '',
        'robots' => '',
        'rss' => array(),
        'css' => '',
        'banner' => '',
    );
    foreach ($default_params as $k => $v) {
        if (!array_key_exists($k, $params))
            $params[$k] = $v;
    }
    foreach ($params as $k => $v) {
        if (!preg_match('/^(noprint|last-modified|etag|cache-max-age|id|body_id|body_class|pref|ref|robots|rss|css|override|banner)$/', $k))
            err("bad key '$k' with value '$v' in PARAMS argument to page_header");
        if ($k == 'id' && $v)
            $params['body_id'] = ' id="' . $params['id'] . '"';
        if ($k == 'body_class' && $v)
            $params['body_class'] = ' class="' . $params['body_class'] . '"';
        if ($k == 'robots' && $v)
            $params['robots'] = '<meta name="robots" content="' . $params['robots'] . '">';
        if ($k == 'css' && $v)
            $params['css'] = '<style type="text/css" media="all">@import url(\'' . $params['css'] . '\');</style>';
    }

    static $header_outputted = 0;
    if ($header_outputted && !array_key_exists('override', $params)) {
        return;
    }
    $header_outputted = 1;

    global $contact_ref;
    $contact_ref = '';
    if (array_key_exists('ref', $params)) {
        $contact_ref = "?ref=" . htmlspecialchars($params['ref']);
    }

    /* On an error page don't do anything complicated like check login */
    global $err_handling_error;
    $P = null;
    if (!$err_handling_error) 
        $P = pb_person_if_signed_on(true); /* Don't renew any login cookie. */

    $js_file = "js/pb.$lang.js";
    if ($microsite) {
        $microsite_js_file = "js/pb.$lang.$microsite.js";
        if (file_exists($microsite_js_file)) {
            $js_file = $microsite_js_file;
        }
    }
    if (file_exists($js_file))
        $params['js_file'] = '<script type="text/javascript" src="/' . $js_file . '"></script>';
    else
        $params['js_file'] = '';

    if ($title) {
        $title = strip_title($title) . " - ";
        $title .= strip_title(_('PledgeBank'));
    } else {
        $title = strip_title(_('PledgeBank'));
        # TRANS: 'PledgeBank' here is the first part of the HTML title which appears on browser windows, and search engines
        $title .= ' - ' . _("Tell the world \"I'll do it, but only if you'll help\"");
    }

    # Okay, actual output
    header('Content-Type: text/html; charset=utf-8');
    page_cache_headers($params);
    $site = $microsite;
    if (!$site) $site = 'website';
    include_once "../templates/$site/header.php";

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
    global $lang, $langs;

    if ($page_plain_headers) {
        return;
    }

    static $footer_outputted = 0; 
    if ($footer_outputted) return;
    $footer_outputted = 1;

    // Just logged in, so show password set box if they don't have one
    global $P;
    $P = pb_person_if_signed_on();
    global $stash_in_stashpost;
    if ($P && $stash_in_stashpost && !$P->has_password()) {
        change_personal_details();
        $params['nolocalsignup'] = true; // don't show local signup form as well
    }

    $site = $microsite;
    if (!$site) $site = 'website';
    include_once "../templates/$site/footer.php";

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
    page_send_vary_header($params);
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
        #if (array_key_exists('cache-max-age', $params))
        #    header('Cache-Control: max-age=' . $params['cache-max-age']);
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
