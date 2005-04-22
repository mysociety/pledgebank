<?php
/*
 * comments.php:
 * Comments on pledges.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: comments.php,v 1.5 2005-04-22 19:58:22 matthew Exp $
 * 
 */

require_once('pb.php');
require_once('fns.php');
require_once('db.php');

/* comments_text_to_html TEXT
 * Convert TEXT to HTML. To start with we just turn line-feeds into <br>s and
 * URLs and hostnames beginning "www." into HREFs. */
function comments_text_to_html($text) {
    return nl2br(
            preg_replace(
                "/[\w\.]+@[\w\.\-]+/",
                '<a href="mailto:$0">$0</a>',
                preg_replace(
                    "/((http(s?):\/\/)|(www\.))([a-zA-Z\d\_\.\+\,\;\?\%\~\-\/\#\='\*\$\!\(\)\&]+)([a-zA-Z\d\_\?\%\~\-\/\#\='\*\$\!\(\)\&])/",
                    '<a href="$1">$1</a>',
                    htmlspecialchars($text)
                )
            )
        );
}

/* comments_format_timestamp TIME
 * Format TIME as a friendly version of the timestamp. */
function comments_format_timestamp($time) {
    return $time;
}

/* comments_show_one COMMENT
 * Given COMMENT, an associative array containing fields 'text', 'name' and
 * 'website' (and optional fields 'id', the comment ID, and 'whenposted', the
 * posting time in seconds since the epoch), print HTML for the comment
 * described. */
function comments_show_one($comment) {
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
        else if (strftime('%U', $w) == strftime('%U', $t))
            $tt = "$tt, " . strftime('%A', $w);
        else if (strftime('%Y', $w) == strftime('%Y', $t))
            $tt = "$tt, " . strftime('%A %e %B', $w);
        else
            $tt = "$tt, " . strftime('%A %e %B %Y', $w);

        print " at $tt.";
    }

    if (isset($comment['id']))
        print ' <a class="abusivecommentlink" href="/abusivecomment?id=' . $comment['id'] . '">Abusive? Report it!</a>';

    print '</small></div>';
}

/* comments_show PLEDGE
 * Show the comments for the given PLEDGE (id or reference). */
function comments_show($pledge) {
    $id = $pledge;

    if (is_null($id))
        $id = db_getOne('select id from pledges where ref = ?', $pledge);

    if (is_null($id))
        err("No pledge '$pledge'");

    print '<div class="commentsbox">';
    
    if (db_getOne('select count(id) from comment where pledge_id = ?', $id) == 0)
        print '<em>No comments yet! Why not add one?</em>';
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

            comments_show_one($r);

            print '</li>';
        }

        print "</ul>";
    }
    print "</div>";
}

?>
