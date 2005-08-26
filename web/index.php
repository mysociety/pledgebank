<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.201 2005-08-26 17:19:08 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../phplib/alert.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/person.php';
require_once '../../phplib/gaze.php';

page_header(null, array('rss'=>1, 'id'=>'front', 'gazejs'=>true));
front_page();
page_footer();

function front_page() {
    $email = '';
    $P = person_if_signed_on();
    if (!is_null($P)) {
        $email = $P->email();
    } 
?>
<form accept-charset="utf-8" id="localsignup" name="localalert" action="/alert" method="post">
<input type="hidden" name="subscribe_local_alert" value="1">
<input type="hidden" name="from_frontpage" value="1">
<p><strong><?=_('Sign up for emails about new pledges where you live!') ?> </strong>
<br><label for="email"><?=_('Email:') ?></label><input type="text" size="18" name="email" id="email" value="<?=htmlspecialchars($email) ?>">
<?=_('Country:') ?><? pb_view_gaze_country_choice(null, null, array(), array('noglobal' => true, 'gazeonly' => true)); ?>
<label for="place"><span id="place_postcode_label"><?=_('Town:')?></span></label> <input type="text" size="12" name="place" id="place" value="">
<input type="submit" name="submit" value="<?=_('Subscribe') ?>"> </p>
</form>

<div id="tellworld">
<h2><?=_('Tell the world &#8220;I&#8217;ll do it, but only if you&#8217;ll help me do it&#8221;') ?></h2>
<blockquote class="noindent"><a href="tom-on-pledgebank-vbr.mp3"><img src="tomsteinberg_small.jpg"
alt="" style="vertical-align: top; float:left; margin:0 0.5em 0 0; border: solid 2px #9C7BBD;
"></a>
<?=_('"We all know what it is like to feel powerless, that our own actions
can\'t really change the things that we want to change.  PledgeBank is
about beating that feeling..."') ?>
</blockquote>
<p><a href="tom-on-pledgebank-vbr.mp3"><?=_('Listen to how PledgeBank
works</a>, as explained by mySociety\'s director Tom Steinberg.
Or <a href="/explain">read a full transcript') ?></a>.</p>
</div>

<div id="startblurb">
<h2><?=_('Start your own pledge') ?></h2>
<p><?=_('Pledgebank is free and easy to use. Once you\'ve thought of something you\'d like
to do, just <a href="/new">create a pledge</a> which says "I\'ll do this, but
only if 5 other people will do the same".') ?>
<p id="start"><a href="./new"><?=_('Start your own pledge') ?>&nbsp;&raquo;</a></p>
</div>

<div id="currentpledges">
<?    list_frontpage_pledges(); ?>
<?    list_successful_pledges(); ?>
</div>

<div id="photo"><a href="/offline"><img src="leaflet-phone-scissors-text-275px.jpg" alt="<?=_('How scissors, a phone and some printouts
can make your pledge succeed &raquo;') ?>"></a></div>

<?  comments_show_latest();

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
    print h2(_('Sign up to one of our five newest pledges'));

    $pledges = get_pledges_list("
                SELECT *, date - pb_current_date() AS daysleft
                FROM pledges
                WHERE date >= pb_current_date() AND 
                pin is NULL 
                ORDER BY id DESC
                DESC LIMIT 5");
    if (!$pledges) {
        print '<p>' . _('There are no new pledges at the moment.') . '</p>';
    } else {
        print '<ol>'.$pledges.'</ol>';
    }
}

function list_frontpage_pledges() {
?><a href="/rss"><img align="right" border="0" src="rss.gif" alt="<?=_('RSS feed of newest pledges') ?>"></a>
<h2><?=_('Why not sign a live pledge?') ?></h2><?

    $pledges = get_pledges_list("
                SELECT *, date - pb_current_date() AS daysleft
                FROM pledges
                WHERE 
                pb_pledge_prominence(id) = 'frontpage' AND
                date >= pb_current_date() AND 
                pin is NULL AND 
                whensucceeded IS NULL
                ORDER BY RANDOM()");
    if (!$pledges) {
        print '<p>' . _('There are no featured pledges at the moment.') . '</p>';
    } else {
        print '<ol>' . $pledges . '</ol>';
    }
}

function list_successful_pledges() {
    print h2(_('Recent successful pledges'));
    $pledges = get_pledges_list("
                SELECT *, date - pb_current_date() AS daysleft
                FROM pledges
                WHERE 
                pb_pledge_prominence(id) <> 'backpage' AND
                pin IS NULL AND 
                whensucceeded IS NOT NULL
                ORDER BY whensucceeded DESC
                LIMIT 10");
    if (!$pledges) {
        print '<p>' . _('There are no featured pledges at the moment.') . '</p>';
    } else {
        print '<ol>'.$pledges.'</ol>';
    }
}

?>
