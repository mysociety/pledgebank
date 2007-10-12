<?
// index.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.259 2007-10-12 15:01:23 matthew Exp $

// Load configuration file
require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../phplib/pbperson.php';
require_once '../phplib/success.php';
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
page_footer();

function front_page() {
    global $lang;

    if (microsites_frontpage_has_intro()) {
        echo '<div id="tellworld">';
        microsites_frontpage_intro();
        echo '</div>';
    }
    debug_comment_timestamp("after microsites_frontpage_intro()");

    if (microsites_frontpage_has_start_your_own()) {
        echo '<div id="startblurb">
<p id="start"><a href="./new"><strong>', _('Start your pledge'), '&nbsp;&raquo;</strong></a></p>
<ul>
<li>' . _('Get a unique page for your pledge')
. '<li>' . _('Find tools to help promote your pledge')
. '<li>' . _('Use positive peer pressure to change your community')
. '</ul>
</div>';
    }

    microsites_frontpage_extra_blurb();

    echo '<div id="currentpledges">';
    list_frontpage_pledges();
    debug_comment_timestamp("after list_frontpage_pledges()");
    echo '</div>';

    if (!microsites_no_target()) {
        echo '<div id="successfulpledges">';
        list_closing_pledges();
        list_successful_pledges();
        echo '</div>';
    }
    debug_comment_timestamp("after list_successful_pledges()");

    if (microsites_comments_allowed()) {
        comments_show_latest();
        debug_comment_timestamp("after comments_show_latest()");
    }

    microsites_frontpage_credit_footer();
}

function format_pledge_list($pledges, $params) {
    $out = '<ul class="search_results">';
    $params['firstperson'] = 'includename';
    foreach ($pledges as $pledge)  {
        $out .= '<li>' . $pledge->new_summary($params) . '</li>';
    }
    $out .= '</ul>';
    return $out;
}

function list_frontpage_pledges() {
    global $pb_today;
    echo '<a href="', pb_domain_url(array('explicit'=>true, 'path'=>"/rss/list")),
        '"><img align="right" border="0" src="rss.gif" alt="', _('RSS feed of new pledges'), '"></a>';
    echo microsites_frontpage_sign_invitation_text();

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
        print format_pledge_list($pledges, array('showcountry'=>false));
    }

    if ($more) {
        $succeeded_url = pb_domain_url(array('path'=>'/list/succeeded'));
        print p("<a href=\"$succeeded_url\">"._('More successful pledges...')."</a>");
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

