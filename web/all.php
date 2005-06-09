<?
// all.php:
// List all pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: all.php,v 1.6 2005-06-09 18:12:39 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

page_header("All Pledges");

$type = $_SERVER['REQUEST_URI'];
$type = substr($type, strpos($type, '?')+1);
$order = 'date';
if ($type == 'title') {
    $order = 'title';
} elseif ($type =='target') {
    $order = 'target';
} elseif ($type =='deadline') {
    $order = 'date';
} elseif ($type =='creator') {
    $order = 'name';
} elseif ($type =='ref') {
    $order = 'ref';
}
$q = db_query('SELECT *
        FROM pledges 
        WHERE confirmed 
        AND date>=pb_current_date() 
        AND pin IS NULL 
        AND prominence <> \'backpage\'
        ORDER BY '.$order.' LIMIT 50');
if (db_num_rows($q)) {
    print '<h2>All '.db_num_rows($q).' Open Pledges</h2>';
    print '<p id="allpage">';
    $c = 0;
    while ($r = db_fetch_array($q)) {
        $class = "allleft";
        if ($c % 2 == 1) {
            $class = "allright";
        }
        $pledge = new Pledge($r);
        print "<div class=\"$class\">";
        $pledge->render_box(array('id'=>'allpledge', 'href'=>$pledge->url_main()));
        print "</div>";
        $c++;
    }
    print "</p>";
} else {
    print '<h2>All Open Pledges</h2><p>There are currently no open pledges.</p>';
}

page_footer();

?>
