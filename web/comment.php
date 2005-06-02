<?php
/*
 * comment.php:
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: comment.php,v 1.12 2005-06-02 19:20:04 matthew Exp $
 * 
 */

require_once('../phplib/pb.php');

require_once('../../phplib/importparams.php');
require_once('../../phplib/emailaddress.php');

require_once('../phplib/pledge.php');
require_once('../phplib/comments.php');

$err = importparams(
            array('pledge_id',          '/^[1-9][0-9]*$/',      "Missing pledge id"),
            array('pin',                 '//',                  "Missing PIN",     null),
            array('comment_id',         '/^[1-9][0-9]*$/',      "Missing comment id",     null)
        );

if (!is_null($err))
    err("Sorry -- something seems to have gone wrong");

/* Allocate a comment ID here to stop double-posting. */
if (is_null($q_comment_id)) {
    $q_comment_id = $q_h_comment_id = db_getOne("select nextval('comment_id_seq')");
    db_commit();
}

$pledge_id = $q_pledge_id;
$comment_id = $q_comment_id;

$pledge = new Pledge(intval($pledge_id));
$ref = $pledge->ref();

if (!check_pin($ref, $pledge->pin()))
    err("Permission denied");

// TODO: test for commenting on expired pledges etc.

page_header("Commenting on '" . $pledge->h_title() . "'");

/* Grab comment variables themselves. */
$err = importparams(
            array('author_name',        '//',                   "",     null),
            array('author_email',       '//',                   "",     null),
            array('author_website',     '//',                   "",     null),
            array('text',               '//',                   "",     ""),
            array('n',                  '/^[0-9]+$/',           "",     0),
            array('submit',             '//',                   "",     false)
        );

if (!is_null($err))
    err("Sorry -- something seems to have gone wrong");

$err = array();

$q_author_name = trim($q_author_name);
$q_author_email = trim($q_author_email);
if (!is_null($q_author_website))
    $q_author_website = trim($q_author_website);
$q_text = trim($q_text);

if (!$q_author_email)
    array_push($err, "Please give your email address");
elseif (!emailaddress_is_valid($q_author_email))
    array_push($err, htmlspecialchars("'$q_author_email'") . " is not a valid email address; please check it carefully");

if (strlen($q_author_name) == 0)
    array_push($err, "Please give your name $q_author_name");

if (strlen($q_text) == 0)
    array_push($err, "Please enter a message");

if ($q_author_website == '')
    $q_author_website = null;
if (!is_null($q_author_website) && !preg_match('#^https?://.+#', $q_author_website))
    $q_author_website = 'http://' . $q_author_website;

if (sizeof($err) == 0 && isset($_POST['submit'])) {
    /* Actually post the comment. Guard against double-insertion. */
    $id = db_getOne('select id from comment where id = ? for update', $comment_id);
    if (is_null($id))
        db_query('
                insert into comment (id, pledge_id, name, email, website, text)
                values (
                    ?, ?,
                    ?, ?, ?,
                    ?)',
                array(
                    $comment_id, $pledge_id,
                    $q_author_name, $q_author_email, $q_author_website,
                    $q_text
                ));
    db_commit();
    $values = $pledge->data;
    $values['comment_text'] = $q_text;
    $values['comment_url'] = $pledge->url_comments();
    $values['comment_author_name'] = $q_author_name;
    $values['comment_author_email'] = $q_author_email;
    $values['comment_author_website'] = $q_author_website;
    $success = pb_send_email_template($pledge->creator_email(), 'comment-creator', $values);
    if (!$success) {
        err("Problems sending message to pledge creator.");
    }
    print <<<EOF
<p>Thank you! Your comment has now been posted.</p>
EOF;
    if (is_null($pledge->pin()))
        print <<<EOF
<p><a href="/$ref#comments">Go back to the pledge comments</a></p>
EOF;
    else
        print <<<EOF
<form method="post" action="/$ref">
<p>
<input type="hidden" name="pin" value="$q_h_pin">
<input type="submit" value="Go back to the pledge">
</p>
</form>
EOF;
} else {
    $nextn = $q_n + 1;
    if ($q_n > 0) {
        if (sizeof($err) > 0)
            print '<div id="errors"><ul><li>'
                    . implode('</li><li>', array_map('htmlspecialchars', $err))
                    . '</li></div>';

        print "<h2>Here's how your comment will appear</h2><blockquote>";
        comments_show_one(array('name' => $q_author_name, 'email' => $q_author_email, 'website' => $q_author_website, 'text' => $q_text));
        print '</blockquote>';
    }

    comments_form($pledge_id, $nextn, sizeof($err) == 0);

    print <<<EOF
    </td>
</tr>
</table>

</form>
</div>
EOF;
}

page_footer();

?>
