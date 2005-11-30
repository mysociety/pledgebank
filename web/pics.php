<?php
/*
 * pics.php:
 * Display picture from database, saving in cache directory.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: pics.php,v 1.3 2005-11-30 17:43:32 francis Exp $
 * 
 */

require_once '../phplib/pb.php';
require_once '../../phplib/db.php';

$file = get_http_var('file');
if (!$file)
    err("Picture filename required");
if (preg_match('/[^a-z0-9-.]/i',$file)) 
    err("Invalid picture filename");

# Get from database
$data = db_getOne("select data from picture where filename = ?", array($file));
if (!$data)
    err("Picture file doesn't exist");
$data = pg_unescape_bytea($data);
if (preg_match('/.jpe?g$/i', $file))
    header("Content-type: image/jpeg");
elseif (preg_match('/.gif/i', $file))
    header("Content-type: image/jpeg");
elseif (preg_match('/.png/i', $file))
    header("Content-type: image/jpeg");
else
    err('Unknown image type');
print $data;

?>
