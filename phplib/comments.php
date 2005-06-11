<?php
/*
 * comments.php:
 * Comments on pledges.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: comments.php,v 1.16 2005-06-11 19:01:12 matthew Exp $
 * 
 */

require_once('pb.php');
require_once('person.php');
require_once('fns.php');
require_once('db.php');

/* comments_text_to_html TEXT
 * Convert TEXT to HTML. To start with we just turn line-feeds into <br>s and
 * URLs and hostnames beginning "www." into HREFs. */
function comments_text_to_html($text) {
    return nl2br(make_clickable( htmlspecialchars($text)));
}

/* comments_format_timestamp TIME
 * Format TIME as a friendly version of the timestamp. */
function comments_format_timestamp($time) {
    return $time;
}

/* comments_show_one COMMENT [NOABUSE]
 * Given COMMENT, an associative array containing fields 'text', 'name' and
 * 'website' (and optional fields 'id', the comment ID, and 'whenposted', the
 * posting time in seconds since the epoch), print HTML for the comment
 * described. If NOABUSE is true, don't show the link for reporting an abusive
 * comment. */
function comments_show_one($comment, $noabuse = false) {
    print '<div class="commentcontent">'
            . comments_text_to_html($comment['text'])
            . '</div>';
    print '<div class="commentheader"><small>';  /* XXX or h1 or something? */
    if (isset($comment['ref'])) {
        print 'To pledge <a href="/' . $comment['ref'] . '">' . $comment['ref'] . '</a> by ';
    }

    if (isset($comment['website']))
        print '<a href="' . htmlspecialchars($comment['website']) . '">'
                . htmlspecialchars($comment['name'])
                . '</a>';
    else
        print htmlspecialchars($comment['name']);

    /* Format the time sanely. */
    if (isset($comment['whenposted'])) {
        print ' at ' . prettify($comment['whenposted']) . '.';
    }

    if (isset($comment['id']) && !$noabuse)
        print ' <a class="abusivecommentlink" href="/abuse?what=comment&amp;id=' . $comment['id'] . '">Abusive? Report it!</a>';

    print '</small></div>';
}

/* comments_show PLEDGE [NOABUSE]
 * Show the comments for the given PLEDGE (id or reference). If NOABUSE is
 * true, don't show the link for reporting an abusive comment. */
function comments_show($pledge, $noabuse = false) {
    $id = $pledge;

    if (is_null($id))
        $id = db_getOne('select id from pledges where ref = ?', $pledge);

    if (is_null($id))
        err("No pledge '$pledge'");

    print '<div class="commentsbox">';
    
    if (db_getOne('select count(id) from comment where pledge_id = ?', $id) == 0)
        print '<p><em>No comments yet! Why not add one?</em></p>';
    else {
        print '<ul class="commentslist">';

        $q = db_query('
                    select id, extract(epoch from whenposted) as whenposted,
                        text, name, website
                    from comment
                    where comment.pledge_id = ?
                        and not ishidden
                    order by whenposted', $id);

        while ($r = db_fetch_array($q)) {
            print '<li class="comment" id="comment_' . $r['id'] . '">';

            comments_show_one($r, $noabuse);

            print '</li>';
        }

        print "</ul>";
    }
    print "</div>";
}

/* comment_summary COMMENT 
 * Display comment for index, such as front page or search results.
 */
function comment_summary($r) {
    return '<a href="/' . $r['ref'] . '#comment_' . $r['id'] . '">' .
        (strlen($r['text'])>20 ? substr($r['text'], 0, 20) : $r['text']) . '...</a> by ' . 
    htmlspecialchars($r['name']) . ', on pledge <a href="/' . $r['ref'] . '">' . $r['ref'] . '</a> at ' .
        prettify($r['whenposted']);
}

function latest_comments() { ?>
<div id="comments">
<h2>Latest comments</h2>
<?  $comments_to_show = 10;
    $q = db_query('SELECT comment.id,extract(epoch from whenposted) as whenposted,text,comment.name,website,ref FROM comment,pledges WHERE comment.pledge_id = pledges.id AND NOT ishidden ORDER BY whenposted DESC LIMIT ' . $comments_to_show);
    print '<ul>';
    while($r = db_fetch_array($q)) {
        print '<li>';
        print comment_summary($r);
        #        comments_show_one($r, true);
        print '</li>';
    }
    print '</ul></div>';
}

function comments_form($pledge_id, $nextn, $allow_post = false) {
    global $q_h_comment_id;
    global $q_h_author_name, $q_h_author_email, $q_h_author_website;
    global $q_h_text;
    global $signed_on_person;

    $P = $signed_on_person;
    if (!is_null($P)) {
        if (is_null($q_h_author_name) || !$q_h_author_name)
            $q_h_author_name = htmlspecialchars($P->name_or_blank());
        if (is_null($q_h_author_email) || !$q_h_author_email)
            $q_h_author_email = htmlspecialchars($P->email());
    }
    
?>
<form method="POST" action="comment.php" id="commentform" name="commentform" class="pledge">
<input type="hidden" name="pledge_id" value="<?=$pledge_id ?>">
<h2>Add Comment</h2>

<div class="form_row">
 <label for="author_name">Your name</label>
 <input type="text" id="author_name" name="author_name" value="<?=$q_h_author_name?>" size="30">
</div>

<div class="form_row">
<label for="author_email">Your email</label>
  <input type="text" id="author_email" name="author_email" value="<?=$q_h_author_email?>" size="30">
</div>

<div class="form_row">
<label for="author_website">Your web site</label> <small><i>(Optional)</i></small>
  <input type="text" id="author_website" name="author_website" value="<?=$q_h_author_website?>" size="30">
</div>

<div class="form_row">
<label for="text">Your comment</label>
  <textarea style="max-width: 100%" name="text" id="text" cols="40" rows="10"><?=$q_h_text?></textarea>

<? if ($q_h_comment_id) { ?>
<input type="hidden" name="comment_id" value="<?=$q_h_comment_id?>">
<? } ?>
<input type="hidden" name="n" value="<?=$nextn?>">
</div>

<input type="submit" name="preview" value="Preview">
<? if ($allow_post) { ?>
<input type="submit" name="submit" value="Post comment">
<? } ?>

<?  if ($p = get_http_var('pin')) print '<input type="hidden" name="pin" value="$p">'; ?>
</form>
<?
}

?>
