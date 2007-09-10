<?php
// pbfacebook.php:
// Functions for PledgeBank Facebook integration.
//
// Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: pbfacebook.php,v 1.45 2007-09-10 10:54:19 francis Exp $

if (OPTION_PB_STAGING) 
    $GLOBALS['facebook_config']['debug'] = true;
$GLOBALS['facebook_config']['debug'] = false; # comment out for debug of FB calls

require_once '../../phplib/facebookphp4/facebook.php';

// Find anyone's name. Well, anyone who hasn't turned off their visibility
// from Facebook search results by people who aren't their friends - returns
// untrue value for those.
function pbfacebook_get_user_name($facebook_id) {
    global $facebook;
    $facebook_info = $facebook->api_client->users_getInfo(array($facebook_id), array('name'));
    return $facebook_info[0]['name'];
}

// Returns comma separated lists of logged in user's friends
function pbfacebook_friends_list() {
    global $facebook;
    $friends = $facebook->api_client->friends_get();
    $friends_joined = null;
    if ($friends)
        $friends_joined = join(",", $friends);
    if (!$friends_joined) # no friends case
        $friends_joined = -1; 
    return $friends_joined;
}

// Write the static FBML to the given user's profile box
function pbfacebook_update_profile_box($uid) {
    global $facebook;

    $out = "";
    $got = 0;

    // XXX If we have too many here to show, should use fb:subtitle to say
    // "Displaying n of m wall posts. See all.", as with the Wall.

    // Created
    $q = db_query("SELECT pledges.*, country,
            (SELECT COUNT(*) FROM signers WHERE signers.pledge_id = pledges.id) AS signers
            FROM pledges
            LEFT JOIN location ON location.id = pledges.location_id
            LEFT JOIN person ON person.id = pledges.person_id
            WHERE pin IS NULL AND
                  person.facebook_id = ? AND
                  pledges.via_facebook
            ORDER BY creationtime DESC",
            array($uid));
    if (db_num_rows($q) > 0) {
        $got = 1;
        $out .= "<fb:name uid=\"$uid\"/> has created these Pledges.";
        $out .= '<ol>';
        while ($r = db_fetch_array($q)) {
            $pledge = new Pledge($r);
            pbfacebook_update_fbmlref_profilepledge($pledge);
            $out .= '<li>';
            $out .= '<fb:ref handle="profilepledge-'.$pledge->ref().'" />';
            $out .= '</li>';
        }
        $out .= '</ol>';
    }     

    // Signed
    $q = db_query("SELECT pledges.*, country,
            (SELECT COUNT(*) FROM signers WHERE signers.pledge_id = pledges.id) AS signers
            FROM pledges
            LEFT JOIN location ON location.id = pledges.location_id
            LEFT JOIN signers on signers.pledge_id = pledges.id
            LEFT JOIN person ON person.id = signers.person_id
            WHERE pin IS NULL AND
                  person.facebook_id = ? AND
                  signers.via_facebook
            ORDER BY signtime DESC",
            array($uid));
    if (db_num_rows($q) > 0) {
        $got = 1;
        $out .= "<fb:name uid=\"$uid\"/> has signed these Pledges.";
        $out .= '<ol>';
        while ($r = db_fetch_array($q)) {
            $pledge = new Pledge($r);
            pbfacebook_update_fbmlref_profilepledge($pledge);
            $out .= '<li>';
            $out .= '<fb:ref handle="profilepledge-'.$pledge->ref().'" />';
            $out .= '</li>';
        }
        $out .= '</ol>';
    }

    if (!$got) {
        $out = "<fb:name uid=\"$uid\"/> has not signed or created any pledges in Facebook.";
        $out .= "<p><a href=\"".OPTION_FACEBOOK_CANVAS."\">Find a pledge to sign</a>.</p>";
    } else {
        $out .= "<p><a href=\"".OPTION_FACEBOOK_CANVAS."\">Find more pledges to sign</a>.</p>";
    }

    $ret = $facebook->api_client->profile_setFBML($out, $uid);
    if ($ret != 1) err("Error calling profile_setFBML for $uid: ". print_r($ret, TRUE) . "\n");
}

