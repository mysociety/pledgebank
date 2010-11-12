<?
// index.php:
// Front page template for main PledgeBank website.
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org

$banner_src = 'howitworks.png';
if ($lang == 'zh' || $lang == 'eo' || $lang == 'fr' || $lang == 'sk')
    $banner_src = 'howitworks_' . $lang . '.png';

echo '<div id="tellworld">';
echo '</div>';

echo '<div id="col">';

echo '<div id="startblurb1"><div id="startblurb">
<div id="startbubble"></div>
<p id="start"><a href="./new"><strong>', _('Start your pledge'), '&nbsp;&raquo;</strong></a></p>
<ul>
<li>' . _('Get your own page')
. '<li>' . _('Help with promotion')
. '<li>' . _('Use positive peer pressure to change your community')
. '</ul>
</div></div>';

echo '<div id="currentpledges">';
list_frontpage_pledges();
debug_comment_timestamp("after list_frontpage_pledges()");
echo '</div>';

comments_show_latest();
debug_comment_timestamp("after comments_show_latest()");

echo '</div>'; # col

echo '<div id="successfulpledges">';
list_closing_pledges();
list_successful_pledges();
echo '</div>';
debug_comment_timestamp("after list_successful_pledges()");

