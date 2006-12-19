<?php
// rsscomments.php:
// RSS feed of all comments.
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: rsscomments.php,v 1.1 2006-12-19 22:58:44 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/comments.php';

$heading = _("Comments on all pledges");
rss_header($heading, $heading, array(
        /* 'last-modified' => $last_modified */
));
$rss_items = comments_show_latest(20, 1);
rss_footer($rss_items);


