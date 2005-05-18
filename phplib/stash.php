<?php
/*
 * stash.php:
 * Stash and retrieve request parameters, for deferred activities like login.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: stash.php,v 1.1 2005-05-18 12:56:56 chris Exp $
 * 
 */

require_once '../../phplib/error.php';
require_once '../../phplib/rabx.php';   /* for serialise/unserialise */
require_once '../../phplib/utility.php';

require_once '../phplib/db.php';

db_connect();

/* stash_request [EXTRA]
 * Stash details of the request (i.e., method, URL, and any URL-encoded form
 * parameters in the content) in the database, and return a key for the stashed
 * data. EXTRA is an optional extra string stored with the stashed request. */
function stash_request($extra = null) {
    $key = bin2hex(random_bytes(4));
    $url = invoked_url();
    if (!is_null($_SERVER['QUERY_STRING']))
        $url .= "?${_SERVER['QUERY_STRING']}";
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        db_query('
                insert into requeststash (key, method, url, extra)
                values (?, ?, ?, ?)',
                array($key, 'GET', $url, $extra));
    } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        /* Save form data too. */
        $ser = '';
        rabx_wire_wr($_POST, $ser);
        db_query('
                insert into requeststash (key, method, url, post_data, extra)
                values (?, ?, ?, ?, ?)',
                array($key, 'POST', $url, $ser, $extra));
    } else
        err("Cannot stash request for method '${_SERVER['REQUEST_METHOD']}'");

    /* Also take this opportunity to remove old stashed state from the db. */
    db_query("delete from requeststash where whensaved < pb_current_timestamp() - '7 days'::interval");

    return $key;
}

/* stash_redirect KEY
 * Redirect the user (either by means of an HTTP redirect, for a GET request,
 * or by constructing a form, for a POST request) into the context of the
 * stashed request identified by KEY. */
function stash_redirect($key) {
    list($method, $url, $post_data) = db_getRow_list('select method, url, post_data from requeststash where key = ?', $key);
    if (is_null($method))
        err("Unknown stash ID '$key'");
    if (headers_sent())
        err("Headers have already been sent in stash_redirect('$key')");
    if ($method == 'GET') {
        /* should we ob_clean here? */
        header("Location: $url");
        exit();
    } else {
        /* Postgres/PEAR DB BYTEA madness -- see comment in auth.php. */
        $post_data = pg_unescape_bytea($post_data);
        $pos = 0;
        $stashed_POST = rabx_wire_rd(&$post_data, &$pos);
        if (rabx_is_error($stashed_POST))
            err("Bad serialised POST data in stash_redirect('$key')");
        /* Nasty. XXX can we manually stuff things into the $_POST array
         * instead? Not in general, because that could turn a POST into a GET,
         * but perhaps.... */
        ob_clean();
        header("Content-Type: text/html; charset=utf-8");
        ?>
<html><head><title>Redirect...</title></head><body onload="document.f.submit()">
<form name="f" method="POST" action="<?=htmlspecialchars($url)?>">
<?
        foreach ($stashed_POST as $k => $v)
            printf('<input type="hidden" name="%s" value="%s">', htmlspecialchars($k), htmlspecialchars($v));

        $i = 0;
        while (array_key_exists("__stash_submit_button_$i", $stashed_POST))
            ++$i;
        ?>
<input type="submit" name="__stash_submit_button_<?=$i?>" id="__stash_submit_button" value="Click here to continue...">
</form></body></html>
<?
        exit();
    }
}

/* stash_get_extra KEY
 * Return any extra data from that stashed with KEY. */
function stash_get_extra($key) {
    return db_getOne('select extra from requeststash where key = ?', $key);
}

/* stash_delete KEY
 * Delete any stashed request identified by KEY. */
function stash_delete($key) {
    db_query('delete from requeststash where key = ?', $key);
}

?>
