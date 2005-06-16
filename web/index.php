<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.189 2005-06-16 07:31:49 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../phplib/alert.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/utility.php';

page_header(null, array('rss'=>1, 'id'=>'front'));
front_page();
page_footer();

function front_page() {
    if (!local_alert_subscribed()) {
        $email = '';
        $P = person_if_signed_on();
        if (!is_null($P)) {
            $email = $P->email();
        } ?>
<form accept-charset="utf-8" id="localsignup" name="localalert" action="/alert" method="post">
<input type="hidden" name="subscribe_alert" value="1">
<p><strong>Get emails about local pledges &mdash;</strong>
<label for="email">Email:</label><input type="text" size="18" name="email" id="email" value="<?=htmlspecialchars($email) ?>">
<label for="postcode">UK Postcode:</label><input type="text" size="12" name="postcode" id="postcode" value="">

<input type="submit" name="submit" value="Subscribe"> </p>
</form>
<?  } ?>

<div id="tellworld">
<h2>Tell the world &#8220;I&#8217;ll do it, but only if you&#8217;ll help me do it&#8221;</h2>
<blockquote class="noindent"><a href="tom-on-pledgebank-vbr.mp3"><img src="tomsteinberg_small.jpg"
alt="" style="vertical-align: top; float:left; margin:0 0.5em 0 0; border: solid 2px #9C7BBD;
"></a>
"We all know what it is like to feel powerless, that our own actions
can't really change the things that we want to change.  PledgeBank is
about beating that feeling..."
</blockquote>
<p><a href="tom-on-pledgebank-vbr.mp3">Listen to how PledgeBank
works</a>, as explained by mySociety's director Tom Steinberg.
Or <a href="/explain">read a full transcript</a>.</p>
</div>

<div id="startblurb">
<h2>Start your own pledge</h2>
<p>Pledgebank is free and easy to use. Once you've thought of something you'd like
to do, just <a href="/new">create a pledge</a> which says "I'll do this, but
only if 5 other people will do the same".
<p id="start"><a href="./new">Start your own pledge&nbsp;&raquo;</a></p>
</div>

<div id="currentpledges">
<?    list_frontpage_pledges(); ?>
<?    list_successful_pledges(); ?>
</div>

<div id="photo"><a href="/offline"><img src="leaflet-phone-scissors-text-275px.jpg" alt="How scissors, a phone and some printouts
can make your pledge succeed &raquo;"></a></div>

<?  latest_comments();

    //list_newest_pledges();
}

function get_pledges_list($query) {
    $q = db_query($query);
    $pledges = '';
    while ($r = db_fetch_array($q)) {
        $r['signers'] = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
        $pledges .= '<li>';
        $pledges .= pledge_summary($r, array('html'=>true, 'href'=>$r['ref']));
        
        $pledges .= '</li>';
    }
    return $pledges;
}

function list_newest_pledges() {
?><h2>Sign up to one of our five newest pledges</h2><?

    $pledges = get_pledges_list("
                SELECT *, date - pb_current_date() AS daysleft
                FROM pledges
                WHERE date >= pb_current_date() AND 
                pin is NULL AND confirmed
                ORDER BY id DESC
                DESC LIMIT 5");
    if (!$pledges) {
        print '<p>There are no new pledges at the moment.</p>';
    } else {
        print '<ol>'.$pledges.'</ol>';
    }
}

function list_frontpage_pledges() {
?><a href="/rss"><img align="right" border="0" src="rss.gif" alt="RSS feed of newest pledges"></a>
<h2>Why not sign a live pledge?</h2><?

    $pledges = get_pledges_list("
                SELECT *, date - pb_current_date() AS daysleft
                FROM pledges
                WHERE 
                pb_pledge_prominence(id) = 'frontpage' AND
                date >= pb_current_date() AND 
                pin is NULL AND 
                confirmed AND
                whensucceeded IS NULL
                ORDER BY RANDOM()");
    if (!$pledges) {
        print '<p>There are no featured pledges at the moment.</p>';
    } else {
        print '<ol>'.$pledges.'</ol>';
    }
}

function list_successful_pledges() {
?><h2>Recent successful pledges</h2><?
    $pledges = get_pledges_list("
                SELECT *, date - pb_current_date() AS daysleft
                FROM pledges
                WHERE 
                pb_pledge_prominence(id) <> 'backpage' AND
                pin IS NULL AND 
                confirmed AND
                whensucceeded IS NOT NULL
                ORDER BY whensucceeded DESC
                LIMIT 10");
    if (!$pledges) {
        print '<p>There are no featured pledges at the moment.</p>';
    } else {
        print '<ol>'.$pledges.'</ol>';
    }
}


?>
