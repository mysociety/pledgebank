<?
// all.php:
// List all pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: all.php,v 1.8 2005-06-10 10:58:43 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

page_header("All Pledges", array('id'=>'all'));

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
$q = db_query('SELECT *, (SELECT count(*) FROM signers
                            WHERE signers.pledge_id = pledges.id) AS signers
        FROM pledges 
        WHERE confirmed 
        AND date>=pb_current_date() 
        AND pin IS NULL 
        AND prominence <> \'backpage\'
        ORDER BY '.$order.' LIMIT 50');
if (db_num_rows($q)) {
    print '<h2>All '.db_num_rows($q).' Open Pledges</h2>';
    $c = 0;
    while ($r = db_fetch_array($q)) {
        $pledge = new Pledge($r);
        $pledge->render_box(array('all'=>$c%2, 'href'=>$pledge->url_main()));
        $c++;
    }
} else {
    print '<h2>All Open Pledges</h2><p>There are currently no open pledges.</p>';
}

page_footer();

?>
