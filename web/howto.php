<?
// howto.php:
// How to use PledgeBank for a specific activity.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: howto.php,v 1.7 2008-10-07 17:02:41 timsk Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

// This page is deliberately not translated -- we should write special
// page in each language specifically, to cover cultural differences.

$what = get_http_var('what');
if (!$what)
    err('Please specify what you want to know how to use PledgeBank for.', E_USER_NOTICE);

if ($what == 'football') {
    $heading = "Want to organise a kickabout?";
} elseif ($what == 'residents') {
    $heading = "Want to start a residents' association for your street, estate or building?";
} else {
    err('Unknown activity.');
}

page_header($heading);
print "<h2>$heading</h2>";

if ($what == 'football') {
?>
<img src="/howto_football.jpg" style="float: right; margin-left: 10px; margin-bottom: 10px" width="300" height="173" alt="">

<p>PledgeBank can help!</p>

<p>The way it works is simple - it lets people create pledges that say
"I'll do something, but only if 10 other people will". It's useful for
all sorts of things, from cleaning up your local park, to raising
money, to organising kickabouts.</p>

<p>All you have to do is create a pledge a bit like this one:</p>

<p>"I will arrange a weekly kickabout at Green Park, but only if  9 other
people (or more) will pledge to come along when they can"</p>

<p class="starthowto"><a href="/new/football">Start your own football pledge &nbsp;&raquo;</a></p>

<p>Once you've made your pledge, we help you spread the word.

<ol>
<li>We provide posters and flyers advertising your pledge.</li>
<li>People can sign up not just on line, but via SMS. Handy for
signing people up on the school bus, or at the office.</li>
</ol>

<p>Try it today!</p>

<p class="starthowto"><a href="/new/football">Start your own football pledge' &nbsp;&raquo;</a></p>

<?
} elseif ($what == 'residents') {
?>
<img src="/howto_residents.jpg" style="float: right; margin-left: 10px; margin-bottom: 10px" width="200" height="273" alt="">

<p>Any group of people living in the same area can set up a Residents'
Association; an Association can be a useful tool to help solve
problems in your local area, get funding to improve local facilities
and housing, and foster a sense of community.</p>

<p>PledgeBank is a free website service for getting things done in local
communities. One of the things it can help with is getting people who
don't know each other to feel comfortable with meeting up to start new
groups.</p>

<p>The way it works is simple. You use this site, PledgeBank to create a
pledge that says you'll get involved, but only if other people will
too. For example:</p>

<p>"I will hold a meeting to set up a Residents' Association and provide
tea and cake, but only if 5 local people will pledge to come along".</p>

<p class="starthowto"><a href="/new/residents">Click here to start a pledge like this &nbsp;&raquo;</a></p>

<p>Once you've set up your pledge, we help you to spread the word around
your neighbours:</p>

<ul>
<li> We give you printable leaflets and flyers to pin up or distribute in
your local area. </li>
<li> People can sign up via a cheap two word text message, so no internet
access is required. </li>
</ul>

<h3>More information?</h3>

<p>Why not read more information on how residents associations work, and
then create a pledge when you feel ready?</p>

<ul>
<li><a href="http://www.bbc.co.uk/dna/collective/A26100505" target="_new">How we set up a tenants and residents assocation</a> (BBC Action Network)</li>
</ul>

<p class="starthowto"><a href="/new/residents">Then, click here to get started &nbsp;&raquo;</a></p>

<?
} else {
    err('Unknown activity.');
}


page_footer();

?>
