<?php
/*
 * ref-share.php:
 * Front end for non-JS browsers
 * 
 * Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-share.php,v 1.2 2007-11-06 16:27:24 matthew Exp $
 * 
 */

require_once '../phplib/pb.php';
require_once '../phplib/pledge.php';
require_once '../phplib/share.php';

page_check_ref(get_http_var('ref'));
$p = new Pledge(get_http_var('ref'));
microsites_redirect($p);
deal_with_pin($p->url_email(), $p->ref(), $p->pin());

$params = array(
    'ref' => $p->ref(),
    'pref' => $p->url_typein(),
    'css' => '/jslib/share/share.css',
);
page_header(_('Share this'), $params);
$p->render_box(array('showdetails'=>true));
pb_share_page($p);
page_footer();

