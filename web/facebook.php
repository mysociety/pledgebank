<?php
// facebook.php:
// Canvas page for Facebook application for PledgeBank. This is called from
// URLs like http://apps.facebook.com/pledgebank/ and similar.
//
// Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: facebook.php,v 1.63 2007-11-22 12:20:30 francis Exp $

/*
TODO:

- Matthew doesn't get notifications :(
- Check out notifications.sendEmail
    http://developers.facebook.com/documentation.php?v=1.0&method=notifications.sendEmail
- Can send plain text as well as FBML version now?

- 64 bit ids http://developers.facebook.com/news.php?blog=1&story=45

- On pledge listing pages play with showing:
    * pledges which have lots of Facebook signers
    * featured pledges in facebook via mutual signer connections (first need to fix connections to use Facebook ids as well as emails?)
    * pledges made by / signed by people in same network (e.g. university) as you
    * fastest growing pledges
    * pledges signed by people with similar interests to you
- Show in "friends pledges" section ones *created* by friends separately

- Show explicitly on e.g. http://apps.facebook.com/pledgebank/list/friends
  that a pledge is closed.
- And also if you have already signed it.
- The link to the pledge being the title part doesn't make it clear you can
  click the link, especially when the sign button is there. Not sure what to
  do. Maybe make title 'Pledge "buttonmooon"' the link?

- "Share" links just uses text "Sign this pledge", could be better
- Text used in feed story should be better (maybe use pledge ref as in
  notifications short text)


- Ability to link existing PledgeBank account with Facebook account, so can show pledges already 
  created/signed on profile. (Put link on profile message "has not signed any pledges in Facebook"
  amongst other places)
- Ability to add pledges you haven't signed to your profile.
- Ability to remove pledges that you created/signed from your profile.
- Option to have dynamically changing featured pledges appear on your profile.

- Let people say "I've done it!" on the pledges.
- Send questionnaire to ask if they've done it.
- On profile page, highlight more successful pledges, and that you must do them / have done them.

- Use friend request form (mini?) rather than share link everywhere, as has confused people
  by starting a large thread. Maybe have "invite your friends" link explicitly?
- Let creators of pledges use fancy form to share it.

- Announce message shouldn't link to pledgebank.com, but to facebook.com
- Somehow remove extra login dialog which you get if you try to sign a pledge
  you haven't seen before, that is caused by post button. Not sure can be done
  really.

- Link to creators of via facebook pledges, and show their photo
- Tidy up display when lots of signers http://apps.facebook.com/pledgebank/Pauls-100mill

- Call http://wiki.developers.facebook.com/index.php/Feed.publishTemplatizedAction
  instead of PublishActionofUser
  See http://www.facebook.com/topic.php?uid=2205007948&topic=13926&start=30&hash=503b3e81edba5aeff0c1ac98eb58a61a at end for PHP function to add to the API file
- This gives some tips on news feeds.
  http://developers.facebook.com/news.php?blog=1&story=55

Not so important:
- Cache names of Facebook signers in the person table
- Add chivvy email about Facebook
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
    print "Doing test...";
    print "Nothing";
    exit;
}

// Beginning of main code
pbfacebook_init_webpage();

# print "<pre>";print_r($_POST);print "</pre>";

if (get_http_var("test")) {
    do_test();
}
$ref = get_http_var("ref");
if ($ref == 'new') {
    // Redirect to PledgeBank.com to make new pledges 
    $facebook->redirect(pbfacebook_new_pledge_url());
    exit;
}
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

    pbfacebook_render_header();
    pbfacebook_render_dashboard();
    if (get_http_var("really_sign_in_facebook")) {
        $verified = auth_verify_with_shared_secret($pledge->id().":".$facebook->get_loggedin_user(), OPTION_CSRF_SECRET, get_http_var("csrf_sig"));
        if ($verified) {
            pbfacebook_sign_pledge($pledge);
        }
    }
    pbfacebook_render_pledge($pledge);
    pbfacebook_render_footer();
}

