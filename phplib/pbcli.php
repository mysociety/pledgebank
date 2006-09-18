<?php
/* 
 * pbcli.php:
 * Include file for PHP CLI scripts for PledgeBank.
 * Use pb.php instead for web pages.
 *
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org/
 *
 * $Id: pbcli.php,v 1.4 2006-09-18 12:37:18 francis Exp $
 *
 */

require_once "../conf/general";
require_once '../../phplib/phpcli.php';
require_once '../../phplib/db.php';
require_once '../../phplib/locale.php';
require_once '../../phplib/countries.php';

/* Date which PledgeBank application believes it is */
$pb_today = db_getOne('select ms_current_date()');
$pb_timestamp = substr(db_getOne('select ms_current_timestamp()'), 0, 19);
$pb_time = strtotime($pb_timestamp);

/* Language negotiation */
locale_negotiate_language(OPTION_PB_LANGUAGES, 'en-gb');
locale_change();
locale_gettext_domain('PledgeBank');


