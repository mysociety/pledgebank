<?php
/*
 * share.php:
 * PledgeBank specific code for Share this functionality.
 * 
 * Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: share.php,v 1.4 2007-11-06 16:27:23 matthew Exp $
 * 
 */

require_once '../commonlib/phplib/sharethis.php';

function pb_share_details($p) {
    $name = ''; $email = '';
    $P = pb_person_if_signed_on();
    if (!is_null($P)) {
        $name = $P->name_or_blank();
        $email = $P->email();
    }

    return array(
        'name' => $name,
	'email' => $email,
	'url' => $p->url_typein(),
	'title' => 'PledgeBank pledge ' . $p->ref(),
	'email_url' => '/' . $p->ref() . '/email',
    );
}

function pb_share_form($p) {
    $data = pb_share_details($p);
    share_form($data['url'], $data['title'],
        $data['email_url'], $data['name'], $data['email']);
}

function pb_share_page($p) {
    $data = pb_share_details($p);
    echo '<div id="col1">';
    echo p(_('From this page you can use the <em>Social Web</em> links to save this pledge to a social bookmarking site, or the <em>Email</em> form to send a link via email.'));
    echo h2(_('Social Web'));
    share_form_social($data['url'], $data['title']);
    echo '<h2 style="margin-top: 1em">' . _('Email') . '</h2>';
    share_form_email($data['email_url'], $data['name'], $data['email']);
    echo '</div>';
}

