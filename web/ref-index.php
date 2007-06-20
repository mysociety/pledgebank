<?
// ref-index.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-index.php,v 1.102 2007-06-20 21:58:00 francis Exp $

require_once '../conf/general';
require_once '../phplib/page.php';
require_once '../../phplib/conditional.php';
require_once '../../phplib/db.php';

/* Short-circuit the conditional GET as soon as possible -- parsing the rest of
 * the includes is costly. */
page_send_vary_header();
if (array_key_exists('ref', $_GET)
    && ($id = db_getOne('select id from pledges where ref = ?', $_GET['ref']))
    && cond_maybe_respond(intval(db_getOne('select extract(epoch from pledge_last_change_time(?))', $id))))
    exit();

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../../phplib/utility.php';

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

/* Redirect to correct microsite */
microsites_redirect($p);

/* Do this again because it's possible we'll reach here with a non-canonical
 * ref (e.g. different case from that entered by the creator). */
if (cond_maybe_respond($p->last_change_time()))
    exit();

$pin_box = deal_with_pin($p->url_main(), $p->ref(), $p->pin());
if ($pin_box) {
    page_header(_("Enter PIN"));
    print $pin_box;
    page_footer();
    exit;
}

function draw_status_plaque($p) {
    if ($p->is_cancelled()) {
        print '<p class="cancelled">' . comments_text_to_html($p->data['cancelled']) . '</p>';
        return;
    }
    if ($p->data['notice']) {
        print '<p class="notice">' . comments_text_to_html($p->data['notice']) . '</p>';
    }
    if (!$p->open()) {
        print '<p id="finished">' . microsites_pledge_closed_text() . '</p>';
    }
    if ($p->byarea()) {
        if ($p->byarea_successes() > 0) {
            print '<p class="success">';
            print sprintf(
                ngettext('This pledge has been successful in <strong>%d place</strong>!',
                        'This pledge has been successful in <strong>%d places</strong>!',
                        $p->byarea_successes()), 
                $p->byarea_successes());
            if (!$p->finished()) {
                print '<br>' . _('<strong>You can still sign up</strong>, to help make it successful where you live.');
            }
            print '</p>';
        }
    } elseif ($p->left() <= 0 && !microsites_no_target()) {
        print '<p class="success">' . _('This pledge has been successful!');
        if (!$p->finished()) {
            print '<br>' . _('<strong>You can still add your name to it</strong>, because the deadline hasn\'t been reached yet.');
        }
        print '</p>';
    }
}

function draw_spreadword($p) { ?>
    <div id="spreadword">
<?  if (!$p->finished()) {
        print '<h2>' . _('Spread the word on and offline') . '</h2>';
    } else {
        print '<h2>' . _('Things to do with this pledge') . '</h2>';
    }
    print '<ul>';
    if (!$p->finished()) {
        print '<li>';
        print_link_with_pin($p->url_email(), "", _("Email pledge to your friends"));
        print '</li>';
        if (microsites_has_flyers())
            print '<li>';
            print_link_with_pin($p->url_flyers(), _("Stick them places!"), _("Print out customised flyers"));
            print '</li>';
    } ?>
    <li><a href="/new/local/<?=$p->ref() ?>"><?=_('Create a local version of this pledge') ?></a></li>
    <li> <a href="<?=$p->url_announce()?>" title="<?=_('Only if you made this pledge') ?>"><?=_('Send message to signers') ?></a> <?=_('(creator only)');
    if (!$p->finished()) { ?>
    <li> <?
        print '<a href="' . $p->url_picture() . '" title="' . _('Only if you made this pledge') . '">';
        if ($p->has_picture()) {
            print _('Change the pledge picture');
        } else {
            print _('Add a picture to your pledge');
        }
        print '</a> ' . _('(creator only)'); ?>
    </li>
<?  }
    print '</ul>';
    print '</div>';
}

define('MAX_PAGE_SIGNERS', '500');

// Internal
function display_anonymous_signers($p, &$anon, &$mobilesigners, &$facebooksigners, &$in_ul) {
    if ($anon || $mobilesigners || $facebooksigners) {
        if (!$in_ul) {
            print "<ul>";
            $in_ul = true;
        }
        if ($anon)
            print "<li>". sprintf(ngettext('%d person who did not want to give their name', '%d people who did not want to give their names', $anon), $anon) . "</li>";
        if ($mobilesigners)
            print "<li>". sprintf(ngettext('%d person who signed up via mobile', '%d people who signed up via mobile', $mobilesigners), $mobilesigners) . "</li>";
        if ($facebooksigners)
            print "<li>". sprintf(ngettext('%d person who signed up <a href="%s">in Facebook</a>', '%d people who signed up <a href="%s">in Facebook</a>', $facebooksigners), $facebooksigners, OPTION_FACEBOOK_CANVAS . $p->ref()) . "</li>";

        $anon = 0;
        $mobilesigners = 0;
        $facebooksigners = 0;
    }
}

