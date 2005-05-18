<?
// faq.php:
// FAQ page for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: faq.php,v 1.5 2005-05-18 19:55:14 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

page_header("Frequently Asked Questions");

?>
<h2>Frequently Asked Questions</h2>

<dl>

<dt>What is PledgeBank for?

<dd>PledgeBank is a site to help people get things done, especially things
that require several people. We think that the world needs such a
service: lots of good things don't happen because there aren't enough
organised people to do them.

<dt>Can you give me some examples?

<dd>Sure. 'I will start recycling if 100 people in my town will do the
same'; 'I will organise my child's school play if 3 other parents will
help'; 'I will build a useful website if 1000 people promise to
contribute to it'.

<dt>How does it work?

<dd>PledgeBank allows users to set up pledges and then encourages other
people to sign up to them. A pledge is a statement of the form 'I will
do something, if a certain number of people will help me do it'. The
creator of the pledge then publicises their pledge and encourages
people to sign up. One of two possible outcomes is possible - either
the pledge fails to get enough subscribers before it expires. In that
case, we contact everyone and tell them 'better luck next time'. But
the better possibility is that a pledge attracts enough people that
they are all sent a message saying 'Well done - now get going!' 

<dt>What can I ask people to pledge?

<dd>Pretty much anything which isn't illegal, and which doesn't incite
people to commit illegal actions. Anything too obscene might also be
taken down - this is a family friendly site!

<dt>How can you be sure people will bother to carry out the pledge?

<dd>We can't; PledgeBank is based on a psychological bet. We believe that
if a person posesses a slight desire to do something, and then we help
connect them to a bunch of  people who also want to do the same thing
then that first person is much more likely to act. Our guess is that
this will work rather well in some areas of activity and not at all in
others - such is human nature.

<dt>What do I get out of it?

<dd>As a well intentioned subscriber, you get insurance against being the
only person to show up to the demo the freezing rain or against being
the parent who discovers they've just volunteered to run the entire
school play on their own. As a pledge creator - you get a greatly
improved chance of achieving whatever change it is you want.

<dt>Is it free?

<dd>Other than a 25p charge for SMS subscribers (to cover our costs of
sending update SMSes back to them), the site is free to use, no matter
how big a pledge you create. PledgeBank is run by a charitable
organisation, though, so if you want to run an especially big pledge,
and feel like making a contribution, please contact us.

<dt>Who built PledgeBank?

<dd>This site was built by <a href="http://www.mysociety.org">mySociety</a>. 
mySociety is a charitable organisation which has grown out of this community of
volunteers who built sites like <a href="http://www.theyworkforyou.com">TheyWorkForYou.com</a>. 
mySociety's primary
mission is to build Internet projects which give people simple, tangible
benefits in the civic and community aspects of their lives. Our first project
was <a href="http://www.writetothem.com">WriteToThem.com</a>, where you can write to any of your
elected representatives, for free.

<dt>Who pays for it?

<dd>PledgeBank has been built under the by mySociety thanks to the effort
of a combination of paid core developers and unpaid volunteers. The
core developers were paid for by the 
<acronym title="Office of the Deputy Prime Minister">ODPM</acronym>'s 
e-innovations fund in partnership with West Sussex County Council.

<dt>What sort of pledges work and what sort of pledges languish in obscurity?

<dd>We're not sure yet, but we reckon that there are some general rules
which will apply. First, the lower a pledge target (in terms of
numbers), the more likely it is to succeed. Second, the sheer energy
with which you push a pledge, whether your own or one you've
subscribed to, will have a big difference. If you are willing to take
a wireless enabled laptop from door to door, and get people to sign up
then and there, you're more likely to be successful than someone who
puts their pledge up and forgets about it.

<dt>Why have you imposed a 200 person cap?

<dd>In order to focus people's minds on highly achievable pledges, we have
set this relatively low cap of maximum subscribers. We can raise the
cap for individuals and organisations who ask - just contact us if you
want to launch a bigger pledge.

<dt>How many people should I ask for?

<dd>We recommend that you pick the lowest target you can possibly bare to.
Choose the number of people for which you could only just be bothered to carry
out your part of the pledge.  One less it wouldn't be worth it.  Don't put the
value any higher than that.  This makes it most likely your pledge will succeed,
and more people than you expected can always sign up.

<dt><a name="editpledge">Why can't I edit my pledge after I've made it?</a>

<dd>People who sign up to a pledge are signing up to the specific wording of
the pledge.  If you change the wording, then their signatures would no longer
be valid.  You can <a href="/contact">contact us</a> if there is a cosmetic
change that you need to make.

<dt>Who gets to see my email address?

<dd>We will never disclose your email address to anyone, including the
creator of your pledge. We do let the pledge creators send a certain,
fixed number of emails to pledge subscribers to explain what's going
on, to motivate them etc. However, we don't show the pledge creator
the addresses of the subscribers getting his email. If you reply to an
email from the pledge creator yourself, you will give them your email
address - please be aware!

<dt>Will you send nasty, brutish spam to my email address?

<dd>Nope. When you sign up to a pledge we will tell you a specific maximum
number of emails you'll ever be sent in relation to your pledge. These
will be a mixture of status emails from WriteToThem itself, and
missives from the pledge creator, trying to chivvy you into greater
support. Thereafter you'll never be emailed again. And it goes without
saying that we'd never give or sell your email addresses to anyone
else.

<dt>Do you need any help with the project?

<dd>Yes, we can use help in all sorts of ways, technical or non-technical.
Please contact us if you want to get in touch.

<dt>Can I put up pledges in languages other than English?

<dd>You can try! Fully internationalised versions are more than possible,
but we can't do them without speakers of other languages. If you are
interested in volunteering to help translate the site, please contact
us.

<dt>Do I have to be British to use this service?

<dd>No, although you get some extra, handy features if you are in the UK,
such as the ability to search for pledges close to your location.
Again, we'd be happy to talk to anyone who could help us extend these
overseas - do contact us.

<dt>Can I modify a pledge once people have signed up to it?

<dd>No, because you'd be changing the nature of what they'd agreed to.
What you can do is create a new pledge, and ask your subscribers if
they want to move across. As a pledge creator this means just sending
a mail to your subscribers using the link that you were sent by email.

</dl>
<?

page_footer();
