<?
// faq.php:
// FAQ page for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: faq.php,v 1.64 2009-01-10 18:27:05 timsk Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

############################################################################

function default_faq() {
    global $microsite, $lang;

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
    print dd($paid);

    print dt(_('Do you need any help with the project?'));

    print dd(_('Yes, we can use help in all sorts of ways, technical or non-technical.
    Please <a href="http://www.mysociety.org/helpus/volunteering-for-pledgebank/">see our volunteering page</a>.'));

    print dt(_('Where\'s the "source code" to PledgeBank?'));

    print dd(_('The software behind PledgeBank is open source, and available to you
    mainly under the GNU Affero GPL software license. You can <a
    href="http://github.com/mysociety/pledgebank">download the source
    code</a> and help us develop it. You\'re welcome to use it
    in your own projects, although you must also make available the source code to
    any such projects.'));

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
    Tom Steinberg.'));

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
    Aliaksej Lavo&#324;&#269;yk (Belarusian),
    Jaroslav Rynik (Slovak).'));

    print dd(_('<strong>Thanks also to</strong>
    <a href="http://www.tangentlabs.co.uk/">Tangent Labs</a> (for SMS in the UK), 
    the <a href="http://earth-info.nga.mil/gns/html/">US military</a> (for the world gazetteer),
    <a href="http://sedac.ciesin.columbia.edu/gpw/">CIESIN</a> (for population density data),
    <a href="http://www.ordnancesurvey.co.uk">Ordnance Survey</a> (for UK postcodes),
    Ben Furber for our favicon, 
    the entire free software community (FreeBSD, Linux, PHP, Perl, Python, Apache,
    MySQL, PostgreSQL, we love and use you all!) and
    <a href="http://www.bytemark.co.uk/">Bytemark</a> (who host all
    our servers).

    Let us know if we\'ve missed anyone.'));


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

    print dt('<a name="allpledges">'._('Why does my new pledge not appear on the All Pledges page or in search results?').'</a>');

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
    pledges which are really nothing more than advertising or propaganda, or which
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
    PledgeBank looks like in other countries, click on the country name at the top of any
    page, just right of the PledgeBank logo.  Please <a href=\"/contact\">contact
    us</a> if you'd like to help get more people in your country using PledgeBank."));

    print dt(_('Why do you only have UK postcodes?'));

    print dd(_("We'd be happy to talk to anyone who could help us extend
    the postcode search overseas&mdash;please <a
    href=\"https://secure.mysociety.org/admin/lists/mailman/listinfo/internationalisation\">join this
    email list</a> to help us out."));

    print dt(_('... and SMS?'));

    print dd(_("Again, please <a href=\"/contact\">contact us</a> if you
    can help add text messaging in your country."));

    print "</dl>\n";

    ############################################################################

    print h2('<a name="privacy">'._('Privacy Questions').'</a>');
    print p(_('Please see our separate <a href="/privacy">privacy page</a>.'));
}

############################################################################

page_header(_("Frequently Asked Questions"), array('cache-max-age' => 600));

# Contact Us sidebar
echo '<div id="sidebar"><h2>' . _('Contact Us') . '</h2>
<p>' . _('If your question isn&rsquo;t answered here, or you just
wanted to let us know something about the site, <a href="/contact">contact&nbsp;us</a>.')
    . '</p></div>';

default_faq();
page_footer();
