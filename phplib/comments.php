<?php
/*
 * comments.php:
 * Comments on pledges.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: comments.php,v 1.12 2005-05-24 23:18:39 francis Exp $
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
    if (isset($comment['website']))
        print '<a href="' . htmlspecialchars($comment['website']) . '">'
                . htmlspecialchars($comment['name'])
                . '</a>';
    else
        print htmlspecialchars($comment['name']);

    /* Format the time sanely. */
    if (isset($comment['whenposted'])) {
        $w = $comment['whenposted'];
        $tt = strftime('%H:%M', $w);
        $t = time();
        if (strftime('%Y%m%d', $w) == strftime('%Y%m%d', $t))
            $tt = "$tt today";
        elseif (strftime('%U', $w) == strftime('%U', $t))
            $tt = "$tt, " . strftime('%A', $w);
        elseif (strftime('%Y', $w) == strftime('%Y', $t))
            $tt = "$tt, " . strftime('%A %e %B', $w);
        else
            $tt = "$tt, " . strftime('%a %e %B %Y', $w);

        print " at $tt.";
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

function comments_form($pledge_id, $nextn, $allow_post = false) {
    global $q_h_comment_id;
    global $q_h_author_name, $q_h_author_email, $q_h_author_website;
    global $q_h_text;

    $P = person_if_signed_on();
    if (!is_null($P)) {
        if (is_null($q_h_author_name) || !$q_h_author_name)
            $q_h_author_name = htmlspecialchars($P->name());
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
