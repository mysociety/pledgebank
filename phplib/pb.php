<?php
/*
 * pb.php:
 * General purpose functions specific to PledgeBank.  This must
 * be included first by all scripts to enable error logging.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: pb.php,v 1.1 2005-02-23 15:44:59 francis Exp $
 * 
 */

// Load configuration file
require_once "../conf/general";

require_once '../templates/page.php';

require_once "../../phplib/error.php";
require_once "../../phplib/utility.php";

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
        print("<strong>$message</strong> in $file:$line");
    } else {
        /* Nuke any existing page output to display the error message. */
        ob_clean();
        /* Message will be in log file, don't display it for cleanliness */
        pb_show_error("Please try again later, or <a
            href=\"mailto:contact@pledgebank.com\">email us</a> to let us know.");
    }
}
err_set_handler_display('pb_handle_error');

/* pb_show_error MESSAGE
 * General purpose eror display. */
function pb_show_error($message) {
    page_header("Sorry! Something's gone wrong.");
?>
<h2>Sorry!  Something's gone wrong.</h2>
<p><?=$message ?></p>
<?
    page_footer();
}

?>
