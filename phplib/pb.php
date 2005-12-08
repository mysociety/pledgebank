<?php
/*
 * pb.php:
 * General purpose functions specific to PledgeBank.  This must
 * be included first by all scripts to enable error logging.
 * This is only used by the web page PHP scripts, command line ones 
 * use pbcli.php.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: pb.php,v 1.57 2005-12-08 21:32:06 francis Exp $
 * 
 */

// Load configuration file
require_once "../conf/general";
// Some early config files - put most config files after language negotiation below
require_once "../../phplib/error.php";
require_once "../../phplib/locale.php";
require_once 'microsites.php';
require_once 'page.php';

// Googlebot is crawling all our domains for different languages/codes at 
// a high rate, which in combination is too much for our server.
$lockfilehandle = null;
if (array_key_exists('HTTP_USER_AGENT', $_SERVER) && stristr($_SERVER['HTTP_USER_AGENT'], "Googlebot")) {
    $lockfilehandle = fopen("../conf/general", "rw");
    if ($lockfilehandle)
        flock($lockfilehandle, LOCK_SH);
}

/* Output buffering: PHP's output buffering is broken, because it does not
 * affect headers. However, it's worth using it anyway, because in the common
 * case of outputting an HTML page, it allows us to clear any output and
 * display a clean error page when something goes wrong. Obviously if we're
 * displaying an error, a redirect, an image or anything else this will break
 * horribly.*/
ob_start();

/* pb_handle_error NUMBER MESSAGE
 * Display a PHP error message to the user. */
function pb_handle_error($num, $message, $file, $line, $context) {
    if (OPTION_PB_STAGING) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        page_header(_("Sorry! Something's gone wrong."), array('override'=>true));
        print("<strong>$message</strong> in $file:$line");
        page_footer(array('nolocalsignup'=>true));
    } else {
        /* Nuke any existing page output to display the error message. */
        while (ob_get_level()) {
            ob_end_clean();
        }
        /* Message will be in log file, don't display it for cleanliness */
        $err = p(_('Please try again later, or <a href="mailto:team@pledgebank.com">email us</a> for help resolving the problem.'));
        if ($num & E_USER_ERROR) {
            $err = "<p><em>$message</em></p> $err";
        }
        pb_show_error($err);
    }
}
err_set_handler_display('pb_handle_error');

# Extract language and country from URL.
# OPTION_WEB_HOST . OPTION_WEB_DOMAIN - default
# xx . OPTION_WEB_DOMAIN - xx is an ISO 639-1 country code
# xx . yy . OPTION_WEB_DOMAIN - xx is a country code, yy a language code (either aa or aa-bb)
$domain_lang = null;
$domain_country = null;
if (OPTION_WEB_HOST == 'www') {
    if (preg_match('#^(?:[^.]+|www)\.(..(?:-..)?)\.#', strtolower($_SERVER['HTTP_HOST']), $m))
        $domain_lang = $m[1];
} else {
    if (preg_match('#^'.OPTION_WEB_HOST.'(?:-[^.]+)?\.(..(?:-..)?)\.#', strtolower($_SERVER['HTTP_HOST']), $m))
        $domain_lang = $m[1];
}
if (OPTION_WEB_HOST == 'www') {
    if (preg_match('#^([^.]+)\.#', strtolower($_SERVER['HTTP_HOST']), $m))
        $domain_country = strtoupper($m[1]);
} else {
    if (preg_match('#^'.OPTION_WEB_HOST.'-([^.+])\.#', strtolower($_SERVER['HTTP_HOST']), $m))
        $domain_country = strtoupper($m[1]);
}

# Language negotiation
locale_negotiate_language(OPTION_PB_LANGUAGES, $domain_lang);
locale_change();
locale_gettext_domain('PledgeBank');

# Do includes after language negotiation, so translated globals
# are translated in them
require_once '../../phplib/countries.php';
require_once '../../phplib/db.php';
require_once '../../phplib/stash.php';
require_once "../../phplib/utility.php";
require_once "../../phplib/gaze.php";

# Country negotiation
# Find country for this IP address
$ip_country = gaze_get_country_from_ip($_SERVER['REMOTE_ADDR']);
if (rabx_is_error($ip_country) || !$ip_country)
    $ip_country = null;
else if (!array_key_exists($ip_country, $countries_code_to_name)) # make sure we know country
    $ip_country = null;
$microsite = null;
if (array_key_exists(strtolower($domain_country), $microsites_list)) {
    $microsite = strtolower($domain_country);
}
if ($domain_country) {
    if (array_key_exists('UK', $countries_code_to_name)) 
        err('UK in countries_code_to_name');
    if ($domain_country == 'UK') {
        $domain_country = 'GB';
    }
    if (!array_key_exists($domain_country, $countries_code_to_name)) {
        $domain_country = null;
    }
    $site_country = $domain_country;
} else {
    $site_country = $ip_country;
}
if ($site_country) {
    $microsite = null;
}

if ($site_country == null && $microsite == null) {
    # Without this, would go to 'Global' (only global pledges)
    $microsite = "everywhere";
}

/* POST redirects */
stash_check_for_post_redirect();

/* Date which PledgeBank application believes it is */
$pb_today = db_getOne('select pb_current_date()');
$pb_timestamp = substr(db_getOne('select pb_current_timestamp()'), 0, 19);
$pb_time = strtotime($pb_timestamp);

/* pb_show_error MESSAGE
 * General purpose eror display. */
function pb_show_error($message) {
    header('HTTP/1.0 500 Internal Server Error');
    page_header(_("Sorry! Something's gone wrong."), array('override'=>true));
    print _('<h2>Sorry!  Something\'s gone wrong.</h2>') .
        "\n<p>" . $message . '</p>';
    page_footer(array('nolocalsignup'=>true));
}