// Draw pledge index page within Facebook
function pbfacebook_update_fbmlref_profilepledge($pledge) {
    global $facebook;
    
    $content = pbfacebook_render_share_pledge($pledge);
    $content .= $pledge->summary(array('html'=>true, 'href'=>$pledge->url_facebook(), 'showcountry'=>true));
    $facebook->api_client->fbml_setRefHandle("profilepledge-".$pledge->ref(), $content);
}

// Signature confirmation dialog
function pbfacebook_render_sign_confirm($pledge) {
    global $facebook;

    $csrf_sig = auth_sign_with_shared_secret($pledge->id().":".$facebook->get_loggedin_user(), OPTION_CSRF_SECRET);
    $url = $pledge->url_facebook() . "?really_sign_in_facebook=1&csrf_sig=" . $csrf_sig;
    $cancel_url = $pledge->url_facebook();
?>
<p></p>
<table class="pop_dialog_table"> <tbody>
<tr><td class="pop_topleft"/><td class="pop_border"/> <td class="pop_topright"/> </tr>
<tr>
<td class="pop_border"/>
<td id="pop_content" class="pop_content">
<h2> <span>Add your signature to pledge?</span> </h2>
<div class="dialog_content">
<div class="dialog_body">Do you want to join this pledge? <p>"<?=$pledge->sentence(array('firstperson'=>'includename'))?>"</p></div>
<div id="dialog_buttons" class="dialog_buttons">
<form style="display: inline" method="post" action="<?=$url?>" name="confirm_sign" id="confirm_sign"> <input type="submit" value="Sign Pledge" class="inputsubmit"/> </form>
<form style="display: inline" method="post" action="<?=$cancel_url?>" name="cancel_sign" id="cancel_sign"> <input type="submit" value="Cancel" class="inputsubmit"/> </form>
</div>
</div>
</td>
<td class="pop_border"/>
</tr>
<tr> <td class="pop_bottomleft"/> <td class="pop_border"/> <td class="pop_bottomright"/> </tr>
</tbody></table>
<p></p>
<?
}

