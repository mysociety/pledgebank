<?
// all.php:
// List all pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: list.php,v 1.32 2006-08-18 09:44:08 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

require_once '../../phplib/importparams.php';

define('PAGE_SIZE', 50);

$err = importparams(
            array('offset', '/^(0|[1-9]\d*)$/', '', 0),
            array('sort', '/^(title|target|date|name|ref|creationtime|percentcomplete|category)\/?$/', '', 'default'),
            array('type', '/^[a-z_]*$/', '', 'open')
        );
if ($err) {
    err(_('Illegal offset or sort parameter passed'));
}
if ($q_type == 'all') $q_type = 'open';

$rss = get_http_var('rss') ? true : false;

// Old postcode RSS feed
if ($rss && get_http_var('postcode')) {
    header("Location: /rss/search?q=" . urlencode(get_http_var('postcode'))); 
    exit;
}

// Strip any trailing '/'.
$original_sort = preg_replace("#/$#", "", $q_sort);
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
} else {
    $open = '>='; $succeeded = '<';
    $open_byarea = '>='; $succeeded_byarea = null;
    if ($q_sort == "default") $q_sort = $rss ? "creationtime" : "percentcomplete";
}
$date_range_clause = ($open ? " AND date $open '$pb_today' " : "");
$signers_range_clause = "(SELECT count(*) FROM signers WHERE signers.pledge_id = pledges.id) 
                         $succeeded target";
$byarea_clause = ($open_byarea ? " date $open_byarea '$pb_today' AND " : "") 
                . ($succeeded_byarea ? "(SELECT count(*) FROM byarea_location WHERE 
                    byarea_location.pledge_id = pledges.id AND byarea_location.whensucceeded IS NOT NULL) 
                    $succeeded_byarea" : " (1=1)");
$page_clause = "AND ( (pledges.target_type = 'overall' AND $signers_range_clause $date_range_clause)
                OR (pledges.target_type = 'byarea' AND $byarea_clause) )";

$sql_params = array();
$locale_clause = "(".
    pb_site_pledge_filter_main($sql_params) . 
    ' OR ' . pb_site_pledge_filter_general($sql_params).
    ")";
$query = "
                SELECT count(pledges.id), extract(epoch from max(pledge_last_change_time(pledges.id)))
                FROM pledges LEFT JOIN location ON location.id = pledges.location_id
                WHERE $locale_clause AND pin IS NULL 
                $page_clause
                AND (".microsites_normal_prominences()." OR cached_prominence = 'frontpage')";
list($ntotal, $last_modified) = db_getRow($query , $sql_params);
if (cond_maybe_respond($last_modified))
    exit();

if ($ntotal < $q_offset) {
    $q_offset = $ntotal - PAGE_SIZE;
    if ($q_offset < 0)
        $q_offset = 0;
}

