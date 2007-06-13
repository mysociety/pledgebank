<?php
/*
 * ref-similar.js.php:
 * Produce includable Javascript for showing similar pledges on another web page.
 * 
 * Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-related.js.php,v 1.1 2007-06-13 16:40:44 matthew Exp $
 * 
 */

require_once '../phplib/pb.php';
require_once '../phplib/pledge.php';
require_once '../../phplib/utility.php';

page_check_ref(get_http_var('ref'));
$p  = new Pledge(get_http_var('ref'));
microsites_redirect($p);
if (defined($p->pin()) && $p->pin() != '')
    err("Permission denied");

$ref = $p->ref();
$url = $p->url_typein();

$html = <<<EOF
<div class="pledgebank_pledgebox" style="float:left;border:solid 1px #522994;font-family:'Lucida Grande','Lucida Sans Unicode','Lucida Sans',Arial,sans-serif;width:17em;font-size:83%;margin-bottom:1em">
<div style="background-color:#9c7bbd;color:#ffffff;border-bottom:solid 1px #522994;padding:2px">
<a href="http://www.pledgebank.com/" style="font-weight:bold;text-decoration:none">
<span style="color:#ffffff;background-color:#9c7bbd;">Pledge</span><span
 style="color:#21004a;background-color:#9c7bbd;">Bank</span></a> related pledges
</div>
<div style="padding:2px">
<p style="margin:0">
Pledges also signed by people who signed
<a style="color:#522994;" href="$url">$ref</a>:</p>
<ul style="margin:0;padding: 2px 2px 2px 1.5em;">
EOF;
$html .= draw_connections($p);
$html .= '</ul></div>';

header('Content-Type: text/javascript; charset=utf-8');
$html = addslashes($html);  /* XXX check this works with UTF-8 and is correct
                             * for JS. */
$html = preg_replace("/\n/s", '\\n', $html);
print "<!--\ndocument.write(\"$html\");\n//-->\n";

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
        $out .= '<li><a style="color:#522994;" href="' . $p2->url_typein() . '">' . $p2->h_title() . '</a>';
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
