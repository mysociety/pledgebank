<?
// index.php:
// Front page template for main PledgeBank website.
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org

$banner_src = 'howitworks.png';
if ($lang == 'zh' || $lang == 'eo' || $lang == 'fr' || $lang == 'sk')
    $banner_src = 'howitworks_' . $lang . '.png';

if (! microsites_no_success_stories()) {
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

if (!microsites_comments_on_new_row()) {
    comments_show_latest();
    debug_comment_timestamp("after comments_show_latest()");
}

echo '</div>'; # col

if (microsites_comments_on_new_row()) {
    comments_show_latest();
    debug_comment_timestamp("after comments_show_latest()");
}

echo '<div id="successfulpledges">';
list_closing_pledges();
list_successful_pledges();
echo '</div>';
debug_comment_timestamp("after list_successful_pledges()");

