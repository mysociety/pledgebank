<?
// index.php:
// Front page template for catcomm PledgeBank website.
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.264 2008-09-24 14:27:57 matthew Exp $

echo '<div id="tellworld">';
?>
    <h2>Tell the world &#8220;I&#8217;ll support communities working to solve local problems, but only if you will too!&#8221;</h2>
    <p>Catalytic Communities (CatComm) develops, inspires and empowers
    communities worldwide to generate and share their own local
    solutions. Imagine a world where community-generated solutions are
    just a mouse-click away, where anyone, anywhere, confronting a local
    problem, can find the inspiration and tools they need to implement
    the solution, learning from their peers. This site brings people
    together, forming a network of support for building this work...</p>
    For technical help, contact us at <a href="mailto:techhelp&#64;catcomm.org">techhelp&#64;catcomm.org</a>.
<?
echo '</div>';
debug_comment_timestamp("after tellworld");

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

?>

<div id="extrablurb">
<h2>About CatComm</h2>
<p>Catalytic Communities (<a href="http://www.catcomm.org">www.catcomm.org</a>) leverages peer-to-peer
support by gathering community leaders together to make change in
communities around the world. The organization offers, online and in
three languages (Portuguese, English and Spanish) a unique Community
Solutions Database, featuring over 125 community programs from 9
countries and visited by over 20,000 people each month. "Leaders in
squatter communities around the world don't have to reinvent the
wheel if they can locate and learn from peers elsewhere who've
tackled significant challenges like sewerage, unemployment, and HIV,"
explains Executive Director Theresa Williamson. They also run a
community technology center, the "Casa" in downtown Rio de Janeiro
that serves as a space for exchange among local leaders.</p>

<p>To learn more: visit our <a href="http://www.catcomm.org/">homepage</a>,
<a href="http://casacomcat.blogspot.com/">Casa Blog</a>, and latest
<a href="http://www.comcat.org/articles/e-news/english/02.htm">e-newsletter</a>.
</p>

<h2>You can help!</h2>

<p>Thanks to a partnership with PledgeBank,  you can post your own
pledge in support of communities working to solve local problems, worldwide!</p>

<ul>
<li> Pledge your financial commitment to grow this effort and
leverage your pledge to get others to join you! (Be a CatComm Champion!)</li>
<li> Pledge to provide tools and build capacity in support of
specific community projects that excite you, but only if others do, too!</li>
<li> Pledge to bring exposure to local solutions from your area by
posting them in the Community Solutions Database, if others will, too!</li>
<li> Pledge to research and reach out to community solutions in
regions still uncovered by the CatComm site, if others will join you!</li>
<li> Pledge to translate community projects into languages where
they could be inspirational, if others do, too!</li>
</ul>

</div>

<?

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

