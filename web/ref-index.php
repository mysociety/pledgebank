<?
// ref-index.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-index.php,v 1.50 2005-09-10 12:32:25 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../../phplib/utility.php';

page_check_ref(get_http_var('ref'));
$p  = new Pledge(get_http_var('ref'));

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
        print '<p id="finished">' . _('This pledge is now closed, as its deadline has passed.') . '</p>';
    }
    if ($p->left() <= 0) {
        if ($p->exactly()) {
            print '<p id="finished">' . _('This pledge is now closed, as its target has been reached.') . '</p>';
        } else {
            print '<p id="success">' . _('This pledge has been successful!');
            if (!$p->finished()) {
                print '<br>' . _('<strong>You can still add your name to it</strong>, because the deadline hasn\'t been reached yet.');
            }
            print '</p>';
        }
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
    if (!$p->finished()) { ?>
    <li> <? print_link_with_pin($p->url_email(), "", _("Email pledge to your friends")) ?></li>
<!--    <li> <? print_link_with_pin($p->url_ical(), "", _("Add deadline to your calendar")) ?> </li> -->
    <li> <? print_link_with_pin($p->url_flyers(), _("Stick them places!"), _("Print out customised flyers"));
    } ?>
    <li><a href="/new/local/<?=$p->ref() ?>">Create a local version of this pledge</a></li>
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

function draw_signatories($p) {
    $nsigners = db_getOne('select count(id) from signers where pledge_id = ?', $p->id());
    ?>
    <div id="signatories">
<?
    print '<h2><a name="signers">' . _('Current signatories') . '</a></h2>';

    if ($nsigners == 0) {

        print '<p>'
                . htmlspecialchars(sprintf(_('So far, only %s, the Pledge Creator, has signed this pledge.'), $p->creator_name()))
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

            $showall_nav = sprintf("<a href=\"/%s/?showall=1\">&gt;&gt; "
                    . htmlspecialchars(_("Show all signers"))
                    . "</a>",
                    htmlspecialchars($p->ref()));
            $limit = 1;

            print p($showall_para);
        }
    }
   
    $out = '';

    if (!$limit)
        $out = '<p>'
                . htmlspecialchars(sprintf(_('%s, the Pledge Creator, joined by:'), $p->creator_name()))
                . '</p>';

    $out .= "<ul>";
  
    $anon = 0;
    $unknownname = 0;

    $query = "SELECT * FROM signers WHERE pledge_id = ? ORDER BY id";
    if ($limit) {
        $query .= " LIMIT " . MAX_PAGE_SIGNERS . " OFFSET " . ($nsigners - MAX_PAGE_SIGNERS);
    }
    $q = db_query($query, $p->id());
    while ($r = db_fetch_array($q)) {
        $showname = ($r['showname'] == 't');
        if ($showname) {
            if (isset($r['name'])) {
                $out .= '<li>'
                        . htmlspecialchars($r['name'])
                        /*.' <small>(<a href="/abuse?what=signer&amp;id='
                            . htmlspecialchars($r['id'])
                        . '">dodgy? report it</a>)</small>' */
                        . '</li>';
            } else {
                ++$unknownname;
            }
        } else {
            $anon++;
        }
    }
    print $out;
    if ($anon || $unknownname) {
        $extra = '';
        if ($anon)
            $extra .= sprintf(ngettext('%d person who did not want to give their name', '%d people who did not want to give their names', $anon), $anon);
        if ($unknownname) {
            if ($anon) {
                /* XXX shouldn't assume we can split sentences like this --
                 * make it two bullet points? */
                $extra .= sprintf(ngettext(', and %d who signed up via mobile', ', and %d who signed up via mobile', $unknownname), $unknownname);
            } else {
                $extra .= sprintf(ngettext('%d person who signed up via mobile', '%d people who signed up via mobile', $unknownname), $unknownname);
            }
        }
        print "<li>$extra</li>";
    }
    print '</ul>';
    if ($showall_para) {
        print p($showall_nav);
        print p($showall_para);
    }
    print '<p>';
    print_link_with_pin($p->url_info(), "", _("View signup rate graph"));
    print '</div>';
}

define('MAX_PAGE_COMMENTS', '25');
function draw_comments($p) {
    print "\n\n" . '<div class="comments">';
    print '<a href="' . $p->url_comments_rss() . '"><img align="right" border="0" src="rss.gif" alt="' . _('RSS feed of comments on this pledge') . '"></a>';
    print '<h2><a name="comments">' . _('Comments on this pledge') . '</a></h2>';

    $limit = 0;
    $showall_para = '';
    $showall_nav = '';
    $ncomments = comments_count($p->id());
    if ($ncomments > MAX_PAGE_COMMENTS) {
        if (!get_http_var('showall')) {
            $showall_para = sprintf(_("Because there are so many comments, only the most recent %d are shown on this page."), MAX_PAGE_COMMENTS);

            $showall_nav = sprintf("<a href=\"/%s/?showall=1\">&gt;&gt; "
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

    comments_form($p->id(), 1);
    print '</div>';
}

function draw_connections($p) {
    global $pb_today;
    $s = db_query("SELECT a_pledge_id, b_pledge_id, strength 
        FROM pledge_connection 
            LEFT JOIN pledges AS a_pledges ON a_pledge_id = a_pledges.id
            LEFT JOIN pledges AS b_pledges ON b_pledge_id = b_pledges.id
        WHERE
            (a_pledge_id = ? AND b_pledges.date >= $pb_today AND b_pledges.whensucceeded is null) or
            (b_pledge_id = ? AND a_pledges.date >= $pb_today AND a_pledges.whensucceeded is null)
        ORDER BY STRENGTH DESC 
        LIMIT 8", array($p->id(), $p->id()));
    if (0 == db_num_rows($s))
        return;

    print "\n\n" . '<div id="connections"><h2><a name="connections">' . _('People who signed this pledge also pledged to...') . '</a></h2><ul>' . "\n\n";
    while (list($a, $b, $strength) = db_fetch_row($s)) {
        $id = $a == $p->id() ? $b : $a;
        $p2 = new Pledge(intval($id));
        print '<li><a href="/' . htmlspecialchars($p2->ref()) . '">' . $p2->h_title() . '</a></li>';
        print "<!-- strength $strength -->\n";
    }
    print "\n\n";
    print '</ul></div>';
}

page_header("'I will " . $p->h_title() . "'", array('ref'=>$p->url_main(), 'noreflink'=>1,
    'rss'=>array($p->url_comments_rss(), sprintf(_("Comments on Pledge '%s'"), $p->ref())) ));
draw_status_plaque($p);
$p->render_box(array('showdetails' => true, 'reportlink' => true));
if (!$p->finished()) { pledge_sign_box(); } 
draw_spreadword($p);
draw_comments($p);
draw_signatories($p);
draw_connections($p);

page_footer();
?>

