<?
// success.php:
// Success Stories
//
// Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: success.php,v 1.5 2007-10-11 19:24:27 matthew Exp $

require_once "../phplib/pb.php";
page_header('Success Stories', array('id'=>'success'));

# XXX i18n

$ref = get_http_var('ref');
if ($ref == 'airfarespledge') {
    story_airfarespledge();
} else {
    story_front();
}

function story_front() {
?>

<h2>Success Stories</h2>

<p>Here at PledgeBank, we&rsquo;re quite proud of the actions people have taken to
improve their communities. Each of the successful pledges on the site mark
something significant &ndash; trees planted, books distributed, friendships formed,
votes cast, money donated, letters written, and a whole host of other actions.
We&rsquo;re happy to say that people all over the world have created change and taken
initiative in a wide variety of ways, and would like to share some of our
favourite success stories with you.
</p>

<!-- <div>
BAKUL PICTURE
<h3><a href="/Bakul-Library">Bakul-Library</a></h3>
<p>Lots about the Bakul Library pledge here.</p>
</div> -->

<div>
<p><img align="right" src="/i/airfarespledge.tn.jpeg" style="margin: 0 0 0.5em 0.5em; border: solid 1px #666666;" title="My best friend in Biloxi MS" alt="The creator of the pledge with a friendly dog, tagged 595">
<h3><a href="/success/airfarespledge">Organise aid trip to New Orleans &ndash; airfarespledge</a></h3>
<p>&ldquo;After seeing the news about Hurricane Katrina, which had some pretty graphic
pictures showing the damage, I thought of how I could help....Being a fan
of Jazz music and knowing a lot about New Orleans, Harry Connick Jr was the
first person I thought of, I found connick.com to see if they had a donation
or fund raiser link.  A few of the members were donating their time and
money, and being an international person, donating money, just didn't work
for me.</p>
<p>&ldquo;My pledge was originally to raise some money to help pay for airfares to get
to New Orleans to help with Hurricane Katrina Relief
organisation CERMT (Emergency Relief Massage Therapy).</p>
<p><a href="/success/airfarespledge">Continue reading&hellip;</a></p>
</div>

<div>
<p><img align="left" style="margin:0 0.5em 0.5em 0" alt="Election observer Becky, outside a polling station"
src="http://www.openrightsgroup.org/wp-content/uploads/observers.jpg" width="195" height="200"></p>
<h3><a href="/electionwatch07">Gather people to monitor elections &ndash; electionwatch07</a></h3>
<p>&ldquo;Basically,
without PledgeBank I doubt we would have had nearly as many election observers
volunteer. The public nature of PledgeBank also seemed to encourage the very
high follow-through rate which meant that most people who signed up actually
did the election observation. The result of the pledge was that 25 people were
accredited by the Electoral Commission to observe elections in Scotland and
England. Their observations fed into
<a href="http://www.openrightsgroup.org/e-voting-main/" title="ORG's report into
the May 2007 elections">our 65-page report</a> which was recently
launched with significant press coverage.&rdquo;

<div>
<p><img align="right" style="margin: 0 0 0.5em 0.5em" alt="First1000 logo"
src="http://www.pledgebank.com/pics/First1000.gif" width="100" height="90"></p>
<h3><a href="/First1000">Get 1000 people to move house &ndash; First1000</a></h3>
<p>&ldquo;The membership of the
Free State Project was very active in recruiting signers, and as a result of
the pledge, 1000 people will be moving to New Hampshire by the end of 2008 in
order to become activists for liberty!&rdquo;
</div>

<div>
<h3><a href="http://promise.livesimply.org.uk/">Changing lifestyles &ndash; LiveSimply</a></h3>

<p>&ldquo;One of the main objectives for using the website
was evaluation of the livesimply initiative. The initiative is calling people
to change their lifestyle, which is a qualitative objective (i.e it's hard to
measure it with numbers). So having a website where you can get that colourful
data of people's thoughts, experience and opinions can provide us with some
insight in how people are changing their lives.
</div>

<p><img align="right" alt="LiveSimply logo"
src="http://promise.livesimply.org.uk/microsites/livesimply/promise_banner_right.jpg"
style="margin:0 0 0.5em 0.5em">
&ldquo;The features PledgeBank has were suitable for our needs - we wanted to
create a community of people who make promises so that they can support each
other. We could just go for a simpler system where people could only make a
promise. But we wanted to make sure that they feel that there is a bit more to
it. So the fact that PledgeBank requires you to make a promise with someone -
hence you need to promote it - and the fact that it has the system for nudging
people about their promise (reminding them about deadlines, the number of
sign-ups etc) is what made PledgeBank a very appropriate tool for us.

<p>&ldquo;Also, we wanted to use the system that was tested and which we wouldn't have
to customize too much. mySociety was my choice because I like the ethical
background of the organisation.&rdquo;
</div>

<div>
<p><img align="left" style="margin: 0 0.5em 0.5em 0" alt="Save The Sycamore"
width="150" height="200"
src="http://www.pledgebank.com/pics/SaveTheSycamore.jpeg"></p>
<h3><a href="/SaveTheSycamore">Campaign to save a local tree &ndash; SaveTheSycamore</a></h3>

<p>&ldquo;The amount
of elderly people who supported me in this pledge by writing and phoning the
council was phenomenal. Thank you so much for affording us a professional
launch pad. My next pledge will be to promote Pledgebank - I am so grateful. I
cannot thank you enough for giving me the opportunity to make a difference. I
truly am <strong>astounded</strong>!!!!!! With thanks from myself, family and my community.&rdquo;
</div>

<div>
<h3><a href="/trees4kenya">Raise money for charity &ndash; trees4kenya</a></h3>

<p>&ldquo;I've followed MySociety's projects for some time, and when PledgeBank was
announced, it sounded like a great idea so I got involved. Harnessing the power
of collective action is made much easier by this easy-to-use tool.

<p>&ldquo;Animal Aid has a large group of supporters, so we asked them by email to
support the Pledge, to get the ball rolling. Then we asked everyone who had
signed up to tell their friends and get them to sign up too. As we've done more
pledges, we'll email people who have signed up to related pledges telling them
about the new pledges we launch.

<p>&ldquo;As a result of the pledges, a vegetarian orphanage has an irrigation system,
and areas of land have been converted to food production, so that the
institution can be self-sufficient. We've also raised money for scientific
reports, and coordinated mass letter writing efforts to our elected
representatives.&rdquo;
</div>

<div>
<h3><a href="/give3hugsaday">Make people smile &ndash; give3hugsaday</a></h3>

<p>&ldquo;I started with a fairly simple pledge that most users would be able
to sign on to and just see what happens&hellip; I also was really intrigued
by the idea of power in numbers and how this could be used for something with
more significant, concrete results&hellip; I emailed it out to about 5 or 6
friends/family; and within a few hours 2 or 3 had joined. This was apparently
enough to get it shown to the public, and from there people kept signing.&rdquo;
</p>
</div>

<div>
<h3><a href="/showerpower">Help the environment &ndash; showerpower</a></h3>

<p>&ldquo;I heard about PledgeBank through reading Michael Norton's book 365
Ways to Change the World - I bought this book for my sister and I, as we found
that in an evening we would often fall into the habit of bemoaning the problems
of the world and feeling helpless to do anything about them. One of the major
environmental issues in Australia is the lack of water. my sister and I started
thinking if we personally could change our habits this would be a very small
step in the right direction - then we read about PledgeBank and thought if we
could get other individuals to change their habits this would start adding up!

<p>&ldquo;I suppose my great hope is that as individuals if we can regain a sense of
personal responsibility for our actions and connection to the external
environment, then the world would be a better place. As opposed to seeing these
major world problems as something someone needs to address, maybe we could have
a greater sense of our own ability to positively change the present and the
future.

<p>&ldquo;I must say that at first, a shorter shower and a bucket to move daily seemed
to be a sort of strange punishment! But as the days have gone by this daily
action has become something that gives me a great sense of joy and reconnection
to the world. As a household (there are four of us doing this) we have become
more interested in our garden, because rather than watering it absentmindedly
with a hose, we have to really look at what needs to be watered with our
bucket. I have started to notice the growth of each plant and also I now notice
the birds and animals that were probably there all the time but I was too busy
to notice! Instead of long shower, head to the city - it has become short
shower, time in the garden, before heading to the city.

<p>&ldquo;A few people I don't know somewhere in the world signed the
pledge&hellip;and that made me really happy.&rdquo;
</div>

<?

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

page_footer();

