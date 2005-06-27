<?
// ref-index.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-index.php,v 1.33 2005-06-27 12:21:42 chris Exp $

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
    if (!$p->open()) {
        print '<p class="finished">' . _('This pledge is now closed, as its deadline has passed.') . '</p>';
    }
    if ($p->left() <= 0) {
        if ($p->exactly()) {
            print '<p class="finished">' . _('This pledge is now closed, as its target has been reached.') . '</p>';
        } else {
            print '<p class="success">' . _('This pledge has been successful!');
            if (!$p->finished()) {
                print '<br>' . _('<strong>You can still add your name to it</strong>, because the deadline hasn\'t been reached yet.');
            }
            print '</p>';
        }
    }
}

function draw_spreadword($p) {
    if (!$p->finished()) { ?>
    <div id="spreadword">
    <h2><?=_('Spread the word on and offline') ?></h2>
    <ul>
    <li> <? print_link_with_pin($p->url_email(), "", _("Email pledge to your friends")) ?></li>
<!--    <li> <? print_link_with_pin($p->url_ical(), "", _("Add deadline to your calendar")) ?> </li> -->
    <li> <? print_link_with_pin($p->url_flyers(), _("Stick them places!"), _("Print out customised flyers")) ?>
    <li> <a href="<?=$p->url_announce()?>" title="<?=_('Only if you made this pledge') ?>"><?=_('Send message to signers') ?></a> <?=_('(creator only)') ?>
    <li> <?
        print '<a href="' . $p->url_picture() . '" title="' . _('Only if you made this pledge') . '">';
        if ($p->has_picture()) {
            print _('Change the pledge picture');
        } else {
            print _('Add a picture to your pledge');
        }
        print '</a> ' . _('(creator only)'); ?>
    </li>
    </ul>
    <!--    <br clear="all"> -->
    </div>
    <?
    }
}

define('MAX_PAGE_SIGNERS', '500');

function draw_signatories($p) {
    $nsigners = db_getOne('select count(id) from signers where pledge_id = ?', $p->id());
    $offset = 0;
    if ($nsigners > MAX_PAGE_SIGNERS) {
        $offset = get_http_var('signers_offset');
        if (!preg_match('/^(0|[1-9]\d*)$/', $offset))
            $offset = MAX_PAGE_SIGNERS * (int)(($nsigners - 1) / MAX_PAGE_SIGNERS);
        else {
            $offset = MAX_PAGE_SIGNERS * (int)($offset / MAX_PAGE_SIGNERS);
            if ($offset > $nsigners - 1)
                $offset = MAX_PAGE_SIGNERS * (int)(($nsigners - 1) / MAX_PAGE_SIGNERS);
        }
    }
    ?>
    <div id="signatories">
<?
    print '<h2><a name="signers">' . _('Current signatories') . '</a></h2>';

    $npage = $nsigners - $offset > MAX_PAGE_SIGNERS ? MAX_PAGE_SIGNERS : $nsigners - $offset;

    /* XXX need to do something about layout here -- it breaks badly when the
     * height of the comments column is greater than that of the signers
     * column. */
    if ($nsigners > MAX_PAGE_SIGNERS) {
        print "<p>";
        if ($npage < MAX_PAGE_SIGNERS)
            printf(_("Because there are so many signers, only the most recent %d are shown on this page."), $npage);
        else
            printf("<p>" . _("Because there are so many signers, only %d are shown on this page"), $npage);
        print "</p>";

        print "<p>";
        if ($offset > 0)
            printf("<a href=\"/%s/?signers_offset=%d\">"
                    . htmlspecialchars(_("<<< Earlier signers"))
                    . "</a>",
                    htmlspecialchars($p->ref()),
                    $offset - MAX_PAGE_SIGNERS);
        print " ";
        if ($offset + MAX_PAGE_SIGNERS < $nsigners - 1)
            printf("<a href=\"/%s/?signers_offset=%d\">"
                    . htmlspecialchars(_("Later signers >>>"))
                    . "</a>",
                    htmlspecialchars($p->ref()),
                    $offset + MAX_PAGE_SIGNERS);
        print "</p>";
    }
   
    $out = '';
   
    if ($offset == 0)
        $out = '<li>' . $p->h_name() . ' ' . _('(Pledge Creator)') . '</li>';

    $anon = 0;
    $unknownname = 0;
    
    $q = db_query("SELECT * FROM signers WHERE pledge_id = ? ORDER BY id LIMIT " . MAX_PAGE_SIGNERS . " OFFSET $offset", $p->id());
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
    print '<ul>'.$out;
    if ($anon || $unknownname) {
        $extra = '';
        if ($anon)
            $extra .= sprintf(ngettext('Plus %d other who did not want to give their name', 'Plus %d others who did not want to give their names', $anon), $anon);
        if ($unknownname) {
            if ($anon) {
                $extra .= sprintf(ngettext(', and %d other who signed up via mobile.', ', and %d others who signed up via mobile.', $unknownname), $unknownname);
            } else {
                $extra .= sprintf(ngettext('Plus %d other who signed up via mobile.', 'Plus %d others who signed up via mobile', $unknownname), $unknownname);
            }
        }
        print "<li>$extra</li>";
    }
    print '</ul>';
    print '<p><a href="' . $p->url_info() . '">' . _('View signup rate graph') . '</a></p>';
    print '</div>';
}

function draw_comments($p) {
    print "\n\n" . '<div class="comments"><h2><a name="comments">' . _('Comments on this pledge') . '</a></h2>';
    comments_show($p->id()); 
    comments_form($p->id(), 1);
    print '</div>';
}

function draw_connections($p) {
    $s = db_query("SELECT a_pledge_id, b_pledge_id, strength 
        FROM pledge_connection 
            LEFT JOIN pledges AS a_pledges ON a_pledge_id = a_pledges.id
            LEFT JOIN pledges AS b_pledges ON b_pledge_id = b_pledges.id
        WHERE 
            (a_pledge_id = ? AND b_pledges.date >= pb_current_date()) or
            (b_pledge_id = ? AND a_pledges.date >= pb_current_date())
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

page_header("'I will " . $p->h_title() . "'", array('ref'=>$p->url_main(), 'noreflink'=>1) );
draw_status_plaque($p);
$p->render_box(array('showdetails' => true, 'reportlink' => true));
if (!$p->finished()) { pledge_sign_box(); } 
if (!$p->finished()) { draw_spreadword($p); }
draw_comments($p);
draw_signatories($p);
draw_connections($p);

page_footer();
?>

