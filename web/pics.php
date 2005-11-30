<?php
/*
 * pics.php:
 * Display picture from database, saving in cache directory.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: pics.php,v 1.2 2005-11-30 17:28:50 francis Exp $
 * 
 */

require_once '../phplib/pb.php';
require_once '../../phplib/db.php';

$file = get_http_var('file');
if (!$file)
    err("Picture filename required");
if (preg_match('/[^a-z0-9-.]/i',$file)) 
    err("Invalid picture filename");

# Legacy: Look for file in filesystem, copy into database
$filesystem_name = OPTION_PB_PICTURE_DIR . "/" . $file;
if (file_exists($filesystem_name)) {
    $picture_contents = file_get_contents($filesystem_name);
    if (!$picture_contents)
        err("Failed to read file into memory");
    db_query("delete from picture where filename = ?", array($file));
    db_query("insert into picture (filename, data) values ('$file', ".
        "'".pg_escape_bytea($picture_contents)."')");
    db_commit();
}

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
