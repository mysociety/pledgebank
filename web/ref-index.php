<?
// ref-index.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-index.php,v 1.138 2010-02-01 12:50:32 matthew Exp $

define('MAX_PAGE_SIGNERS', '500');
define('MAX_PAGE_COMMENTS', '50');

require_once '../conf/general';
require_once '../phplib/page.php';
require_once '../phplib/moderation.php';
require_once '../commonlib/phplib/conditional.php';
require_once '../commonlib/phplib/db.php';

/* Short-circuit the conditional GET as soon as possible -- parsing the rest of
 * the includes is costly. */
$etag = page_send_vary_header();
if (array_key_exists('ref', $_GET)
    && ($id = db_getOne('select id from pledges where ref = ?', $_GET['ref']))) {
        $t = intval(db_getOne('select extract(epoch from pledge_last_change_time(?))', $id));
        if (cond_maybe_respond($t, sha1($etag . $t)))
            exit;
}

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
#require_once '../phplib/share.php';
require_once '../commonlib/phplib/utility.php';

$ref = get_http_var('ref');

/* Reference aliases */
if (strcasecmp($ref, 'sportsclubpatrons') == 0) 
    $ref = 'Sportclubpatrons';

/* Look up pledge */
page_check_ref($ref);
$p = new Pledge($ref);

/* Redirect to canonical case spelling of the pledge ref */
if ($ref != $p->ref()) {
    header("Location: /" . $p->ref());
    exit;
}

moderation_redirect($p);

/* Redirect to correct microsite */
microsites_redirect($p);

/* Do this again because it's possible we'll reach here with a non-canonical
 * ref (e.g. different case from that entered by the creator). */
$etag = sha1($etag . $p->last_change_time());
if (cond_maybe_respond($p->last_change_time(), $etag))
    exit();

deal_with_pin($p->url_main(), $p->ref(), $p->pin());

# Okay, main display activity
locale_push($p->lang());

$title = "'" . microsites_pledge_prefix(_('I will'), $p->creator_name()) . ' ' . $p->h_title() . "'";
locale_pop();
$params = array(
    'ref' => $p->ref(),
    'pref' => $p->url_typein(),
    'last-modified' => $p->last_change_time(),
    'etag' => $etag,
    'body_class' => $p->ref(),
    #'css' => '/jslib/share/share.css',
);
if (microsites_comments_allowed() && !$p->pin())
    $params['rss'] = array(sprintf(_("Comments on Pledge '%s'"), $p->ref()) => $p->url_comments_rss());
 
page_header($title, $params);
include_once microsite_template('ref-index');
page_footer();
 
// Internal
function display_anonymous_signers($p, &$anon, &$anon_done, &$mobilesigners, &$facebooksigners, &$in_ul) {
    if ($anon || $mobilesigners || $facebooksigners) {
        if (!$in_ul) {
            print "<ul>";
            $in_ul = true;
        }
        if ($anon) {
            print "<li>" . sprintf(ngettext('%d person who did not want to give their name', '%d people who did not want to give their names', $anon), $anon);
            if ($anon_done == $anon && $anon>1)
                print _(', all of whom have done the pledge');
            elseif ($anon_done == $anon)
                print _(', who has done the pledge');
            elseif ($anon_done)
                printf(ngettext(', %d of whom has done the pledge', ', %d of whom have done the pledge', $anon_done), $anon_done);
            print "</li>";
        }
        if ($mobilesigners)
            print "<li>". sprintf(ngettext('%d person who signed up via mobile', '%d people who signed up via mobile', $mobilesigners), $mobilesigners) . "</li>";
        if ($facebooksigners)
            print "<li>". sprintf(ngettext('%d person who signed up <a href="%s">in Facebook</a>', '%d people who signed up <a href="%s">in Facebook</a>', $facebooksigners), $facebooksigners, OPTION_FACEBOOK_CANVAS . $p->ref()) . "</li>";

        $anon = 0;
        $anon_done = 0;
        $mobilesigners = 0;
        $facebooksigners = 0;
    }
}

