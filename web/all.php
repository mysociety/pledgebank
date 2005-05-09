<?
// all.php:
// List all pledges.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: all.php,v 1.3 2005-05-09 18:48:15 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

page_header("All Pledges");

$type = $_SERVER['REQUEST_URI'];
$type = substr($type, strpos($type, '?')+1);
$order = 'id DESC';
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
$q = db_query('SELECT title,target,date,name,ref 
        FROM pledges 
        WHERE confirmed 
        AND date>=pb_current_date() 
        AND password IS NULL 
        AND prominence <> \'backpage\'
        ORDER BY '.$order.' LIMIT 50');
$out = '<table width="100%"><tr><th><a href="./all?title">Title</a></th><th><a href="./all?target">Target</a></th><th><a href="./all?deadline">Deadline</a></th><th><a href="./all?creator">Creator</a></th><th><a href="./all?ref">Short name</a></th></tr>';
while ($r = db_fetch_row($q)) {
        $r[0] = '<a href="'.$r[4].'">'.$r[0].'</a>';
        $out .= '<tr><td>'.join('</td><td align="center">',array_map('prettify',$r)).'</td></tr>';
}
$out .= '</table>';
print '<h2>Open Pledges 1-'.db_num_rows($q).':</h2>';
print $out;

page_footer();
