<?
// faq.php:
// FAQ page for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: faq.php,v 1.21 2005-07-12 23:56:49 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

page_header(_("Frequently Asked Questions"));

############################################################################

print h2(_('Frequently Asked Questions'));
print "<dl>\n";

print dt(_('What is PledgeBank for?'));
print dd(_("PledgeBank is a site to help people get things done, especially things
that require several people. We think that the world needs such a
service: lots of good things don't happen because there aren't enough
organised people to do them."));
print dt(_('Can you give me some examples?'));
print dd(_("Sure. 'I will start recycling if 100 people in my town will do the
same'; 'I will organise my child's school play if 3 other parents will
help'; 'I will build a useful website if 1000 people promise to
contribute to it'."));

print dt(_('How does it work?'));

print dd(_("PledgeBank allows users to set up pledges and then encourages other
people to sign up to them. A pledge is a statement of the form 'I will
do something, if a certain number of people will help me do it'. The
creator of the pledge then publicises their pledge and encourages
people to sign up. One of two possible outcomes is possible - either
the pledge fails to get enough subscribers before it expires. In that
case, we contact everyone and tell them 'better luck next time'. But
the better possibility is that a pledge attracts enough people that
they are all sent a message saying 'Well done - now get going!' "));

print dt(_('What can I ask people to pledge?'));

print dd(_("Pretty much anything which isn't illegal, and which doesn't incite
people to commit illegal actions. Anything too obscene might also be
taken down - this is a family friendly site!"));

print dt(_('How can you be sure people will bother to carry out the pledge?'));

print dd(_("We can't; PledgeBank is based on a psychological bet. We believe that
if a person posesses a slight desire to do something, and then we help
connect them to a bunch of  people who also want to do the same thing
then that first person is much more likely to act. Our guess is that
this will work rather well in some areas of activity and not at all in
others - such is human nature."));

print dt(_('What do I get out of it?'));

print dd(_("As a well intentioned subscriber, you get insurance against being the
only person to show up to the demo the freezing rain or against being
the parent who discovers they've just volunteered to run the entire
school play on their own. As a pledge creator - you get a greatly
improved chance of achieving whatever change it is you want."));

print dt(_('Is it free?'));

print dd(_("The site is free to use, no matter how big a pledge you create. PledgeBank
is run by a charitable organisation, though, so if you want to run an
especially big pledge, and feel like making a contribution, please contact us.
SMS messages to PledgeBank cost your normal text fee."));

print "</dl>\n";

############################################################################

print h2(_('Organisation Questions'));
print "<dl>\n";

print dt(_('Who built PledgeBank?'));

print dd(_('This site was built by <a href="http://www.mysociety.org">mySociety</a>. 
mySociety is a charitable organisation which has grown out of this community of
volunteers who built sites like <a href="http://www.theyworkforyou.com">TheyWorkForYou.com</a>. 
mySociety\'s primary
mission is to build Internet projects which give people simple, tangible
benefits in the civic and community aspects of their lives. Our first project
was <a href="http://www.writetothem.com">WriteToThem.com</a>, where you can write to any of your
elected representatives, for free.'));

print dt(_('Who pays for it?'));

print dd(_('PledgeBank has been built by mySociety thanks to the effort
of a combination of paid core developers and unpaid volunteers. The
core developers were paid for by the 
<acronym title="Office of the Deputy Prime Minister">ODPM</acronym>\'s 
e-innovations fund in partnership with West Sussex County Council.'));

print dt(_('Do you need any help with the project?'));

print dd(_('Yes, we can use help in all sorts of ways, technical or non-technical.
Please <a href="/contact">contact us</a> if you want to get in touch.'));

print "</dl>\n";

############################################################################

print h2(_('Pledge Creators\' Questions'));
print "<dl>\n";

print dt(_('What sort of pledges work and what sort of pledges languish in obscurity?'));

print dd(_("We're not sure yet, but we reckon that there are some general rules
which will apply. First, the lower a pledge target (in terms of
numbers), the more likely it is to succeed. Second, the sheer energy
with which you push a pledge, whether your own or one you've
subscribed to, will have a big difference. If you are willing to take
a wireless enabled laptop from door to door, and get people to sign up
then and there, you're more likely to be successful than someone who
puts their pledge up and forgets about it."));

print dt(_('<a name="targets">How many people should I ask for?</a>'));

print dd(_("We recommend that you pick the lowest target you can possibly bear to.
Choose the number of people for which you could only just be bothered to carry
out your part of the pledge.  One more than the number where it wouldn't
be worth it.  Don't put the value any higher than that.  This makes it
most likely your pledge will succeed, and more people than you expected
can always sign up."));

print dt(sprintf(_('Why have you imposed a %d person cap?'), OPTION_PB_TARGET_CAP));

print dd(_("In order to focus people's minds on highly achievable pledges, we have
set this cap of maximum subscribers. We can raise the cap for individuals and
organisations who ask - just contact us if you want to launch a bigger pledge."));

print dt(_('Why does my new pledge not appear on the All Pledges page?'));

print dd(_("New pledges have just their own page, and are not shown elsewhere on
the site, until a few people have signed up to them. This is to make
sure we only show good quality pledges, which have an active creator
and some support behind them.  So get out there and tell your friends and
neighbours about your pledge!"));

print dt(_('Do you remove silly or illegal pledges?'));

print dd(_("PledgeBank reserves the right to 'backpage' any pledge which we
consider to be inappropriate. This means that your pledge will work, but will
not show up on the all page, or in the search. We will normally backpage
pledges which are really nothing more than advertising or propoganda, or which
are entirely frivolous. We will also delete pledges which promote or incite
illegal behaviour."));

print dt(_('<a name="editpledge">Why can\'t I modify my pledge after I\'ve made it?</a>'));

print dd(_("People who sign up to a pledge are signing up to the specific wording of
the pledge.  If you change the wording, then their signatures would no longer
be valid.  You can <a href=\"/contact\">contact us</a> if there is a cosmetic
change that you need to make.  For larger changes, you can create a new pledge,
and ask your subscribers if they want to move across.  There's a link to do
this from the 'Spread the word' section of your pledge page."));

print dt(_('Can I contact the people who have signed my pledge?'));

print dd(_("At any time, you can send a message to your signers so far.  Go 
to your pledge's page, and follow the link under 'Spread the word'.  For cost
reasons, this will not go to SMS signers.  If your pledge succeeds you will be
given a link to send a message to all your signers, including those who
signed up by text message.  You should give your contact details, and ask for
theirs, so you can stay in touch as you carry out your pledge.  If anybody
signs your pledge later, all your messages will be automatically forwarded to
them."));

print dt(_('How do I tell people to sign up by SMS?'));

print dd(sprintf(_("This is described on the flyers for your pledge, so the
easiest thing to do is print some out and carry them with you.  You can find
the flyers from the 'Spread the word' section on your pledge's page.  Otherwise
tell people to text <strong>pledge REFERENCE</strong> to
<strong>%s</strong>.  Here REFERENCE is the short reference for your pledge
which you chose when you made it, and which appears at the end of its
address."), OPTION_PB_SMS_DISPLAY_NUMBER));

print "</dl>\n";

############################################################################

print h2(_('International Questions'));
print "<dl>\n";

print dt(_('Can I put up pledges in languages other than English?'));

print dd(_("You can try! Fully internationalised versions of PledgeBank are
more than possible, but we can't do them without speakers of other languages.
If you are interested in volunteering to help translate the site, please
<a href=\"/translate\">read our translation help page</a>."));

print dt(_('Do I have to have a UK postcode to use this service?'));

print dd(_("No, although you get some extra, handy features if you are in the UK,
such as the ability to search for pledges close to your location.
Again, we'd be happy to talk to anyone who could help us extend these overseas
- please <a
href=\"http://www.mysociety.org/mailman/listinfo/mysociety-i18n\">join this
email list</a> to help us out.
"));

print "</dl>\n";

############################################################################

print h2(_('Privacy Questions'));
print "<dl>\n";

print dt(_("I don't want my name visible to everyone when I sign a pledge!"));

print dd(_("You can add yourself secretly to a pledge by unchecking the 'Show
my name on this pledge' box when you sign up.  Alternatively, you can sign
up by SMS.  If you are about to make a sensitive pledge, you may want to 
make it private, which means only people with a PIN you give them can
view the pledge page."));

print dt(_('Who gets to see my email address?'));

print dd(_("We will never disclose your email address to anyone, including the
creator of your pledge. We do let the pledge creators send emails to pledge
subscribers to explain what's going on, to motivate them etc. However, we don't
show the pledge creator the addresses of the subscribers getting his email. If
you reply to an email from the pledge creator yourself, you will give them your
email address - please be aware!"));

print dt(_('Will you send nasty, brutish spam to my email address?'));

print dd(_("Nope. After you sign up to a pledge we will send you emails in
relation to your pledge. These will be a mixture of status emails from
PledgeBank itself, and missives from the pledge creator, trying to chivvy you
into greater support.  It goes without saying that we'd never give or sell your
email addresses to anyone else."));

print "</dl>\n";

############################################################################

page_footer();
