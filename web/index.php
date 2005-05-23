<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.165 2005-05-23 11:00:06 sandpit Exp $

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
<p id="tomtalks">
<a href="tom-on-pledgebank-vbr.mp3"><img src="tomsteinberg_small.jpg" alt=""></a>
<br>Tom Steinberg explains what all this pledge stuff can do for you.
<a href="tom-on-pledgebank-vbr.mp3">
(listen to MP3)</a></p>

<h2>I'll do it if other people will too</h2>

<p>Welcome to PledgeBank, the site that helps you get things done that
you couldn't do on your own.</p>

<!-- Attempt at transcript (Tom speaks quicker than I type)
<p>I'm Tom Steinberg the directory of mysociety the charitable group
which is building 

we all know what it is like to feel powerless, that our own actions

booting that feeling by connecting you with other people who also
want to make the change, but don't want to take the risk of being
the only person who wanted to turn up to the meeting or

I'll dos omething but only if other people will pledge to do the same
thing.

Street party, but only if three people in my street will help me to
run it

After hours sports club but only if 5 other
I'll hold a gig but only if 40 people will come along

Real world testing for a while already, and there are some successful
completed pledges outside
20 other fans from a BBC radio series to lobby for its release on CD
8 people who he'd never met to
5th birthday party.
-->

<p>PledgeBank works by letting people set up pledges like "I'll organise
a residents' association, but only if 5 people on my street pledge to
come to my house to talk about it". We've only just entered our
testing phase; feel free to <a href="/new">set up your own pledges</a>,
but if you want to publicise them widely, please
<a href="mailto:team@pledgebank.com">contact us</a> first.</p>

<p id="start"><a href="./new"><strong>Start your own pledge &raquo;</strong></a></p>

<form accept-charset="utf-8" id="search" action="/search" method="get">
<h2>Search</h2>
<p><label for="s">Enter a PledgeBank Reference, or a search string:</label>
<input type="text" id="s" name="q" size="10" value=""></p>
<p style="margin-top: 0.5em; text-align: right"><input type="submit" value="Go"></p>
</form>
<?
    //list_newest_pledges();
    //list_highest_signup_pledges();
    list_frontpage_pledges();
    list_successful_pledges();
}

function list_newest_pledges() {
?>
<h2>Sign up to one of our five newest pledges</h2><?

    $q = db_query("
                SELECT *, date - pb_current_date() AS daysleft
                FROM pledges
                WHERE date >= pb_current_date() AND 
                password is NULL AND confirmed
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
                AND pledges.password is NULL
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
<h2>Some current pledges</h2><?

    $q = db_query("
                SELECT *, date - pb_current_date() AS daysleft
                FROM pledges
                WHERE 
                prominence = 'frontpage' AND
                date >= pb_current_date() AND 
                password is NULL AND confirmed
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
                password IS NULL AND 
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
