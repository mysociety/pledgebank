<?
// ref-index.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-index.php,v 1.13 2005-06-06 18:28:53 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../../phplib/utility.php';

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
    ?>
    <div id="spreadword">
    <?    if (!$p->finished()) { ?>
    <h2>Spread the word</h2>
    <ul id="spread">
    <li> <? print_link_with_pin($p->url_email(), "", "Email pledge to your friends") ?></li>
    <li> <? print_link_with_pin($p->url_ical(), "", "Add deadline to your calendar") ?> </li>
    <li> <? print_link_with_pin($p->url_flyers(), "Stick them places!", "Print out customised flyers") ?>
    <li> <a href="<?=$p->url_announce()?>" title="Only if you made this pledge">Send message to signers</a> (author only)
    <li> <a href="<?=$p->url_addpicture()?>" title="Only if you made this pledge">Add a picture to your pledge</a> (author only)
    </li>
    </ul>
    <br clear="all">
    <?
    } ?>
    </div>
    <?
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
                        .' <small>(<a href="/abuse?what=signer&amp;id='
                            . htmlspecialchars($r['id'])
                        . '">suspicious signer?</a>)</small>'
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
    print '</div>';
}

function draw_comments($p) {
    print "\n\n" . '<div id="comments"><h2><a name="comments">Comments on this pledge</a></h2>';
    comments_show($p->id()); 
    comments_form($p->id(), 1);
    print '</div>';
}

page_header("'I will " . $p->h_title() . "'", array('ref'=>$p->url_main(), 'noreflink'=>1) );
draw_status_plaque($p);
$p->render_box(array('showdetails' => true));
if (!$p->finished()) { pledge_sign_box(); }
draw_spreadword($p);
draw_signatories($p);
draw_comments($p);

page_footer();
?>

