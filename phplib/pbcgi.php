<?php
/* 
 * pbcgi.php:
 * Include file for PHP CGI scripts for PledgeBank
 *
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org/
 *
 * $Id: pbcgi.php,v 1.1 2005-09-10 12:32:25 francis Exp $
 *
 */

require_once "../conf/general";
require_once '../../phplib/phpcgi';
require_once '../../phplib/db.php';

/* Date which PledgeBank application believes it is */
$pb_today = db_getOne('select pb_current_date()');
$pb_timestamp = substr(db_getOne('select pb_current_timestamp()'), 0, 19);
$pb_time = strtotime($pb_timestamp);

