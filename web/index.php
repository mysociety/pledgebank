<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.231 2006-02-22 21:23:35 francis Exp $

// Load configuration file
require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/person.php';

page_header(null, 
            array('rss'=> array(
                    _('New Pledges at PledgeBank.com') => pb_domain_url(array('explicit'=>true, 'path'=>'/rss/list')),
                    _('Successful Pledges at PledgeBank.com') => pb_domain_url(array('explicit'=>true, 'path'=>'/rss/list/succeeded'))
                    ), 
                'id'=>'front', 'gazejs'=>true)
            );
front_page();
page_footer(array('nolocalsignup'=>true));

function front_page() {
    pb_view_local_alert_quick_signup("localsignupfrontpage");
?>
<div id="tellworld">
<?microsites_frontpage_intro()?>
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

<? global $lang;
   if ($lang == 'en-gb') { ?>
<div id="photo"><a href="/offline"><img src="leaflet-phone-scissors-text-275px.jpg" alt="<?=_('How scissors, a phone and some printouts
can make your pledge succeed &raquo;') ?>"></a></div>
<? } else { ?>
<div id="photo"><a href="/offline"><img src="leaflet-phone-scissors-275px.jpg" alt="<?=_('Scissors, a phone and some printouts &mdash; ') ?>"></a></div>
<div id="photocaption"><a href="/offline"><?=_("Find out why these things are the secret of a successful pledge")?> &raquo;</a></div>
<? }?>

<?  comments_show_latest();
}

# params must have:
# 'global' - true or false, whether global pledges to be included
# 'main' - true or false, whether site country pledges to be included
# 'foreign' - true or false, whether pledges from other countries (or all countries if no site country) to be included
# 'showcountry' - whether to display country name in summary
function get_pledges_list($where, $params) {
    global $site_country, $pb_today;
    $query = "SELECT pledges.*, pledges.ref, pledges.date - '$pb_today' AS daysleft,
                     country
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
    $q = db_query($query, $sql_params);
    $pledges = array();
    while ($r = db_fetch_array($q)) {
        $r['signers'] = db_getOne('SELECT COUNT(*) FROM signers WHERE pledge_id = ?', array($r['id']));
        $pstring = '<li>';
        $pstring .= pledge_summary($r, array('html'=>true, 'href'=>$r['ref'], 'showcountry'=>$params['showcountry']));
        
        $pstring .= '</li>';
        $pledges[] = $pstring;
    }
    return $pledges;
}

function list_frontpage_pledges() {
    global $pb_today;
?><a href="<?=pb_domain_url(array('explicit'=>true, 'path'=>"/rss/list"))?>"><img align="right" border="0" src="rss.gif" alt="<?=_('RSS feed of new pledges') ?>"></a>
<h2><?=_('Why not sign a live pledge?') ?></h2><?
    $pledges_required_fp = 8; // number of pledges to show on main part of front page if frontpaged
    $pledges_required_n = 4; // number of pledges below which we show normal pledges, rather than just frontpaged ones
    $pledges = get_pledges_list("
                pb_pledge_prominence(pledges.id) = 'frontpage' AND
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
                    pb_pledge_prominence(pledges.id) = 'frontpage' AND
                    date >= '$pb_today' AND 
                    pin is NULL AND 
                    whensucceeded IS NULL
                    ORDER BY RANDOM()
                    LIMIT '$more'", array('global'=>true,'main'=>false,'foreign'=>false,'showcountry'=>false));
        $pledges = array_merge($pledges, $global_pledges);
        //print "<p>global frontpage: ".count($global_pledges);
    }
    if (count($pledges) < $pledges_required_n) {
        // If too few, show a few of the normal pledges for the country
        $more = $pledges_required_n - count($pledges);
        $normal_pledges = get_pledges_list("
                    pb_pledge_prominence(pledges.id) = 'normal' AND
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
                    pb_pledge_prominence(pledges.id) = 'normal' AND
                    date >= '$pb_today' AND 
                    pin is NULL AND 
                    whensucceeded IS NULL
                    ORDER BY RANDOM()
                    LIMIT '$more'", array('global'=>true,'main'=>false,'foreign'=>false,'showcountry'=>false));
        $pledges = array_merge($pledges, $global_normal_pledges);
        //print "<p>global normal: ".count($global_normal_pledges);
    }


    if (!$pledges) {
        pb_print_no_featured_link();
    } else {
        pb_print_filter_link_main_general();
        print '<ol>' . join("",$pledges) . '</ol>';
    }

    if (count($pledges) < 4) {
        $more = 4 - count($pledges);
        $pledges = get_pledges_list("
                    pb_pledge_prominence(pledges.id) = 'frontpage' AND
                    date >= '$pb_today' AND 
                    pin is NULL AND 
                    whensucceeded IS NULL
                    ORDER BY RANDOM()
                    LIMIT $more", array('global'=>false,'main'=>false,'foreign'=>true,'showcountry'=>true));
        if ($pledges) {
            print p(_("Interesting pledges from other countries"));
            print '<ol>' . join("",$pledges) . '</ol>';
        }
    }
}

function list_successful_pledges() {
?><a href="<?=pb_domain_url(array('explicit'=>true, 'path'=>"/rss/list/succeeded"))?>"><img align="right" border="0" src="rss.gif" alt="<?=_('RSS feed of successful pledges') ?>"></a><?
    print h2(_('Recent successful pledges'));

    $pledges = get_pledges_list("
                pb_pledge_prominence(pledges.id) <> 'backpage' AND
                pin IS NULL AND 
                whensucceeded IS NOT NULL
                ORDER BY whensucceeded DESC
                LIMIT 10", array('global'=>true, 'main'=>true,'foreign'=>false,'showcountry'=>false));
    if (!$pledges) {
        pb_print_no_featured_link();
    } else {
        pb_print_filter_link_main_general();
        print '<ol>'.join("",$pledges).'</ol>';
    }
}

?>
