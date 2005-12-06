<?
// howto.php:
// How to use PledgeBank for a specific activity.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: howto.php,v 1.2 2005-12-06 18:35:04 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

// This page is deliberately not translated -- we should write special
// page in each language specifically, to cover cultural differences.

$what = get_http_var('what');
if (!$what)
    err('Please specify what you want to know how to use PledgeBank for.');

if ($what == 'football') {
    $heading = "Start your local football team";
} elseif ($what == 'residents') {
    $heading = "Start a residents association";
} else {
    err('Unknown activity.');
}

page_header($heading);
print "<h2>$heading</h2>";

if ($what == 'football') {
?>
<img src="/howto_football.jpg" style="float: right; margin-left: 10px; margin-bottom: 10px" width="300" height="173" alt="">

   <p>Want to start your local football team?  Don't want to be the only one
    who shows up at the pitch come Sunday morning? PledgeBank can help!</p>

   <p>The way it works is simple. You create a pledge that has the basic
   format "I'll do something but only if a number X of other people will
   pledge to do the same thing by this day." If setting up a local
   football match you are after, you could organise a pledge that says
   "I'll play footie in the park every Sunday, but only if 21 people in
   my neighbourhood show up too".</p>

    <p id="start"><a href="./new/football"><?=_('Start your own football pledge') ?>&nbsp;&raquo;</a></p>

   <p>We have developed PledgeBank to not just start your Pledge but help
   you run it too! We believe there are people around you with the same
   ideas and interests; we just want to help you all find each other.</p>

   <p>To help you find your fellow footballers:</p>

   <ul>
    <li> You get an easy to remember website address to advertise, be it on
       flyers under your neighbours' doors, posters or emails to friends
       you think would be interested. (say
       www.pledgebank.com/SundayFootball). It will contain your Pledge,
       its due date and people who have pledged with you. This will give
       you a central communication point to organise your efforts around! </li>

    <li> Your friends can sign up to your pledge online or through a simple
       two-word text message! (UK only) </li>

    <li> Sport enthusiasts from your neighbourhood can see your pledge and
       join. PledgeBank allows its users to search based on interest and
       geographical location! </li>

    <li> Subscribers from your neighbourhood will receive an email update
       regarding your pledge and the opportunity to join you! </li>

    <li> You can contact fellow players through PledgeBank and see your
       idea through! </li>
   </ul>

   <p>PledgeBank is free and easy to use. And if you wish, it can help you
   organise more than just football. It could be useful
   if you are considering a neighbourhood watch scheme, a residents
   association or even a street party.</p>

   <p>Try it today!</p>

    <p class="align: left" id="start"><a href="./new/football"><?=_('Start your own football pledge') ?>&nbsp;&raquo;</a></p>

<?
} elseif ($what == 'residents') {
?>
<img src="/howto_residents.jpg" style="float: right; margin-left: 10px; margin-bottom: 10px" width="200" height="273" alt="">
<?
} else {
    err('Unknown activity.');
}


page_footer();

?>
