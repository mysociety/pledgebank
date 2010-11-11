<?php
/* 
 * pbcli.php:
 * Include file for PHP CLI scripts for PledgeBank.
 * Use pb.php instead for web pages.
 *
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org/
 *
 * $Id: pbcli.php,v 1.7 2007-08-10 03:02:02 matthew Exp $
 *
 */

require_once "../conf/general";
require_once '../commonlib/phplib/phpcli.php';
require_once '../commonlib/phplib/db.php';
require_once '../commonlib/phplib/locale.php';
require_once '../commonlib/phplib/countries.php';

/* Date which PledgeBank application believes it is */
$pb_today = db_getOne('select ms_current_date()');
$pb_timestamp = db_getOne('select ms_current_timestamp()');
$pb_time = strtotime(substr($pb_timestamp, 0, 19));

/* Language negotiation */
locale_negotiate_language(OPTION_PB_LANGUAGES, 'en-gb');
locale_change();
locale_gettext_domain(OPTION_PB_GETTEXT_DOMAIN);

$microsite = null;