function draw_signatories_list($p, $nsigners, $include_creator=true) {
    $P = pb_person_if_signed_on();

    /* XXX need to do something about layout here -- it breaks badly when the
     * height of the comments column is greater than that of the signers
     * column. */
    $limit = 0;
    $showall_para = '';
    $showall_nav = '';
    if ($nsigners > MAX_PAGE_SIGNERS) {
        if (!get_http_var('showall')) {
            $showall_para = sprintf(_("Because there are so many signers, only the most recent %d are shown on this page."), MAX_PAGE_SIGNERS);
            $showall_nav = sprintf("<a href=\"/%s?showall=1\">"
                    . htmlspecialchars(_("Show all signers"))
                    . "</a>",
                    htmlspecialchars($p->ref()));
            $limit = 1;

            print p($showall_para);
        }
    }

    if ($p->byarea()) {
        // Not quite ready yet
        print '<p>';
        print '<img src="/byarea-map.cgi?pledge_id='.$p->id().'" alt="">';
        print '</p>';
    } else {
        if (!$limit && $include_creator) {
            print '<p>';
            print sprintf(_('%s, the Pledge Creator, joined by:'), htmlspecialchars($p->creator_name()));
            print '</p>';
        }
    }

    $anon = 0;
    $anon_done = 0;
    $mobilesigners = 0;
    $facebooksigners = 0;
    
    $order_by = "ORDER BY id";
    $extra_select = "";
    $extra_join = "";
    if ($p->byarea()) {
        $order_by = "ORDER BY signers.byarea_location_id, id";
        $extra_select = ", date_trunc('day', byarea_location.whensucceeded) as whensucceeded";
        $extra_join = "LEFT JOIN byarea_location ON byarea_location.byarea_location_id = signers.byarea_location_id AND byarea_location.pledge_id = signers.pledge_id";
    }
    $query = "SELECT signers.*, person.mobile as mobile, person.facebook_id as facebook_id,
            location.description as location_description, location.country as location_country
            $extra_select
        from signers 
        LEFT JOIN location on location.id = signers.byarea_location_id 
        LEFT JOIN person on person.id = signers.person_id 
        $extra_join
        WHERE signers.pledge_id = ? $order_by";
    if ($limit) {
        $query .= " LIMIT " . MAX_PAGE_SIGNERS . " OFFSET " . ($nsigners - MAX_PAGE_SIGNERS);
    }
    $q = db_query($query, $p->id());
    $last_location_description = "";
    $in_ul = false;
    while ($r = db_fetch_array($q)) {
        $showname = ($r['showname'] == 't');
        $loc_desc_with_country = $r['location_description'];
        if ($r['location_country'] && (!$p->country_code() || ($p->country_code() != $r['location_country']))) {
            global $countries_code_to_name;
            $loc_desc_with_country .= ", ". $countries_code_to_name[$r['location_country']];
        }
        if ($p->byarea() && $last_location_description != $loc_desc_with_country) {
            display_anonymous_signers($p, $anon, $anon_done, $mobilesigners, $facebooksigners, $in_ul);
            if ($in_ul)  {
                print "</ul></div>";
                $in_ul = false;
            }
            print '<div class="location';
            if ($r['whensucceeded']) print '_success';
            print '">';
            if ($r['whensucceeded']) {
                print '<p>';
                printf(_("Succeeded on %s"), prettify($r['whensucceeded']));
                print '</p>';
            }
            print '<h3>' . $loc_desc_with_country . "</h3>";
            $last_location_description = $loc_desc_with_country;
        }
        if (!$in_ul) {
            print "<ul>";
            $in_ul = true;
        }
        if ($showname) {
            if (isset($r['name'])) {
                print '<li id="signer' . $r['id'] . '"';
                if ($r['done']=='t') print ' class="done"';
                print '>';
                if (microsites_has_survey() && !is_null($P) && $r['person_id'] == $P->id()) {
                    print '<form method="post" action="' . $p->url_survey() . '"><input type="hidden" name="r" value="pledge">';
                }
                print htmlspecialchars($r['name']);
                if (microsites_has_survey() && !is_null($P) && $r['person_id'] == $P->id()) {                
                    if ($r['done']=='f' ) {
                        print ' &ndash; <input type="submit" value="'._("I have now done what I pledged").'">';                    
                    } else {
                        print ' &ndash; <input type="hidden" name="undopledge" value="1"><input type="submit" value="'._("Click this button if in fact you have NOT done what you pledged").'">';
                    }
                    print '</form>';
                }
                print '</li>';
            } else {
                err('showname set but no name');
            }
        } elseif (isset($r['mobile'])) {
            $mobilesigners++;
        } elseif (isset($r['facebook_id']) && $r['via_facebook'] == 't') {
            $facebooksigners++;
        } else {
            $anon++;
            if ($r['done']=='t') $anon_done++;
        }
    }
    display_anonymous_signers($p, $anon, $anon_done, $mobilesigners, $facebooksigners, $in_ul);
    if ($in_ul) {
        print "</ul>";
        if ($p->byarea())
            print "</div>";
        $in_ul = false;
    }
    if ($showall_para) {
        print p($showall_nav);
        print p($showall_para);
    }
}

