<?php
/*
 * comment.php:
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: comment.php,v 1.1 2005-04-18 16:21:47 chris Exp $
 * 
 */

require_once('../../phplib/importparams.php');
require_once('../../phplib/emailaddress.php');

require_once('../phplib/pb.php');
require_once('../phplib/comments.php');

$err = importparams(
            array('pledge_id',          '/^[1-9][0-9]*$/',          ""),
            array('comment_id',         '/^[1-9][0-9]*$/',          "",     null)
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

/* Determine author ID, if any. */
$author_id = null;
$author_name = '';
$author_email = '';
$author_website = null;
if (isset($HTTP_COOKIE_VARS['pledgebank_author_id'])) {
    /* Cookie gives author ID and signature. */
    list($author_id, $s) = explode(".", $HTTP_COOKIE_VARS['pledgebank_author_id']);
    $r = db_getRow('select * from author where id = ?', $author_id);
    if ($s == sha1(db_secret() . $author_id) && !is_null($r)) {
        /* Valid cookie; save its values for default. */
        $author_name = $r['name'];
        $author_email = $r['email'];
        $author_website = $r['website'];
    } else {
        /* Invalid cookie; nuke it. */
        $author_id = null;
        setcookie('pledgebank_author_id', false, time() + 365 * 86400, '/', OPTION_WEB_DOMAIN);
    }
}

page_header("Commenting on '${pledge['title']}'");

/* Grab comment variables themselves. */
$err = importparams(
            array('author_name',        '//',                   "",     $author_name),
            array('author_email',       '//',                   "",     $author_email),
            array('author_website',     '//',                   "",     $author_website),
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

/* If author has edited their name at all, then we need to create a new author
 * record. */
if (!is_null($author_id) && ($q_author_name != $author_name || $q_author_email != $author_email || $q_author_website != $author_website)) {
    $author_id = null;
    setcookie('pledgebank_author_id', false, time() + 365 * 86400, '/', OPTION_WEB_DOMAIN);
}

if (!$q_author_email)
    array_push($err, "Please give your email address");
else if (!emailaddress_is_valid($q_author_email))
    array_push($err, htmlspecialchars("'$author_email'") . " is not a valid email address; please check it carefully");

if (strlen($q_author_name) == 0)
    array_push($err, "Please give your name $q_author_name");

if (strlen($q_text) == 0)
    array_push($err, "Please enter a message");

if ($q_author_website == '')
    $q_author_website = null;
if (!is_null($q_author_website) && !preg_match('#^https?://.+#', $q_author_website))
    array_push($err, "Your website address should begin 'http://'");

if (sizeof($err) == 0 && isset($_POST['submit'])) {
    /* Actually post the comment. */
    if (is_null($author_id)) {
        $author_id = db_getOne("select nextval('author_id_seq')");
        db_query('insert into author (id, name, email, website) values (?, ?, ?, ?)', array($author_id, $q_author_name, $q_author_email, $q_author_website));
        setcookie('pledgebank_author_id', $author_id . '.' . sha1(db_secret() . $author_id), time() + 365 * 86400, '/', OPTION_WEB_DOMAIN);
    }
    /* Guard against double-insertion. */
    $id = db_getOne('select id from comment where id = ? for update', $comment_id);
    if (is_null($id))
        db_query('insert into comment (id, pledge_id, author_id, text) values (?, ?, ?, ?)', array($comment_id, $pledge_id, $author_id, $q_text));
    db_commit();
    print <<<EOF
<p>Thank you! Your comment has now been posted.</p>
<p><a href="/${pledge['ref']}">Go back to the pledge</a></p>
EOF;
} else {
    $nextn = $q_n + 1;
    if ($q_n > 0) {
        if (sizeof($err) > 0)
            print '<div id="errors"><ul><li>'
                    . implode('</li><li>', array_map('htmlspecialchars', $err))
                    . '</li></div>'
                    . "<h2>Here's how your comment will appear</h2>";

        comments_show_one(array('name' => $q_author_name, 'email' => $q_author_email, 'website' => $q_author_website, 'text' => $q_text));
    }
    print <<<EOF
<form id="postcomment" method="POST">
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
