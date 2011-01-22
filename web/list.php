<?
// list.php:
// List all pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: list.php,v 1.51 2008-01-02 19:08:22 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

require_once '../commonlib/phplib/importparams.php';

define('PAGE_SIZE', 50);

$err = importparams(
            array('offset', '/^(0|[1-9]\d*)$/', '', 0),
            array('sort', '/^(' . join('|', array_keys(microsites_list_sort_options())) . ')\/?$/', '', 'default'),
            array('type', '/^[a-z_]*$/', '', 'all')
        );
if ($err) {
    err(_('Illegal offset or sort parameter passed'));
}
$viewsarray = microsites_list_views();
$viewsarray_keys = array_keys($viewsarray);
if ($q_type == 'all') $q_type = $viewsarray_keys[0];

$rss = get_http_var('rss') ? true : false;

// Old postcode RSS feed
if ($rss && get_http_var('postcode')) {
    header("Location: /rss/search?q=" . urlencode(get_http_var('postcode'))); 
    exit;
}

// Strip any trailing '/'.
$q_sort = preg_replace("#/$#", "", $q_sort);
if ($q_type == 'failed') {
    $open = '<'; $succeeded = '<';
    $open_byarea = '<'; $succeeded_byarea = ' = 0';
    if ($q_sort == "default") $q_sort = "creationtime";
} elseif ($q_type == 'succeeded_closed') {
    $open = '<'; $succeeded = '>=';
    $open_byarea = '<'; $succeeded_byarea = ' > 0';
    if ($q_sort == "default") $q_sort = $rss ? "whensucceeded" : "creationtime";
} elseif ($q_type == 'succeeded_open') {
    $open = '>='; $succeeded = '>=';
    $open_byarea = '>='; $succeeded_byarea = ' < 0'; # never allowed
    if ($q_sort == "default") $q_sort = $rss ? "whensucceeded" : "date";
} elseif ($q_type == 'succeeded') {
    $open = null; $succeeded = '>=';
    $open_byarea = null; $succeeded_byarea = ' > 0';
    if ($q_sort == "default") $q_sort = $rss ? "whensucceeded" : "creationtime";
} else { // open
    $open = '>='; $succeeded = '<';
    $open_byarea = '>='; $succeeded_byarea = null;
    if ($q_sort == "default") $q_sort = $rss ? "creationtime" : "percentcomplete";
}
$date_range_clause = ($open ? " AND date $open '$pb_today' " : "");
if ($succeeded) 
    $signers_range_clause = "AND (SELECT count(*) FROM signers WHERE signers.pledge_id = pledges.id) $succeeded target";
else
    $signers_range_clause = "";
$byarea_clause = ($open_byarea ? " date $open_byarea '$pb_today' AND " : "") 
                . ($succeeded_byarea ? "(SELECT count(*) FROM byarea_location WHERE 
                    byarea_location.pledge_id = pledges.id AND byarea_location.whensucceeded IS NOT NULL) 
                    $succeeded_byarea" : " (1=1)");
$page_clause = "AND ( (pledges.target_type = 'overall' $signers_range_clause $date_range_clause)
                OR (pledges.target_type = 'byarea' AND $byarea_clause) )";

$limit_to_category = intval(get_http_var('category'));
if ($limit_to_category) {
    $category_clause = ' AND (SELECT count(*) from pledge_category where pledge_category.pledge_id = pledges.id and pledge_category.category_id = '.$limit_to_category.') > 0 ';
} else {
    $category_clause = '';
}

$sql_params = array();
$locale_clause = "(".
    pb_site_pledge_filter_main($sql_params) . 
    ' OR ' . pb_site_pledge_filter_general($sql_params).
    ")";
$query = "
                SELECT count(pledges.id)
                FROM pledges LEFT JOIN location ON location.id = pledges.location_id
                WHERE $locale_clause AND pin IS NULL 
                $page_clause
                $category_clause
                AND (".microsites_normal_prominences()." OR cached_prominence = 'frontpage')";
$ntotal = db_getOne($query, $sql_params);

if ($ntotal < $q_offset) {
    $q_offset = $ntotal - PAGE_SIZE;
    if ($q_offset < 0)
        $q_offset = 0;
}

if ($q_sort == 'percentcomplete' && microsites_no_target())
    $q_sort = 'signers';

if ($q_sort == 'creationtime' || $q_sort == 'whensucceeded' || $q_sort == 'signers') {
    $sort_phrase = "$q_sort DESC";
} elseif ($q_sort == 'percentcomplete') {
    $sort_phrase = "( 
                (SELECT count(*) FROM signers WHERE signers.pledge_id = pledges.id)::numeric
                / target) DESC";
} elseif ($q_sort == 'category') {
    $sort_phrase = "coalesce ((SELECT name FROM pledge_category, category WHERE 
            pledge_category.category_id = category.id AND parent_category_id IS NULL AND 
            pledge_category.pledge_id = pledges.id LIMIT 1), '"._("Miscellaneous")."')";
} else {
    $sort_phrase = $q_sort;
}

