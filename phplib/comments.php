<?php
/*
 * comments.php:
 * Comments on pledges.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: comments.php,v 1.71 2007-10-25 15:41:37 francis Exp $
 * 
 */

require_once('pbperson.php');
require_once('../commonlib/phplib/utility.php');
require_once('../commonlib/phplib/db.php');

/* comments_text_to_html TEXT
 * Convert TEXT to HTML. To start with we just turn line-feeds into <br>s and
 * URLs and hostnames beginning "www." into HREFs. */
function comments_text_to_html($text) {
    return str_replace("\n", "<br>\n", ms_make_clickable(
        htmlspecialchars($text), array('contract'=>true, 'nofollow'=>true)
    ));
}

/* comments_format_timestamp TIME
 * Format TIME as a friendly version of the timestamp. */
function comments_format_timestamp($time) {
    return $time;
}

/* comments_show_one COMMENT [NOABUSE] [ADMIN]
 * Given COMMENT, an associative array containing fields 'text', 'name' and
 * 'website' (and optional fields 'id', the comment ID, and 'whenposted', the
 * posting time in seconds before now), print HTML for the comment
 * described. If NOABUSE is true, don't show the link for reporting an abusive
 * comment. If ADMIN is true display email address and form for changing hidden
 * status.
 */
function comments_show_one($comment, $noabuse = false, $admin = false) {
    if (isset($comment['id']) && $admin) {
        print '<form name="deletecommentform'.$comment['id'].'" method="post" action="'.OPTION_ADMIN_URL.'?page=pb&amp;pledge_id='.$comment['pledge_id'].'"><input type="hidden" name="deletecomment_comment_id" value="' . $comment['id'] . '">';
    }

    $name = htmlspecialchars($comment['name']);
    if (isset($comment['website']))
        $name = '<a rel="nofollow" href="' . htmlspecialchars($comment['website']) . '">' . $name . '</a>';
    if ($admin)
        $name .= " (<a href=\"mailto:" . $comment['email'] . "\">" . $comment['email'] . "</a>)";

    if (array_key_exists('ishidden', $comment) && $comment['ishidden'] == 't')
        print "<strike>";
    print comments_text_to_html($comment['text']);
    if (array_key_exists('ishidden', $comment) && $comment['ishidden'] == 't')
        print "</strike>";
    print '<div><small>';  /* XXX or h1 or something? */
    if (isset($comment['ref'])) {
        $r = '<a href="/' . $comment['ref'] . '">' . $comment['ref'] . '</a>';
        if (isset($comment['whenposted'])) {
            # TRANS: This appears immediately after a comment, to show who wrote it when, as: "To pledge <pledge reference> by <person's name>, <how long ago>."
            # TRANS: e.g. "blah, blah, this is a comment. - To pledge artnotads by Matthew, 3 hours ago."
            printf(_('To pledge %s by %s, %s.'), $r, $name, prettify_duration($comment['whenposted']));
        } else {
            # TRANS: This is a shortened version of the above string: "To pledge <pledge reference> by <person's name>."
            printf(_('To pledge %s by %s.'), $r, $name);
        }
    } else {
        /* Format the time sanely. */
        if (isset($comment['whenposted'])) {
            # TRANS: This appears immediately after a comment, to show who wrote it when, as: "<person's name>, <how long ago>."
            # TRANS: e.g. "Matthew, 4 minutes ago."
            printf(_('%s, %s.'), $name, prettify_duration($comment['whenposted']));
        } else {
            print $name;
        }
    }

    if (isset($comment['id']) && !$noabuse)
        print ' <a rel="nofollow" href="/contact?pledge_id=' . $comment['pledge_id'] . '&amp;comment_id=' . $comment['id'] . '">' . _('Abusive? Report it!') . '</a>';
    if (isset($comment['id']) && $admin) {
        print '<select name="deletecomment_status">';
        print '<option value="0"' . ($comment['ishidden'] == 'f'?' selected':'') . '>Visible</option>';
        print '<option value="1"' . ($comment['ishidden'] == 't'?' selected':'') . '>Hidden</option>';
        print '</select>';
        print '<input type="submit" name="deletecomment" value="update">';
    }

    print '</small></div>';
    if (isset($comment['id']) && $admin) {
        print '</form>';
    }
}