function draw_comments($p) {
    if (!$p->pin())
        print '<a href="' . $p->url_comments_rss() . '"><img align="right" border="0" src="rss.gif" alt="' . _('RSS feed of comments on this pledge') . '"></a>';
    print '<div id="comments">';
    print h2('<a name="comments">' . _('Comments on this pledge') . '</a>');

    $limit = 0;
    $showall_para = '';
    $showall_nav = '';
    $ncomments = comments_count($p->id());
    if ($ncomments > MAX_PAGE_COMMENTS) {
        if (!get_http_var('showall')) {
            $showall_para = sprintf(_("Because there are so many comments, only the most recent %d are shown on this page."), MAX_PAGE_COMMENTS);

            $showall_nav = sprintf("<a href=\"/%s?showall=1\">"
                    . htmlspecialchars(_("Show all comments"))
                    . "</a>",
                    htmlspecialchars($p->ref()));
            $limit = 1;

            print "<p>".$showall_para."</p>";
            print '<p id="toomany">' . $showall_nav . '</p>';
        }
    }

    if ($limit)
        comments_show($p->id(), false, MAX_PAGE_COMMENTS); 
    else 
        comments_show($p->id(), false); 

    /*if ($showall_para) {
        print p($showall_nav);
        print p($showall_para);
    }*/

    print '</div>';
    comments_form($p->id(), 1, false, $p->closed_for_comments());
}

$connections = array();
function fetch_connection_data($id) {
    global $connections, $pb_today;
    if (!$connections)
        $connections = db_getAll("SELECT a_pledge_id as a, b_pledge_id as b, strength 
        FROM pledge_connection 
            LEFT JOIN pledges AS a_pledges ON a_pledge_id = a_pledges.id
            LEFT JOIN pledges AS b_pledges ON b_pledge_id = b_pledges.id
        WHERE
            (a_pledge_id = ? AND b_pledges.date >= '$pb_today' AND b_pledges.whensucceeded is null) or
            (b_pledge_id = ? AND a_pledges.date >= '$pb_today' AND a_pledges.whensucceeded is null)
        ORDER BY strength DESC,RANDOM() 
        LIMIT 8", array($id, $id));
    return $connections;
}

function draw_connections($p) {
    $s = fetch_connection_data($p->id());
    if (!count($s)) return;

    print "\n\n" . '<div id="connections"><h2><a name="connections">' . 
        _('Suggested pledges') . ' </a></h2>'.
        p(_('Some of the people who signed this pledge also signed these pledges...')) . '<ul>' . "\n\n";
    foreach ($s as $row) {
        $id = $row['a'] == $p->id() ? $row['b'] : $row['a'];
        $strength = $row['strength'];
        $p2 = new Pledge(intval($id));
        print '<li><a href="/' . htmlspecialchars($p2->ref()) . '">' . $p2->h_title() . '</a>';
        print ' (';
        printf(ngettext('%s person', '%s people', $strength), $strength);
        print ')';
        print '</li>';
        print "<!-- strength $strength -->\n";
    }
    print "\n\n";
    print '</ul></div>';
}

// When a pledge has closed, we advertise other pledges (ones signed by same
// people, or else featured ones)
function draw_connections_for_finished($p) {
    $try_pledges_required = 4;
    $pledges = array();
    $s = fetch_connection_data($p->id());
    if (count($s)) {
        $k = 0;
        foreach ($s as $row) {
            $id = $row['a'] == $p->id() ? $row['b'] : $row['a'];
            $p2 = new Pledge(intval($id));
            $pledges[] = $p2;
            if ($k++ == 4) break;
        }
    } 
    if (count($pledges) < $try_pledges_required) {
        list($extra_pledges, $more) = pledge_get_frontpage_list($try_pledges_required - count($pledges), $try_pledges_required - count($pledges));
        foreach ($extra_pledges as $ep) {
            foreach ($pledges as $pp) {
                if ($pp->id() == $ep->id()) continue 2; 
            }
            $pledges[] = $ep;
        }
    }

    print "\n\n" . '<div id="finished_connections" class="';
    if ($p->left()<=0 && !microsites_no_target()) {
        print 'success">';
        print strong(_('This pledge has now closed; it was successful!'));
    } else {
        print 'finished">';
            print microsites_pledge_closed_text();
    }
    if (count($pledges) > 0) {
        print ' ' . _('You might be interested in these other pledges:');
        print '<ul>' . "\n\n";
        foreach ($pledges as $p2) {
            print '<li><a href="/' . htmlspecialchars($p2->ref()) . '">' . $p2->h_title() . '</a>';
            print '</li>';
        }
        print "\n\n";
        print '</ul>';
    }
    print p(_('See <a href="/list">more pledges</a>, and all <a href="/faq">about how PledgeBank works</a>.'));
    print '</div>';
}

