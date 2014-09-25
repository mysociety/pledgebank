<?
// index.php:
// Front page template for main PledgeBank website.
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org

$banner_src = 'howitworks.png';
if ($lang == 'zh' || $lang == 'eo' || $lang == 'fr' || $lang == 'sk')
    $banner_src = 'howitworks_' . $lang . '.png';

if (! OPTION_NO_SUCCESS_STORIES) {
    echo '<div id="tellworld">';
    echo h2(_('PledgeBank successes'));
    global $success_summary;
    shuffle($success_summary);
    echo $success_summary[0];
    echo '<p align="right"><a href="/success">' . _('More success stories') . '</a></p>';
    echo '</div>';
    debug_comment_timestamp("after tellworld");
}

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

#$news = file_get_contents('http://www.mysociety.org/category/pledgebank/feed/');
$news = '';
if (preg_match('#<item>.*?<title>(.*?)</title>.*?<link>(.*?)</link>.*?<description><!\[CDATA\[(.*?)\]\]></description>#s', $news, $m)) {
    $link = str_replace('www.mysociety.org', 'www.pledgebank.com/blog', $m[2]);
    $excerpt = preg_replace('#\s+\[\.\.\.\]#', '&hellip; <a href="'
        . $link . '">' . _('more') . '</a>', $m[3]);
    echo '<div id="blogexcerpt">',
         h2(_('Latest from our blog')), ' <h3 class="head_with_mast">',
         $m[1], '</h3> <p class="head_mast">', $excerpt, '</div>';
}

echo '<div id="currentpledges">';
list_frontpage_pledges();
debug_comment_timestamp("after list_frontpage_pledges()");
echo '</div>';

if (! OPTION_COMMENTS_ON_NEW_ROW) {
    comments_show_latest();
    debug_comment_timestamp("after comments_show_latest()");
}

echo '</div>'; # col

if (OPTION_COMMENTS_ON_NEW_ROW) {
    comments_show_latest();
    debug_comment_timestamp("after comments_show_latest()");
}

echo '<div id="successfulpledges">';
list_closing_pledges();
list_successful_pledges();
echo '</div>';
debug_comment_timestamp("after list_successful_pledges()");

