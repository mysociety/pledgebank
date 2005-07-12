<?
// search.php:
// Search for a pledge.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: search.php,v 1.19 2005-07-12 12:52:37 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/comments.php';
require_once '../../phplib/mapit.php';

page_header(_("Search Results"));
search();
page_footer();

function search() {
    $search = trim(get_http_var('q'));
    $success = 0;

    // Exact pledge reference match
    $pledge_select = 'SELECT pledges.*, pb_current_date() <= pledges.date as open,
                (SELECT count(*) FROM signers WHERE pledge_id=pledges.id) AS signers,
                date - pb_current_date() AS daysleft';

    $q = db_query("$pledge_select FROM pledges WHERE pin is NULL AND ref ILIKE ?", $search);
    if (db_num_rows($q)) {
        $success = 1;
        $r = db_fetch_array($q);
        print sprintf(p(_('Result <strong>exactly matching</strong> pledge <strong>%s</strong>:')), htmlspecialchars($search) );
        print '<ul><li>';
        print pledge_summary($r, array('html'=>true, 'href'=>$r['ref']));
        print '</li></ul>';
    }

    // Postcodes
    if (validate_postcode($search))  {
        $location = mapit_get_location($search);
        if (mapit_get_error($location)) {
            print "<p>We couldn't find that postcode, please check it again.</p>";
        } else {
            $q = db_query($pledge_select . ', distance
                        FROM pledge_find_nearby(?,?,?) AS nearby 
                        LEFT JOIN pledges ON nearby.pledge_id = pledges.id
                        WHERE 
                            pin IS NULL AND
                            pledges.prominence <> \'backpage\'
                        ORDER BY distance', array($location['wgs84_lat'], $location['wgs84_lon'], 50)); // 50 miles. XXX Should be indexed with wgs84_lat, wgs84_lon; ordered by distance?
            $closed = ''; $open = '';
            if (db_num_rows($q)) {
                print sprintf(p(_('Results for pledges near UK postcode <strong>%s</strong>:')), htmlspecialchars(strtoupper($search)) );
                $success = 1;
                print '<ul>';
                while ($r = db_fetch_array($q)) {
                    print '<li>';
                    if (round($r['distance'],0) < 1) 
                        print '<strong>under 1 km</strong> away: ';
                    else
                        print '<strong>' . round($r['distance'],0) . " km</strong> away: ";
                    print pledge_summary($r, array('html'=>true, 'href'=>$r['ref']));
                    print '</li>';
                }
                print '</ul>';
            }
        }
    }
 
    // Searching for similar pledge references, or text in pledges
    $q = db_query($pledge_select . ' FROM pledges 
                WHERE pin IS NULL 
                    AND pledges.prominence <> \'backpage\'
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
        print sprintf(p(_('Results for <strong>open pledges</strong> matching <strong>%s</strong>:')), htmlspecialchars($search) );
        print '<ul>' . $open . '</ul>';
    }

    if ($closed) {
        print sprintf(p(_('Results for <strong>closed pledges</strong> matching <strong>%s</strong>:')), htmlspecialchars($search) );
        print '<ul>' . $closed . '</ul>';
    }

    // Comments
    $comments_to_show = 10;
    $q = db_query('SELECT comment.id,
                          extract(epoch from whenposted) as whenposted,
                          text,comment.name,website,ref 
                   FROM comment,pledges 
                   WHERE pin IS NULL
                        AND comment.pledge_id = pledges.id 
                        AND NOT ishidden 
                        AND text ILIKE \'%\' || ? || \'%\'
                   ORDER BY whenposted DESC', array($search));
    if (db_num_rows($q)) {
        $success = 1;
        print sprintf(p(_("Results for <strong>comments</strong> matching <strong>%s</strong>:")), htmlspecialchars($search) );
        print '<ul>';
        while($r = db_fetch_array($q)) {
            print '<li>';
            print comments_summary($r);
            print '</li>';
        }
        print '</ul>';
    }

    // Signers and creators (NOT people, as we only search for publically visible names)
    $people = array();
    $q = db_query('SELECT ref, title, name FROM pledges WHERE pin IS NULL AND name ILIKE \'%\' || ? || \'%\' ORDER BY name', $search);
    while ($r = db_fetch_array($q)) {
        $people[$r['name']][] = array($r['ref'], $r['title'], 'creator');
    }
    $q = db_query('SELECT ref, title, signers.name FROM signers,pledges WHERE showname AND pin IS NULL AND signers.pledge_id = pledges.id AND signers.name ILIKE \'%\' || ? || \'%\' ORDER BY name', $search);
    while ($r = db_fetch_array($q)) {
        $people[$r['name']][] = array($r['ref'], $r['title'], 'signer');
    }
    if (sizeof($people)) {
        $success = 1;
        print sprintf(p(_('Results for <strong>people</strong> matching <strong>%s</strong>:')), htmlspecialchars($search) );
        print '<dl>';
        ksort($people);
        foreach ($people as $name => $array) {
            print '<dt><b>'.htmlspecialchars($name). '</b></dt> <dd>';
            foreach ($array as $item) {
                print '<dd>';
                print '<a href="' . $item[0] . '">' . $item[1] . '</a>';
                if ($item[2] == 'creator') print _(" (creator)");
                print '</dd>';
            }
        }
        print '</dl>';
    }

    if (!$success) {
        print sprintf(p(_('Sorry, we could find nothing that matched "%s".')), htmlspecialchars($search) );
    }

    if (validate_postcode($search)) {
        $email = '';
        $P = person_if_signed_on();
        if (!is_null($P)) {
            $email = $P->email();
        } 
?>
<form accept-charset="utf-8" id="localsignupsearch" name="localalert" action="/alert" method="post">
<input type="hidden" name="subscribe_local_uk_alert" value="1">
<p><strong><?=_('Get daily email about new local pledges') ?> &mdash;</strong>
<label for="email"><?=_('Email:') ?></label><input type="text" size="18" name="email" id="email" value="<?=htmlspecialchars($email) ?>">
<label for="postcode"><?=_('UK Postcode:') ?></label><input type="text" size="12" name="postcode" id="postcode" value="<?=htmlspecialchars($search)?>">
<input type="submit" name="submit" value="<?=_('Subscribe') ?>"> </p>
</form>
<?
    }
}

