<?
// explain.php:
// Explanation of how PledgeBank works
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: explain.php,v 1.10 2006-11-03 16:19:22 francis Exp $

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
    <img src="/microsites/livesimply/markwoods.jpg"
    alt="" style="vertical-align: top; float:right; margin:0 0.5em 0 0; padding: 1em">
    <?

    ?>

    <p>Hello. I'm Mark Woods, the Live Simply network coordinator. I am writing
    to you directly because I found that explaining Live Simply Promise in
    person often works better than using the written word.</p>

    <p>Events like tsunami, Niger drought or Pakistan earthquake expose the
    poverty that majority of the people in the Southern hemisphere of this
    planet live in. Last year's MAKEPOVERTYHISTORY campaign taught us that
    what keeps people poor are also international debt, insufficient aid and
    unjust trade. MAKEPOVERTHISTORY also taught us that we can do something
    about this and, hey, did we do it - 8 million people in the UK wore a
    white band, quarter of a million went to Edinburgh to ask G8 to make
    poverty history and over 500,000 people emailed Tony Blair asking him to
    keep his promise to eradicate poverty.</p>

    <p>We've influenced politicians and will keep doing that. But we also need
    to look at how do our lifestyles impact on the lives of the poor and
    more importantly how we can use this link to help them.</p>

    <p>Live Simply is challenges you to reflect on your lifestyle, understand
    how it impacts on the lives of the poor and take action for justice. But
    that's not all. If you persuade people in your community to do the same
    the impact of your action will be even more significant.</p>

    <p>Live Simply Promise enables you to do this.</p>

    <h2>Signing up to a promise</h2>

    <p>There is a range of things our supporters said they'd like to change in
    their lives. So we've pre-set a few promises to reflect this.

    <p>Each of the promises are set up for a group of people to do. If you sign
    up to a promise and promote it to others, you'll create a nice little
    community for yourself. So when you hit the wall and feel that you can't
    do it, there will always be a group of people you can turn to for
    support, advice and a kind word.</p>

    <h2>Starting your own promise</h2>

    <p>If you have a specific idea of what you need to change in your lifestyle
    and it's definitely not one of the promises we've pre-set, you can make
    a promise of your own. You can choose to do it with one more person or
    with another 50 people. Choose the number wisely though - set the target
    bearing in mind who you can promote your promise to and how much time
    you'll have to do this.</p>

    <h2>Promoting your promise</h2>

    <p>To get as many people as possible to join you in fulfilling your
    promise, you need to promote it. Live Simply Promise enables you to
    create leaflets and posters just by clicking on a link.</p>

    <h2>Sign a promise by SMS</h2>

    <p>Live Simply Promise isn't just limited to people who use the internet a
    lot. You can sign up to any pledge by sending a two word text message
    (in the UK only). Ideal for getting your friends in the pub involved,
    people in your street and so on.</p>

    <p>That's all I had to say. All you need to do now is to choose your
    promise, involve others and make sure you fulfil it!</p>

    <p>Thank you and good luck,</p>
    <p>Mark</p>


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
