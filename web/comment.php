<?php
/*
 * comment.php:
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: comment.php,v 1.42 2007-04-07 09:03:01 matthew Exp $
 * 
 */

require_once('../phplib/pb.php');

require_once('../../phplib/importparams.php');

require_once('../phplib/pbperson.php');
require_once('../phplib/pledge.php');
require_once('../phplib/comments.php');
require_once('../phplib/alert.php');

$err = importparams(
            array('pledge_id',          '/^[1-9][0-9]*$/',  _("Missing pledge id")),
            array(array('pin', true),   '//',               _("Missing PIN"),           null),
            array('comment_id',         '/^[1-9][0-9]*$/',  _("Missing comment id"),    null)
        );

if (!is_null($err)) {
    /* this error seems mostly to be triggered by bots presumably trying to
     * find places to post spam, so just redirect to the home page. */
    //err(_("Sorry -- something seems to have gone wrong. "));
    header("Location: /");
    exit();
}

/* Allocate a comment ID here to stop double-posting. */
if (is_null($q_comment_id)) {
    $q_comment_id = $q_h_comment_id = db_getOne("select nextval('comment_id_seq')");
    db_commit();
}

$pledge_id = $q_pledge_id;
$comment_id = $q_comment_id;

$pledge = new Pledge(intval($pledge_id));
$ref = $pledge->ref();
if ($pledge->closed_for_comments()) {
    err(_("Sorry, this pledge is now closed for new comments."));
}

if (!check_pin($ref, $pledge->pin()))
    err(_("Permission denied"));

// TODO: test for commenting on expired pledges etc.

page_header(sprintf(_("Commenting on '%s'"), $pledge->h_title()));

/* Grab comment variables themselves. */
$err = importparams(
            array(array('author_name', true),
                                        '//',                   "",     null),
            array('author_email',       '//',                   "",     null),
            array('author_website',     '//',                   "",     null),
            array('comment_alert_signup',
                                        '//',                   "",     false),
            array(array('text', true),  '//',                   "",     ""),
            array('n',                  '/^[0-9]+$/',           "",     0),
            array('submit',             '//',                   "",     false)
        );

if (!is_null($err))
    err(_("Sorry -- something seems to have gone wrong"));
    
if (strlen($q_comment_alert_signup) == 0)
    $q_comment_alert_signup = false;
else
    $q_comment_alert_signup = true;

$err = array();

$q_author_name = trim($q_author_name);
$q_author_email = trim($q_author_email);
if (!is_null($q_author_website))
    $q_author_website = trim($q_author_website);
$q_text = trim($q_text);

if (!$q_author_email)
    array_push($err, _("Please enter your email address"));
elseif (!validate_email($q_author_email))
    array_push($err, sprintf(_("'%s' is not a valid email address; please check it carefully"), htmlspecialchars($q_author_email)));
elseif ($email_err = microsites_invalid_email_address($q_author_email))
    array_push($err, $email_err);

if (strlen($q_author_name) == 0)
    array_push($err, _("Please enter your name"));

if (strlen($q_text) == 0)
    array_push($err, _("Please enter a message"));
if (strlen($q_text) > 3000)
    array_push($err, _("Your comment is far too long, please keep it short and sweet. Or put a link to a website with the information in your comment."));
elseif (strlen($q_text) > 2000)
    array_push($err, _("Your comment is a bit too long, please keep it short and sweet. Or put a link to a website with the information in your comment."));

if ($q_author_website && !preg_match('#^https?://.+#', $q_author_website))
    $q_author_website = 'http://' . $q_author_website;

if (sizeof($err) == 0 && isset($_POST['submit'])) {
    if ($q_author_website == '')
        $q_author_website = null;
    /* Require login for comments */
    $r = $pledge->data;
    $r['reason_web'] = _('Before adding your comment to the pledge, we need to check that your email is working.');
    $r['reason_email'] = _('Your comment will then be displayed on the pledge page.');
    $r['reason_email_subject'] = _('Adding your comment to a pledge at PledgeBank.com');
    $P = pb_person_signon($r, $q_author_email, $q_author_name);
    $P->set_website($q_author_website);

    /* Actually post the comment. Guard against double-insertion. */
    $id = db_getOne('select id from comment where id = ? for update', $comment_id);
    if (is_null($id)) {
        $hidden = false;
        $text_click = ms_make_clickable($q_text);
        $text_no_links = preg_replace('#<a.*?</a>#s', '', $text_click);
        if (substr_count($text_click, '<a') > 4 && strlen($text_no_links) / strlen($q_text) <= 0.5)
            $hidden = true;
        db_query('
                insert into comment (id, pledge_id, person_id, name, website, text, ishidden)
                values (
                    ?, ?,
                    ?, ?, ?,
                    ?, ?)',
                array(
                    $comment_id, $pledge_id,
                    $P->id(), $q_author_name, $q_author_website,
                    $q_text, $hidden
                ));
        if ($q_comment_alert_signup) {
            alert_signup($P->id(), "comments/ref", array('pledge_id' => $pledge->id()));
        }
    }
    db_commit();
    print "<p>" . _("Thank you! Your comment has now been posted.");
    if ($q_comment_alert_signup)
        print ' ' . _("You will be emailed when anyone adds a comment to the pledge.");
    print "</p>";
    if (is_null($pledge->pin()))
        print "<p><a href=\"/$ref#comments\">" . _('Go back to the pledge comments') . '</a></p>';
    else { ?>
<form method="post" action="/<?=$ref ?>">
<p>
<input type="hidden" name="pin" value="<?=$q_h_pin ?>">
<input type="submit" value="<?=_('Go back to the pledge') ?>">
</p>
</form>
<?  }
} else {
    $nextn = $q_n + 1;
    if ($q_n > 0) {
        if (sizeof($err) > 0)
            print '<div id="errors"><ul><li>'
                    . implode('</li><li>', array_map('htmlspecialchars', $err))
                    . '</li></div>';

        print '<div id="preview"><div class="comments">';
        print _("<h2>Here's how your comment will appear</h2>");
        print '<ul class="commentslist"><li class="comment">';
        comments_show_one(array('name' => $q_author_name, 'email' => $q_author_email, 
                'website' => $q_author_website, 'text' => $q_text, 
                'whenposted' => strval($pb_time), 'pledge_id' => $pledge->id()));
        print '</li></ul></div></div>';
    }

    print "\n\n" . '<div class="comments">';
    comments_form($pledge_id, $nextn, sizeof($err) == 0, $pledge->closed_for_comments());
    print '</div>';
}

page_footer();

?>
