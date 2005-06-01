<?php
/*
 * announce.php:
 * Legacy code, redirect to ref-announce.php
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: announce.php,v 1.29 2005-06-01 14:00:33 francis Exp $
 * 
 */

require_once "../phplib/pb.php";
require_once "../phplib/db.php";
require_once "../phplib/fns.php";
require_once "../phplib/pledge.php";
require_once "../phplib/auth.php";

require_once "../../phplib/importparams.php";

$err = importparams( array('token', '/.+/', "Missing token"));
$data = auth_token_retrieve('announce-post', $q_token);
if (!$data) {
    err("Token now known, please check your link or try again.");
}
$pledge = new Pledge(intval($data['pledge_id']));
$url = person_make_signon_url(array("reason" => "send an announcement"), 
    $pledge->creator_email(), "GET", $pledge->url_announce(), array());
db_commit();
header("Location: $url");

?>
