<?php
/*
 * pb.php:
 * General purpose functions specific to PledgeBank.  This must
 * be included first by all scripts to enable error logging.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: pb.php,v 1.22 2005-07-29 13:18:19 matthew Exp $
 * 
 */

// Load configuration file
require_once "../conf/general";
require_once '../../phplib/db.php';
require_once '../../phplib/stash.php';
require_once "../../phplib/error.php";
require_once "../../phplib/utility.php";
require_once 'page.php';

# Language stuff
# require_once 'HTTP.php';
# Translations available of PledgeBank
$langs = array('en-gb'=>'English', 'pt-br'=>'Portugu&ecirc;s (Brazil)');
# Map of lang to directory
$langmap = array('en-gb'=>'en_GB', 'pt-br'=>'pt_BR');
if (preg_match('#^'.OPTION_WEB_HOST.'-(.*?)\.#', strtolower($_SERVER['HTTP_HOST']), $m) && array_key_exists($m[1], $langs))
    $lang = $m[1];
else {
    $lang = negotiateLanguage($langs);
    if ($lang=='en-US' || !$lang || !array_key_exists($lang, $langmap)) $lang = 'en'; # Default override
}

putenv('LANG='.$langmap[$lang].'.UTF-8');
setlocale(LC_ALL, $langmap[$lang].'.UTF-8');
bindtextdomain('PledgeBank', '../../locale');
textdomain('PledgeBank');

/* POST redirects */
stash_check_for_post_redirect();

/* Date which PledgeBank application believes it is */
$pb_today = db_getOne('select pb_current_date()');
$pb_timestamp = substr(db_getOne('select pb_current_timestamp()'), 0, 19);
$pb_time = strtotime($pb_timestamp);

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
        page_header(_("Sorry! Something's gone wrong."));
        print("<strong>$message</strong> in $file:$line");
        page_footer();
    } else {
        /* Nuke any existing page output to display the error message. */
        ob_clean();
        /* Message will be in log file, don't display it for cleanliness */
        $err = p(_('Please try again later, or <a href="mailto:team@pledgebank.com">email us</a> for help resolving the problem.'));
        if ($num & E_USER_ERROR) {
            $err = "<p><em>$message</em></p> $err";
        }
        pb_show_error($err);
    }
}
err_set_handler_display('pb_handle_error');

/* pb_show_error MESSAGE
 * General purpose eror display. */
function pb_show_error($message) {
    page_header(_("Sorry! Something's gone wrong."), array('override'=>true));
    print _('<h2>Sorry!  Something\'s gone wrong.</h2>') .
        "\n<p>" . $message . '</p>';
    page_footer();
}

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

?>