$sql_params[] = PAGE_SIZE;
$qrows = db_query("
        SELECT pledges.*, '$pb_today' <= pledges.date AS open,
            (SELECT count(*) FROM signers WHERE signers.pledge_id = pledges.id) AS signers,
            person.email AS email, address_1, address_postcode,
            country, state, description, method, latitude, longitude
            FROM pledges 
            LEFT JOIN person ON person.id = pledges.person_id
            LEFT JOIN location ON location.id = pledges.location_id
            WHERE $locale_clause
            AND pin IS NULL
            $page_clause
            $category_clause
            AND (".microsites_normal_prominences()." OR cached_prominence = 'frontpage')
            ORDER BY $sort_phrase,pledges.id LIMIT ? OFFSET $q_offset", $sql_params);
/* PG bug: mustn't quote parameter of offset */

if ($q_type == 'open') {
    $heading = _("Pledges which need signers");
    if ($rss)
        $heading = _('New pledges');
} elseif ($q_type == 'succeeded_open') {
    $heading = _("Successful pledges, open to new signers");
} elseif ($q_type == 'succeeded_closed') {
    $heading = _("Successful pledges, closed to new signers");
} elseif ($q_type == 'succeeded') {
    $heading = _("Successful pledges");
} elseif ($q_type == 'failed') {
    $heading = _("Failed pledges");
} else {
    header("Location: /list");
    exit;
}
if ($rss) 
    rss_header($heading, $heading, array());
else {
    page_header($heading, array('id'=>'all',
            'rss'=> array(
                    $heading => pb_domain_url(array('explicit'=>true, 'path'=>'/rss'.$_SERVER['REQUEST_URI']))
                    ),
            'cache-max-age' => 60,
    ));
}

if ($limit_to_category) {
    $heading .= " " . _("(one category only)");
}

if (!$rss) {
?><a href="<?=pb_domain_url(array('explicit'=>true, 'path'=>"/rss".$_SERVER['REQUEST_URI']))?>"><img align="right" border="0" src="/rss.gif" alt="<?=_('RSS feed of ') . $heading ?>"></a><?
    print '<div style="float:left;width:57%">';
    print '<h2 class="head_with_mast">' . $heading . '</h2>';

    pb_print_filter_link_main_general('class="head_mast" style="font-style:italic"');
    print '</div>';

    $views = '';
    $c = 0;
    foreach ($viewsarray as $s => $desc) {
        $c++;
        if ($q_type == $s) continue;
        $views .= "<li><a href=\"/list/$s\">" . str_replace(' ', '&nbsp;', $desc) . '</a>';
    }

    $sort = ($q_sort) ? '&amp;sort=' . $q_sort : '';
    $off = ($q_offset) ? '&amp;offset=' . $q_offset : '';
    $prev = '<span class="greyed">&laquo; '._('Previous page').'</span>'; $next = '<span class="greyed">'._('Next page').' &raquo;</span>';
    if ($q_offset > 0) {
        $n = $q_offset - PAGE_SIZE;
        if ($n < 0) $n = 0;
        $prev = "<a href=\"?offset=$n$sort\">&laquo; "._('Previous page')."</a>";
    }
    if ($q_offset + PAGE_SIZE < $ntotal) {
        $n = $q_offset + PAGE_SIZE;
        $next = "<a href=\"?offset=$n$sort\">"._('Next page')." &raquo;</a>";
    }
    $navlinks1 = '<ul style="margin-top:0; float:right; width:30%">' . $views . "</ul>\n";
    $navlinks2 = '';
    $navlinks3 = '';
    if ($ntotal > 0) {
        $navlinks2 = '<p align="center" style="margin:0.5em 0;font-size: 89%">' . _('Sort by'). ': ';
        $arr = microsites_list_sort_options();
        $c = 0;
        foreach ($arr as $s => $desc) {
            $c ++;
            if ($q_sort != $s) $navlinks2 .= "<a href=\"?sort=$s$off\">$desc</a>"; else $navlinks2 .= $desc;
            if ($c != count($arr)) $navlinks2 .= ' | ';
        }
        $navlinks2 .= '</p>';
        $navlinks3 = '<p align="center" style="margin:0.5em 0;">';
        $navlinks3 .= $prev . ' | '._('Pledges'). ' ' . ($q_offset + 1) . ' &ndash; ' . 
            ($q_offset + PAGE_SIZE > $ntotal ? $ntotal : $q_offset + PAGE_SIZE) . ' of ' .
            $ntotal . ' | ' . $next;
        $navlinks3 .= '</p>';
    }
    print $navlinks1.'<div style="clear:both"></div>'.$navlinks2.$navlinks3;
}

$rss_items = array();
if ($ntotal > 0) {
    $c = 0;
    $lastdivision = 'none';
    while ($row = db_fetch_array($qrows)) {
        $pledge = new Pledge($row);
        if ($q_sort == "category") {
            $categories = $pledge->categories();
            $thiscategory = _("Miscellaneous");
            $thiscategory_no = -1;
            foreach ($categories as $thiscategory_no => $thiscategory) {
                # just let variables fill in with last one
            }
            if ($lastdivision <> $thiscategory) {
                if (!$rss)
                    print '<h2 style="clear:both">' . _($thiscategory) . "</h2> <!-- $thiscategory_no -->";
                $c = 0;
                $lastdivision = $thiscategory;
            }
        }
        $arr = array('class'=>"pledge-".$c%2, 'href' => $pledge->url_main() );
        if ($rss)
            $rss_items[] = $pledge->rss_entry();
        else
            $pledge->render_box($arr);
        $c++;
    }
    if (!$rss)
        print "<br style=\"clear: both;\">$navlinks3$navlinks2";
} else {
    if (!$rss)
        print p(_('There are currently none.'));
}

if ($rss) {
    rss_footer($rss_items);
} else {
    if (microsites_new_pledges_prominence() != 'frontpage')
        print '<p style="clear: both" align="center"><small>'._('New pledges are not shown here. <a href="/faq#allpledges">Read our FAQ</a> for details of when they appear.').'</small></p>';
    page_footer();
}

?>