/* comments_count PLEDGE
 * Returns how many comments pledge has. */
function comments_count($pledge) {
    $id = $pledge;
    return db_getOne('select count(id) from comment where pledge_id = ? and not ishidden', $id);
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
        # TRANS: this is an error message meaning, "The pledge called '%s' doesn't exist.
        err(sprintf(_("No pledge '%s'"), $pledge));

    $count = db_getOne('select count(id) from comment where pledge_id = ? and not ishidden', $id);
    if ($count == 0)
        print '<p><em>' . _('No comments yet! Why not add one?') . '</em></p>';
    else {
        print '<ul id="comments">';

        $query = '
                    select id, extract(epoch from ms_current_timestamp()-whenposted) as whenposted,
                        text, name, website, pledge_id
                    from comment
                    where comment.pledge_id = ?
                        and not ishidden
                    order by whenposted desc';
        if ($limit) {
            $query .= " LIMIT " . $limit . " OFFSET " . ($count - $limit);
        }
        $q = db_query($query , $id);

        while ($r = db_fetch_array($q)) {
            print '<li id="comment_' . $r['id'] . '">';

            comments_show_one($r, $noabuse);

            print '</li>';
        }

        print "</ul>";
    }
}

/* comments_summary COMMENT 
 * Display comment for index, such as front page or search results. */
function comments_summary($r, $search = '') {
    $text = $r['text'];
    if (strlen($text) > 30) {
        if ($search) {
            $start = strpos(strtolower($text), strtolower($search)) - 20;
            $end = strlen($search) + 50;
        } else {
            $start = 0;
            $end = 30;
        }
        $text = trim_characters($text, $start, $end);
    }
    $text = htmlspecialchars($text);
    if ($search)
        $text = str_replace($search, "<strong>$search</strong>", $text);
    $text = '<a href="/' . $r['ref'] . '#comment_' . $r['id'] . '">' . $text . '</a>';
    # TRANS: This appears under "Latest comments" on the front page as: "<start of comment text...> by <name>, on <pledge reference link>, <how long ago>"
    # TRANS: e.g. "Most of my veg are British..." by Esther, on ukfood, 3 hours ago"
    return sprintf(_('%s by %s, on %s, %s'), "$text<br><small>", htmlspecialchars($r['name']), "<a href=\"/$r[ref]\">$r[ref]</a>", prettify_duration($r['whenposted']).'</small>');
}

/* comments_rss_entry COMMENT 
 * Comment in RSS form. */
function comments_rss_entry($r) {
    $text = $r['text'];
    if (strlen($text) > 250) $text = trim_characters($text, 0, 250);
    
    return array(
          # TRANS: this appears in an RSS feed as: "Comment on <pledge reference> pledge by <person's name>"
          # TRANS: e.g. "Comment on Electric pledge by Owen Blacker"
          'title' => sprintf(_('Comment on %s pledge by %s'), $r['ref'], htmlspecialchars($r['name'])),
          'link' => pb_domain_url(array('explicit'=>true, 'path'=>"/". $r['ref'] . '#comment_' . $r['id'])),
          'description' => htmlspecialchars($text),
          'whenposted' => $r['whenposted']
    );

}

/* comments_show_latest [NUM] [RSS]
 * Show a brief summary of the NUM (default 10) most recent comments. 
 * If RSS is set, then instead of printing HTML return an RSS array. */
