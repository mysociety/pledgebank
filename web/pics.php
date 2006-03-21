<?php
/*
 * pics.php:
 * Display picture from database, saving in cache directory.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: pics.php,v 1.6 2006-03-21 17:24:32 chris Exp $
 * 
 */

require_once '../phplib/pb.php';
require_once '../../phplib/db.php';

$file = get_http_var('file');
if (!$file)
    err("Picture filename required");
if (preg_match('/[^a-z0-9-.]/i', $file)) 
    err("Invalid picture filename '$file'");

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

header("Content-Length: " . strlen($data));
/* XXX should also record time picture was uploaded and send a Last-Modified:
 * for that time. */
print $data;

?>
