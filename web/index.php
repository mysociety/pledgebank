<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.255 2007-07-11 11:11:37 francis Exp $

// Load configuration file
require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../phplib/pbperson.php';
require_once '../../phplib/utility.php';

page_header(null, 
            array('rss'=> array(
                    _('New Pledges at PledgeBank.com') => pb_domain_url(array('explicit'=>true, 'path'=>'/rss/list')),
                    _('Successful Pledges at PledgeBank.com') => pb_domain_url(array('explicit'=>true, 'path'=>'/rss/list/succeeded')),
                    _('Comments on All Pledges PledgeBank.com') => pb_domain_url(array('explicit'=>true, 'path'=>'/rss/comments'))
                    ), 
                'id'=>'front',
                'cache-max-age' => 600)
            );
debug_comment_timestamp("after page_header()");
front_page();
debug_comment_timestamp("after front_page()");
page_footer(array('nolocalsignup'=>true));

function front_page() {
    debug_comment_timestamp("in front_page()");
    if (microsites_frontpage_has_local_emails()) {
        pb_view_local_alert_quick_signup("localsignupfrontpage");
    }
    debug_comment_timestamp("after pb_view_local_alert_quick_signup()");

    if (microsites_frontpage_has_intro()) {
?>
<div id="tellworld">
<?
    microsites_frontpage_intro();
?>
</div>
<?  }
    debug_comment_timestamp("after microsites_frontpage_intro()");

    if (microsites_frontpage_has_start_your_own()) {
?>
<div id="startblurb">
<h2><?=_('Start your own pledge') ?></h2>
<p><?=_('PledgeBank is free and easy to use. Once you\'ve thought of something you\'d like
to do, just <a href="/new">create a pledge</a> which says "I\'ll do this, but
only if 5 other people will do the same".') ?>
<p id="start"><a href="./new"><?=_('Start your own pledge') ?>&nbsp;&raquo;</a></p>
</div>
<?
    }

    microsites_frontpage_extra_blurb();

?>
<div id="currentpledges">
<?
    list_frontpage_pledges();
    debug_comment_timestamp("after list_frontpage_pledges()");
    if (!microsites_no_target())
        list_successful_pledges();
    debug_comment_timestamp("after list_successful_pledges()");
?>
</div>

<? global $lang;
   if (microsites_frontpage_has_offline_secrets()) {
       if ($lang == 'en-gb') { ?>
<div id="photo"><a href="/offline"><img src="leaflet-phone-scissors-text-275px.jpg" alt="<?=_('How scissors, a phone and some printouts
can make your pledge succeed &raquo;') ?>"></a></div>
    <? } else { ?>
<div id="photo"><a href="/offline"><img src="leaflet-phone-scissors-275px.jpg" alt="<?=_('Scissors, a phone and some printouts &mdash; ') ?>"></a></div>
<div id="photocaption"><a href="/offline"><?=_("Find out why these things are the secret of a successful pledge")?> &raquo;</a></div>
    <? }?>
<?  }

    if (microsites_comments_allowed()) {
        comments_show_latest();
        debug_comment_timestamp("after comments_show_latest()");
    }


    microsites_frontpage_credit_footer();
}

function format_pledge_list($pledges, $params) {
    $out = '<ol>';
    foreach ($pledges as $pledge)  {
        $out .= '<li>';
        $out .= $pledge->summary(array('html'=>true, 'href'=>$pledge->ref(), 'showcountry'=>$params['showcountry']));
        
        $out .= '</li>';
    }
    $out .= '</ol>';
    return $out;
}

function list_frontpage_pledges() {
    global $pb_today;
?><a href="<?=pb_domain_url(array('explicit'=>true, 'path'=>"/rss/list"))?>"><img align="right" border="0" src="rss.gif" alt="<?=_('RSS feed of new pledges') ?>"></a>
<?=microsites_frontpage_sign_invitation_text()?><?

    list($pledges, $more) = pledge_get_frontpage_list(8, 6);

    if (!$pledges) {
        pb_print_no_featured_link();
    } else {
        pb_print_filter_link_main_general();
        print format_pledge_list($pledges, array('showcountry'=>false));
    }

    if (count($pledges) < 4) {
        $foreign_more = 4 - count($pledges);
        $pledges = pledge_get_list("
                    cached_prominence = 'frontpage' AND
                    date >= '$pb_today' AND 
                    pin is NULL AND 
                    whensucceeded IS NULL
                    ORDER BY RANDOM()
                    LIMIT $foreign_more", array('global'=>false,'main'=>false,'foreign'=>true));
        if ($pledges) {
            print p(_("Interesting pledges from other countries"));
            print format_pledge_list($pledges, array('showcountry'=>true));
        }
    } 
    if ($more) {
        $succeeded_url = pb_domain_url(array('path'=>'/list'));
        print p("<a href=\"$succeeded_url\">"._('More pledges to sign...')."</a>");
    }
}

function list_successful_pledges() {
?><a href="<?=pb_domain_url(array('explicit'=>true, 'path'=>"/rss/list/succeeded"))?>"><img align="right" border="0" src="rss.gif" alt="<?=_('RSS feed of successful pledges') ?>"></a><?
    print h2(_('Recent successful pledges'));

    // Try to avoid global pledges
    $pledges = pledge_get_list("
                (".microsites_normal_prominences()." OR cached_prominence = 'frontpage') AND
                pin IS NULL AND 
                whensucceeded IS NOT NULL
                ORDER BY whensucceeded DESC
                LIMIT 11", array('global'=>false, 'main'=>true,'foreign'=>false));
    // Include global pledges if we need them
    if (count($pledges) < 11) {
        $pledges = pledge_get_list("
            (".microsites_normal_prominences()." OR cached_prominence = 'frontpage') AND
            pin IS NULL AND 
            whensucceeded IS NOT NULL
            ORDER BY whensucceeded DESC
            LIMIT 11", array('global'=>true, 'main'=>true,'foreign'=>false));
    }

    $more = false;
    if (!$pledges) {
        pb_print_no_featured_link();
    } else {
        if (count($pledges) == 11) {
            $more = true;
            array_pop($pledges);
        }
        pb_print_filter_link_main_general();
        print format_pledge_list($pledges, array('showcountry'=>false));
    }

    if ($more) {
        $succeeded_url = pb_domain_url(array('path'=>'/list/succeeded'));
        print p("<a href=\"$succeeded_url\">"._('More successful pledges...')."</a>");
    }
}

?>
