<?php
// facebook.php:
// Facebook application for PledgeBank.
//
// Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: facebook.php,v 1.14 2007-06-24 12:41:30 francis Exp $

/*

TODO:

- Success / failures / announce messages
- Store infinite session key or keys for Pledge success cron job.
  http://wiki.developers.facebook.com/index.php/Infinite_session_keys

- Adding app while 'inviting friends', check works OK

- Don't use mySociety logo for notification icon
- Fix sorting of pledges in profile box
- Fix $invite_intro stuff that isn't used

- Posting sending message sucks.

- Update the pledges on everyone's profile with new numbers of signers
    http://dev.formd.net/facebook/lastfmCharts/tutorial.html

- Test what happens if you add app, but refuse each of the major permissions

Not so important
- Lower case and fuzzy matching of pledge refs
- Detect language that Facebook is using, and tell PledgeBank pages to use that.

*/

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/pbfacebook.php';

function pbfacebook_after_sent() {
    header('Location: '.OPTION_FACEBOOK_CANVAS);
    exit;
}
if (get_http_var('sent')) {
    # XXX I don't understand why we get sent back here after doing a
    # notifications_send. If you try to instantiate the Facebook $facebook
    # object you get redirected more confusingly in some sort of infinite loop.
    # This is the best I could do to regain control after the callback URL.
    pbfacebook_after_sent();
}

if (OPTION_PB_STAGING) 
    $GLOBALS['facebook_config']['debug'] = true;
$GLOBALS['facebook_config']['debug'] = false; # comment out for debug of FB calls
$page_plain_headers = true;

require_once '../../phplib/facebookphp4/facebook.php';

function do_test() {
    global $facebook;
#    $facebook->require_login();
#    pbfacebook_update_profile_box($facebook->get_loggedin_user());
    print "Doing test";

    $notifications = $facebook->api_client->friends_get();
    #$notifications = $facebook->api_client->notifications_get();
    print_r($notifications);
    exit;
}

// Beginning of main code
pbfacebook_init_webpage();

if (get_http_var("test")) {
    do_test();
}
$ref = get_http_var("ref");
if (is_null(db_getOne('select ref from pledges where ref = ?', $ref))) {
    $ref = null;
    $pledge = null;
    pbfacebook_render_header();
    pbfacebook_render_dashboard();
    pbfacebook_render_frontpage();
    pbfacebook_render_footer();
} else {
    $pledge = new Pledge($ref);
    if ($pledge->pin()) {
        err("PIN protected pledges can't be accessed from Facebook");
    }
    if (get_http_var("sign_in_facebook")) {
        $facebook->require_add('/'.$pledge->ref()."/?sign_in_facebook=1&csrf=".get_http_var('csrf'));
        $verified = auth_verify_with_shared_secret($pledge->id().":".$facebook->get_loggedin_user(), OPTION_CSRF_SECRET, get_http_var("csrf"));
    }
    $no_send_error = false;
    if (get_http_var("invite_friends")) {
        $facebook->require_add('/'.$pledge->ref());
        if (array_key_exists('ids', $_POST)) {
            if (!pbfacebook_send_to_friends($pledge,$_POST['ids'])) {
                $no_send_error = true;
            }
        }
    }
#    print_r($_POST);
#    print_r($_GET);

    pbfacebook_render_header();
    pbfacebook_render_dashboard();
    if ($no_send_error)
        print '<p class="errors">'."Sorry, couldn't send the pledge to your friends, probably because you've sent too many messages in too short a time.".'</p>';
    if (get_http_var("sign_in_facebook") && $verified) {
        $pledge = pbfacebook_sign_pledge($pledge);
    }
    pbfacebook_render_pledge($pledge);
    pbfacebook_render_footer();
}

