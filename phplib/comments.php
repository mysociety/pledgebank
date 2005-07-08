<?php
/*
 * comments.php:
 * Comments on pledges.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: comments.php,v 1.31 2005-07-08 11:32:51 matthew Exp $
 * 
 */

require_once('pb.php');
require_once('person.php');
require_once('fns.php');
require_once('../../phplib/db.php');

/* comments_text_to_html TEXT
 * Convert TEXT to HTML. To start with we just turn line-feeds into <br>s and
 * URLs and hostnames beginning "www." into HREFs. */
function comments_text_to_html($text) {
    return nl2br(make_clickable( htmlspecialchars($text), array('contract'=>true)));
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
    $name = htmlspecialchars($comment['name']);
    if (isset($comment['website']))
        $name = '<a href="' . htmlspecialchars($comment['website']) . '">' . $name . '</a>';

    print '<div class="commentcontent">'
            . comments_text_to_html($comment['text'])
            . '</div>';
    print '<div class="commentheader"><small>';  /* XXX or h1 or something? */
    if (isset($comment['ref'])) {
        $r = '<a href="/' . $comment['ref'] . '">' . $comment['ref'] . '</a>';
        if (isset($comment['whenposted'])) {
            printf(_('To pledge %s by %s at %s.'), $r, $name, prettify($comment['whenposted']));
        } else {
            printf(_('To pledge %s by %s.'), $r, $name);
        }
    } else {
        /* Format the time sanely. */
        if (isset($comment['whenposted'])) {
            printf(_('%s at %s.'), $name, prettify($comment['whenposted']));
        } else {
            print $name;
        }
    }

    if (isset($comment['id']) && !$noabuse)
        print ' <a class="abusivecommentlink" href="/abuse?what=comment&amp;id=' . $comment['id'] . '">' . _('Abusive? Report it!') . '</a>';

    print '</small></div>';
}

/* comments_count PLEDGE
 * Returns how many comments pledge has. */
function comments_count($pledge) {
    $id = $pledge;
    return db_getOne('select count(id) from comment where pledge_id = ?', $id);
}

/* comments_show PLEDGE [NOABUSE] [LIMIT]
 * Show the comments for the given PLEDGE (id or reference). If NOABUSE is
 * true, don't show the link for reporting an abusive comment.  If LIMIT
 * is present only show the last LIMIT comments. */
function comments_show($pledge, $noabuse = false, $limit = 0) {
    $id = $pledge;

    if (is_null($id))
        $id = db_getOne('select id from pledges where ref = ?', $pledge);

    if (is_null($id))
        err(sprintf(_("No pledge '%s'"), $pledge));

    print '<div class="commentsbox">';
    
    $count = db_getOne('select count(id) from comment where pledge_id = ?', $id);
    if ($count == 0)
        print '<p><em>' . _('No comments yet! Why not add one?') . '</em></p>';
    else {
        print '<ul class="commentslist">';

        $query = '
                    select id, extract(epoch from whenposted) as whenposted,
                        text, name, website
                    from comment
                    where comment.pledge_id = ?
                        and not ishidden
                    order by whenposted';
        if ($limit) {
            $query .= " LIMIT " . $limit . " OFFSET " . ($count - $limit);
        }
        $q = db_query($query , $id);

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
    $text = $r['text'];
    if (strlen($text) > 20) $text = substr($text, 0, 20) . '...';
    $text = '<a href="/' . $r['ref'] . '#comment_' . $r['id'] . '">' . $text . '</a>';
    
    return sprintf(_('%s by %s, on pledge %s at %s'), $text, htmlspecialchars($r['name']), "<a href=\"/$r[ref]\">$r[ref]</a>", prettify($r['whenposted']));
}

function latest_comments() { 
    $comments_to_show = 10;
    $q = db_query("
                SELECT comment.id,
                    extract(epoch from whenposted) as whenposted, text,
                    comment.name, website, ref
                FROM comment, pledges
                WHERE comment.pledge_id = pledges.id
                    AND NOT ishidden
                    AND pb_pledge_prominence(pledges.id) <> 'backpage'
                ORDER BY whenposted DESC
                LIMIT $comments_to_show");
    if (db_num_rows($q) > 0) {
        ?><div class="comments">
        <?=_('<h2>Latest comments</h2>') ?> <?  
        print '<ul>';
        while($r = db_fetch_array($q)) {
            print '<li>';
            print comment_summary($r);
            #        comments_show_one($r, true);
            print '</li>';
        }
        print '</ul></div>';
    }
}

function comments_form($pledge_id, $nextn, $allow_post = false) {
    global $q_h_comment_id;
    global $q_h_author_name, $q_h_author_email, $q_h_author_website, $q_comment_alert_signup;
    global $q_h_text;

    $P = person_if_signed_on();
    if (!is_null($P)) {
        if (is_null($q_h_author_name) || !$q_h_author_name)
            $q_h_author_name = htmlspecialchars($P->name_or_blank());
        if (is_null($q_h_author_email) || !$q_h_author_email)
            $q_h_author_email = htmlspecialchars($P->email());
        if (is_null($q_h_author_website) || !$q_h_author_website)
            $q_h_author_website = htmlspecialchars($P->website_or_blank());
    }
    if ($nextn == 1 && (is_null($q_comment_alert_signup) || !$q_comment_alert_signup))
        $q_comment_alert_signup = 1;
    
?>
<form method="POST" action="comment.php" id="commentform" name="commentform" class="pledge">
<input type="hidden" name="pledge_id" value="<?=$pledge_id ?>">
<?=_('<h2>Add Comment</h2>') ?>

<div class="form_row">
 <label for="author_name"><?=_('Your name') ?></label>
 <input type="text" id="author_name" name="author_name" value="<?=$q_h_author_name?>" size="30">
</div>

<div class="form_row">
<label for="author_email"><?=_('Your email') ?></label>
  <input type="text" id="author_email" name="author_email" value="<?=$q_h_author_email?>" size="30">
</div>

<div class="form_row">
<label for="author_website"><?=_('Your web site') ?></label> <small><i><?=_('(optional)') ?></i></small>
  <input type="text" id="author_website" name="author_website" value="<?=$q_h_author_website?>" size="30">
</div>

<p><strong><?=_('Your comment') ?></strong>
<br><textarea style="max-width: 100%" name="text" id="text" cols="40" rows="10"><?=$q_h_text?></textarea>
</p>

<? if ($q_h_comment_id) { ?>
<input type="hidden" name="comment_id" value="<?=$q_h_comment_id?>">
<? } ?>
<input type="hidden" name="n" value="<?=$nextn?>">
<p><small><?=_('Your name and web site, if given, will be shown on your comment,
but your email address will not be.') ?></small></p>
<p><input type="checkbox" name="comment_alert_signup" <?=$q_comment_alert_signup ? "checked" : ""?>>
<?=_('Email me any replies to my comment') ?></p>

<p><input type="submit" name="preview" value="<?=_('Preview') ?>">
<? if ($allow_post) { ?>
<input type="submit" name="submit" value="<?=_('Post comment') ?>">
<? } ?>

<?  if ($p = get_http_var('pin')) print '<input type="hidden" name="pin" value="$p">'; ?>
</form>
<?
}

?>
