<?php
/*
 * pics.php:
 * Display picture from database, saving in cache directory.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: pics.php,v 1.9 2007-08-18 09:58:01 matthew Exp $
 * 
 */

require_once '../phplib/pb.php';
require_once '../../phplib/conditional.php';
require_once '../../phplib/db.php';

$file = get_http_var('file');
if (!$file)
    err("Picture filename required");
if (preg_match('/[^a-z0-9-.]/i', $file)) 
    err("Invalid picture filename '$file'");

/* Implement conditional GET. */
$time = db_getOne('select extract(epoch from uploaded) from picture where filename = ?', $file);
if (!is_null($time)) cond_maybe_respond($time);

# Get from database
$data = db_getOne('select data from picture where filename = ?', $file);
if (!$data)
    err("Picture file doesn't exist", E_USER_NOTICE);
$data = pg_unescape_bytea($data);
if (preg_match('/\.jpe?g$/i', $file))
    header("Content-type: image/jpeg");
elseif (preg_match('/\.gif/i', $file))
    header("Content-type: image/gif");
elseif (preg_match('/\.png/i', $file))
    header("Content-type: image/png");
else
    err('Unknown image type');

cond_headers($time);
header("Content-Length: " . strlen($data));
print $data;

?>
