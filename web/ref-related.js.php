<?php
/*
 * ref-similar.js.php:
 * Produce includable Javascript for showing similar pledges on another web page.
 * 
 * Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-related.js.php,v 1.4 2007-08-15 12:51:00 matthew Exp $
 * 
 */

require_once '../phplib/pb.php';
require_once '../phplib/pledge.php';
require_once '../commonlib/phplib/utility.php';
require_once '../phplib/js.php';

page_check_ref(get_http_var('ref'));
$p  = new Pledge(get_http_var('ref'));
microsites_redirect($p);
if (defined($p->pin()) && $p->pin() != '')
    err("Permission denied", E_USER_NOTICE);

$ref = $p->ref();
$url = $p->url_typein();
$connections = draw_connections($p);

$html = js_header('related pledges');
$html .= <<<EOF
<p>Pledges also signed by people who signed <a href="$url">$ref</a>:</p>
<ul>
$connections
</ul>
EOF;
$html .= js_footer();

js_output($html);

# XXX: Copy from ref-index, will factor out after testing
function draw_connections($p) {
    global $pb_today;
    $s = db_query("SELECT a_pledge_id, b_pledge_id, strength
            FROM pledge_connection
            LEFT JOIN pledges AS a_pledges ON a_pledge_id = a_pledges.id
            LEFT JOIN pledges AS b_pledges ON b_pledge_id = b_pledges.id
            WHERE
            (a_pledge_id = ? AND b_pledges.date >= '$pb_today' AND b_pledges.whensucceeded is null) or
            (b_pledge_id = ? AND a_pledges.date >= '$pb_today' AND a_pledges.whensucceeded is null)
            ORDER BY STRENGTH DESC
            LIMIT 8", array($p->id(), $p->id()));
    if (0 == db_num_rows($s))
        return;

    $out = '';
    while (list($a, $b, $strength) = db_fetch_row($s)) {
        $id = $a == $p->id() ? $b : $a;
        $p2 = new Pledge(intval($id));
        $out .= '<li><a href="' . $p2->url_typein() . '">' . $p2->h_title() . '</a>';
        $out .= ' (';
        $out .= sprintf(ngettext('%s person', '%s people', $strength), $strength);
        $out .= ')';
        $out .= '</li>';
    }
    if (!$out)
        $out = "<li>None, I'm afraid</li>";
    return $out;
}

?>
