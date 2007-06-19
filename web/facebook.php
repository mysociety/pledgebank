<?php
// facebook.php:
// Facebook application for PledgeBank.
//
// Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: facebook.php,v 1.2 2007-06-19 13:56:20 francis Exp $

/*

TODO:
- Detect language that Facebook is using, and tell PledgeBank pages to use that.

*/

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';

$GLOBALS['facebook_config']['debug'] = true;
$page_plain_headers = true;

// the facebook client library
require_once '../phplib/facebookphp4/facebook.php';

/*function render_profile_action($id, $num) {
  return '<fb:profile-action url="http://apps.facebook.com/footprints/?to=' . $id . '">'
       .   '<fb:name uid="' . $id . '" firstnameonly="true" capitalize="true"/> '
       .   'has been stepped on ' . $num . ' times.'
       . '</fb:profile-action>';
}*/

function render_profile_box($uid) {
    return "
<fb:if-is-own-profile>
    You haven't signed any pledges in Facebook yet.
<fb:else>
    <fb:name uid=\"$uid\"/> has not signed any pledges in Facebook.
</fb:else>
</fb:if-is-own-profile>
<a href=\"".OPTION_FACEBOOK_CANVAS."\">Find a pledge to sign</a>.
";
}

function do_test() {
    global $facebook;
    print "Doing test";

    // Set Profile FBML
    #$uid = 36908918;
    #$uid = 703090157;
    #$fbml = render_profile_box($uid);
    #$ret = $facebook->api_client->profile_setFBML($fbml, $uid);
    #if ($ret != 1) err("Error calling profile_setFBML");

    // Send notification email
/*    $send_email_url =
      $facebook->api_client->notifications_send($to, '<fb:notif-subject>You have been stepped on...</fb:notif-subject>' .
        '<a href="http://apps.facebook.com/footprints/">Check out your Footprints!</a>', false);*/

    // Publish feed story
#    $feed_title = '<fb:userlink uid="'.$from.'" shownetwork="false"/> used feed_publishActionOfUser.';
#    $feed_body = 'Check out <a href="http://apps.facebook.com/pledgebank/?to='.$to.'">' .
#                 '<fb:name uid="'.$to.'" firstnameonly="true" possessive="true"/> PledgeBank</a>.';
#    $feed_body = null;
#    $ret = $facebook->api_client->feed_publishActionOfUser($feed_title, $feed_body);
#    $ret = $facebook->api_client->feed_publishStoryToUser($feed_title, $feed_body);
#    $ret = $facebook->api_client->feed_publishStoryToUser("fai title", "fai body");
#    print "feed_publishStoryToUser ret:";

#    $content = "Pledge to clean the local park, but only if 10 other people do too. 7 people have pledge so far, the deadline is the 20th July. 
#<fb:req-choice url=\"http://apps.facebook.com/pledgebank/cleanpark/sign\" label=\"Make the pledge!\" />
#";
#    $ret = $facebook->api_client->notifications_sendRequest($from/*"582616613"*/, "pledge", $content, "http://www.mysociety.org/mysociety_sm.gif", "invitation");
#    print "notifications_sendRequest ret:";
#    print_r($ret);

/*  if (isset($send_email_url) && $send_email_url) {
    $facebook->redirect($send_email_url . '&next=' . urlencode('?to=' . $to) . '&canvas');
  }*/
  #return $prints;
}

function render_pledge($p) {
    global $facebook;
?>
<style>
.pledge .c {
    text-align: center;
}
.pledge, #pledgeaction {
    border: solid 2px #522994;
    margin: 0 auto 1em;
    padding: 10px;
    background-color: #c6b5de;
}
.pledge p, #pledgeaction p {
    margin-bottom: 0;
}

.pledge, #tips, #pledge, #all .pledge, #yourpledges .pledge {
    border: solid 2px #522994;
    background-color: #f6e5ff;
    margin-bottom: 1em;
    padding: 10px;
}

img.creatorpicture {
    float: left;
    display: inline;
    margin-right: 10px;
}

</style>
<? $p->render_box(array('class'=>'')) ?>

<? /*<fb:share-button href="http://www.pledgebank.com/helo" class="url" /> */ ?>

<? /* <fb:editor action="?do-it" labelwidth="100">
  <fb:editor-buttonset>
    <fb:editor-button value="Sign Pledge"/>
  </fb:editor-buttonset>
</fb:editor> */ ?>

<?
}

function render_comments() {
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

function render_dashboard() {
?>
<fb:dashboard>
  <fb:action href="<?=OPTION_FACEBOOK_CANVAS?>">All Pledges</fb:action>
  <fb:action href="<?=pb_domain_url(array('path'=>'/new'))?>">Create a New Pledge</fb:action>
  <fb:help href="<?=pb_domain_url(array('path'=>'/faq'))?>" title="Need help">Help</fb:help>
  <fb:create-button href="<?=pb_domain_url(array('path'=>'/new'))?>">Create a New Pledge</fb:create-button>
</fb:dashboard>
<?
}

function render_frontpage() {
    global $facebook, $pb_today;
/*<p><a href="<?= $facebook->get_add_url() ?>">Put PledgeBank in your profile</a>, if you haven't already!</p>*/
/*<fb:tabs>
<fb:tab-item title="Friends pledges" selected="true" href="http://apps.facebook.com/pledgebank/list/friends" />
<fb:tab-item title="Recent pledges" href="http://apps.facebook.com/pledgebank/list/recent" />
<fb:tab-item title="Successful pledges" href="http://apps.facebook.com/pledgebank/list/success" />
</fb:tabs>*/
?>
<p>Here are some pledges from the test database:</p>
<?
    $pledges = pledge_get_list("
                cached_prominence = 'frontpage' AND
                date >= '$pb_today' AND 
                pin is NULL AND 
                whensucceeded IS NULL
                ORDER BY RANDOM()
                LIMIT 10", array('global'=>true,'main'=>true,'foreign'=>true));


    $out = '<ol>';
    foreach ($pledges as $pledge)  {
        $out .= '<li>';
        $out .= $pledge->summary(array('html'=>true, 'href'=>$pledge->ref(), 'showcountry'=>true));

        $out .= '</li>';
    }
    $out .= '</ol>';
    print $out;

    return;
}

// Beginning of main code
$facebook = new Facebook(OPTION_FACEBOOK_API_KEY, OPTION_FACEBOOK_SECRET);
#print_r($facebook->fb_params); exit;

$facebook->require_frame();
#$facebook->require_add();
#$user = $facebook->require_login();

?> <div style="padding: 10px;">  <?
if (OPTION_PB_STAGING) {
?>
<p><i>This is a development version of PledgeBank in Facebook. It doesn't work yet, but it
will soon. Any pledges are test ones in a test database, not real ones from 
<a href="http://www.pledgebank.com">PledgeBank.com</a>.</i></p>
<?
}

if (get_http_var("test")) {
    do_test();
}

$ref = get_http_var("ref");
# XXX do lowercase, and fuzzy matching
if (is_null(db_getOne('select ref from pledges where ref = ?', $ref))) {
    $ref = null;
    $p = null;
    render_dashboard();
    render_frontpage();
} else {
    $p = new Pledge($ref);
    render_dashboard();
    render_pledge($p);
}

?> 
<div style="clear: both;"/>
</div> <?


