<?
// all.php:
// List all pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: list.php,v 1.1 2005-06-24 22:13:55 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

require_once '../../phplib/importparams.php';

define('PAGE_SIZE', 50);

$err = importparams(
            array('offset', '/^(0|[1-9]\d*)$/', '', 0),
            array('sort', '/^(title|target|date|name|ref|creationtime)$/', '', 'date'),
            array('type', '/^[a-z_]*$/', '', '')
        );
if ($err) {
    err(_('Illegal offset or sort parameter passed'));
}

page_header(_("All Pledges"), array('id'=>'all'));

if ($q_type == 'failed') {
    $open = '<'; $succeeded = '<';
} elseif ($q_type == 'succeeded_closed') {
    $open = '<'; $succeeded = '>=';
} elseif ($q_type == 'succeeded_open') {
    $open = '>='; $succeeded = '>=';
} else {
    $open = '>='; $succeeded = '<';
}

$ntotal = db_getOne("
                select count(id)
                from pledges
                where pin is null
                    and confirmed
                    and date $open pb_current_date()
                    AND (SELECT count(*) FROM signers WHERE signers.pledge_id = pledges.id) $succeeded target
                    and pb_pledge_prominence(id) <> 'backpage'");
if ($ntotal < $q_offset) 
    $q_offset = $ntotal - PAGE_SIZE;

$sort_phrase = $q_sort;
if ($q_sort == 'creationtime') {
    $sort_phrase .= " DESC";
}
$qrows = db_query("
        SELECT *, (SELECT count(*) FROM signers
                    WHERE signers.pledge_id = pledges.id) AS signers
            FROM pledges 
            WHERE confirmed 
            AND date $open pb_current_date() 
            AND pin IS NULL
            AND (SELECT count(*) FROM signers WHERE signers.pledge_id = pledges.id) $succeeded target 
            AND pb_pledge_prominence(id) <> 'backpage'
            ORDER BY $sort_phrase LIMIT ? OFFSET $q_offset", PAGE_SIZE);
/* PG bug: mustn't quote parameter of offset */

if ($q_type == 'succeeded_open') {
    print h2(_("Successful pledges which are still open"));
    $views = '<a href="/list">Open pledges</a> | <strong>Successful open pledges</strong> | <a href="/list/succeeded_closed">Successful closed pledges</a> | <a href="/list/failed">Failed pledges</a>';
} elseif ($q_type == 'succeeded_closed') {
    print h2(_("Successful, closed pledges"));
    $views = '<a href="/list">Open pledges</a> | <a href="/list/succeeded_open">Successful open pledges</a> | <strong>Successful closed pledges</strong> | <a href="/list/failed">Failed pledges</a>';
} elseif ($q_type == 'failed') {
    print h2(_("Failed pledges"));
    $views = '<a href="/list">Open pledges</a> | <a href="/list/succeeded_open">Successful open pledges</a> | <a href="/list/succeeded_closed">Successful closed pledges</a> | <strong>Failed pledges</strong>';
} else {
    print h2(_("Pledges which still need signers <small>(to which at least a few people have signed up)</small>"));
    $views = '<strong>Open pledges</strong> | <a href="/list/succeeded_open">Successful open pledges</a> | <a href="/list/succeeded_closed">Successful closed pledges</a> | <a href="/list/failed">Failed pledges</a>';
}

if ($ntotal > 0) {
    
    $sort = ($q_sort) ? '&amp;sort=' . $q_sort : '';
    $off = ($q_offset) ? '&amp;offset=' . $q_offset : '';
    $prev = '<span class="greyed">&laquo; Previous page</span>'; $next = '<span class="greyed">Next page &raquo;</span>';
    if ($q_offset > 0) {
        $n = $q_offset - PAGE_SIZE;
        if ($n < 0) $n = 0;
        $prev = "<a href=\"all?offset=$n$sort\">&laquo; Previous page</a>";
    }
    if ($q_offset + PAGE_SIZE < $ntotal) {
        $n = $q_offset + PAGE_SIZE;
        $next = "<a href=\"all?offset=$n$sort\">Next page &raquo;</a>";
    }
    $navlinks = '<p align="center">' . $views . "</p>\n";
    $navlinks .= '<p align="center" style="font-size: 89%">Sort by: ';
    $arr = array('creationtime'=>'Created', 'title'=>'Title', 'target'=>'Target', 'date'=>'Deadline', 'name'=>'Creator', 'ref'=>'Short name');
    foreach ($arr as $s => $desc) {
        if ($q_sort != $s) $navlinks .= "<a href=\"?sort=$s$off\">$desc</a>"; else $navlinks .= $desc;
        if ($s != 'ref') $navlinks .= ' | ';
    }
    $navlinks .= '</p> <p align="center">';
    $navlinks .= $prev . ' | Pledges ' . ($q_offset + 1) . ' &ndash; ' . 
        ($q_offset + PAGE_SIZE > $ntotal ? $ntotal : $q_offset + PAGE_SIZE) . ' of ' .
        $ntotal . ' | ' . $next;
    $navlinks .= '</p>';
    print $navlinks;

    $c = 0;
    while (list($id) = db_fetch_row($qrows)) {
        $pledge = new Pledge(intval($id));
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
