<?php
/*
 * ref-share.php:
 * Front end for non-JS browsers
 * 
 * Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-share.php,v 1.1 2007-11-02 15:36:07 matthew Exp $
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
    'css' => '/share/share.css',
);
page_header(_('Share this'), $params);
$p->render_box(array('showdetails'=>true));
share_page($p);
page_footer();

function share_page($p) {
    echo '<div id="col1">';
    echo p(_('From this page you can use the <em>Social Web</em> links to save this pledge to a social bookmarking site, or the <em>Email</em> form to send a link via email.'));
    echo h2(_('Social Web'));
    share_form_social($p);
    echo '<h2 style="margin-top: 1em">' . _('Email') . '</h2>';
    share_form_email($p->ref());
    echo '</div>';
}

