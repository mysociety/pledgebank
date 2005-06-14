<?
// all.php:
// List all pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: all.php,v 1.12 2005-06-14 20:20:02 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

require_once '../../phplib/importparams.php';

define('PAGE_SIZE', 6);

$err = importparams(
            array('offset', '/^(0|[1-9]\d*)$/', '', 0),
            array('sort', '/^(title|target|date|name|ref)$/', '', 'ref')
        );
if ($err) {
    err('Illegal offset or sort parameter passed');
}

page_header("All Pledges", array('id'=>'all'));

$ntotal = db_getOne("select count(id) from pledges where pin is null and confirmed and date >= pb_current_date() and prominence <> 'backpage'");
if ($ntotal < $q_offset) $q_offset = $ntotal - PAGE_SIZE;

$s = db_query('SELECT *, (SELECT count(*) FROM signers
                            WHERE signers.pledge_id = pledges.id) AS signers
        FROM pledges 
        WHERE confirmed 
        AND date >= pb_current_date() 
        AND pin IS NULL 
        AND prominence <> \'backpage\'
        ORDER BY lower(' . $q_sort . ') LIMIT ? OFFSET ' . $q_offset, PAGE_SIZE );
/* PG bug: mustn't quote parameter of offset */

if ($ntotal > 0) {
    print "<h2>All $ntotal Open Pledges</h2>";
    $navlinks = '';
    if ($ntotal > PAGE_SIZE) {
        $prev = '<span class="greyed">&laquo; Previous page</span>'; $next = '<span class="greyed">Next page &raquo;</span>';
        if ($q_offset > 0) {
            $n = $q_offset - PAGE_SIZE;
            if ($n < 0) $n = 0;
            $prev = "<a href=\"all?offset=$n\">&laquo; Previous page</a>";
        }
        if ($q_offset + PAGE_SIZE < $ntotal) {
            $n = $q_offset + PAGE_SIZE;
            $next = "<a href=\"all?offset=$n\">Next page &raquo;</a>";
        }
        $navlinks = '<p align="center">' . $prev . ' | Pledges ' . ($q_offset + 1) . ' &ndash; ' . 
            ($q_offset + PAGE_SIZE > $ntotal ? $ntotal : $q_offset + PAGE_SIZE) . ' of ' .
            $ntotal . ' | ' . $next . '<br>Sort by: ';
        $off = '';
        if ($q_offset) {
            $off = '&amp;offset=' . $q_offset;
        }
        if ($q_sort != 'title') $navlinks .= "<a href=\"all?sort=title$off\">Title</a>"; else $navlinks .= 'Title';
        $navlinks .= ' | ';
        if ($q_sort != 'target') $navlinks .= "<a href=\"all?sort=target$off\">Target</a>"; else $navlinks .= 'Target';
        $navlinks .= ' | ';
        if ($q_sort != 'date') $navlinks .= "<a href=\"all?sort=date$off\">Deadline</a>"; else $navlinks .= 'Deadline';
        $navlinks .= ' | ';
        if ($q_sort != 'name') $navlinks .= "<a href=\"all?sort=name$off\">Creator</a>"; else $navlinks .= 'Creator';
        $navlinks .= ' | ';
        if ($q_sort != 'ref') $navlinks .= "<a href=\"all?sort=ref$off\">Reference</a>"; else $navlinks .= 'Reference';
        $navlinks .= '</p>';
        print $navlinks;
    }
    $c = 0;
    while (list($id) = db_fetch_row($s)) {
        $pledge = new Pledge(intval($id));
        $pledge->render_box(array('all'=>$c%2, 'href'=>$pledge->url_main()));
        $c++;
    }
    if ($ntotal > PAGE_SIZE)
        print "<br style=\"clear: both;\">$navlinks";
} else {
    print '<h2>All Open Pledges</h2><p>There are currently no open pledges.</p>';
}

page_footer();

?>
