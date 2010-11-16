<?
// ref-index.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//

pledge_draw_status_plaque($p);
debug_comment_timestamp("after draw_status_plaque()");
$p->render_box(array('showdetails' => true, 'reportlink' => true, 'showcontact' => true, 'id' => 'pledge_main'));
debug_comment_timestamp("after \$p->render_box()");
print '<div id="col2">';
if (!$p->finished())
    $p->sign_box();
else
    draw_connections_for_finished($p);
draw_spreadword($p);
debug_comment_timestamp("after draw_spreadword()");
if (microsites_comments_allowed())
    draw_comments($p);
print '</div>';
debug_comment_timestamp("after draw_comments()");
print '<div id="col1">';
draw_signatories($p);
debug_comment_timestamp("after draw_signatories()");
draw_connections($p);
debug_comment_timestamp("after draw_connections()");
print '</div>';

