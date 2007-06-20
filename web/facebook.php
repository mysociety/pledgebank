<?php
// facebook.php:
// Facebook application for PledgeBank.
//
// Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: facebook.php,v 1.6 2007-06-20 18:21:08 francis Exp $

/*

TODO:
- Sort out when we require an add

- Detect language that Facebook is using, and tell PledgeBank pages to use that.
- Lower case and fuzzy matching of pledge refs
- Make sure can't do redirect in another site's iframe to sign pledge without permission 
- Test PIN protected pledges are safe
- Fix sorting of pledges in profile box

*/

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';

if (OPTION_PB_STAGING) 
    $GLOBALS['facebook_config']['debug'] = true;
#$GLOBALS['facebook_config']['debug'] = false; # comment out for debug of FB calls
$page_plain_headers = true;

// the facebook client library
require_once '../../phplib/facebookphp4/facebook.php';

/*function render_profile_action($id, $num) {
  return '<fb:profile-action url="http://apps.facebook.com/footprints/?to=' . $id . '">'
       .   '<fb:name uid="' . $id . '" firstnameonly="true" capitalize="true"/> '
       .   'has been stepped on ' . $num . ' times.'
       . '</fb:profile-action>';
}*/

function update_profile_box($uid) {
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
            $out .= render_share_pledge($pledge);
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

function do_test() {
    global $facebook;
    $user = $facebook->require_login();

    update_profile_box($user);
    print "Doing test";
    exit;
}

function render_pledge($pledge) {
    global $facebook;

    $invite_intro = "Invite your friends to also sign this pledge.";
    $signer_id = db_getOne("select signers.id from signers left join person on person.id = signers.person_id
            where facebook_id = ?", array($facebook->get_loggedin_user()));
    if ($signer_id) {
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

    $pledge->render_box(array('class'=>'', 'facebook-sign'=>$signer_id ? false: true));
    print render_share_pledge($pledge);
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

function render_share_pledge($pledge) {
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

function render_dashboard() {
?>
<fb:dashboard>
  <fb:action href="<?=OPTION_FACEBOOK_CANVAS?>">All Pledges</fb:action>
  <fb:action href="<?=pb_domain_url(array('path'=>'/new'))?>">Create a New Pledge</fb:action>
  <fb:help href="<?=pb_domain_url(array('path'=>'/faq'))?>" title="Need help">Help</fb:help>
</fb:dashboard>
<?
  /*<fb:create-button href="<?=pb_domain_url(array('path'=>'/new'))?>">Create a New Pledge</fb:create-button> */
}

function render_frontpage() {
    global $facebook, $pb_today;
/*<p><a href="<?= $facebook->get_add_url() ?>">Put PledgeBank in your profile</a>, if you haven't already!</p>*/
/*<fb:tabs>
<fb:tab-item title="Friends pledges" selected="true" href="http://apps.facebook.com/pledgebank/list/friends" />
<fb:tab-item title="Recent pledges" href="http://apps.facebook.com/pledgebank/list/recent" />
<fb:tab-item title="Successful pledges" href="http://apps.facebook.com/pledgebank/list/success" />
</fb:tabs>*/
    if (OPTION_PB_STAGING) {
?> <p>Here are some pledges from the test database:</p> <?
    } else {
?> <p>Here are some pledges:</p> <?
    }
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
        $out .= render_share_pledge($pledge);
        $out .= $pledge->summary(array('html'=>true, 'href'=>OPTION_FACEBOOK_CANVAS.$pledge->ref(), 'showcountry'=>true));
        
        #$out .= $pledge->render_box(array('class'=>'', 'facebook'=>true));

        $out .= '</li>';
    }
    $out .= '</ol>';
    print $out;

    return;
}

function sign_pledge_in_facebook($pledge, $user) {
    global $facebook;

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
        print '<p>'. _("Thanks for signing up to this pledge!") . '</p>';

        # See if they tipped the balance
        $pledge = new Pledge($pledge->ref());
        if (!$f1 && $pledge->succeeded()) {
            print '<p><strong>' . _("Your signature has made this pledge reach its target! Woohoo!") . '</strong></p>';
        }

        # Show on their profile that they have signed it
        update_profile_box($user);

        # Publish feed story
        $feed_title = '<fb:userlink uid="'.$user.'" shownetwork="false"/> has signed a pledge.';
        $feed_body = $pledge->summary(array('html'=>true, 'href'=>OPTION_FACEBOOK_CANVAS.$pledge->ref(), 'showcountry'=>false));
        $ret = $facebook->api_client->feed_publishActionOfUser($feed_title, $feed_body);
        if ($ret[0] != 1) err("Error calling feed_publishActionOfUser: " . print_r($ret, TRUE));
        #$ret = $facebook->api_client->feed_publishStoryToUser($feed_title, $feed_body);
        #if ($ret[0] != 1) err("Error calling feed_publishStoryToUser: " . print_r($ret, TRUE));

    } else if ($R == PLEDGE_SIGNED) {
        print '<p>'._('You\'ve already signed this pledge!').'</p>';
    } else {
        /* Something else has gone wrong. */
        print '<p><strong>' . _("Sorry &mdash; it wasn't possible to sign that pledge.") . ' '
                . htmlspecialchars(pledge_strerror($R))
                . ".</strong></p>";
    }

}

// Send notification email
function send_pledge_to_friends($pledge, $friends) {
    global $facebook;

    #$invite_intro = "Invite more friends to sign this pledge.";
    $content = "<p>I've signed this pledge, and thought you might like to sign it as well.</p>";
    $content .= "<p>'".$pledge->h_sentence(array('firstperson'=>'includename')) ."'</p>";
    $content .= "
<fb:req-choice url=\"".OPTION_FACEBOOK_CANVAS.$pledge->ref()."\" label=\"Sign the pledge!\" />
";
    $ret = $facebook->api_client->notifications_sendRequest(join(",", $friends), "pledge", $content, "http://www.mysociety.org/mysociety_sm.gif", "invitation");
    if (is_int($ret)) err("Error calling notifications_sendRequest: " . print_r($ret, TRUE));
    if (!$ret) err("Couldn't send the pledge to your friends, probably because too many messages in too short a time.");
    $facebook->redirect($ret);
    exit;
}

function render_header() {
?> <div style="padding: 10px;">  <?
if (OPTION_PB_STAGING) {
?>
<p><i>This is a development version of PledgeBank in Facebook. It doesn't work yet, but it
will soon. Any pledges are test ones in a test database, not real ones from 
<a href="http://www.pledgebank.com">PledgeBank.com</a>.</i></p>
<?
}

?>
<style>
.pledge, #pledgeaction {
    border: solid 2px #522994;
    margin: 0 50px 50px 50px;
    padding: 10px;
    background-color: #c6b5de;
}
.pledge p, #pledgeaction p {
    margin-bottom: 0;
    text-align: center;
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
<? 
}

function render_footer() {
?> 
<div style="clear: both;"/>
</div> <?

}

// Beginning of main code
$facebook = new Facebook(OPTION_FACEBOOK_API_KEY, OPTION_FACEBOOK_SECRET);
#print_r($facebook->fb_params); exit;

$facebook->require_frame();
#$facebook->require_add();
#$user = $facebook->require_login();
#print $facebook->get_add_url() ; 

if (get_http_var("test")) {
    do_test();
}
$ref = get_http_var("ref");
if (is_null(db_getOne('select ref from pledges where ref = ?', $ref))) {
    $ref = null;
    $pledge = null;
    render_header();
    render_dashboard();
    render_frontpage();
    render_footer();
} else {
    $pledge = new Pledge($ref);
    if ($pledge->pin()) {
        err("PIN protected pledges can't be accessed from Facebook");
    }
    if (get_http_var("sign_in_facebook")) {
        $user = $facebook->require_login();
    }
    if (get_http_var("invite_friends")) {
        $user = $facebook->require_login();
        send_pledge_to_friends($pledge, $_POST['ids']);
    }

    render_header();
    render_dashboard();
    if (get_http_var("sign_in_facebook")) {
        sign_pledge_in_facebook($pledge, $user);
    }
    render_pledge($pledge);
    render_footer();
}


