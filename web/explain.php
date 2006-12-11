<?
// explain.php:
// Explanation of how PledgeBank works
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: explain.php,v 1.12 2006-12-11 17:54:55 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/microsites.php';

function default_explanation() {
    page_header(_("What is PledgeBank?"));

    print h2(_("Tell the world \"I'll do it, but only if you'll help me do it\""));

    global $lang; 
    if ($lang == 'en-gb') {
        print p(_('You can <a href="tom-on-pledgebank-vbr.mp3">listen to an MP3</a> of this instead.'));
    } else {
        print p(_('You can <a href="tom-on-pledgebank-vbr.mp3">listen to an MP3 (English only)</a> of this instead.'));
    }

    ?>
    <blockquote><a href="tom-on-pledgebank-vbr.mp3"><img src="tomsteinberg_small.jpg"
    alt="" style="vertical-align: top; float:left; margin:0 0.5em 0 0"></a>
    <?

    print p(_("Hello.  I'm Tom Steinberg, the director of mySociety, the charitable
    group which is building PledgeBank.  I've taken the unusual step of
    recording this introduction because PledgeBank is a slightly unusual
    idea.  I've found that explaining it in person often works better than
    using the written word."));
    print p(_("We all know what it is like to feel powerless, that our own actions
    can't really change the things that we want to change.  PledgeBank is
    about beating that feeling by connecting you with other people who also
    want to make a change, but who don't want the personal risk of being
    the only person to turn up to a meeting or the only person to donate ten
    pounds to a cause that actually needed a thousand."));
    print p(_("The way it works is simple.  You create a pledge which has the basic
    format 'I'll do something, but only if other people will pledge to do
    the same thing'.  For example, if you'd always want to organise a street
    party you could organise a pledge which said 'I'll hold a street party,
    but only if three people who live in my street will help me to run it'"));
    print p(_("The applications of PledgeBank are limitless.  If you are a parent
    you could say that 'I will help run an after hours sports club but only
    if 5 other parents will commit one evening a week to doing it '.  If you
    are in a band you could say 'I'll hold a gig but only if 40 people will
    come along'."));
    print p(_("PledgeBank has been undergoing real world testing for a few weeks
    already, and there are already some successful completed pledges
    completely outside our original ideas of how people might use the site.
    One person gathered 20 other fans of a BBC radio series to lobby for its
    release on CD. Another encouraged 8 people who he'd never met to bury
    buckets in their own gardens to make homes for endangered stag beetles.
    And a member of an online community said he'd organise a 5th birthday
    party and now has 30 members of that community saying they'll come
    along."));
    print p(sprintf(_("PledgeBank isn't just limited to people who use the internet a lot.
    You can sign up to any pledge with a simple two word text message (in %s only).
    Ideal for getting your friends in the pub involved, people in your street
    and so on."), sms_countries_description()));
    print p(_("PledgeBank is free, easy to use, and needs your involvement
    before we can launch.  So if there's something you'd like to achieve in
    your community, in your place of employment, your university, amongst
    your friends, or in your street, please take a look at PledgeBank.com
    and create a pledge right now.  Thank you."));
    print '</blockquote> <p><a href="/">' . _('To PledgeBank front page') . '</a></p>';

    page_footer();
}

function livesimply_explanation() {
    page_header(_("How does Live Simply Promise work?"));

    print h2("How does Live Simply Promise work?");

    ?>

    <p>Hello. I'm Mark Woods, part of the livesimply team. The livesimply: promise is
    a way to really make your faith talk. It works like this: "I promise to live
    simply, sustainably and in solidarity with the poor by ..., but only if 10 other
    people will do the same."</p>

    <p>You sign up online to the promise you like best, and encourage other people to
    join the same one as you. Once the target number of people is reached, we let
    you know so you can all start living your promise.</p>

    <h2>Signing up to a promise</h2>

    <p>We have set up a range of promises to get you started.
    If you sign up to a promise and promote it to others, you'll be helping create
    a community action. Not only are you being the best you can be, you're
    encouraging others to do the same. Also, if you're finding your promise hard to
    keep, you'll know there are others out there you can turn to for support.</p>

    <h2>Why make a promise?</h2>

    <p>Our faith tells us the good things of the world were meant for everyone to
    share. Yet a glance at our world shows that some people have everything, while
    others have nothing. This isn't right. We can act for justice in simple ways. </p>

    <p>Last year for example, 8 million ordinary people in the UK showed they wanted
    to make poverty history by wearing a white band. Politicians had to listen, and
    they promised to change the structural problems that keep people poor. </p>

    <p>Now livesimply says it's our turn to look at ways we can change things in
    ourselves and in the world. It's not just about money. It's a challenge to
    think about how our lifestyles can reflect our solidarity with people who are
    poor. Make a start, by making a <em>live</em>simply:promise.</p>

    <h2>Promoting your promise</h2>

    <p>To get as many people as possible to join you in
    carrying out the promise you signed up to, you need to promote it. Create
    personalised leaflets and posters just by clicking on a "Print out customised
    flyers" link.</p>

    <h2>Starting your own promise</h2>

    <p>If your idea of what you want to change about your lifestyle is not one
    of the existing promises, make a promise of your own.  Don't pitch your target
    number of participants too high - bear in mind who you can promote your promise
    to and how much time you've got. </p>

    <h2>Sign a promise by SMS (in the UK only)</h2>

    <p>You can sign up to any promise by sending a two word text message. You
    will find the full instructions on the leaflets generated for your promise.
    Ideal for getting your friends in the pub involved, young people and so on.
    </p>

    <p>That's all from me. Now just choose your promise, get your friends on board,
    and carry it out! </p>

    <p>Thanks and good luck, </p>

    <p>Mark and the <em>live</em>simply team</p>

    <?

    print '<p><a href="/">' . 'To Live Simply Promise front page' . '</a></p>';

    page_footer();
}

global $microsite;
if ($microsite && $microsite == 'livesimply') {
    livesimply_explanation();
} else {
    default_explanation();
}
