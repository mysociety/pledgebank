<?
// search.php:
// Search for a pledge.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: search.php,v 1.2 2005-05-24 23:18:40 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

page_header("Search Results");
print search();
page_footer();

function search() {
    $out = ''; $success = 0;
    $search = get_http_var('q');
    $id = db_getOne('SELECT id FROM pledges WHERE ref ILIKE ?', $search);
    if ($id) {
        Header("Location: " . OPTION_BASE_URL . '/' . $search);
        exit;
    }

    $q = db_query('SELECT date,ref,title, pb_current_date() <= date as open FROM pledges WHERE pin IS NULL AND (title ILIKE \'%\' || ? || \'%\' OR detail ILIKE \'%\' || ? || \'%\') ORDER BY date', array($search, $search));
    if (!db_num_rows($q)) {
    } else {
        $success = 1;
        $closed = ''; $open = '';
        while ($r = db_fetch_array($q)) {
            $text = '<li><a href="' . $r['ref'] . '">' . htmlspecialchars($r['title']) . '</a></li>';
            if ($r['open']=='t') {
                $open .= $text;
            } else {
                $closed .= $text;
            }
        }
        if ($open) {
            $out .= '<p>The following currently open pledges matched your search term "' . htmlspecialchars($search) . '" in either their title or More Details:</p>';
            $out .= '<ul>' . $open . '</ul>';
        }
        if ($closed) {
            $out .= '<p>The following are closed pledges that match your search term:</p>';
            $out .= '<ul>' . $closed . '</ul>';
        }
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
        $out .= '<p>The following creators or signatures matched your search term "'.htmlspecialchars($search).'":</p> <dl>';
        ksort($people);
        foreach ($people as $name => $array) {
            $out .= '<dt><b>'.htmlspecialchars($name). '</b></dt> <dd>';
            foreach ($array as $item) {
                $out .= '<dd>';
                $out .= '<a href="' . $item[0] . '">' . $item[1] . '</a>';
                if ($item[2] == 'creator') $out .= " (creator)";
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