// Draw pledge index page within Facebook
function pbfacebook_render_pledge($pledge) {
    global $facebook;

    $title = "'" . _('I will') . ' ' . $pledge->h_title() . "'";
    print "<fb:title>".$title."</fb:title>";

    $already_signed = pbfacebook_already_signed($pledge);
    
    $announce_messages = db_getOne("select count(*) from message where pledge_id = ? and sendtosigners and emailbody is not null", array($pledge->id())); 

    // Fancy invitation section
    if (!$announce_messages) {
        $invite_intro = "Invite your friends to also sign this pledge.";
        if ($already_signed && !$pledge->finished()) {
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
    }


    // Announcement messages
    if ($announce_messages && $already_signed) {
        $q = db_query('select id, whencreated, fromaddress, emailsubject, emailbody from message where pledge_id = ? and sendtosigners and emailbody is not null order by id desc', $pledge->id());
        $num = db_num_rows($q);
        if ($num > 0) {
            print '<h3 class="wallkit_title">Messages from Pledge Creator</h3>';
            $c = 0;
            while (list($id, $when, $from, $subject, $body) = db_fetch_row($q)) {
                ?>
                <p><strong><?=_("From:") ?></strong>
                <?= $pledge->h_name() ?> &lt;<a href="mailto:<?=htmlspecialchars($pledge->creator_email())?>"><?= htmlspecialchars($pledge->creator_email()) ?></a>&gt;
                <strong><?=_('on') ?></strong>
                <?= prettify(substr($when, 0, 10)) ?>
                <br><strong><?=_('Subject') ?></strong>:
                <?= htmlspecialchars($subject) ?>
                </p>
                <div class="message" id="message_<?=$id?>" ><?= comments_text_to_html($body) ?></div>
        <?
                if (++$c != $num) {
                    print '<hr>';
                }
                /*
style="display: none"
<a href="#" clicktoshow="message_<?=$id?>">Show text of message from creator</a>
                / <a href="#" clicktohide="message_<?=$id?>">Hide text</a> */

            }
            print "<p></p>";
        }
    }

    // Pledge itself
    print '<h3 class="wallkit_title">The Pledge</h3>';
    pledge_draw_status_plaque($pledge);
    $pledge->render_box(array('class'=>'', 
            'facebook-sign'=>!$pledge->finished() && !$already_signed,
            'showdetails' => true,
            'facebook-share' => pbfacebook_render_share_pledge($pledge) 
            ));

    // Show signers
    print '<div class="pb_signers">';
    print '<h3 class="wallkit_title">Current Signatories</h3>';

    $friends_joined = pbfacebook_friends_list();
    $q = db_query("SELECT facebook_id FROM signers LEFT JOIN person ON person.id = signers.person_id
                WHERE signers.via_facebook AND person.facebook_id is not null
                      AND signers.pledge_id = ?
                ORDER BY (person.facebook_id in ($friends_joined)) DESC, signtime DESC", array($pledge->id()));
    $c = 0;
    if (db_num_rows($q) > 0) {
        // Grrr - annoyingly, fb:user-table only works in a profile.
        $signers_per_rows = 4;
        print '<table border="0"><tr>';
        while ($r = db_fetch_array($q)) {
            print "<td>";
            print '<table border="0"><tr><td style="text-align: center">';
            print '<fb:profile-pic uid="'.$r['facebook_id'].'" size="thumb" /> ';
            print '</td></tr><tr><td style="text-align: center">';
            print '<fb:userlink shownetwork="0" uid="'.$r['facebook_id'].'"/> ';
            print "</td></tr></table>";
            print "</td>";
            $c++;
            if ($c % $signers_per_rows == 0) {
                print "</tr><tr>";
            }
        }
        print "</tr></table>";
    }
    $remaining = $pledge->signers() - $c;
    if ($remaining > 0) {
        print "<p>";
        if ($c > 0) {
            print " and ";
        }

        $q = db_query("SELECT signers.name FROM signers LEFT JOIN person ON person.id = signers.person_id
                    WHERE (NOT signers.via_facebook OR person.facebook_id IS NULL)
                          AND showname AND signers.pledge_id = ?
                    ORDER BY signtime DESC", array($pledge->id()));

        if (db_num_rows($q) > 0) {
            while ($r = db_fetch_array($q)) {
                print htmlspecialchars($r['name']);
                $c++;
                if ($c != db_num_rows($q))
                    print ", ";
            }
        }

        $remaining = $pledge->signers() - $c;
        if ($remaining > 0) {
            print " plus $remaining " . make_plural($remaining, "other");
        }
        print " from <a href=\"".$pledge->url_typein()."\">outside Facebook</a>.";
        print "</p>";
    }
    print "</div>";

    // Comments
    print '<div class="pb_wall">';
    print '<fb:comments xid="pledge_'.$pledge->ref().'_comments" canpost="true" candelete="false">';
    print '<fb:title>The Wall</fb:title>';
    print '</fb:comments>';
    print '</div>';

    // Link to pledgebank.com
    print '<p class="pb_visit">Visit this pledge at ';
    print '<a href="'.$pledge->url_typein().'">';
    print '<strong>'. str_replace('http://', '', $pledge->url_typein()) . '</strong>';
    print '</a>';
    print " for flyers, SMS signup and to share it with people not on Facebook.";
    print '</p>';

}

// Draw "share" button for a pledge on Facebook
function pbfacebook_render_share_pledge($pledge) {
    $out = "<div style=\"float: right\">";
    $out .='
      <fb:share-button class="meta">
          <meta name="title" content="Sign this pledge"/>
          <meta name="description" content="'.$pledge->h_sentence(array('firstperson'=>'includename')).'"/>
          <link rel="target_url" href="'.$pledge->url_facebook().'"/>
      </fb:share-button>';
    $out .= "</div>";
    return $out;
}

// Render common top of PledgeBank pages on Facebook
function pbfacebook_render_dashboard() {
    global $facebook;
    $urlpart = "";
    if ($facebook->get_loggedin_user()) {
        $facebook_name = pbfacebook_get_user_name($facebook->get_loggedin_user());
        $sig = auth_sign_with_shared_secret($facebook->get_loggedin_user().":".$facebook_name, OPTION_CSRF_SECRET);
        $urlpart = "?facebook_id=" . $facebook->get_loggedin_user() . "&facebook_name=".urlencode($facebook_name)."&facebook_id_sig=" . $sig;
    }
?>
<fb:dashboard>
  <fb:action href="<?=OPTION_FACEBOOK_CANVAS?>">Browse Pledges</fb:action>
  <fb:action href="<?=pb_domain_url(array('path'=>'/new'.$urlpart))?>">Create a New Pledge</fb:action>
  <fb:help href="<?=pb_domain_url(array('path'=>'/faq'))?>" title="Need help">Help</fb:help>
</fb:dashboard>
<?
}

// See if pledge has already been signed, or was created by, the user
function pbfacebook_already_signed($pledge) {
    global $facebook;
    $signer_id = db_getOne("select signers.id from signers left join person on person.id = signers.person_id
            where facebook_id = ? and signers.pledge_id = ?", array($facebook->get_loggedin_user(), $pledge->id())); 
    if (!$signer_id) {
        $owner_id = db_getOne("select person_id from pledges left join person on person.id = pledges.person_id
                where facebook_id = ? and pledges.id = ?", array($facebook->get_loggedin_user(), $pledge->id())); 
    }
    return ($signer_id || $owner_id);
}

// Render frontpage of PledgeBank on Facebook
function pbfacebook_render_frontpage($page = "") {
    global $facebook, $pb_today;

    if ($page == "" && !$facebook->get_loggedin_user()) {
        $page = "feature";
    }
    if ($page == "" && $facebook->get_loggedin_user()) {
        $page = "friends";
    }

?>    <fb:tabs>
    <fb:tab-item title="Friends' pledges" <?=($page=="friends")?'selected="true"':''?> href="<?=OPTION_FACEBOOK_CANVAS?>list/friends" />
    <fb:tab-item title="Your pledges" <?=($page=="your")?'selected="true"':''?> href="<?=OPTION_FACEBOOK_CANVAS?>list/your" />
    <fb:tab-item title="Featured pledges" <?=($page=="feature")?'selected="true"':''?> href="<?=OPTION_FACEBOOK_CANVAS?>list/feature" />
<!--    <fb:tab-item title="Successful pledges" <?=($page=="success")?'selected="true"':''?> href="<?=OPTION_FACEBOOK_CANVAS?>list/success" /> -->
    </fb:tabs> <?

    $friends_signed_joined = "";
    if ($page == "friends" ) {
        print "<fb:title>"."Friends' pledges"."</fb:title>";

        $facebook->require_login('/list/friends');
        $friends_joined = pbfacebook_friends_list();
        $query = "SELECT pledges.*, country, 
                (SELECT COUNT(*) FROM signers WHERE signers.pledge_id = pledges.id) AS signers,
                person.facebook_id as facebook_id
                FROM pledges 
                LEFT JOIN location ON location.id = pledges.location_id
                LEFT JOIN signers on signers.pledge_id = pledges.id
                LEFT JOIN person ON person.id = signers.person_id
                WHERE pin IS NULL AND 
                    person.facebook_id in ($friends_joined) AND
                    signers.via_facebook
                ORDER BY pledges.id DESC
                LIMIT 30
                ";
        $friends_signed = array();
        $q = db_query($query);
        if (db_num_rows($q) > 0) {
            print '<ul>';
            $prev = "";
            $r = db_fetch_array($q);
            while ($r) {
                $pledge = new Pledge($r);
                $already_signed = pbfacebook_already_signed($pledge);

                $friends_sig = array();
                $friends_sig[] = $r['facebook_id'];
                while ($r = db_fetch_array($q)) {
                    if ($r['ref'] == $pledge->ref())
                        $friends_sig[] = $r['facebook_id'];
                    else
                        break;
                }

                $friends_text = array();
                foreach ($friends_sig as $friend) {
                    $friends_text[] = '<fb:userlink shownetwork="0" uid="'.$friend.'" />';
                    #$friends_pics[] ='<fb:profile-pic style="vertical-align: middle" uid="'.$friend.'"/> ';
                }

                print '<li>';
                print join(", ", $friends_text) . " ";
                print make_plural(count($friends_sig), "has pledged:", "have pledged:") . " ";
                $pledge->render_box(array('class'=>'', 'facebook-share' => pbfacebook_render_share_pledge($pledge),
                        'facebook-sign'=>!$pledge->finished() && !$already_signed,
                        'href'=>$pledge->url_facebook()));
                $friends_signed[] = $pledge->id();
                print '</li>';
/*                $out .= "<table><tr><td>";
                $out .= "</td></tr><tr><td>";
                $out .= '<fb:profile-pic style="vertical-align: middle" uid="'.$r['facebook_id'].'"/> ';
                $out .= "</td></tr></table>";*/
            }
            print '</ul>';
        } else {
            print "<p>"._("None of your friends have made or signed any pledges.").
                    " <a href=\"".OPTION_FACEBOOK_CANVAS."list/feature\">"._("See some featured pledges.")."</a>".
                    "</p>";
        }
        if ($friends_signed) 
            $friends_signed_joined = " AND pledges.id NOT IN (".join(",", $friends_signed).")";
    }

    if ($page == "your" ) {
        print "<fb:title>"."Your pledges"."</fb:title>";

        $facebook->require_login('/list/your');
        $you_id = $facebook->get_loggedin_user();
        $got = 0;

        $query = "SELECT pledges.*, country, 
                (SELECT COUNT(*) FROM signers WHERE signers.pledge_id = pledges.id) AS signers,
                person.facebook_id as facebook_id
                FROM pledges 
                LEFT JOIN location ON location.id = pledges.location_id
                LEFT JOIN person ON person.id = pledges.person_id
                WHERE pin IS NULL AND 
                    person.facebook_id = ? AND
                    pledges.via_facebook
                ORDER BY creationtime DESC
                ";
        $q = db_query($query, $you_id);
        if (db_num_rows($q) > 0) {
            $got = 1;
            print "<p>"._("Pledge you've created:")."</p>";
            while ($r = db_fetch_array($q)) {
                $pledge = new Pledge($r);
                $pledge->render_box(array('class'=>'', 'facebook-share' => pbfacebook_render_share_pledge($pledge),
                        'href'=>$pledge->url_facebook()));
            }
        }

        $query = "SELECT pledges.*, country, 
                (SELECT COUNT(*) FROM signers WHERE signers.pledge_id = pledges.id) AS signers,
                person.facebook_id as facebook_id
                FROM pledges 
                LEFT JOIN location ON location.id = pledges.location_id
                LEFT JOIN signers on signers.pledge_id = pledges.id
                LEFT JOIN person ON person.id = signers.person_id
                WHERE pin IS NULL AND 
                    person.facebook_id = ? AND
                    signers.via_facebook
                ORDER BY signtime DESC
                ";
        $q = db_query($query, $you_id);
        if (db_num_rows($q) > 0) {
            $got = 1;
            print "<p>"._("Pledge you've signed:")."</p>";
            while ($r = db_fetch_array($q)) {
                $pledge = new Pledge($r);
                $pledge->render_box(array('class'=>'', 'facebook-share' => pbfacebook_render_share_pledge($pledge),
                        'href'=>$pledge->url_facebook()));
            }
        }

        if (!$got) {
            print "<p>"._("You've neither made nor signed any pledges.")."</p>";
        }
    }

    if ($page == "feature") {
        print "<fb:title>"."Featured pledges"."</fb:title>";
#$facebook_info = $facebook->api_client->users_getInfo(array($facebook->get_loggedin_user()), array('hometown_location'));
#print_r($facebook_info);exit;

        print '<p style="text-align:center"><a href="http://www.pledgebank.com">Find more pledges</a> to sign over on <a href="http://www.pledgebank.com">www.pledgebank.com</a></p>';
        list($pledges, $more) = pledge_get_frontpage_list(8, 6);
        if ($pledges) {
            foreach ($pledges as $pledge)  {
                $already_signed = pbfacebook_already_signed($pledge);
                $pledge->render_box(array('class'=>'', 'facebook-share' => pbfacebook_render_share_pledge($pledge),
                        'facebook-sign'=>!$pledge->finished() && !$already_signed,
                        'href'=>$pledge->url_facebook()));
            }
        }
    }

    return;
}

// Sign a pledge, update profile, add to feeds, on Facebook. Must be signing
// the pledge for the logged in user, so can do feed publishing properly.
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
        # Update our record session key, if it is infinite
        if ($facebook->fb_params['expires'] == 0) {
            db_query("delete from facebook where facebook_id = ?", array($user));
            db_query("insert into facebook (facebook_id, session_key) values (?, ?)", array($user, $facebook->fb_params['session_key']));
        }
        # Add them as a signer
        db_query('insert into signers (pledge_id, name, person_id, showname, signtime, ipaddr, byarea_location_id, via_facebook) values (?, ?, ?, ?, ms_current_timestamp(), ?, ?, ?)', array($pledge->id(), null, $person_id, 'f', $_SERVER['REMOTE_ADDR'], null, 't'));
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
        $feed_title = '<fb:userlink uid="'.$user.'" shownetwork="false"/> signed '; 
        if (OPTION_PB_STAGING) 
            $feed_title .= 'a test pledge.';
        else
            $feed_title .= 'a pledge.';
        $feed_body = $pledge->summary(array('html'=>true, 'href'=>$pledge->url_facebook(), 'showcountry'=>false));
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

    $user = $facebook->get_loggedin_user();

    // Requests version
    $content = '<fb:name uid="'.$user.'" firstnameonly="true" capitalize="true"/> just signed this pledge, and would like you to take a look. ';
    $content .= "
<fb:req-choice url=\"".$pledge->url_facebook()."\" label=\"Go to the pledge\" />
";
    $ret = $facebook->api_client->notifications_sendRequest(join(",", $friends), "pledge", $content, 
            $pledge->has_picture() ? $pledge->picture_url() : (OPTION_BASE_URL . "/pyramid.gif"), 
            "invitation");
    if (is_int($ret)) err("Error calling notifications_sendRequest: " . print_r($ret, TRUE));

    // Email version
/*
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
*/

    if (!$ret) { 
        # XXX maybe this happens when sending was successful, but Facebook 
        # error reporting sucks in this regard so who knows.
        err("Probably failed to send request to your friend.");
   } 

    $facebook->redirect($ret."&canvas=1");
    return true;
}

// FBML header for all PledgeBank Facebook pages
function pbfacebook_render_header() {
?> <div style="padding: 10px;" id="all_content">  <?
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
#    print file_get_contents("pb.css"); 
?>
.pledge {
    margin: 2em 10%;
    border: solid 2px #522994;
    background-color: #f6e5ff;
    padding: 10px;
}
.pledge p {
    margin-bottom: 0;
    text-align: center;
}
.pledge p#moredetails {
    text-align: left;
}

.errors {
    color: #ff0000;
}
/* formnote is for non-negative notes at top of page about form filling in */
.formnote, .success {
    background-color: #ccffcc;
    border: solid 2px #009900;
}
.finished, .errors {
    background-color: #ffcccc;
    border: solid 2px #990000;
}
.errors, .formnote {
    margin: 0 auto 1em;
    padding: 3px;
    text-align: center;
}
.errors ul, .formnote ul {
    padding: 0;
    margin: 0 0 0 1.5em;
}
.success, .finished {
    color: #000000;
    margin: 1em auto 0;
    width: 80%;
    padding: 10px;
    text-align: center;
}

img.creatorpicture {
    float: left; 
    display: inline;
    margin-right: 10px;
}
.pb_signers {
    width: 50%;
    float: left;
}
.pb_wall {
    width: 45%;
    float: right;
}
.pb_visit {
    clear: both;
    text-align: center;
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

// Call from Facebook callbook hook pages
function pbfacebook_init_webpage() {
    global $facebook;

    $facebook = new Facebook(OPTION_FACEBOOK_API_KEY, OPTION_FACEBOOK_SECRET);
    #print_r($facebook->fb_params); exit;

    $facebook->require_frame();
}

// Call from other scripts like cron jobs
function pbfacebook_init_cron($user) {
    global $facebook;

    $facebook = new Facebook(OPTION_FACEBOOK_API_KEY, OPTION_FACEBOOK_SECRET);

    $session_key = db_getOne("select session_key from facebook where facebook_id = ?", $user);
    if (!$session_key) 
        err("No session key for Facebook user $user");

    #print "session key: $session_key";

    # XXX their ought be a (working!) function in the Facebook PHP class to do this
    #$facebook->set_user($user, $session_key);
    $facebook->user = $user;
    $facebook->api_client->session_key = $session_key;
}

// Like pb_send_email in pb/phplib/fns.php
function pbfacebook_send($to, $message) {
    return pbfacebook_send_internal($to, $message);
}

// Like pb_send_email_template in pb/phplib/fns.php
function pbfacebook_send_template($to, $template_name, $values, $headers = array()) {
    global $pbfacebook_values;
    $pbfacebook_values = pb_message_add_template_values($values);

    $template = file_get_contents("../templates/facebook/$template_name");
    $template = _($template);
    $template = preg_replace_callback("|<\?\=\\\$values\['([^']+)'\]\?>|", create_function('$a',
            ' global $pbfacebook_values; return $pbfacebook_values[$a[1]]; '
        ), $template);

    return pbfacebook_send_internal($to, $template);
}

// XXX this calls pbfacebook_init_cron so perhaps should be in frequentupdate
function pbfacebook_send_internal($to, $message) {
    global $facebook;

    // 703090157 = Francis Irving
    // 582616613 = Opera Tuck

    pbfacebook_init_cron($to); 
    $to_info = $facebook->api_client->users_getInfo($to, array("name"));
    #print "pbfacebook_send_internal: ". $message. "\nTo:". $to_info[0]['name'];

    # Publish feed story
    $lines = split("\n", $message);
    $feed_title = array_shift($lines);
    $feed_body = join("\n", $lines);

    $ret = $facebook->api_client->feed_publishStoryToUser($feed_title, $feed_body);
    if (!$ret) {
        print("Calling feed_publishStoryToUser failed; maybe due to 1 msg / 12 hour limit, or length limit: " . print_r($ret, TRUE)) . "\n";
        return false;
    } elseif ($ret[0] == 0) {
        print("Calling feed_publishStoryToUser failed; permissions error, user probably disallows app from posting to feed\n");
        return false;
    } elseif ($ret[0] == 1) {
        // OK
    } else {
        err("Error calling feed_publishStoryToUser in pbfacebook_send_internal: " . print_r($ret, TRUE));
    }

    # Frustratingly, this always requires URL confirmation, even when the users have added the application,
    # so we can't use it from a cron job.
    /*
    $ret = $facebook->api_client->notifications_sendRequest($to, "pledge", $message, "http://www.mysociety.org/mysociety_sm.gif", "invitation");
    #$ret = $facebook->api_client->notifications_send($to, $message, FALSE);
    if (is_int($ret)) err("Error calling notifications_sendRequest in pb_send_facebook: " . print_r($ret, TRUE)); 
    if ($ret) err("Need URL confirmation calling notifications_sendRequest in pb_send_facebook: " . print_r($ret, TRUE));
    print "Success\n";
    */
    return true;
}

/*
    $csrf_sig = auth_sign_with_shared_secret($pledge->id().":".$facebook->get_loggedin_user(), OPTION_CSRF_SECRET);
    $verified = auth_verify_with_shared_secret($pledge->id().":".$facebook->get_loggedin_user(), OPTION_CSRF_SECRET, get_http_var("csrf"));
*/

