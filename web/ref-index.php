<?
// ref-index.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-index.php,v 1.24 2005-06-13 17:03:50 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../../phplib/utility.php';

page_check_ref(get_http_var('ref'));
$p  = new Pledge(get_http_var('ref'));

$pin_box = deal_with_pin($p->url_main(), $p->ref(), $p->pin());
if ($pin_box) {
    page_header("Enter PIN"); 
    print $pin_box;
    page_footer();
    exit;
}

function draw_status_plaque($p) {
    if (!$p->open()) {
        print '<p class="finished">This pledge is now closed, as its deadline has passed.</p>';
    }
    if ($p->left() <= 0) {
        if ($p->exactly()) {
            print '<p class="finished">This pledge is now closed, as its target has been reached.</p>';
        } else {
            print '<p class="success">This pledge has been successful!';
            if (!$p->finished()) {
                print '<br><strong>You can still add your name to it</strong>, because the deadline hasn\'t been reached yet.';
            }
            print '</p>';
        }
    }
}

function draw_spreadword($p) {
    if (!$p->finished()) { ?>
    <div id="spreadword">
    <h2>Spread the word on and offline</h2>
    <ul>
    <li> <? print_link_with_pin($p->url_email(), "", "Email pledge to your friends") ?></li>
<!--    <li> <? print_link_with_pin($p->url_ical(), "", "Add deadline to your calendar") ?> </li> -->
    <li> <? print_link_with_pin($p->url_flyers(), "Stick them places!", "Print out customised flyers") ?>
    <li> <a href="<?=$p->url_announce()?>" title="Only if you made this pledge">Send message to signers</a> (author only)
    <li> <a href="<?=$p->url_picture()?>" title="Only if you made this pledge"><? if ($p->has_picture()) { ?>Change the pledge picture<? } else { ?>Add a picture to your pledge<? } ?></a> (author only)
    </li>
    </ul>
    <!--    <br clear="all"> -->
    </div>
    <?
    }
}

function draw_signatories($p) {
    ?>
    <div id="signatories">
    <h2><a name="signers">Current signatories</a></h2><?

    $out = '<li>' . $p->h_name() . ' (Pledge Author)</li>';
    $anon = 0;
    $unknownname = 0;
    $q = db_query('SELECT * FROM signers WHERE pledge_id=? ORDER BY id', array($p->id()));
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
        /* XXX i18n-a-go-go */
        $extra = '';
        if ($anon)
            $extra .= "Plus $anon "
                        . make_plural($anon, 'other')
                        . ' who did not want to give their '
                        . make_plural($anon, 'name');
        if ($unknownname)
            $extra .= ($anon ? ', and' : 'Plus')
                        . " $unknownname "
                        . make_plural($unknownname, 'other')
                        . ' whose '
                        . make_plural($unknownname, 'name')
                        . " we don't know.";
        print "<li>$extra</li>";
    }
    print '</ul>';
    print '<p><a href="' . $p->url_info() . '">View signup rate graph</a></p>';
    print '</div>';
}

function draw_comments($p) {
    print "\n\n" . '<div id="comments"><h2><a name="comments">Comments on this pledge</a></h2>';
    comments_show($p->id()); 
    comments_form($p->id(), 1);
    print '</div>';
}

function draw_connections($p) {
    $s = db_query('SELECT a_pledge_id, b_pledge_id, strength 
        FROM pledge_connection 
            LEFT JOIN pledges AS a_pledges ON a_pledge_id = a_pledges.id
            LEFT JOIN pledges AS b_pledges ON b_pledge_id = b_pledges.id
        WHERE 
            (a_pledge_id = ? AND b_pledges.date >= pb_current_date()) OR
            (b_pledge_id = ? AND a_pledges.date >= pb_current_date())
        ORDER BY STRENGTH DESC 
        LIMIT 6', array($p->id(), $p->id()));
    if (0 == db_num_rows($s))
        return;

    print "\n\n" . '<div id="connections"><h2><a name="connections">People who signed this pledge also pledged to...</a></h2><ul>' . "\n\n";
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

