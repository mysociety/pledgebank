<?php
/*
 * comment.php:
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: comment.php,v 1.5 2005-04-21 17:49:26 matthew Exp $
 * 
 */

require_once('../phplib/pb.php');

require_once('../../phplib/importparams.php');
require_once('../../phplib/emailaddress.php');

require_once('../phplib/pledge.php');
require_once('../phplib/comments.php');

$err = importparams(
            array('pledge_id',          '/^[1-9][0-9]*$/',      ""),
            array('pw',                 '//',                   "",     null),
            array('comment_id',         '/^[1-9][0-9]*$/',      "",     null)
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

$pledge = db_getRow('select * from pledges where id = ?', $pledge_id);
if (is_null($pledge))
    err("Bad pledge ID");
/* test for commenting on expired pledges etc. */

if (!check_password($pledge['ref'], $pledge['password']))
    err("Permission denied");

page_header("Commenting on '${pledge['title']}'");

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
    $author_website = trim($q_author_website);
$q_text = trim($q_text);

if (!$q_author_email)
    array_push($err, "Please give your email address");
else if (!emailaddress_is_valid($q_author_email))
    array_push($err, htmlspecialchars("'$q_author_email'") . " is not a valid email address; please check it carefully");

if (strlen($q_author_name) == 0)
    array_push($err, "Please give your name $q_author_name");

if (strlen($q_text) == 0)
    array_push($err, "Please enter a message");

if ($q_author_website == '')
    $q_author_website = null;
if (!is_null($q_author_website) && !preg_match('#^https?://.+#', $q_author_website))
    array_push($err, "Your website address should begin 'http://'");

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
    print <<<EOF
<p>Thank you! Your comment has now been posted.</p>
EOF;
    if (is_null($pledge['password']))
        print <<<EOF
<p><a href="/${pledge['ref']}">Go back to the pledge</a></p>
EOF;
    else
        print <<<EOF
<form method="post" action="/${pledge['ref']}">
<p>
<input type="hidden" name="pw" value="$q_h_pw">
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
    print <<<EOF
<form id="postcomment" method="POST">
<input type="hidden" name="pw" value="$q_h_pw">
<input type="hidden" name="pledge_id" value="$q_h_pledge_id">
<input type="hidden" name="comment_id" value="$q_h_comment_id">
<input type="hidden" name="n" value="$nextn">
<table>
<tr>
    <th><label for="author_name">Your name</label></th>
    <td><input type="text" id="author_name" name="author_name" value="$q_h_author_name"></td>
</tr>
<tr>
    <th><label for="author_email">Your email address</label></th>
    <td><input type="text" id="author_email" name="author_email" value="$q_h_author_email"></td>
</tr>
<tr>
    <th><label for="author_website">Your web site</label><br>
        <span style="font-size: 80%; font-style: italic">Optional</span></th>
    <td><input type="text" id="author_website" name="author_website" value="$q_h_author_website"></td>
</tr>
<tr>
    <th colspan="2"><label for="text">Your comment</label></th>
</tr>
<tr>
    <td colspan="2"><textarea name="text" cols="50" rows="15">$q_h_text</textarea></td>
</tr>
<tr>
    <td colspan="2">
        <input type="submit" name="preview" value="Preview">
EOF;

    if (sizeof($err) == 0)
        print ' <input type="submit" name="submit" value="Post comment">';

    print <<<EOF
    </td>
</tr>
</table>

</form>
EOF;
}

page_footer();

?>
