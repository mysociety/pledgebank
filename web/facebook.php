<?php
// facebook.php:
// Facebook application for PledgeBank.
//
// Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: facebook.php,v 1.12 2007-06-24 02:42:35 francis Exp $

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

// Write the static FBML to the given user's profile box
function pbfacebook_update_profile_box($uid) {
    global $facebook;

    $out = "";
    $q = db_query("SELECT pledges.*, country, 
            (SELECT COUNT(*) FROM signers WHERE signers.pledge_id = pledges.id) AS signers
            FROM pledges 
            LEFT JOIN location ON location.id = pledges.location_id
            LEFT JOIN person ON person.id = pledges.person_id
            WHERE pin IS NULL AND 
            (person.facebook_id = ?
            OR pledges.id IN (SELECT pledge_id FROM signers LEFT JOIN person on person.id = signers.person_id
                    WHERE facebook_id = ?))",
            array($uid, $uid));
    if (db_num_rows($q) > 0) {
        $out .= "
<fb:if-is-own-profile>
    You have signed these Pledges.
<fb:else>
    <fb:name uid=\"$uid\"/> has signed these Pledges.
</fb:else>
</fb:if-is-own-profile>
";
        $out .= '<ol>';
        while ($r = db_fetch_array($q)) {
            $pledge = new Pledge($r);
            $out .= '<li>';
            $out .= pbfacebook_render_share_pledge($pledge);
            $out .= $pledge->summary(array('html'=>true, 'href'=>OPTION_FACEBOOK_CANVAS.$pledge->ref(), 'showcountry'=>true));
            #$out .= $pledge->render_box(array('class'=>'', 'facebook'=>true));
            $out .= '</li>';
        }
        $out .= '</ol>';
        $out .= "<p><a href=\"".OPTION_FACEBOOK_CANVAS."\">Find more pledges to sign</a>.</p>";
    } else {
    $out = "
<fb:if-is-own-profile>
    You haven't signed any pledges in Facebook yet.
<fb:else>
    <fb:name uid=\"$uid\"/> has not signed any pledges in Facebook.
</fb:else>
</fb:if-is-own-profile>
<p><a href=\"".OPTION_FACEBOOK_CANVAS."\">Find a pledge to sign</a>.</p>
";

    }

    $ret = $facebook->api_client->profile_setFBML($out, $uid);
    if ($ret != 1) err("Error calling profile_setFBML");
}

