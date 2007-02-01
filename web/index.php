<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.251 2007-02-01 16:29:07 matthew Exp $

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

# params must have:
# 'global' - true or false, whether global pledges to be included
# 'main' - true or false, whether site country pledges to be included
# 'foreign' - true or false, whether pledges from other countries (or all countries if no site country) to be included
# 'showcountry' - whether to display country name in summary
function get_pledges_list($where, $params) {
    $query = "SELECT pledges.*, pledges.ref, country
            FROM pledges LEFT JOIN location ON location.id = pledges.location_id
            WHERE ";
    $sql_params = array();
    
    $queries = array();
    if ($params['main'])
        $queries[] = pb_site_pledge_filter_main($sql_params);
    if ($params['foreign'])
        $queries[] = pb_site_pledge_filter_foreign($sql_params);
    if ($params['global'])
        $queries[] = pb_site_pledge_filter_general($sql_params);
    $query .= '(' . join(" OR ", $queries) . ')';

    $query .= " AND " . $where;
#print "<p>query: $query</p>"; print_r($sql_params);
    $q = db_query($query, $sql_params);
    $pledges = array();
    while ($r = db_fetch_array($q)) {
        $r['signers'] = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
        $pledge = new Pledge($r);
        $pstring = '<li>';
        $pstring .= $pledge->summary(array('html'=>true, 'href'=>$r['ref'], 'showcountry'=>$params['showcountry']));
        
        $pstring .= '</li>';
        $pledges[] = $pstring;
    }
    return $pledges;
}

function list_frontpage_pledges() {
    global $pb_today;
?><a href="<?=pb_domain_url(array('explicit'=>true, 'path'=>"/rss/list"))?>"><img align="right" border="0" src="rss.gif" alt="<?=_('RSS feed of new pledges') ?>"></a>
<?=microsites_frontpage_sign_invitation_text()?><?
    $pledges_required_fp = 8 + 1; // number of pledges to show on main part of front page if frontpaged
    $pledges_required_n = 6 + 1; // number of pledges below which we show normal pledges, rather than just frontpaged ones
    $more_threshold = $pledges_required_fp;
    $pledges = get_pledges_list("
                cached_prominence = 'frontpage' AND
                date >= '$pb_today' AND 
                pin is NULL AND 
                whensucceeded IS NULL
                ORDER BY RANDOM()
                LIMIT $pledges_required_fp", array('global'=>false,'main'=>true,'foreign'=>false,'showcountry'=>false));
    //print "<p>main frontpage: ".count($pledges);
    if (count($pledges) < $pledges_required_fp) {
        // If too few, show some global frontpage pledges
        $more =$pledges_required_fp - count($pledges);
        $global_pledges = get_pledges_list("
                    cached_prominence = 'frontpage' AND
                    date >= '$pb_today' AND 
                    pin is NULL AND 
                    whensucceeded IS NULL
                    ORDER BY RANDOM()
                    LIMIT '$more'", array('global'=>true,'main'=>false,'foreign'=>false,'showcountry'=>false));
        $pledges = array_merge($pledges, $global_pledges);
        //print "<p>global frontpage: ".count($global_pledges);
    }
    if (count($pledges) <= $pledges_required_n) 
        $more_threshold = $pledges_required_n;
    
    if (count($pledges) < $pledges_required_n) {
        // If too few, show a few of the normal pledges for the country
        $more = $pledges_required_n - count($pledges);
        $normal_pledges = get_pledges_list("
                    ".microsites_normal_prominences()." AND
                    date >= '$pb_today' AND 
                    pin is NULL AND 
                    whensucceeded IS NULL
                    ORDER BY RANDOM()
                    LIMIT $more", array('global'=>false,'main'=>true,'foreign'=>false,'showcountry'=>false));
        $pledges = array_merge($pledges, $normal_pledges);
        //print "<p>main normal: ".count($normal_pledges);
    }
    if (count($pledges) < $pledges_required_n) {
        // If too few, show some global normal pledges
        $more =$pledges_required_n - count($pledges);
        $global_normal_pledges = get_pledges_list("
                    ".microsites_normal_prominences()." AND
                    date >= '$pb_today' AND 
                    pin is NULL AND 
                    whensucceeded IS NULL
                    ORDER BY RANDOM()
                    LIMIT '$more'", array('global'=>true,'main'=>false,'foreign'=>false,'showcountry'=>false));
        $pledges = array_merge($pledges, $global_normal_pledges);
        //print "<p>global normal: ".count($global_normal_pledges);
    }

    $more = false;
    if (count($pledges) == $more_threshold) {
        $more = true;
        array_pop($pledges);
    }

    if (!$pledges) {
        pb_print_no_featured_link();
    } else {
        pb_print_filter_link_main_general();
        print '<ol>' . join("",$pledges) . '</ol>';
    }

    if (count($pledges) < 4) {
        $foriegn_more = 4 - count($pledges);
        $pledges = get_pledges_list("
                    cached_prominence = 'frontpage' AND
                    date >= '$pb_today' AND 
                    pin is NULL AND 
                    whensucceeded IS NULL
                    ORDER BY RANDOM()
                    LIMIT $foriegn_more", array('global'=>false,'main'=>false,'foreign'=>true,'showcountry'=>true));
        if ($pledges) {
            print p(_("Interesting pledges from other countries"));
            print '<ol>' . join("",$pledges) . '</ol>';
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

    $pledges = get_pledges_list("
                (".microsites_normal_prominences()." OR cached_prominence = 'frontpage') AND
                pin IS NULL AND 
                whensucceeded IS NOT NULL
                ORDER BY whensucceeded DESC
                LIMIT 11", array('global'=>true, 'main'=>true,'foreign'=>false,'showcountry'=>false));
    $more = false;
    if (!$pledges) {
        pb_print_no_featured_link();
    } else {
        if (count($pledges) == 11) {
            $more = true;
            array_pop($pledges);
        }
        pb_print_filter_link_main_general();
        print '<ol>'.join("",$pledges).'</ol>';
    }

    if ($more) {
        $succeeded_url = pb_domain_url(array('path'=>'/list/succeeded'));
        print p("<a href=\"$succeeded_url\">"._('More successful pledges...')."</a>");
    }
}

?>
