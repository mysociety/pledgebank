<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.264 2008-09-24 14:27:57 matthew Exp $

// Load configuration file
require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../phplib/pbperson.php';
require_once '../phplib/success.php';
require_once '../commonlib/phplib/utility.php';

$banner_src = 'howitworks.png';
if ($lang == 'zh' || $lang == 'eo' || $lang == 'fr' || $lang == 'sk')
    $banner_src = 'howitworks_' . $lang . '.png';

page_header(null, 
    array(
        'rss'=> array(
            _('New Pledges at PledgeBank.com') => pb_domain_url(array('explicit'=>true, 'path'=>'/rss/list')),
            _('Successful Pledges at PledgeBank.com') => pb_domain_url(array('explicit'=>true, 'path'=>'/rss/list/succeeded')),
            _('Comments on All Pledges PledgeBank.com') => pb_domain_url(array('explicit'=>true, 'path'=>'/rss/comments'))
        ), 
        'id' => 'front',
        #'cache-max-age' => 600,
        'banner' => $microsite ? '' : '<p id="banner"><img src="/i/' . $banner_src . '" alt="' . _('How PledgeBank works: PledgeBank is a free site to help people get things done - especially things that require several people. It is very simple - you make a pledge, set a target, find people to agree and sign the pledge, and succeed!') . '"></p>',
    )
);
debug_comment_timestamp("after page_header()");
$site = $microsite;
if (!$site) $site = 'website';
include_once "../templates/$site/index.php";
debug_comment_timestamp("after front_page()");
page_footer();

function format_pledge_list($pledges, $params) {
    $out = '<ul class="search_results">';
    if (!array_key_exists('firstperson', $params))
        $params['firstperson'] = 'includename';
    $c = 0;
    foreach ($pledges as $pledge)  {
        $out .= '<li';
        if (isset($params['swap'])) {
            $out .= ' class="';
            $out .= ($c++%2) ? 'even' : 'odd';
            $out .= '"';
        }
        $out .= '>';
        if (isset($params['iconpath'])) {
            $out .= '<div class="ms-pledge-list-icon" style="background-image:url(' . $params['iconpath'] . strtolower($pledge->ref()) . '.jpg);"></div>'; 
        }
        $out .= $pledge->new_summary($params) . '</li>';
    }
    $out .= '</ul>';
    return $out;
}

function list_frontpage_pledges() {
    global $pb_today;
    echo '<a href="', pb_domain_url(array('explicit'=>true, 'path'=>"/rss/list")),
        '"><img align="right" border="0" src="rss.gif" alt="', _('RSS feed of new pledges'), '"></a>';
    print '<h2 class="head_with_mast">';
    print _('Sign a pledge');
    print "</h2>";

    list($pledges, $more) = pledge_get_frontpage_list(3, 3);

    if (!$pledges) {
        pb_print_no_featured_link();
    } else {
        pb_print_filter_link_main_general('class="head_mast"');
        print format_pledge_list($pledges, array('showcountry'=>false));
    }

    if (count($pledges) < 3) {
        $foreign_more = 3 - count($pledges);
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
    echo '<a href="', pb_domain_url(array('explicit'=>true, 'path'=>"/rss/list/succeeded")),
        '"><img align="right" border="0" src="rss.gif" alt="', _('RSS feed of successful pledges'), '"></a>';
    print '<h2 class="head_with_mast">' . _('Recent successful pledges') . '</h2>';

    $num_to_show = 5;
    // Try to avoid global pledges
    $pledges = pledge_get_list("
                (".microsites_normal_prominences()." OR cached_prominence = 'frontpage') AND
                pin IS NULL AND 
                whensucceeded IS NOT NULL
                ORDER BY whensucceeded DESC
                LIMIT $num_to_show", array('global'=>false, 'main'=>true,'foreign'=>false));
    // Include global pledges if we need them
    if (count($pledges) < $num_to_show) {
        $pledges = pledge_get_list("
            (".microsites_normal_prominences()." OR cached_prominence = 'frontpage') AND
            pin IS NULL AND 
            whensucceeded IS NOT NULL
            ORDER BY whensucceeded DESC
            LIMIT $num_to_show", array('global'=>true, 'main'=>true,'foreign'=>false));
    }

    $more = false;
    if (!$pledges) {
        pb_print_no_featured_link();
    } else {
        if (count($pledges) == $num_to_show) {
            $more = true;
            array_pop($pledges);
        }
        pb_print_filter_link_main_general('class="head_mast"');
        print format_pledge_list($pledges, array('showcountry'=>false, 'swap'=>true));
    }

    if ($more) {
        $succeeded_url = pb_domain_url(array('path'=>'/list/succeeded'));
        print "<p id='success_more'><a href='$succeeded_url'>" .
            _('More successful pledges...') . '</a></p>';
    }
}

function list_closing_pledges() {
    $num_to_show = 3;

    // Try to avoid global pledges
    $pledges = pledge_get_list("
                (".microsites_normal_prominences()." OR cached_prominence = 'frontpage') AND
                pin IS NULL AND 
                whensucceeded IS NULL
                AND date = ms_current_date()
                ORDER BY RANDOM()
                LIMIT $num_to_show", array('global'=>false, 'main'=>true,'foreign'=>false));
    // Include global pledges if we need them
    if (count($pledges) < $num_to_show) {
        $pledges = pledge_get_list("
            (".microsites_normal_prominences()." OR cached_prominence = 'frontpage') AND
            pin IS NULL AND 
            whensucceeded IS NULL
            AND date = ms_current_date()
            ORDER BY RANDOM()
            LIMIT $num_to_show", array('global'=>true, 'main'=>true,'foreign'=>false));
    }

    $more = false;
    if ($pledges) {
        print '<h2 class="head_with_mast">' . _('Sign these or they fail today') . '</h2>';
        if (count($pledges) == $num_to_show) {
            $more = true;
            array_pop($pledges);
        }
        pb_print_filter_link_main_general('class="head_mast"');
        print format_pledge_list($pledges, array('showcountry'=>false));
    }

    if ($more) {
        $more_url = pb_domain_url(array('path'=>'/list?sort=date'));
        print p("<a href=\"$more_url\">"._('More pledges...')."</a>");
    }
}

