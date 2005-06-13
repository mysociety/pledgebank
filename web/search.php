<?
// search.php:
// Search for a pledge.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: search.php,v 1.5 2005-06-13 07:47:43 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/comments.php';

page_header("Search Results");
print search();
page_footer();

function search() {
    $pledge_select = 'SELECT *, pb_current_date() <= date as open,
                (SELECT count(*) FROM signers WHERE pledge_id=pledges.id) AS signers,
                date - pb_current_date() AS daysleft';

    $out = ''; $success = 0;
    $search = get_http_var('q');
    $q = db_query("$pledge_select FROM pledges WHERE ref ILIKE ?", $search);
    if (db_num_rows($q)) {
        $success = 1;
        $r = db_fetch_array($q);
        $out .= '<p>Result <strong>exactly matching</strong> pledge <strong>' . htmlspecialchars($search) . '</strong>:</p>';
        $out .= '<ul><li>';
        $out .= pledge_summary($r, array('html'=>true, 'href'=>$r['ref']));
        $out .= '</li></ul>';
    }

    $q = db_query($pledge_select . ' FROM pledges 
                WHERE pin IS NULL 
                    AND (title ILIKE \'%\' || ? || \'%\' OR 
                         detail ILIKE \'%\' || ? || \'%\' OR 
                         ref ILIKE \'%\' || ? || \'%\' OR
                         id in (select pledge_id from pledge_find_fuzzily(?) limit 5)
                         )
                    AND ref NOT ILIKE ?
                ORDER BY date DESC', array($search, $search, $search, $search, $search));
    $closed = ''; $open = '';
    if (db_num_rows($q)) {
        $success = 1;
        while ($r = db_fetch_array($q)) {
            $text = '<li>';
            $text .= pledge_summary($r, array('html'=>true, 'href'=>$r['ref']));
            $text .= '</li>';
            if ($r['open']=='t') {
                $open .= $text;
            } else {
                $closed .= $text;
            }
        }
    }

    if ($open) {
        $out .= '<p>Results for <strong>open pledges</strong> matching <strong>' . htmlspecialchars($search) . '</strong>:</p>';
        $out .= '<ul>' . $open . '</ul>';
    }

    if ($closed) {
        $out .= '<p>Results for <strong>closed pledges</strong> matching <strong>'.htmlspecialchars($search).'</strong>:</p>';
        $out .= '<ul>' . $closed . '</ul>';
    }

    // Comments
    $comments_to_show = 10;
    $q = db_query('SELECT comment.id,
                          extract(epoch from whenposted) as whenposted,
                          text,comment.name,website,ref 
                   FROM comment,pledges 
                   WHERE comment.pledge_id = pledges.id 
                        AND NOT ishidden 
                        AND text ILIKE \'%\' || ? || \'%\'
                   ORDER BY whenposted DESC', array($search));
    if (db_num_rows($q)) {
        $success = 1;
        $out .= "<p>Results for <strong>comments</strong> matching <strong>".htmlspecialchars($search)."</strong>:</p>";
        $out .= '<ul>';
        while($r = db_fetch_array($q)) {
            $out .= '<li>';
            $out .= comment_summary($r);
            $out .= '</li>';
        }
        $out .= '</ul>';
    }

    $people = array();
    $q = db_query('SELECT ref, title, name FROM pledges WHERE confirmed AND name ILIKE \'%\' || ? || \'%\' ORDER BY name', $search);
    while ($r = db_fetch_array($q)) {
        $people[$r['name']][] = array($r['ref'], $r['title'], 'creator');
    }
    $q = db_query('SELECT ref, title, signers.name FROM signers,pledges WHERE showname AND NOT reported AND signers.pledge_id = pledges.id AND signers.name ILIKE \'%\' || ? || \'%\' ORDER BY name', $search);
    while ($r = db_fetch_array($q)) {
        $people[$r['name']][] = array($r['ref'], $r['title'], 'signer');
    }
    if (sizeof($people)) {
        $success = 1;
        $out .= '<p>Results for <strong>people</strong> matching <strong>'.
            htmlspecialchars($search).'</strong>:</p> <dl>';
        ksort($people);
        foreach ($people as $name => $array) {
            $out .= '<dt><b>'.htmlspecialchars($name). '</b></dt> <dd>';
            foreach ($array as $item) {
                $out .= '<dd>';
                $out .= '<a href="' . $item[0] . '">' . $item[1] . '</a>';
                if ($item[2] == 'creator') $out .= " (author)";
                $out .= '</dd>';
            }
        }
        $out .= '</dl>';
    }

    if (!$success) {
        $out .= '<p>Sorry, we could find nothing that matched "' . htmlspecialchars($search) . '".</p>';
    }
    return $out;
}

