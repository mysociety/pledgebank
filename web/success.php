<?
// success.php:
// Success Stories
//
// Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: success.php,v 1.7 2008-03-05 14:51:35 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/success.php';

page_header('Success Stories', array('id'=>'success'));

# XXX i18n

$ref = get_http_var('ref');
if ($ref == 'airfarespledge') {
    story_airfarespledge();
} else {
    story_front();
}
page_footer();

# ---

function story_front() {
    global $success_stories, $success_titles;
?>

<h2>Success Stories</h2>

<ul>
<?
    foreach ($success_titles as $link => $story) {
        print "<li><a href='#$link'>$story</a>";
    }
?>
</ul>

<p>Here at PledgeBank, we&rsquo;re quite proud of the actions people have taken to
improve their communities. Each of the successful pledges on the site mark
something significant &ndash; trees planted, books distributed, friendships formed,
votes cast, money donated, letters written, and a whole host of other actions.
We&rsquo;re happy to say that people all over the world have created change and taken
initiative in a wide variety of ways, and would like to share some of our
favourite success stories with you.
</p>

<?
    foreach ($success_stories as $story) {
        echo '<div>';
        echo $story['entry'];
        echo '</div>';
    }
}

function story_airfarespledge() {
?>

<h2>airfarespledge success story</h2>

<p>Fiona, one of the creators of the <a href="/airfarespledge">airfarespledge</a>,
writes to say:</p>

<p><img align="right" src="/i/airfarespledge.jpeg" style="margin: 0 0 0.5em 0.5em; border: solid 1px #666666;" title="My best friend in Biloxi MS" alt="The creator of the pledge with a friendly dog, tagged 595">
&ldquo;After seeing the news about Hurricane Katrina, which had some pretty graphic
pictures showing the damage, I thought of how I could help....Being a fan
of Jazz music and knowing a lot about New Orleans, Harry Connick Jr was the
first person I thought of, I found connick.com to see if they had a donation
or fund raiser link.  A few of the members were donating their time and
money, and being an international person, donating money, just didn't work
for me.

<p>&ldquo;My pledge was originally to raise some money to help pay for airfares to get
to New Orleans to help with Hurricane Katrina Relief
organisation CERMT (Emergency Relief Massage Therapy).  By now I had many
internet friends from the US who helped out a lot, donating either emotional help
or money, but one person, donated his flight points to fly us, first and
business class to the US.  Of course I thought it was too good to be true,
but, it just happend that the gentleman was extremly helpful and he arranged
most of our flight arragements. Including meeting us at the Dallas airport on the
way through.

<p>&ldquo;Unfortunately two months later, the plans with CERMT fell through as they were not efficient
enough to help, we had these wonderful donations and no where to go.  After
searching through several internet sites and many e-mails, we finally found
an organisation that were stable enough to match our plans, Hands on USA. It
was stressful enough for me because I have never travelled overseas and
didn't know what to expect and I have a son, who I was leaving for one
month, which was January, the month of his birthday! 

<p>&ldquo;As for the experience...I will describe it as an emotional rollercoaster.  Our
accomodation was "Camping out" in winter colder than we've had before, which,
we didn't mind at all. As long as we didn't have to bring our own tent.  We
helped many devistated families by cleaning up, helped stranded animals and
most of all helped the people that were helping others, by massage and
relaxation, we even found ourselves in the kitchen, cooking for 100 + people.
We also took part in the Martin Luther King jr parade, holding an Australian
flag, as we marched down the streets of MS.  People were crying and at the same
time cheering and thanking everyone from different US states who marched.  We
had no time to travel, but we took the opportunity to going on a road trip to
New York for a couple of days, where I saw snow for the first time. We stopped
off at Los Angeles on the way home for one night, but that was it. We had plans
to visit a group of volunteers in New Orleans, but, they let us down and left
us out in the open in the lower 9th ward, which was not safe.  We were lucky to
get some accomodation for the night though. 
 
<p>&ldquo;There is so much more to this story, but, if you are wanting to find out what we did,
you can <a href="/airfarespledge">contact me</a> through my pledge and
I will respond from you there. 
  
<p>&ldquo;I still keep in contact with people and have a friendship and would once again, like to thank everyone who helped.&rdquo;

<?
}