function draw_signatories($p) {
    $nsigners = db_getOne('select count(id) from signers where pledge_id = ?', $p->id());
    ?>
    <div id="signatories">
<?
    print '<h2><a name="signers">' . _('Current signatories') . '</a></h2>';

    if ($nsigners == 0) {

        print '<p>'
                . sprintf(_('So far, only %s, the Pledge Creator, has signed this pledge.'), htmlspecialchars($p->creator_name()))
                . '</p></div>';
        return;
    }

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
        if (!$limit) {
            print '<p>';
            print sprintf(_('%s, the Pledge Creator, joined by:'), htmlspecialchars($p->creator_name()));
            print '</p>';
        }
    }

    $anon = 0;
    $mobilesigners = 0;
    $facebooksigners = 0;
    
    $order_by = "ORDER BY id";
    $extra_select = "";
    $extra_join = "";
    if ($p->byarea()) {
        $order_by = "ORDER BY signers.byarea_location_id, id";
        $extra_select = ", byarea_location.whensucceeded";
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
            display_anonymous_signers($p, $anon, $mobilesigners, $facebooksigners, $in_ul);
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
                print '<li>'
                        . htmlspecialchars($r['name'])
                        . '</li>';
            } else {
                err('showname set but no name');
            }
        } elseif (isset($r['mobile'])) {
            $mobilesigners++;
        } elseif (isset($r['facebook_id'])) {
            $facebooksigners++;
        } else {
            $anon++;
        }
    }
    display_anonymous_signers($p, $anon, $mobilesigners, $facebooksigners, $in_ul);
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
    print '<p>';
    print_link_with_pin($p->url_info(), "", _("View signup rate graph"));
    print '</div>';
}

define('MAX_PAGE_COMMENTS', '50');
function draw_comments($p) {
    print "\n\n" . '<div class="comments">';
    if (!$p->pin())
        print '<a href="' . $p->url_comments_rss() . '"><img align="right" border="0" src="rss.gif" alt="' . _('RSS feed of comments on this pledge') . '"></a>';
    print '<h2><a name="comments">' . _('Comments on this pledge') . '</a></h2>';

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
            print "<p class=\"toomany\">".$showall_nav."</p>";
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

    comments_form($p->id(), 1, false, $p->closed_for_comments());
    print '</div>';
}

function draw_connections($p) {
    global $pb_today;
    $s = db_query("SELECT a_pledge_id, b_pledge_id, strength 
        FROM pledge_connection 
            LEFT JOIN pledges AS a_pledges ON a_pledge_id = a_pledges.id
            LEFT JOIN pledges AS b_pledges ON b_pledge_id = b_pledges.id
        WHERE
            (a_pledge_id = ? AND b_pledges.date >= '$pb_today' AND b_pledges.whensucceeded is null) or
            (b_pledge_id = ? AND a_pledges.date >= '$pb_today' AND a_pledges.whensucceeded is null)
        ORDER BY STRENGTH DESC 
        LIMIT 8", array($p->id(), $p->id()));
    if (0 == db_num_rows($s))
        return;

    print "\n\n" . '<div id="connections"><h2><a name="connections">' . 
        _('Suggested pledges') . ' </a></h2>'.
        p(_('Some of the people who signed this pledge also signed these pledges...')) . '<ul>' . "\n\n";
    while (list($a, $b, $strength) = db_fetch_row($s)) {
        $id = $a == $p->id() ? $b : $a;
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

locale_push($p->lang());
$title = "'" . _('I will') . ' ' . $p->h_title() . "'";
locale_pop();
$params = array(
            'ref'=>$p->ref(),
            'pref' => $p->url_typein(),
            'noreflink' => 1,
            'last-modified' => $p->last_change_time()
        );
if (microsites_comments_allowed() && !$p->pin())
    $params['rss'] = array(sprintf(_("Comments on Pledge '%s'"), $p->ref()) => $p->url_comments_rss());
    
page_header($title, $params);
debug_comment_timestamp("after page_header()");
draw_status_plaque($p);
debug_comment_timestamp("after draw_status_plaque()");
$p->render_box(array('showdetails' => true, 'reportlink' => true));
debug_comment_timestamp("after \$p->render_box()");
print '<div id="col2">';
if (!$p->finished()) { $p->sign_box(); } 
draw_spreadword($p);
debug_comment_timestamp("after draw_spreadword()");
if (microsites_comments_allowed())
    draw_comments($p);
print '</div>';
debug_comment_timestamp("after draw_comments()");
print '<div id="col1">';
draw_signatories($p);
debug_comment_timestamp("after draw_signatories()");
draw_connections($p);
debug_comment_timestamp("after draw_connections()");
print '</div>';

page_footer();
?>
