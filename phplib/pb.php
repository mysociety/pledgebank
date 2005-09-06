<?php
/*
 * pb.php:
 * General purpose functions specific to PledgeBank.  This must
 * be included first by all scripts to enable error logging.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: pb.php,v 1.34 2005-09-06 10:21:36 francis Exp $
 * 
 */

// Load configuration file
require_once "../conf/general";
require_once '../../phplib/db.php';
require_once '../../phplib/stash.php';
require_once "../../phplib/error.php";
require_once "../../phplib/utility.php";
require_once "../../phplib/gaze.php";
require_once 'page.php';

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
    if (preg_match('#^..\.(..(?:-..)?)\.#', strtolower($_SERVER['HTTP_HOST']), $m))
        $domain_lang = $m[1];
} else {
    if (preg_match('#^'.OPTION_WEB_HOST.'-..\.(..(?:-..)?)\.#', strtolower($_SERVER['HTTP_HOST']), $m))
        $domain_lang = $m[1];
}

if (OPTION_WEB_HOST == 'www') {
    if (preg_match('#^(..)\.#', strtolower($_SERVER['HTTP_HOST']), $m))
        $domain_country = strtoupper($m[1]);
} else {
    if (preg_match('#^'.OPTION_WEB_HOST.'-(..)\.#', strtolower($_SERVER['HTTP_HOST']), $m))
        $domain_country = strtoupper($m[1]);
}

# Language negotiation
$pb_langs = explode('|', OPTION_PB_LANGUAGES);
$langs = array(); $langmap = array();
foreach ($pb_langs as $pb_lang) {
    list($code, $verbose, $locale) = explode(',', $pb_lang);
    $langs[$code] = $verbose;
    $langmap[$code] = $locale;
}
if ($domain_lang && array_key_exists($domain_lang, $langs))
    $lang = $domain_lang;
else {
    $lang = negotiateLanguage($langs); # local copy, see further down this file
    if ($lang=='en-US' || !$lang || !array_key_exists($lang, $langmap)) $lang = 'en-gb'; # Default override
}

# Country negotiation
# Find country for this IP address
$ip_country = gaze_get_country_from_ip($_SERVER['REMOTE_ADDR']);
if (rabx_is_error($ip_country) || !$ip_country)
    $ip_country = null;
$site_country = $domain_country;
if (!$domain_country) 
    $site_country = $ip_country;
if ($site_country) {
    if (array_key_exists('UK', $countries_code_to_name)) 
        err('UK in countries_code_to_name');
    if ($site_country == 'UK') {
        $site_country = 'GB';
    }
    if (!array_key_exists($site_country, $countries_code_to_name)) {
        $site_country = null;
    }
}

/* Note: To get a language working from PHP on Unix, you also need
to install the system locale for that language. In Debian this is done
using "dpkg-reconfigure locales". You may need to restart Apache also. */
putenv('LANG='.$langmap[$lang].'.UTF-8');
setlocale(LC_ALL, $langmap[$lang].'.UTF-8');
bindtextdomain('PledgeBank', '../../locale');
textdomain('PledgeBank');
bind_textdomain_codeset('PledgeBank', 'UTF-8');

/* POST redirects */
stash_check_for_post_redirect();

/* Date which PledgeBank application believes it is */
$pb_today = db_getOne('select pb_current_date()');
$pb_timestamp = substr(db_getOne('select pb_current_timestamp()'), 0, 19);
$pb_time = strtotime($pb_timestamp);

/* pb_show_error MESSAGE
 * General purpose eror display. */
function pb_show_error($message) {
    page_header(_("Sorry! Something's gone wrong."), array('override'=>true));
    print _('<h2>Sorry!  Something\'s gone wrong.</h2>') .
        "\n<p>" . $message . '</p>';
    page_footer(array('nolocalsignup'=>true));
}

# PHP's own negotiateLanguage in HTTP.php is broken in old versions, so we use a copy
function negotiateLanguage(&$supported) {
    $supported = array_change_key_case($supported, CASE_LOWER);
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $accepted = preg_split('/\s*,\s*/', strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])));
        for ($i = 0; $i < count($accepted); $i++) {
            if (preg_match('/^([a-z_-]+);\s*q=([\d\.]+)/', $accepted[$i], $arr)) {
                $q = (double)$arr[2];
                $l = $arr[1];
            } else {
                $q = 1;
                $l = $accepted[$i];
            }
            if ($q > 0.0) {
                if (!empty($supported[$l])) {
                    if ($q == 1) {
                        return $l;
                    }
                    $candidates[$l] = $q;
                } else {
                    foreach (array_keys($supported) as $value) {
                        if (preg_match("/^$l-/",$value)) {
                            if ($q == 1) {
                                return $value;
                            }
                            $candidates[$value] = $q;
                            break;
                        }
                    }
                }
            }
        }
        if (isset($candidates)) {
            arsort($candidates);
            reset($candidates);
            return key($candidates);
        }
    }
}

function pb_site_country_name() {
    global $countries_code_to_name, $site_country; 
    return $site_country ? $countries_code_to_name[$site_country] : 'Global';
}