$sort_phrase = $q_sort;
if ($q_sort == 'creationtime' || $q_sort == 'created' || $q_sort == 'whensucceeded') {
    $sort_phrase .= " DESC";
}
if ($q_sort == 'percentcomplete') {
    $sort_phrase = "( 
                (SELECT count(*) FROM signers WHERE signers.pledge_id = pledges.id)::numeric
                / target) DESC";
}
if ($q_sort == 'category') {
    $sort_phrase = "coalesce ((SELECT name FROM pledge_category, category WHERE 
            pledge_category.category_id = category.id AND parent_category_id IS NULL AND 
            pledge_category.pledge_id = pledges.id LIMIT 1), '"._("Miscellaneous")."')";
}
$sql_params[] = PAGE_SIZE;
$qrows = db_query("
        SELECT pledges.*, '$pb_today' <= pledges.date AS open,
            (SELECT count(*) FROM signers WHERE signers.pledge_id = pledges.id) AS signers,
            person.email AS email, country, state, description, method, latitude, longitude
            FROM pledges 
            LEFT JOIN person ON person.id = pledges.person_id
            LEFT JOIN location ON location.id = pledges.location_id
            WHERE $locale_clause
            AND pin IS NULL
            $page_clause
            AND (".microsites_normal_prominences()." OR cached_prominence = 'frontpage')
            ORDER BY $sort_phrase,pledges.id LIMIT ? OFFSET $q_offset", $sql_params);
/* PG bug: mustn't quote parameter of offset */

if ($q_type == 'open') {
    $heading = _("Pledges which need signers");
    if ($rss)
        $heading = _('New Pledges');
} elseif ($q_type == 'succeeded_open') {
    $heading = _("Successful pledges, open to new signers");
} elseif ($q_type == 'succeeded_closed') {
    $heading = _("Successful pledges, closed to new signers");
} elseif ($q_type == 'succeeded') {
    $heading = _("Successful pledges");
} elseif ($q_type == 'failed') {
    $heading = _("Failed pledges");
} else {
    err('Unknown type ' . $q_type);
}
if ($rss) 
    rss_header($heading, $heading, array(
        'last-modified' => $last_modified
    ));
else {
    page_header($heading, array('id'=>'all',
            'rss'=> array(
                    $heading => pb_domain_url(array('explicit'=>true, 'path'=>'/rss'.$_SERVER['REQUEST_URI']))
                    ),
            'cache-max-age' => 60,
            'last-modified' => $last_modified,
    ));
}

if (!$rss) {
?><a href="<?=pb_domain_url(array('explicit'=>true, 'path'=>"/rss".$_SERVER['REQUEST_URI']))?>"><img align="right" border="0" src="/rss.gif" alt="<?=_('RSS feed of ') . $heading ?>"></a><?
    print h2($heading);

    pb_print_filter_link_main_general('align="center"');

    $viewsarray = array('open'=>_('Open pledges'), 'succeeded_open'=>_('Successful open pledges'), 
        'succeeded_closed'=>_('Successful closed pledges'), 'failed' => _('Failed pledges'));
    $views = "";
    foreach ($viewsarray as $s => $desc) {
        if ($q_type != $s) $views .= "<a href=\"/list/$s\">$desc</a>"; else $views .= $desc;
        if ($s != 'failed') $views .= ' | ';
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
    $navlinks = '<p align="center">' . $views . "</p>\n";
    if ($ntotal > 0) {
        $navlinks .= '<p align="center" style="font-size: 89%">' . _('Sort by'). ': ';
        $arr = array(
                     'creationtime'=>_('Start date'), 
                     /* 'target'=>_('Target'), */
                     'date'=>_('Deadline'), 
                     'percentcomplete' => _('Percent signed'), 
                     'category' => _('Category'), 
                     );
        # Removed as not useful (search is better for these): 'ref'=>'Short name',
        # 'title'=>'Title', 'name'=>'Creator'
        foreach ($arr as $s => $desc) {
            if ($q_sort != $s) $navlinks .= "<a href=\"?sort=$s$off\">$desc</a>"; else $navlinks .= $desc;
            if ($s != 'category') $navlinks .= ' | ';
        }
        $navlinks .= '</p> <p align="center">';
        $navlinks .= $prev . ' | '._('Pledges'). ' ' . ($q_offset + 1) . ' &ndash; ' . 
            ($q_offset + PAGE_SIZE > $ntotal ? $ntotal : $q_offset + PAGE_SIZE) . ' of ' .
            $ntotal . ' | ' . $next;
        $navlinks .= '</p>';
    }
    print $navlinks;
}

$rss_items = array();
if ($ntotal > 0) {
    $c = 0;
    $lastcategory = 'none';
    while ($row = db_fetch_array($qrows)) {
        $pledge = new Pledge($row);
        if ($q_sort == "category") {
            $categories = $pledge->categories();
            $thiscategory = array_pop($categories);
            if ($thiscategory == null) 
                $thiscategory = _("Miscellaneous");
            if ($lastcategory <> $thiscategory) {
                if (!$rss)
                    print "<h2 style=\"clear:both\">"._($thiscategory)."</h2>";
                $c = 0;
                $lastcategory = $thiscategory;
            }
        }
        $arr = array('class'=>"pledge-".$c%2, 'href' => $pledge->url_main() );
        if ($q_type == 'succeeded_closed' || $q_type == 'failed') $arr['closed'] = true;
        if ($rss)
            $rss_items[] = $pledge->rss_entry();
        else
            $pledge->render_box($arr);
        $c++;
    }
    if (!$rss && $ntotal > PAGE_SIZE)
        print "<br style=\"clear: both;\">$navlinks";
} else {
    if (!$rss)
        print p(_('There are currently none.'));
}

if ($rss)
    rss_footer($rss_items);
else
    page_footer();

?>
