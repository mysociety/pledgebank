<?
// all.php:
// List all pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: all.php,v 1.19 2005-06-17 13:44:09 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

require_once '../../phplib/importparams.php';

define('PAGE_SIZE', 50);

$err = importparams(
            array('offset', '/^(0|[1-9]\d*)$/', '', 0),
            array('sort', '/^(title|target|date|name|ref)$/', '', 'date')
        );
if ($err) {
    err('Illegal offset or sort parameter passed');
}

page_header("All Pledges", array('id'=>'all'));

$ntotal = db_getOne("
                select count(id)
                from pledges
                where pin is null
                    and confirmed
                    and date >= pb_current_date()
                    and pb_pledge_prominence(id) <> 'backpage'");
if ($ntotal < $q_offset) 
    $q_offset = $ntotal - PAGE_SIZE;

$qrows = db_query("
        SELECT *, (SELECT count(*) FROM signers
                    WHERE signers.pledge_id = pledges.id) AS signers
            FROM pledges 
            WHERE confirmed 
            AND date >= pb_current_date() 
            AND pin IS NULL 
            AND pb_pledge_prominence(id) <> 'backpage'
            ORDER BY $q_sort LIMIT ? OFFSET $q_offset", PAGE_SIZE);
/* PG bug: mustn't quote parameter of offset */

print "<h2>All Pledges <small>(which at least a few people have signed up to)</small></h2>";
if ($ntotal > 0) {
    
    $navlinks = '';
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
    $navlinks = '<p align="center">' . $prev . ' | Pledges ' . ($q_offset + 1) . ' &ndash; ' . 
        ($q_offset + PAGE_SIZE > $ntotal ? $ntotal : $q_offset + PAGE_SIZE) . ' of ' .
        $ntotal . ' | ' . $next . '<br>Sort by: ';
    $arr = array('title'=>'Title', 'target'=>'Target', 'date'=>'Deadline', 'name'=>'Creator', 'ref'=>'Short name');
    foreach ($arr as $s => $desc) {
        if ($q_sort != $s) $navlinks .= "<a href=\"all?sort=$s$off\">$desc</a>"; else $navlinks .= $desc;
        if ($s != 'ref') $navlinks .= ' | ';
    }
    $navlinks .= '</p>';
    print $navlinks;

    $c = 0;
    while (list($id) = db_fetch_row($qrows)) {
        $pledge = new Pledge(intval($id));
        $pledge->render_box(array('all'=>$c%2, 'href'=>$pledge->url_main()));
        $c++;
    }
    if ($ntotal > PAGE_SIZE)
        print "<br style=\"clear: both;\">$navlinks";
} else {
    print '<p>There are currently none.</p>';
}

page_footer();

?>
