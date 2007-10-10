<?php
// facebook.php:
// Canvas page for Facebook application for PledgeBank. This is called from
// URLs like http://apps.facebook.com/pledgebank/ and similar.
//
// Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: facebook.php,v 1.48 2007-10-10 18:10:30 francis Exp $

/*

TODO:

- Peruse the new http://bugs.developers.facebook.com/
- Experiment with notifications.send WITHOUT sending email - looks like that
  will be the only option left from the notifications API.

- "The news that you've signed could not be added to your feed." when Tom signed pledge
- Post in news when new pledge created - doesn't seem to work?

- Text on message for "share" links is not perfect.
- Finish links from rest of PledgeBank site.

- Add chivvy email about Facebook

- Text that is shown by Facebook when you add application. (Heather to write)

- Add link to source code

- Show explicitly on e.g. http://apps.facebook.com/pledgebank/list/friends
  that a pledge is closed.
- And also that you have already signed it

Improvements:
- Show pledges which have lots of Facebook signers
- Find pledges by people with similar interests, or who've signed similar pledges etc.
- Include featured pledges on EVERYONE's profile
- Link to creators of via facebook pledges, and show their photo
- Show in "friends pledges" section ones created by friends separately
- Show on http://apps.facebook.com/pledgebank ones you've already signed clearer
- Somehow actually send notifications for success, rather than news post
- On profile page, highlight more successful pledges, and that you must do them.
- Let people say "I've done it!" on the pledges on their profile.
- Show comments (wall!) on Facebook pledges -- use fb:comments?
- Aggregate multiple announce messages more intelligently - if user posts two messages
  within 12 hours, then only post once on news feed. Or link to specific messages from
  news feed, rather than to general pledge.
- Show fastest growing pledges
- Let people remove pledges from their profile page
- Show featured pledges in facebook via mutual signer connections
  (first need to fix connections to use Facebook ids as well as emails?)
- Show pledges made by / signed by people in same network as you
- Richard wants to be able to add app and link to existing PB account so can show pledges on his profile
  And to be able to add it to show his willingness to use it, but without signing a pledge yet
  link from profile message "has not signed any pledges in Facebook"

Not so important:
- Test what happens if you add app, but refuse each of the major permissions
- Lower case and fuzzy matching of pledge refs
- Detect language that Facebook is using, and tell PledgeBank pages to use that.
- Post in friend's news feed when a pledge is successful (but only once if multiple!)

*/

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';

require_once '../phplib/pbfacebook.php';

$page_plain_headers = true;

// Test function
function do_test() {
    global $facebook;
#    $facebook->require_login();
#    pbfacebook_update_profile_box($facebook->get_loggedin_user());
    print "Doing test...";

#    $ret = pbfacebook_send_internal(582616613, "Hello you");
#    print_r($ret);

#    $notifications = $facebook->api_client->friends_get();
    #$notifications = $facebook->api_client->notifications_get();
#    print_r($notifications);

#    $ret = $facebook->api_client->notifications_send(703090157 /*582616613*/, "Notification from Opera!", "Wouldn't it ROCK if you could click here and sign up to the awesomest pledge.");
#    print_r($ret);
#    print "<p>Done";

    exit;
}

// Beginning of main code
pbfacebook_init_webpage();

if (get_http_var("test")) {
    do_test();
}
$ref = get_http_var("ref");
if (!$ref || is_null(db_getOne('select ref from pledges where ref = ?', $ref))) {
    pbfacebook_render_header();
    pbfacebook_render_dashboard();
    if (get_http_var('sent')) {
        print "<p class=\"formnote\">"._("Thanks for sending the pledge to your friends!").
            "<br/>"._("Here are some more pledges you might like.")."</p>";
    }
    pbfacebook_render_frontpage(get_http_var("list"));
    pbfacebook_render_footer();
} else {
    $pledge = new Pledge($ref);
    if ($pledge->pin()) {
        err("PIN protected pledges can't be accessed from Facebook");
    }
    if (get_http_var("sign_in_facebook")) {
        $facebook->require_add('/'.$pledge->ref()."/?sign_in_facebook=1");
        pbfacebook_render_header();
        pbfacebook_render_sign_confirm($pledge);
        pbfacebook_render_footer();
        exit;
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

    pbfacebook_render_header();
    pbfacebook_render_dashboard();
    if (get_http_var("really_sign_in_facebook")) {
        $verified = auth_verify_with_shared_secret($pledge->id().":".$facebook->get_loggedin_user(), OPTION_CSRF_SECRET, get_http_var("csrf_sig"));
        if ($verified) {
            pbfacebook_sign_pledge($pledge);
        }
    }
    if ($no_send_error)
        print '<p class="errors">'."Sorry, couldn't send the pledge to your friends, probably because you've sent too many messages in too short a time.".'</p>';
    pbfacebook_render_pledge($pledge);
    pbfacebook_render_footer();
}

