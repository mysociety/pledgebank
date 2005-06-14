<?
// all.php:
// List all pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: all.php,v 1.11 2005-06-14 16:41:24 chris Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

require_once '../../phplib/importparams.php';

define('PAGE_SIZE', 50);

$err = importparams(
            array('offset', '/^(0|[1-9]\d*)$/', '', 0)
        );

page_header("All Pledges", array('id'=>'all'));

$s = db_query("select id from pledges where pin is null and confirmed and date >= pb_current_date() and prominence <> 'backpage' order by ref limit ? offset $q_offset", PAGE_SIZE); /* PG bug: mustn't quote parameter of offset */
$ntotal = db_getOne("select count(id) from pledges where pin is null and confirmed and date >= pb_current_date() and prominence <> 'backpage'");

if ($ntotal > 0) {
    print "<h2>All $ntotal Open Pledges</h2>";
    $navlinks = '';
    if ($ntotal > 50) {
        printf('<p>Showing pledges %d &mdash; %d of %d</p>', $q_offset + 1, $q_offset + PAGE_SIZE > $ntotal ? $ntotal : $q_offset + PAGE_SIZE, $ntotal);
        
        $navlinks = "<p>";
        if ($q_offset > 0) {
            $n = $q_offset - PAGE_SIZE;
            if ($n < 0) $n = 0;
            $navlinks .= "<a href=\"all?offset=$n\">&lt;&lt; Previous page</a>\n";
        } else if ($q_offset + PAGE_SIZE < $ntotal) {
            $n = $q_offset + PAGE_SIZE;
            $navlinks .= "<a href=\"all?offset=$n\">Next page &gt;&gt;</a>";
        }
        $navlinks .= "</p>";
        print $navlinks;
    }
    $c = 0;
    while (list($id) = db_fetch_row($s)) {
        $pledge = new Pledge(intval($id));
        $pledge->render_box(array('all'=>$c%2, 'href'=>$pledge->url_main()));
        $c++;
    }
    if ($ntotal > 50)
        print "<br style=\"clear: both;\">$navlinks";
} else {
    print '<h2>All Open Pledges</h2><p>There are currently no open pledges.</p>';
}

page_footer();

?>
