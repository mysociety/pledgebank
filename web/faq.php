<?
// faq.php:
// FAQ page for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: faq.php,v 1.58 2007-09-25 16:26:20 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

############################################################################

function default_faq() {
    global $microsite, $lang;

    print h2(_('Frequently Asked Questions'));
    print "<dl>\n";

    # XXX move this question into microsites.php
    if ($microsite && $microsite == 'london') {
        print dt('What is PledgeBank London for?');
        print dd("PledgeBank London is a site to help Londoners to work together to
        improve the quality of life in their city. It is based on PledgeBank,
        a global website, but it only shows Pledges specific to London.");
        
        print dt(_('What does PledgeBank do for me? Why should I use it?'));
        print dd(_('PledgeBank gives you the reassurance that if you decide to do
        something for the wider community you won\'t be doing it alone.'));
    } else {
        print dt(_('What is PledgeBank for?'));
        print dd(_("PledgeBank is a site to help people get things done, especially things
        that require several people. We think that the world needs such a
        service: lots of good things don't happen because there aren't enough
        organised people to do them."));
    }

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
    people to sign up. Two outcomes are possible &ndash; either
    the pledge fails to get enough subscribers before it expires (in which
    case, we contact everyone and tell them 'better luck next time'), or,
    the better possibility, the pledge attracts enough people that
    they are all sent a message saying 'Well done&mdash;now get going!'"));

    print dt(_('What can I ask people to pledge?'));

    print dd(_("Pretty much anything which isn't illegal, and which doesn't incite
    people to commit illegal actions. Anything too obscene might also be
    taken down&mdash;this is a family friendly site!"));

    print dt(_('How can you be sure people will bother to carry out the pledge?'));

    print dd(_("We can't; PledgeBank is based on a psychological bet. We believe that
    if a person possesses a slight desire to do something, and then we help
    connect them to a bunch of people who also want to do the same thing,
    then that first person is much more likely to act. We have some
    <a href='/success'>success stories</a> for a variety of pledges from the site,
    and we have also
    surveyed many of the money-based pledges that have succeeded, and found that
    payment rates vary from 50% to well over 150%, with three-quarters of 
    people paying being typical."));

    print dt(_('What do I get out of it?'));

    print dd(_("As a well intentioned subscriber, you get insurance against being the
    only person to show up to the demo in the freezing rain or against being
    the parent who discovers they've just volunteered to run the entire
    school play on their own. As a pledge creator&mdash;you get a greatly
    improved chance of achieving whatever change it is you want."));

    print dt(_('Is it free?'));

    print dd(sprintf(_("The site is free to use, no matter how big a pledge you create. PledgeBank
    is run by a charitable organisation, though, so if you want to run an
    especially big pledge, and feel like making a contribution, please
    <a href=\"/contact\">contact us</a> or
    <a href=\"https://secure.mysociety.org/donate/\">make a donation directly</a>.
    SMS messages to PledgeBank (available in %s only) cost your normal text fee."),
    sms_countries_description()));

    print "</dl>\n";

    ############################################################################

    print h2(_('Organisation Questions'));
    print "<dl>\n";

    print dt(_('Who built PledgeBank?'));

    print dd(_('This site was built by <a href="http://www.mysociety.org">mySociety</a>. 
    mySociety is a charitable organisation in the United Kingdom, which has grown out of a community of
    volunteers who built sites like <a href="http://www.theyworkforyou.com">TheyWorkForYou.com</a>. 
    mySociety\'s primary
    mission is to build Internet projects which give people simple, tangible
    benefits in the civic and community aspects of their lives. Our first project
    was <a href="http://www.writetothem.com">WriteToThem.com</a>, where UK citizens can write to any of their
    elected representatives, for free.'));

    print dt(_('Who pays for it?'));

    $paid = _('PledgeBank has been built by mySociety thanks to the effort
    of a combination of paid core developers and unpaid volunteers. The
    core developers were paid for by the 
    <acronym title="Office of the Deputy Prime Minister">ODPM</acronym>\'s 
    e-innovations fund in partnership with West Sussex County Council.');
    if ($lang == 'en-gb')
        $paid .= ' The <a href="http://www.williampears.co.uk/found.htm">Pears Foundation</a> sponsored the <a href="http://london.pledgebank.com/">London version of PledgeBank</a>.';
    print dd($paid);

    print dt(_('Do you need any help with the project?'));

    print dd(_('Yes, we can use help in all sorts of ways, technical or non-technical.
    Please <a href="/contact">contact us</a> if you want to get in touch.'));

    print dt(_('Where\'s the "source code" to PledgeBank?'));

    print dd(_('The software behind PledgeBank is open source, and available to you
    mainly under the Affero GPL software license. You can <a
    href="https://secure.mysociety.org/cvstrac/dir?d=mysociety">download the source
    code</a> (look under \'pb\') and help us develop it. You\'re welcome to use it
    in your own projects, although you must also make available the source code to
    any such projects.
    '));

    print dt(_('People build things, not organisations. Who <em>actually</em> built it?'));

    print dd(_('OK, we are
    Mike Bracken,
    Edmund von der Burg,
    James Cronin,
    Francis Irving,
    Chris Lightfoot,
    Etienne Pollard,
    Richard Pope,
    Matthew Somerville,
    Tom Steinberg.
    '));

    print dd(_('<strong>Translations by</strong>
    Nic Dafis (Welsh),
    Hugo Caballero Figueroa (Spanish),
    Tim Morley (Esperanto),
    Diego Galli (Italian),
    LaPingvino and Leo De Cooman (Dutch),
    Creso Moraes (Brazilian Portuguese),
    Alexander Markushin (Russian),
    maidan.org.ua team (Ukrainian),
    Hugo Lamoureux (French),
    Oliver Ding, Isaac Mao, Danny Yu, Nan Yang, Jacky Peng (Chinese),
    Aliaksej Lavońčyk (Belarusian).
    '));

    print dd(_('<strong>Thanks also to</strong>
    <a href="http://www.tangentlabs.co.uk/">Tangent Labs</a> (for SMS in the UK), 
    the <a href="http://earth-info.nga.mil/gns/html/">US military</a> (for the world gazetteer),
    <a href="http://sedac.ciesin.columbia.edu/gpw/">CIESIN</a> (for population density data),
    <a href="http://www.ordnancesurvey.co.uk">Ordnance Survey</a> (for UK postcodes),
    Ben Furber for our favicon, 
    the entire free software community (FreeBSD, Linux, PHP, Perl, Python, Apache,
    MySQL, PostgreSQL, we love and use you all!) and
    <a href="http://www.easynet.net/publicsector/">Easynet</a> (who kindly host all
    our servers).

    Let us know if we\'ve missed anyone.

    '));


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

    /*
    print dt(sprintf(_('Why have you imposed a %d person cap?'), OPTION_PB_TARGET_CAP));

    print dd(_("In order to focus people's minds on highly achievable pledges, we have
    set this cap of maximum subscribers. We can raise the cap for individuals and
    organisations who ask - just contact us if you want to launch a bigger pledge."));
    */

    print dt('<a name="allpledges">'._('Why does my new pledge not appear on the All Pledges page?').'</a>');

    print dd(_("New pledges have just their own page, and are not shown elsewhere on
    the site, until a few people have signed up to them. This is to make
    sure we only show good quality pledges, which have an active creator
    and some support behind them.  So get out there and tell your friends and
    neighbours about your pledge!"));

    print dt(_('Do you remove silly or illegal pledges?'));

    # TRANS: 'backpage' means the pledge is hidden, so you could translate it as "hide"
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
    to your pledge's page, and follow the link under 'Spread the word'.
    If your pledge succeeds you will be
    given a link to send a message to all your signers, including those who
    signed up by text message.  You should give your contact details, and ask for
    theirs, so you can stay in touch as you carry out your pledge.  If anybody
    signs your pledge later, the last message that you sent will be automatically
    forwarded to them, and they will be given a link to read older messages."));

    print dt(_('Can people sign up by SMS (text message), and if so how?'));

    print dd(sprintf(_("Yes, although only in %s. If you'd like to help us organise
    SMS for other countries, please <a href=\"/contact\">contact us</a>. The flyers for your pledge
    describe how to sign up by SMS. So the easiest way to remember is to print some
    out and carry them with you.  You can find the flyers from the 'Spread the
    word' section on your pledge's page.  Otherwise, for people in %s, it says what to text and
    where to text it to at the bottom of each relevant pledge signup box."), 
    sms_countries_description(), sms_countries_description()));

    print dt(_('How do I add a picture to my pledge?'));

    print dd(_("Go to your pledge page and click 'Add a picture to your pledge
    (creator only)' under 'Spread the word on and offline'. Either find a picture
    of something to do with your pledge, or even just use a photo of yourself!"));

    print dt(_('Can you make a special version of PledgeBank for my organisation?'));

    print dd(_("Yes. We can group your pledges together, and alter the logo,
    colours or complete style to match your branding. We can intimately
    link PledgeBank with the rest of your site in any way you imagine. 
    We can make it public to the world, or private just for you.
    We normally charge for doing this, via our commercial subsidiary (any profits
    go back into mySociety). <a href=\"/contact\">Contact us</a> saying a bit about
    what you are thinking of doing. There's a list of example special sites at the
    bottom of <A href=\"/where\">this page</a>."));

    print "</dl>\n";

    ############################################################################

    print h2(_('International Questions'));
    print "<dl>\n";

    print dt(_('Can I put up pledges in languages other than English?'));

    print dd(_("Yes! We have several fully internationalised versions of PledgeBank.
    If your browser is configured to use one of those languages, PledgeBank will 
    automatically appear in them. Otherwise, follow one of the links at the
    bottom of each page.  If you are interested in volunteering to help translate
    the site into your language, please <a href=\"/translate\">read our translation
    help page</a>."));

    print dt(_('What countries does PledgeBank work in?'));

    print dd(_("All of them. We try to detect which country you are in
    automatically, and show you only pledges relevant to your country. To see what
    PledgeBank looks like in other countries, click on '(change)' at the top of any
    page, just right of the PledgeBank logo.  Please <a href=\"/contact\">contact
    us</a> if you'd like to help get more people in your country using PledgeBank."));

    print dt(_('Why do you only have UK postcodes?'));

    print dd(_("We'd be happy to talk to anyone who could help us extend
    the postcode search overseas&mdash;please <a
    href=\"https://secure.mysociety.org/admin/lists/mailman/listinfo/internationalisation\">join this
    email list</a> to help us out.
    "));

    print dt(_('... and SMS?'));

    print dd(_("Again, please <a href=\"/contact\">contact us</a> if you
    can help add text messaging in your country."));

    print "</dl>\n";

    ############################################################################

    print h2('<a name="privacy">'._('Privacy Questions').'</a>');
    print "<dl>\n";

    print dt(_("I don't want my name visible to everyone when I sign a pledge!"));

    print dd(sprintf(_("You can add yourself secretly to a pledge by unchecking the 'Show
    my name on this pledge' box when you sign up.  Alternatively, you can sign
    up by SMS (in %s only).  If you are about to make a sensitive pledge, you may want to 
    make it private, which means only people with a PIN you give them can
    view the pledge page."), sms_countries_description()));

    print dt(_('Who gets to see my email address?'));

    print dd(_("We will never disclose your email address to anyone, including the
    creator of your pledge, unless we are obliged to by law. We do let the pledge
    creators send emails to pledge subscribers to explain what's going on, to
    motivate them etc. However, we don't show the pledge creator the addresses of
    the subscribers getting their email. If you reply to an email from the pledge
    creator yourself, you will give them your email address&mdash;please be
    aware!"));

    print dt(_('Will you send nasty, brutish spam to my email address?'));

    print dd(_("Nope. After you sign up to a pledge we will send you emails in
    relation to your pledge. These will be a mixture of status emails from
    PledgeBank itself, and missives from the pledge creator, trying to encourage you
    into greater support. We will never give or sell your email addresses to anyone
    else, unless we are obliged to by law."));

    print "</dl>\n";
}

############################################################################

function livesimply_faq() {
    print h2(_('Frequently Asked Questions'));
    ?>

<p>Do also see the <a href="/terms">Terms and Conditions</a>.</p>

<dl>

<dt>What is <em>live</em>simply:promise for? </dt>
<dd>Making a <em>live</em>simply promise shows that you want to live more simply, sustainably, and in solidarity with people who are poor. This online action helps you join a promise and get other people to carry it out with you. Making a promise is about making the world a better place for all of us. </dd>

<dt>Can you give me some examples of promises? </dt>
<dd>Sure. "I, Jo Bloggs, promise to live simply, sustainably and in solidarity with people who are poor communities by ... but I'd like 30 other people to join me."</dd>

<dt>How does it work? </dt>
<dd>
<p><em>live</em>simply:promise has a series of online promises for people to sign up to, consisting of simple actions to change something in their lives. Each promise has a target number of people it wants to attract.</p>

<p>The promises take the form: 'I promise to live simply by doing X, but only if X number of people will do the same'. The creator of the promise publishes their promise online and encourage people to sign up within a fixed time frame.</p>

<p>If a promise pulls in enough people to reach its target in time, then we send a message to everyone who signed up, saying "Well done, now live out your promise!" You can also post comments to discuss how you are doing.</p>

<p>If the promise doesn't get enough subscribers before its time is up, we'll send a message round so you can choose whether to go ahead with a reduced number of people or join a different promise. You can manage all this by sending a message to all signers (using a link in the  "Spread the word" section). </p>
</dd>

<dt>How can you be sure people will bother to carry out the promise? </dt>
<dd>We can't. <em>live</em>simply:promise is based on a psychological bet. We believe that if a person wants to do something, and we help connect them to a bunch of people who also want to do the same thing then that first person is much more likely to act. </dd>

<dt>What do I get out of it? </dt>
<dd>You get the satisfaction of knowing you've put your faith into action. As part of an online community, you have a greatly improved chance of achieving whatever change it is you want &mdash; you're raising money, for example, the more people who sign up, the easier it is. You also have the comfort of knowing that other people are making changes to their lives too. </dd>

<dt>Can I contact other people who promised the same as me?</dt>
<dd>Only people who started a promise can send an email to everyone who signed up to their promise. If you want to get in touch with people who started or signed up to other promises, just post a comment on the relevant promise page. </dd>

<dt>Is it free? </dt>
<dd>The site is free to use. SMS messages to <em>live</em>simply:promise (available in the UK only) cost your normal text fee. </dd>

</dl><h2>Organisational Questions</h2><dl>

<dt>Who is behind <em>live</em>simply:promise?</dt>

<dd><p>The <em>live</em>simply:promise is managed by a group of
Catholic agencies and organisations in England and Wales who
together form the <a href="http://www.progressio.org.uk/livesimply/AssociatesInternal/92992/members/"><em>live</em>simply network</a>.
This site was built by <a href="http://www.mysociety.org">mySociety</a>.
mySociety is a charitable organisation which has grown out of this
community of volunteers. mySociety's primary mission is to build
Internet projects which give people simple, tangible benefits in
the civic and community aspects of their lives.</p>

<p><em>live</em>simply network is managing this website as a
space for people to take action in response to the <em>live</em>simply
challenge. The network is not responsible for the content of the
promises themselves.</p>

<p>If you feel that a promise breaks the <a href="/terms">Terms and Conditions</a> of
the website you can report the comment to the network by clicking on
"Anything wrong with this promise? Tell us!?" link underneath each
promise.</p>

<p>The network reserves the right to decide if a promise violates Terms and
Conditions of the website and if it needs to be removed.</p>

</dd>

</dl><h2>Promise Creators' Questions  </h2><dl>

<dt>What can I ask people to promise? </dt>
<dd><p>The point of <em>live</em>simply:promise is to do something that either shows your solidarity with people who are poor, or your concern for a sustainable lifestyle, or your desire to live more simply, and then to  inspire other people to do the same.</p>

<p>So, we'd suggest that you check first if any of the promises on this website appeal to you. By signing up to a promise you are building the <em>live</em>simply:promise community, making that promise more successful. </p>

<p>If none of the existing promises resonate with you, then start your own promise. Use your creativity to think of a great promise that will get people involved. You should not ask people to do anything illegal. This is a family friendly site, so anything offensive will be removed. </p>
</dd>

<dt>What sort of promises work and what sort languish in obscurity? </dt>
<dd>We think a few general rules apply. A low target in terms of numbers of people is easier to reach. Then, the action itself should be achievable so it doesn't put people off.  It needs to be worded in a clear and interesting way. Lastly, the sheer energy with which you push the promise you've signed up to will make a big difference. If you email all your friends and talk to them about it, you're more likely to be successful than someone who signs up and then forgets all about it.</dd>

<dt>Can I contact the people who have signed my promise? </dt>

<dd>Only people who started a promise can get in touch with other people who signed up to their promise using a link in the "Spread the word" section. For cost reasons, this will not go to SMS signers. If your promise succeeds you will be given a link to send a message to all your signers, including those who signed up by SMS. You will not see their addresses, so if you want you should give your email, and ask for theirs, so you can stay in touch as you carry out your promise. If anybody signs your promise later, the last message that you sent will be automatically forwarded to them, and they will be given a link to read older messages. If you want to get in touch with people but you are not a promise-starter, you can post a comment on the promise page.

<dt>If I make my own promise, how many people should I ask for? </dt>
<dd>We recommend you pick a low target, but with enough people to motivate you to carry out your side of the bargain! This makes it most likely your promise will succeed, and more people than you expected can always sign up. </dd>

<dt>Do you remove silly or illegal promises? </dt>
<dd>We reserve the right to 'backpage' any promise which we consider inappropriate. This means that your promise will work, but will not show up on the "All promises" page, or in the search. We will delete promises which are offensive, abusive, promote or incite illegal behaviour.</dd>

<dt>Why can't I modify my promise after I've made it? </dt>
<dd>People who sign up to a promise are signing up to the specific wording of that promise. If you change the wording, then their signatures would no longer be valid. You can contact us if there is a cosmetic change that you need to make. For larger changes, you can create a new promise, and ask your subscribers if they want to move across. There's a link to do this from the 'Spread the word' section of your promise page.</dd>

<dt>Can people sign up by SMS (text message), and if so how? </dt>
<dd>Yes, but only in the UK. The flyers for your promise describe how to sign up by SMS. So the easiest way to remember is to print some out and carry them with you. You can find the flyers from the 'Spread the word' section on your promise's page. Otherwise tell people to text promise REFERENCE to 
<?=OPTION_PB_SMS_DISPLAY_NUMBER?>. Here REFERENCE is the short reference for your promise which you chose when you made it, and which appears at the end of its web address. For example a shortcode for a promise that would be on this URL promise.livesimply.org.uk/testcode is 'testcode'.</dd>

<dt>How do I add a picture to my promise? </dt>
<dd>Go to your promise page and click 'Add a picture to your promise (creator only)' under 'Spread the word on and offline'. Either find a picture of something to do with your promise, or just use a photo of yourself!</dd>


</dl><h2><a name="privacy">Privacy Questions</a></h2><dl>
<dt>I don't want my name visible to everyone when I sign a promise! <dt>
<dd>You can add yourself secretly to a promise by unchecking the 'Show my name on this promise' box when you sign up. Alternatively, you can sign up by SMS (in the UK only). 
</dd>

<dt>Who gets to see my email address? </dt>
<dd><em>live</em>simply network will never disclose your email address to anyone,
including the creator of your promise, unless we are obliged to by law. We do
let the promise creators send emails to promise subscribers to explain what's
going on, to motivate them etc. However, we don't show the promise creator the
addresses of the subscribers getting their email. If you reply to an email from
the promise creator yourself, you will give them your email address &mdash;
please be aware!  <em>live</em>simply network is also reserving the right to use your
email addresses to get in touch with you very occasionally during the life of
the <em>live</em>simply challenge (due to end May 2008). mySociety, who built
the site and run the servers, obviously have access to the email addresses,
but will only use them on behalf of the <em>live</em>simply network for the purposes
described in the rest of this paragraph.

<dt>Will you send nasty, brutish spam to my email address? </dt>
<dd>Nope. After you sign up to a promise we will only send you emails in relation to your promise or to the <em>live</em>simply challenge. You may receive a message from the promise creator, asking how you're getting on, and status emails from <em>live</em>simply promise stating whether the target has been reached. The members of the <a href="http://www.progressio.org.uk/livesimply/AssociatesInternal/92992/members/"><em>live</em>simply network</a> will never give or sell your email addresses to anyone else, unless we are obliged to by law.</dd>

<dt>What is Google Analytics?</dt>
<dd>This website uses Google Analytics, a web analytics service provided by Google.
Google Analytics uses cookies, which are text files placed on your computer, to help
the website analyze how users use the site. The information generated by the cookie
about your use of the website (including your IP address) will be transmitted to and
stored by Google on servers in the United States . Google will use this information
for the purpose of evaluating your use of the website, compiling reports on website
activity for website operators and providing other services relating to website
activity and internet usage.  Google may also transfer this information to third
parties where required to do so by law, or where such third parties process the
information on Google's behalf. Google will not associate your IP address with any
other data held by Google.  You may refuse the use of cookies by selecting the
appropriate settings on your browser, however please note that if you do this you
may not be able to use the full functionality of this website.  By using this
website, you consent to the processing of data about you by Google in the manner and
for the purposes set out above.</dd>

</dl>

    <?
}

############################################################################

page_header(_("Frequently Asked Questions"), array('cache-max-age' => 600));

if ($microsite && $microsite == 'livesimply') {
    livesimply_faq();
} else {
    default_faq();
}

page_footer();
