<?php
// facebook.php:
// Facebook application for PledgeBank.
//
// Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: facebook.php,v 1.17 2007-07-05 20:37:08 francis Exp $

/*

TODO:

- Adding app while signing requires two clicks :(
- Adding app while 'inviting friends', check works OK
- After sending invitation text and URL is a bit rubbish

- Fix sorting of pledges in profile box
- Fix $invite_intro stuff that isn't used

- Display list of Facebook signers on Facebook pledge

- Link to creator
- Link to rest of site

Improvements:
- Post in friend's news feed when a pledge is successful (but only once if multiple!)
- Somehow actually send notifications for success
- Let people say "I've done it!" on the pledges on their profile.
- Show comments (wall!) on Facebook pledges
- Let people add comments to Facebook pledges
- Aggregate multiple announce messages more intelligently - if user posts two messages
  within 12 hours, then only post once on news feed. Or link to specific messages from
  news feed, rather than to general pledge.

Not so important:
- Test what happens if you add app, but refuse each of the major permissions
- Lower case and fuzzy matching of pledge refs
- Detect language that Facebook is using, and tell PledgeBank pages to use that.

*/

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';

# XXX clean up this mess
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

require_once '../phplib/pbfacebook.php';

$page_plain_headers = true;

function do_test() {
    global $facebook;
#    $facebook->require_login();
#    pbfacebook_update_profile_box($facebook->get_loggedin_user());
    print "Doing test";

#    $ret = pbfacebook_send_internal(582616613, "Hello you");
#    print_r($ret);

#    $notifications = $facebook->api_client->friends_get();
    #$notifications = $facebook->api_client->notifications_get();
#    print_r($notifications);
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

