<?
// all.php:
// List all pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: list.php,v 1.10 2005-08-12 13:23:00 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

require_once '../../phplib/importparams.php';

define('PAGE_SIZE', 50);

$err = importparams(
            array('offset', '/^(0|[1-9]\d*)$/', '', 0),
            array('sort', '/^(title|target|date|name|ref|creationtime|percentcomplete|category)$/', '', 'default'),
            array('type', '/^[a-z_]*$/', '', 'open')
        );
if ($err) {
    err(_('Illegal offset or sort parameter passed'));
}

if ($q_type == 'failed') {
    $open = '<'; $succeeded = '<';
    if ($q_sort == "default") $q_sort = "creationtime";
} elseif ($q_type == 'succeeded_closed') {
    $open = '<'; $succeeded = '>=';
    if ($q_sort == "default") $q_sort = "creationtime";
} elseif ($q_type == 'succeeded_open') {
    $open = '>='; $succeeded = '>=';
    if ($q_sort == "default") $q_sort = "date";
} else {
    $open = '>='; $succeeded = '<';
    if ($q_sort == "default") $q_sort = "percentcomplete";
}

$ntotal = db_getOne("
                select count(id)
                from pledges
                where pin is null
                    and date $open pb_current_date()
                    AND (SELECT count(*) FROM signers WHERE signers.pledge_id = pledges.id) $succeeded target
                    and pb_pledge_prominence(id) <> 'backpage'");
if ($ntotal < $q_offset) 
    $q_offset = $ntotal - PAGE_SIZE;

$sort_phrase = $q_sort;
if ($q_sort == 'creationtime' || $q_sort == 'created') {
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
            pledge_category.pledge_id = pledges.id LIMIT 1), 'Miscellaneous')";
}
$qrows = db_query("
        SELECT *, (SELECT count(*) FROM signers
                    WHERE signers.pledge_id = pledges.id) AS signers
            FROM pledges 
            WHERE date $open pb_current_date() 
            AND pin IS NULL
            AND (SELECT count(*) FROM signers WHERE signers.pledge_id = pledges.id) $succeeded target 
            AND pb_pledge_prominence(id) <> 'backpage'
            ORDER BY $sort_phrase,pledges.id LIMIT ? OFFSET $q_offset", PAGE_SIZE);
/* PG bug: mustn't quote parameter of offset */

$heading = 'All Pledges';
if ($q_type == 'open') {
    $heading = _("Pledges which need signers");
} elseif ($q_type == 'succeeded_open') {
    $heading = _("Successful pledges, open to new signers");
} elseif ($q_type == 'succeeded_closed') {
    $heading = _("Successful pledges, closed to new signers");
} elseif ($q_type == 'failed') {
    $heading = _("Failed pledges");
} 
page_header($heading, array('id'=>'all'));
print h2($heading);

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
    $prev = "<a href=\"all?offset=$n$sort\">&laquo; "._('Previous page')."</a>";
}
if ($q_offset + PAGE_SIZE < $ntotal) {
    $n = $q_offset + PAGE_SIZE;
    $next = "<a href=\"all?offset=$n$sort\">"._('Next page')." &raquo;</a>";
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

if ($ntotal > 0) {
    $c = 0;
    $lastcategory = 'none';
    while (list($id) = db_fetch_row($qrows)) {
        $pledge = new Pledge(intval($id));
        if ($q_sort == "category") {
            $categories = $pledge->categories();
            $thiscategory = array_pop($categories);
            if ($thiscategory == null) 
                $thiscategory = "Miscellaneous";
            if ($lastcategory <> $thiscategory) {
                print "<h2 style=\"clear:both\">$thiscategory</h2>";
                $c = 0;
                $lastcategory = $thiscategory;
            }
        }
        $arr = array('class'=>"pledge-".$c%2, 'href' => $pledge->url_main() );
        if ($q_type == 'succeeded_closed' || $q_type == 'failed') $arr['closed'] = true;
        $pledge->render_box($arr);
        $c++;
    }
    if ($ntotal > PAGE_SIZE)
        print "<br style=\"clear: both;\">$navlinks";
} else {
    print p(_('There are currently none.'));
}

page_footer();

?>