// Draw pledge index page within Facebook
function pbfacebook_render_pledge($pledge) {
    global $facebook;

    $invite_intro = "Invite your friends to also sign this pledge.";
    $signer_id = db_getOne("select signers.id from signers left join person on person.id = signers.person_id
            where facebook_id = ? and signers.pledge_id = ?", array($facebook->get_loggedin_user(), $pledge->id()));
    if ($signer_id && !$pledge->finished()) {
        print "<h2 style=\"text-align: center\">$invite_intro</h2>";
        print '
<fb:editor action="" labelwidth="200">
    <fb:editor-custom label="Friends">
        <fb:multi-friend-input/>
    </fb:editor-custom>
    <fb:editor-buttonset>
        <fb:editor-button value="Send Pledge"/>
    </fb:editor-buttonset>
    <input type="hidden" name="invite_friends" value="1">
</fb:editor>
';
    }

    pledge_draw_status_plaque($pledge);

#function auth_verify_with_shared_secret($item, $secret, $signature) {
    $csrf_sig = auth_sign_with_shared_secret($pledge->id().":".$facebook->get_loggedin_user(), OPTION_CSRF_SECRET);
    $pledge->render_box(array('class'=>'', 
            'facebook-sign'=>!$pledge->finished() && !$signer_id, 'facebook-sign-csrf'=>$csrf_sig,
            'showdetails' => true));

    if (!$signer_id)
        print pbfacebook_render_share_pledge($pledge);

    print '<p style="text-align:center">Visit this pledge at ';
    print '<a href="'.$pledge->url_typein().'">';
    print '<strong>'. str_replace('http://', '', $pledge->url_typein()) . '</strong>';
    print '</a>';
    print " for comments, flyers, SMS signup and more.";
    print '</p>';
}

// Draw comments on pledges onto Facebook
// XXX Not finished/used
function pbfacebook_render_comments() {
?>
<fb:wall>
  <fb:wallpost uid="1000550" t="1180070566">
    Whoa, I wrote on a wall!
      <fb:wallpost-action href="reply.php">
        Reply
      </fb:wallpost-action>
  </fb:wallpost>
</fb:wall>
<?
}

// Draw "share" button for a pledge on Facebook
function pbfacebook_render_share_pledge($pledge) {
    $out = "<div style=\"float: right\">";
    $out .='
      <fb:share-button class="meta">
          <meta name="title" content="Sign this pledge"/>
          <meta name="description" content="'.$pledge->h_sentence(array('firstperson'=>'includename')).'"/>
          <link rel="target_url" href="'.OPTION_FACEBOOK_CANVAS.$pledge->ref().'"/>
      </fb:share-button>';
    $out .= "</div>";
    return $out;
}

// Render common top of PledgeBank pages on Facebook
function pbfacebook_render_dashboard() {
?>
<fb:dashboard>
  <fb:action href="<?=OPTION_FACEBOOK_CANVAS?>">All Pledges</fb:action>
  <fb:action href="<?=pb_domain_url(array('path'=>'/new'))?>">Create a New Pledge</fb:action>
  <fb:help href="<?=pb_domain_url(array('path'=>'/faq'))?>" title="Need help">Help</fb:help>
</fb:dashboard>
<?
  /*<fb:create-button href="<?=pb_domain_url(array('path'=>'/new'))?>">Create a New Pledge</fb:create-button> */
}

// Render frontpage of PledgeBank on Facebook
function pbfacebook_render_frontpage() {
    global $facebook, $pb_today;
/*<fb:tabs>
<fb:tab-item title="Friends pledges" selected="true" href="http://apps.facebook.com/pledgebank/list/friends" />
<fb:tab-item title="Recent pledges" href="http://apps.facebook.com/pledgebank/list/recent" />
<fb:tab-item title="Successful pledges" href="http://apps.facebook.com/pledgebank/list/success" />
</fb:tabs>*/

    $friends_signed_joined = "";
    if ($facebook->get_loggedin_user()) {
        $friends = $facebook->api_client->friends_get();
        $friends_joined = join(",", $friends);
        $query = "SELECT pledges.*, country, 
                (SELECT COUNT(*) FROM signers WHERE signers.pledge_id = pledges.id) AS signers
                FROM pledges 
                LEFT JOIN location ON location.id = pledges.location_id
                LEFT JOIN person ON person.id = pledges.person_id
                WHERE pin IS NULL AND 
                (person.facebook_id in ($friends_joined)
                OR pledges.id IN (SELECT pledge_id FROM signers LEFT JOIN person on person.id = signers.person_id
                        WHERE facebook_id in ($friends_joined)))";
        $friends_signed = array();
        $q = db_query($query);
        if (db_num_rows($q) > 0) {
            if (OPTION_PB_STAGING) {
        ?> <p>Some test pledges that your friends have signed:</p> <?
            } else {
        ?> <p>Some pledges your friends have signed:</p> <?
            }

            $out = '<ol>';
            while ($r = db_fetch_array($q)) {
                $pledge = new Pledge($r);
                $out .= '<li>';
                $out .= pbfacebook_render_share_pledge($pledge);
                $out .= $pledge->summary(array('html'=>true, 'href'=>OPTION_FACEBOOK_CANVAS.$pledge->ref(), 'showcountry'=>true));
                #$out .= $pledge->render_box(array('class'=>'', 'facebook'=>true));
                $out .= '</li>';
                $friends_signed[] = $pledge->id();
            }
            $out .= '</ol>';
            print $out;
        }
        if ($friends_signed) 
            $friends_signed_joined = " AND pledges.id NOT IN (".join(",", $friends_signed).")";
    }

    $pledges = pledge_get_list("
                cached_prominence = 'frontpage' AND
                date >= '$pb_today' AND 
                pin is NULL AND 
                whensucceeded IS NULL 
                $friends_signed_joined
                ORDER BY RANDOM()
                LIMIT 10", array('global'=>true,'main'=>true,'foreign'=>true));
    if ($pledges) {
        if (OPTION_PB_STAGING) {
    ?> <p>Here are some pledges from the test database:</p> <?
        } else {
    ?> <p>Here are some pledges:</p> <?
        }
        $out = '<ol>';
        foreach ($pledges as $pledge)  {
            $out .= '<li>';
            $out .= pbfacebook_render_share_pledge($pledge);
            $out .= $pledge->summary(array('html'=>true, 'href'=>OPTION_FACEBOOK_CANVAS.$pledge->ref(), 'showcountry'=>true));
            #$out .= $pledge->render_box(array('class'=>'', 'facebook'=>true));
            $out .= '</li>';
        }
        $out .= '</ol>';
        print $out;
    }

    return;
}

// Sign a pledge, update profile, add to feeds, on Facebook
function pbfacebook_sign_pledge($pledge) {
    global $facebook;
    $user = $facebook->get_loggedin_user();

    $R = pledge_is_valid_to_sign($pledge->id(), null, null, $user);
    $f1 = $pledge->succeeded(true);

    if (!pledge_is_error($R)) {
        # See if there is already a Facebook person with this id XXX factor this out into a function
        $person_id = db_getOne("select id from person where facebook_id = ?", array($user));
        if (!$person_id) {
            $person_id = db_getOne("select nextval('person_id_seq')");
            db_query("insert into person (id, facebook_id) values (?, ?)", array($person_id, $user));
        }
        # Add them as a signer
        db_query('insert into signers (pledge_id, name, person_id, showname, signtime, ipaddr, byarea_location_id) values (?, ?, ?, ?, ms_current_timestamp(), ?, ?)', array($pledge->id(), null, $person_id, 'f', $_SERVER['REMOTE_ADDR'], null));
        db_commit();
        print "<p class=\"formnote\">"._("Thanks for signing up to this pledge!")."</p>";
#        print '<h1 style=\"text-align: center\">'. . '</h1>';

        # See if they tipped the balance
        $pledge = new Pledge($pledge->ref());
        if (!$f1 && $pledge->succeeded()) {
            print '<p class="formnote"><strong>' . _("Your signature has made this pledge reach its target! Woohoo!") . '</strong></p>';
        }

        # Show on their profile that they have signed it
        pbfacebook_update_profile_box($user);

        # Publish feed story
        $feed_title = '<fb:userlink uid="'.$user.'" shownetwork="false"/> has signed '; 
        if (OPTION_PB_STAGING) 
            $feed_title .= 'a test pledge.';
        else
            $feed_title .= 'a pledge.';
        $feed_body = $pledge->summary(array('html'=>true, 'href'=>OPTION_FACEBOOK_CANVAS.$pledge->ref(), 'showcountry'=>false));
        $ret = $facebook->api_client->feed_publishActionOfUser($feed_title, $feed_body);
        if (!$ret) {
            print '<p class="errors">'._('The news that you\'ve signed could not be added to your feed.').'</p>';
        } else {
            if ($ret[0] != 1) err("Error calling feed_publishActionOfUser: " . print_r($ret, TRUE));
        }
        #$ret = $facebook->api_client->feed_publishStoryToUser($feed_title, $feed_body);
        #if ($ret[0] != 1) err("Error calling feed_publishStoryToUser: " . print_r($ret, TRUE));

    } else if ($R == PLEDGE_SIGNED) {
        print '<p class="formnote">'._('You\'ve already signed this pledge!').'</p>';
    } else {
        /* Something else has gone wrong. */
        print '<p class="errors">' . _("Sorry &mdash; it wasn't possible to sign that pledge.") . ' '
                . htmlspecialchars(pledge_strerror($R))
                . ".</p>";
    }
    return $pledge;
}

// Send notification email to friends on Facebook
function pbfacebook_send_to_friends($pledge, $friends) {
    global $facebook;

    #$invite_intro = "Invite more friends to sign this pledge.";

/*    if (OPTION_PB_STAGING) 
        $content = "I've signed this test pledge, and thought you might like to sign it as well. ";
    else
        $content = "I've signed this pledge, and thought you might like to sign it as well. ";
    $content .= " '".$pledge->sentence(array('firstperson'=>'includename')) ."' ";
    $content .= "
<fb:req-choice url=\"".OPTION_FACEBOOK_CANVAS.$pledge->ref()."\" label=\"Sign the pledge!\" />
";
    $ret = $facebook->api_client->notifications_sendRequest(join(",", $friends), "pledge", $content, "http://www.mysociety.org/mysociety_sm.gif", "invitation");
    if (is_int($ret)) err("Error calling notifications_sendRequest: " . print_r($ret, TRUE)); */

    $user = $facebook->get_loggedin_user();

    $content = '
        <fb:notif-subject><fb:name uid="'.$user.'" firstnameonly="true" capitalize="true"/> pledged to do something...</fb:notif-subject> 
        <fb:name uid="' . $user . '" firstnameonly="true" capitalize="true"/>
        just signed a pledge on Facebook and thought you might be interested. 
        <a href="'.OPTION_FACEBOOK_CANVAS.$pledge->ref().'">Click here</a>
        to see the pledge.';
#    print "Friend inviting not ready yet"; exit; 
    $ret = $facebook->api_client->notifications_send(join(",", $friends), $content, FALSE);
    if ($ret == 4) return false; // Special "sent too many" error
    if (is_int($ret)) err("Error calling notifications_send: " . print_r($ret, TRUE));
    if (!$ret) { 
        // Empty URL can, according to docs mean EITHER an error, OR that
        // sending could happen without user confirmation. We just
        // assume the latter for now.
//        pbfacebook_after_sent();
        print "Message probably sent, thank you!";
        exit;
        return true; 
    }
    $facebook->redirect($ret."&canvas=1");
    return true;
}

// FBML header for all PledgeBank Facebook pages
function pbfacebook_render_header() {
?> <div style="padding: 10px;">  <?
if (OPTION_PB_STAGING) {
?>
<p><i>This is a development version of PledgeBank in Facebook.  Any pledges are
test ones in a test database. For real ones try the <a href="http://apps.facebook.com/pledgebank">live
PledgeBank</a> application.</i>
</p>
<?
}
?>
<style>
<? 
    readfile("pb.css"); 
?>
.pledge {
    border: solid 2px #522994;
    margin: 20px 50px 50px 50px;
    padding: 10px;
    background-color: #c6b5de;
}
.pledge p {
    margin-bottom: 0;
    text-align: center;
}

.pledge {
    border: solid 2px #522994;
    background-color: #f6e5ff;
    margin-bottom: 1em;
    padding: 10px;
}
.errors {
    color: #ff0000;
    background-color: #ffcccc;
    border: solid 2px #990000;
    padding: 3px;
    text-align: center;
}
.errors {
    margin: 0 auto 1em;
}
.errors ul {
    padding: 0;
    margin: 0 0 0 1.5em;
}
/* formnote is for non-negative notes at top of page about form filling in */
.formnote {
    margin: 0 auto 1em;
    background-color: #ccffcc;
    border: solid 2px #009900;
    padding: 3px;
    text-align: center;
}
.formnote ul {
    padding: 0;
    margin: 0 0 0 1.5em;
}

</style>
<? 
}

// FBML footer for all PledgeBank Facebook pages
function pbfacebook_render_footer() {
?> 
<div style="clear: both;"/>
</div> <?

}

// Beginning of main code
$facebook = new Facebook(OPTION_FACEBOOK_API_KEY, OPTION_FACEBOOK_SECRET);
#print_r($facebook->fb_params); exit;

$facebook->require_frame();
#$facebook->require_add();
#$facebook->require_login();
#print $facebook->get_add_url() ; 

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

