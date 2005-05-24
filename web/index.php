<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.168 2005-05-24 23:18:40 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/utility.php';

page_header(null, array('rss'=>1));
front_page();
page_footer();

function front_page() {
?>

<h2>Tell the world "I'll do it, but only if you'll help me do it"</h2>

<p><a href="tom-on-pledgebank-vbr.mp3"><img src="tomsteinberg_small.jpg"
alt="" style="vertical-align: top; float:left; margin:0 0.5em 0 0"></a>
"We all know what it is like to feel powerless, that our own actions
can't really change the things that we want to change.  PledgeBank is
about beating that feeling..."
</p>

<p><a href="tom-on-pledgebank-vbr.mp3">Listen to how PledgeBank
works</a>, as explained by mySociety's director Tom Steinberg.
Or <a href="/explain">read a full transcript</a>.</p>

<p id="start"><a href="./new"><strong>Start your own pledge &raquo;</strong></a></p>

<form accept-charset="utf-8" id="search" action="/search" method="get">
<h2>Search</h2>
<p><label for="s">Enter a PledgeBank Reference, or a search string:</label>
<input type="text" id="s" name="q" size="10" value=""></p>
<p style="margin-top: 0.5em; text-align: right"><input type="submit" value="Go"></p>
</form>

<?    list_frontpage_pledges(); ?>

<h2>Start your own pledge</h2>

<p>The way it works is simple. You <a href="/new">create a pledge</a>
which has the basic format 'I'll do something, but only if other people will
pledge to do the same thing'.  There are some examples of successful
pledges below, but you can go wild with your own ideas!

<?    list_successful_pledges(); ?>

<?
    //list_newest_pledges();
    //list_highest_signup_pledges();
}

function list_newest_pledges() {
?>
<h2>Sign up to one of our five newest pledges</h2><?

    $q = db_query("
                SELECT *, date - pb_current_date() AS daysleft
                FROM pledges
                WHERE date >= pb_current_date() AND 
                pin is NULL AND confirmed
                ORDER BY id
                DESC LIMIT 5");
    $new = '';
    while ($r = db_fetch_array($q)) {
        $signatures = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
        $new .= '<li>' . pledge_sentence($r, array('html'=>true, 'href'=>$r['ref'])) . ' ';
        if ($r['target'] - $signatures <= 0) {
            $new .= 'Target met, pledge still open for ' . $r['daysleft'] . ' ' . make_plural($r['daysleft'], 'day', 'days');
        } else {
            $new .= "(${r['daysleft']} "
                        . make_plural($r['daysleft'], 'day', 'days') /* XXX i18n */
                        . " left), "
                    . prettify($r['target'] - $signatures)
                    . " more needed";
        }
        $new .= '</li>';
    }
    if (!$new) {
        print '<p>There are no new pledges at the moment.</p>';
    } else {
        print '<ol>'.$new.'</ol>';
    }
}

function list_highest_signup_pledges() {
?>
<h2>&hellip; or sign a pledge with many signatures</h2><?

    $q = db_query("
            SELECT pledges.id, pledges.name, pledges.title, pledges.signup,
                pledges.date, pledges.target, pledges.type, pledges.ref,
                pledges.comparison, COUNT(signers.id) AS count,
                max(date) - pb_current_date() AS daysleft,
                pledges.identity
            FROM pledges, signers
            WHERE pledges.id = signers.pledge_id
                AND pledges.date >= pb_current_date() AND pledges.confirmed
                AND pledges.pin is NULL
            GROUP BY pledges.id, pledges.name, pledges.title, pledges.date,
                pledges.target, pledges.type, pledges.signup, pledges.ref,
                pledges.comparison, pledges.identity
            ORDER BY count DESC
            limit 5");
    $new = '';
    while ($r = db_fetch_array($q)) {
        $signatures = $r['count'];
        $new .= '<li>' . pledge_sentence($r, array('html'=>true, 'href'=>$r['ref'])) . ' ';
        if ($r['target'] - $signatures <= 0) {
            $new .= 'Target met, pledge still open for ' . $r['daysleft'] . ' ' . make_plural($r['daysleft'], 'day', 'days');
        } else {
            $new .= "(${r['daysleft']} "
                        . make_plural($r['daysleft'], 'day', 'days') /* XXX i18n */
                        . " left), "
                    . prettify($r['target'] - $signatures)
                    . " more needed";
        }
        $new .= '</li>';
    }
    if (!$new) {
        print '<p>There are currently no active pledges.</p>';
    } else {
        print '<ol>'.$new.'</ol>';
    }

}

function list_frontpage_pledges() {
?>
<h2>Sign some current pledges</h2><?

    $q = db_query("
                SELECT *, date - pb_current_date() AS daysleft
                FROM pledges
                WHERE 
                prominence = 'frontpage' AND
                date >= pb_current_date() AND 
                pin is NULL AND confirmed
                ORDER BY id");
    $pledges = '';
    while ($r = db_fetch_array($q)) {
        $signatures = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
        $pledges .= '<li>' . pledge_sentence($r, array('html'=>true, 'href'=>$r['ref'])) . ' ';
        if ($r['target'] - $signatures <= 0) {
            $pledges .= 'Target met, pledge still open for ' . $r['daysleft'] . ' ' . make_plural($r['daysleft'], 'day', 'days');
        } else {
            $pledges .= "(${r['daysleft']} "
                        . make_plural($r['daysleft'], 'day', 'days') /* XXX i18n */
                        . " left), "
                    . prettify($r['target'] - $signatures)
                    . " more needed";
        }
        $pledges .= '</li>';
    }
    if (!$pledges) {
        print '<p>There are no featured pledges at the moment.</p>';
    } else {
        print '<ol>'.$pledges.'</ol>';
    }
}

function list_successful_pledges() {
?>
<h2>Recent successful pledges</h2><?

    $q = db_query("
                SELECT *, date - pb_current_date() AS daysleft
                FROM pledges
                WHERE 
                prominence != 'backpage' AND
                date >= pb_current_date() AND 
                pin IS NULL AND 
                confirmed AND
                whensucceeded IS NOT NULL
                ORDER BY whensucceeded DESC");
    $pledges = '';
    while ($r = db_fetch_array($q)) {
        $signatures = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
        $pledges .= '<li>' . pledge_sentence($r, array('html'=>true, 'href'=>$r['ref'])) . ' ';
        if ($r['target'] - $signatures <= 0) {
            $pledges .= 'Target met, pledge still open for ' . $r['daysleft'] . ' ' . make_plural($r['daysleft'], 'day', 'days');
        } else {
            $pledges .= "(${r['daysleft']} "
                        . make_plural($r['daysleft'], 'day', 'days') /* XXX i18n */
                        . " left), "
                    . prettify($r['target'] - $signatures)
                    . " more needed";
        }
        $pledges .= '</li>';
    }
    if (!$pledges) {
        print '<p>There are no featured pledges at the moment.</p>';
    } else {
        print '<ol>'.$pledges.'</ol>';
    }
}


?>