function comments_show_latest($comments_to_show = 10, $rss = 0) { 
    $rss_items = array();

    $c = 0;

    $sql_params = array();
    $site_limit = pb_site_pledge_filter_main($sql_params);
    $c += comments_show_latest_internal($comments_to_show, $sql_params, $site_limit, $rss, $rss_items);

    if ($c == 0) {
        $sql_params = array();
        $site_limit = pb_site_pledge_filter_general($sql_params);
        $c += comments_show_latest_internal($comments_to_show, $sql_params, $site_limit, $rss, $rss_items);
    }

    if ($c == 0) {
        $sql_params = array();
        $site_limit = pb_site_pledge_filter_foreign($sql_params);
        $c += comments_show_latest_internal($comments_to_show, $sql_params, $site_limit, $rss, $rss_items);
    }

    if ($rss)
        return $rss_items;
}

function comments_show_latest_internal($comments_to_show, $sql_params, $site_limit, $rss, &$rss_items) {
    $sql_params[] = $comments_to_show;
    $q = db_query("
                SELECT comment.id,
                    extract(epoch from ms_current_timestamp()-whenposted) as whenposted, text,
                    comment.name, website, ref,
                    comment.person_id
                FROM comment, pledges, location
                WHERE comment.pledge_id = pledges.id
                    AND location.id = pledges.location_id
                    AND NOT ishidden
                    AND pin IS NULL
                    AND pledges.cached_prominence <> 'backpage'
                    AND ($site_limit)
                ORDER BY whenposted
                LIMIT ?", $sql_params);
    $num = db_num_rows($q);
    $done = array();
    if ($num > 0) {
        if ($rss) {
            while($r = db_fetch_array($q)) {
                $rss_items[] = comments_rss_entry($r);
            }
        } else {
            ?><div id="comments">
<a href="<?=pb_domain_url(array('explicit'=>true, 'path'=>"/rss/comments"))?>"><img align="right" border="0" src="rss.gif" alt="<?=_('RSS feed of comments on all pledges') ?>"></a>
            <?=_('<h2>Latest comments</h2>') ?> <?  
            print '<ul class="search_results">';
            while($r = db_fetch_array($q)) {
                // Only show one comment from each person
                if (array_key_exists($r['person_id'], $done))
                    continue;
                $done[$r['person_id']] = 1;
                print '<li>';
                print comments_summary($r);
                print '</li>';
            }
            print '</ul></div>';
        }
    }
    return $num;
}

/* comments_show_admin PLEDGE [LIMIT]
 * Show the comments for admin page. */
function comments_show_admin($pledge, $limit = 0) {
    $id = $pledge;

    if (is_null($id))
        $id = db_getOne('select id from pledges where ref = ?', $pledge);
    if (is_null($id))
        err(sprintf(_("No pledge '%s'"), $pledge));

    $count = db_getOne('select count(id) from comment where pledge_id = ?', $id);
    if ($count == 0)
        print '<p><em>' . _('No comments') . '</em></p>';
    else {
        print '<ul>';

        $query = ' select comment.id, extract(epoch from ms_current_timestamp()-whenposted) as whenposted,
                        text, comment.name, comment.website, ishidden, pledge_id,
                        person.email as email
                    from comment
                    left join person on comment.person_id = person.id
                    where comment.pledge_id = ?
                    order by whenposted';
        if ($limit) {
            $query .= " LIMIT " . $limit;
        }
        $q = db_query($query , $id);

        while ($r = db_fetch_array($q)) {
            print '<li id="comment_' . $r['id'] . '">';
            comments_show_one($r, true, true);
            print '</li>';
        }
        print "</ul>";
    }
}


/* comments_form PLEDGE N [ALLOWPOST]
 * Show a form for entering a comment on the given PLEDGE. N is a number which
 * should be increased each time the form is shown; unless ALLOWPOST is set,
 * the form displays only a button to preview the comment, rather than to
 * finally post it. If CLOSED_FOR_COMMENTS is true, instead of form displays
 * a message saying no more comments.*/
function comments_form($pledge_id, $nextn, $allow_post, $closed_for_comments) {
    global $q_h_comment_id;
    global $q_h_author_name, $q_h_author_email, $q_h_author_website, $q_comment_alert_signup;
    global $q_h_text;

    $P = pb_person_if_signed_on();
    if (!is_null($P)) {
        if (is_null($q_h_author_name) || !$q_h_author_name)
            $q_h_author_name = htmlspecialchars($P->name_or_blank());
        if (is_null($q_h_author_email) || !$q_h_author_email)
            $q_h_author_email = htmlspecialchars($P->email());
        if (is_null($q_h_author_website))
            $q_h_author_website = htmlspecialchars($P->website_or_blank());
    }
    if ($nextn == 1 && (is_null($q_comment_alert_signup) || !$q_comment_alert_signup))
        $q_comment_alert_signup = 1;
    
?>
<form method="POST" action="comment.php" id="commentform" name="commentform" class="pledge">
<? if ($closed_for_comments) { ?>
<?=_("This pledge is closed for new comments.")?> 
<? } else { ?>
<input type="hidden" name="pledge_id" value="<?=$pledge_id ?>">
<?=_('<h2>Add public comment</h2>') ?>
<div class="form_row">
 <label for="author_name"><?=_('Your name:') ?></label>
 <input type="text" id="author_name" name="author_name" value="<?=$q_h_author_name?>" size="30">
</div>

<div class="form_row">
<label for="author_email"><?=_('Your email:') ?></label> 
  <input type="text" id="author_email" name="author_email" value="<?=$q_h_author_email?>" size="30">
</div>

<div class="form_row">
<label for="author_website"><?=_('Your web site address') ?>:</label> <small><i><?=_('(optional, will be shown publicly)') ?></i></small>
  <input type="text" id="author_website" name="author_website" value="<?=$q_h_author_website?>" size="40">
</div>

<p><strong><?=_('Your comment') ?>:</strong>
<br><textarea name="text" id="text" cols="40" rows="10"><?=$q_h_text?></textarea>
</p>

<? if ($q_h_comment_id) { ?>
<input type="hidden" name="comment_id" value="<?=$q_h_comment_id?>">
<? } ?>
<input type="hidden" name="n" value="<?=$nextn?>">
<p><small><strong><?=_('Privacy note:')?></strong>
<?
print _('Your name (and web site address if given) will be shown publicly on this page
with your comment. Your email address will not be shown. People searching for your
name on the Internet may find your comment.');
?></small></p> <p><input
type="checkbox" name="comment_alert_signup" <?=$q_comment_alert_signup ?
"checked" : ""?>>
<?=_(' Email me future comments on this pledge') ?></p>

<p><input type="submit" name="preview" value="<?=_('Preview') ?>">
<? if ($allow_post) { ?>
<input type="submit" name="submit" value="<?=_('Post comment') ?>">
<? } ?>

<?  if ($p = get_http_var('pin', true)) print '<input type="hidden" name="pin" value="' . $p . '">'; ?>

<? } ?>
</form>
<?
}

function prettify_duration($s) {
    $s = floor(($s+30)/60); # Nearest minute
    $years = floor($s / (60*24*365));
    $months = floor($s / (60*24*(365/12.0)));
    $weeks = floor($s / (60*24*7));
    $days = floor($s / (60*24));
    $hours = floor($s / (60));
    $minutes = floor($s);
    if ($s >= 60*24*365*2)
        return sprintf(ngettext('%d year ago', '%d years ago', $years), $years);
    elseif ($s >= 60*24*(365/12)*2)
        return sprintf(ngettext('%d month ago', '%d months ago', $months), $months);
    elseif ($s >= 60*24*7*2)
        return sprintf(ngettext('%d week ago', '%d weeks ago', $weeks), $weeks);
    elseif ($s >= 60*24)
        return sprintf(ngettext('%d day ago', '%d days ago', $days), $days);
    elseif ($s >= 60)
        return sprintf(ngettext('%d hour ago', '%d hours ago', $hours), $hours);
    elseif ($s >= 1)
        return sprintf(ngettext('%d minute ago', '%d minutes ago', $minutes), $minutes);
    else
        return _('less than a minute ago');
}

?>
