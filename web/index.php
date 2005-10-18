<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.218 2005-10-18 11:29:46 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/person.php';

page_header(null, array('rss'=> array('/rss', _('New Pledges at PledgeBank.com')), 
    'id'=>'front', 'gazejs'=>true));
front_page();
page_footer(array('nolocalsignup'=>true));

function front_page() {
    pb_view_local_alert_quick_signup("localsignupfrontpage");
?>
<div id="tellworld">
<h2><?=_('Tell the world &#8220;I&#8217;ll do it, but only if you&#8217;ll help me do it&#8221;') ?></h2>
<blockquote class="noindent"><a href="tom-on-pledgebank-vbr.mp3"><img src="tomsteinberg_small.jpg"
alt="" style="vertical-align: top; float:left; margin:0 0.5em 0 0; border: solid 2px #9C7BBD;
"></a>
<?=microsites_frontpage_intro() ?>
</blockquote>

<? global $lang; if ($lang == 'en-gb') { ?>
<p><a href="tom-on-pledgebank-vbr.mp3"><?=_('Listen to how PledgeBank
works</a>, as explained by mySociety\'s director Tom Steinberg.
Or <a href="/explain">read a full transcript') ?></a>.</p>
<? } else { ?>
<p><?=_('<a href="/explain">Find out how PledgeBank
works</a>, as explained by mySociety\'s director Tom Steinberg.')?></p>
<? } ?>
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
<div id="photo"><a href="/offline"><img src="leaflet-phone-scissors-275px.jpg" alt="<?=_('Scissors, a phone and some printouts &mdash ') ?>"></a></div>
<div id="photocaption"><a href="/offline"><?=_("Find out why these things are the secret of a successful pledge")?> &raquo;</a></div>
<? }?>

<?  comments_show_latest();
}

# params must have:
# 'global' - true or false, whether global pledges to be included
# 'sitecountry' - specifies which country pledges to include:
#                 true include only site country pledges, 
#                 false include only pledges from other countries (or all countries if no site country)
# 'showcountry' - whether to display country name in summary
function get_pledges_list($where, $params) {
    global $site_country, $pb_today;
    $query = "SELECT pledges.*, pledges.ref, pledges.date - '$pb_today' AS daysleft,
                     country
            FROM pledges LEFT JOIN location ON location.id = pledges.location_id
            WHERE ";
    $sql_params = array();
    
    $query .= '(';
    if ($params['sitecountry'])
        $query .= pb_site_pledge_filter_main($sql_params, true);
    else
        $query .= pb_site_pledge_filter_foreign($sql_params, false);
    if ($params['global'])
        $query .= ' OR ' . pb_site_pledge_filter_general($sql_params, false);
    $query .= ')';

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
?><a href="/rss"><img align="right" border="0" src="rss.gif" alt="<?=_('RSS feed of newest pledges') ?>"></a>
<h2><?=_('Why not sign a live pledge?') ?></h2><?
    $pledges = get_pledges_list("
                pb_pledge_prominence(pledges.id) = 'frontpage' AND
                date >= '$pb_today' AND 
                pin is NULL AND 
                whensucceeded IS NULL
                ORDER BY RANDOM()
                LIMIT 10", array('global'=>true,'sitecountry'=>true,'showcountry'=>false));
    if (count($pledges) < 3) {
        // If too few frontpage, show a few of the normal pledges for the country
        // (but not normal global ones)
        $normal_pledges = get_pledges_list("
                    pb_pledge_prominence(pledges.id) = 'normal' AND
                    date >= '$pb_today' AND 
                    pin is NULL AND 
                    whensucceeded IS NULL
                    ORDER BY RANDOM()
                    LIMIT 3", array('global'=>false,'sitecountry'=>true,'showcountry'=>false));
        $pledges = array_merge($pledges, $normal_pledges);
    }
    if (!$pledges) {
        pb_print_no_featured_link();
    } else {
        pb_print_filter_link_main_general();
        print '<ol>' . join("",$pledges) . '</ol>';
    }

    if (count($pledges) < 3) {
        $pledges = get_pledges_list("
                    pb_pledge_prominence(pledges.id) = 'frontpage' AND
                    date >= '$pb_today' AND 
                    pin is NULL AND 
                    whensucceeded IS NULL
                    ORDER BY RANDOM()
                    LIMIT 5", array('global'=>false,'sitecountry'=>false,'showcountry'=>true));
        if ($pledges) {
            print p(_("Interesting pledges from other countries"));
            print '<ol>' . join("",$pledges) . '</ol>';
        }
    }
}

function list_successful_pledges() {
    print h2(_('Recent successful pledges'));
    $pledges = get_pledges_list("
                pb_pledge_prominence(pledges.id) <> 'backpage' AND
                pin IS NULL AND 
                whensucceeded IS NOT NULL
                ORDER BY whensucceeded DESC
                LIMIT 10", array('global'=>true, 'sitecountry'=>true,'showcountry'=>false));
    if (!$pledges) {
        pb_print_no_featured_link();
    } else {
        pb_print_filter_link_main_general();
        print '<ol>'.join("",$pledges).'</ol>';
    }
}

?>
