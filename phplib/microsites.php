<?php
/*
 * microsites.php:
 * Microsites are special sub-sites for London, Global Cool etc.
 * This file contains lots of functions which return the values appropriate
 * to each microsite. 
 *  
 * The idea is that you can create a new microsite by entirely editing this
 * file, and the rest of the code just has hooks calling functions here, rather
 * than lots of messy if statements. 
 * 
 * In practice, there are some if statements elsewhere for really special
 * cases. For example, page.php has some if statements for the Global Cool
 * style, because it requires inclusion of templates after our headers,
 * in a very particular place, which would be hard to understand if partly
 * explained in functions here.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: microsites.php,v 1.105 2007-05-09 09:37:15 francis Exp $
 * 
 */

#############################################################################
# Name and domain information

/* Codes of microsites, and name displayed next to PledgeBank logo */
$microsites_list = array('everywhere' => _('Everywhere'),
                         'london' => 'London',
                         '365act' => '365 Ways',
                         'glastonbury' => 'Glastonbury',
                         'interface' => 'Interface',
                         'global-cool' => 'Global Cool',
                         'catcomm' => 'CatComm',
                         'livesimply' => '<em>live</em>simply:promise',
                         'o2' => 'O2',
);

/* Other domains which refer to microsites (must be one-to-one as reverse map used to make URLs) */
if (OPTION_PB_STAGING) {
    # Francis's local test domains
    $microsites_from_extra_domains = array('pledge.global-cool' => 'global-cool',
                                           'promise.livesimply' => 'livesimply'); 
} else {
    # If you alter this, also alter web/poster.cgi which has a microsites_from_extra_domains variable
    $microsites_from_extra_domains = array('pledge.global-cool.com' => 'global-cool',
                                           'promise.livesimply.org.uk' => 'livesimply');
}
$microsites_to_extra_domains = array_flip($microsites_from_extra_domains);

/* These are listed on /where */
$microsites_public_list = array('everywhere' => _('Everywhere &mdash; all countries in all languages'),
                                'london' => _('London (United Kingdom)'),
                                /* 'global-cool' => _('Global Cool (One by One, Ton by Ton)'), */
                                'catcomm' => _('Catalytic Communities')
                                );

/* As sometimes microsites.php is included before the locale is set... 
 */
function microsites_for_locale() {
    global $microsites_list, $microsites_public_list;
    $microsites_list['everywhere'] = _('Everywhere');
    foreach ($microsites_public_list as $key => $value) {
        $microsites_public_list[$key] = _($value);
    }
}

/* Pledges made from these microsites are not marked as such in the microsite
 * field in the pledges table. */
$microsites_no_pledge_field = array(
    'everywhere', 
    'london' // London pledges are those in a certain geographical area, they aren't marked in this field
);

/* microsites_get_name 
 * Returns display name of microsite if we are on one. e.g. Glastonbury */
function microsites_get_name() {
    global $microsite, $microsites_list;
    if (array_key_exists($microsite, $microsites_list))
        return $microsites_list[$microsite];
    return null;
}

/* microsites_user_tracking
 * Whether or not to use our cross-site conversion tracking for this microsite.
 * See https://secure.mysociety.org/track/ for more information about this. */
function microsites_user_tracking() {
    global $microsite;
    if (!$microsite) 
        return true;
    if ($microsite == 'everywhere' || $microsite == 'london')
        return true;

    // Don't do tracking on 3rd party sites
    return false;
}

/* microsites_redirect PLEDGE
 * When going to some pledges, a redirect is done so the URL is
 * that of a particular microsite. */
function microsites_redirect($p) {
    global $microsite;
    $redirect_microsite = $microsite;

    # Specific pledges which redirect to certain microsite
    if ($p->ref() == 'Sportclubpatrons') {
        $redirect_microsite = 'london';
    }

    # Microsites for which all pledges marked in the database as belonging to
    # that microsite do a redirect
    $redirect_microsites = array('global-cool');
    if (in_array($p->microsite(), $redirect_microsites)) {
        $redirect_microsite = $p->microsite();
    }
    # Redirect back again, if on, for example, a non-global-cool pledge on
    # global-cool domain
    if ($microsite && in_array($microsite, $redirect_microsites)) {
        $redirect_microsite = $p->microsite();
    }

    # If necessary, do the redirect
    if ($microsite != $redirect_microsite) {
        $newurl = pb_domain_url(array('path' => $_SERVER['REQUEST_URI'], 'microsite' => $redirect_microsite));
        header("Location: $newurl");
        exit;
    }
}

/* microsites_site_country
 * Default country for microsite.  Used for search, and for default country on
 * alerts / new pledge form */
function microsites_site_country() {
    global $site_country, $microsite;
    if ($microsite) {
        if ($microsite == 'london' || $microsite == 'livesimply') # *not* O2
            return 'GB';
        return null;
    }
    return $site_country;
}


#############################################################################
# Styling

/* microsites_logo
 * Returns HTML to use for logo of microsite, or country. */
function microsites_logo() {
    global $microsite, $lang;
    if ($microsite && $microsite == 'interface') {
        return '
<h1><a href="/"><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></a><span id="beta">Beta</span>
<span id="countrytitle"><img src="/microsites/interface-logo.gif" alt="interface">
<a href="/where">' . _('(change)') . '</a></span></h1>';

    } elseif ($microsite && $microsite == '365act') {
        return '
<a href="http://www.365act.com"><img src="/microsites/365-logo-small.png" alt="365 Act" align="left"
    style="
    margin-top: -10px; 
    margin-left: -0.4em 
    background-color: #ffffff;
    float: left;
    border: solid 2px #21004a;
    padding: 0px;
    margin: 10px;
    "></a>
<h1>
<a href="/"><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></a><span id="beta">Beta</span>
<span id="countrytitle"><a href="/where">' . _('(other PledgeBanks)') . '</a></span>
</h1>
';

    } elseif ($microsite && $microsite == 'london') {
        return '
<h1><a href="/"><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span> <span id="logo_pledge">London</span></a>
<span id="countrytitle"><a href="/where">' . _('(change)') . '</a></span></h1>';

    } elseif ($microsite && $microsite == 'catcomm') {
        return '
<a href="http://www.catcomm.org"><img src="/microsites/catcomm-logo.png" alt="Catalytic Communities" align="left"
    style="
    margin-top: -10px; 
    margin-left: -0.4em 
    background-color: #ffffff;
    float: left;
    border: solid 2px #21004a;
    padding: 0px;
    margin: 10px;
    "></a>
<h1>
<a href="/"><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></a><span id="beta">Beta</span>
<span id="countrytitle"><a href="/where">' . _('(other PledgeBanks)') . '</a></span>
</h1>
';
    } elseif ($microsite && $microsite == 'livesimply') {
        return '
<h1><a href="/"><img width="1009" height="143" src="/microsites/livesimply/livesimplypromiseheader.jpg" alt="livesimply:promise" border="0"></a></h1>
';
    } elseif ($microsite && $microsite == 'o2') {
        return '
<h1><a href="/"><img src="/microsites/o2-logo.jpg" alt="O2 Promise Bank" border="0" width="386" height="66"></a></h1>
';
    } elseif ($lang == 'zh') {
        $country_name = pb_site_country_name();
        return '
<h1><a href="/"><span id="logo_zh">' . _('Pledge') . _('Bank') . '</span>
<small><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></small></a>
<span id="countrytitle">' . $country_name . '
<a href="/where">' . _('(change)') . '</a></span></h1>';
    } else {
        $country_name = pb_site_country_name();
        return '
<h1><a href="/"><span id="logo_pledge">' . _('Pledge') . '</span><span id="logo_bank">' . _('Bank') . '</span></a>
<span id="countrytitle">' . $country_name . '
<a href="/where">' . _('(change)') . '</a></span></h1>';
    }
}

/* microsites_html_title_slogan
 * On the front page, slogon to display before site name */
function microsites_html_title_slogan() {
    global $microsite;

    if ($microsite == "livesimply") {
        return "Promise to live simply and get others involved"; # deliberately not translated
    } else {
        # TRANS: 'PledgeBank' here is the first part of the HTML title which appears on browser windows, and search engines
        return _("Tell the world \"I'll do it, but only if you'll help\"");
    }
}
 
/* microsites_css_files
 * Return array of URLs for CSS files */
function microsites_css_files() {
    global $microsite;

    $styles = array();
    // Microsite PledgeBank style sheet
    if ($microsite && in_array($microsite, array(
                'interface', 
                'glastonbury', 
                '365act', 
                'london', 
                'global-cool',
                'catcomm',
                'livesimply',
                'o2',
            ))) {
        $styles[] = "/microsites/autogen/$microsite.css";
    } else {
        $styles[] = "/pb.css";
    }

    // Microsite cobranding style sheet
    if ($microsite) {
        if ($microsite == 'XXX') { // example, remove if you use this
            $styles[] = "/microsites/XXX/YYY.css";
        }
    }

    return $styles;
}

/* microsites_navigation_menu
 * Returns array of text to links for the main site navigation menu.
 */
function microsites_navigation_menu($contact_ref) {
    global $microsite;

    $menu = array();
    $menu[_('Home')] = "/";
    if ($microsite && $microsite == 'livesimply') {
        $menu['How <em>live</em>simply:promise works'] = "/explain";
        $menu['About <em>live</em>simply'] = 'http://www.progressio.org.uk/livesimply/AssociatesInternal/93008/about_live_simply/';
    }
    $menu[_('All Pledges')] = "/list";
    $menu[_('Start a Pledge')] = "/new";
    if ($microsite != 'o2') {
        if ($microsite && $microsite == 'livesimply') {
            $menu['Frequently Asked Questions'] = "/faq";
        } else {
            $menu['<acronym title="'._('Frequently Asked Questions').'">'._('FAQ').'</acronym>'] = "/faq";
        }
    }
    if ($microsite != 'o2')
        $menu[_('Contact')] = '/contact' . $contact_ref;
    $menu[_('Your Pledges')] = "/your";
    $P = pb_person_if_signed_on(true); /* Don't renew any login cookie. */
    debug_timestamp(true, "retrieved person record");
    if ($P) {
        $menu[_('Logout')] = "/logout";
    }
    if ($microsite && $microsite == 'livesimply') {
        $menu['www.livesimply.org.uk'] = 'http://www.livesimply.org.uk/';
    }

    return $menu;
}

# Whether a site has local alerts at all!
function microsites_local_alerts() {
    global $microsite;
    if ($microsite == 'o2') return false;
    return true;
}

/* microsites_frontpage_has_local_emails
 * Whether or not the local alert signup box is present on the
 * top of the front page.
 */
function microsites_frontpage_has_local_emails() {
    global $microsite;
    if ($microsite == 'global-cool' || $microsite == 'catcomm'
        || !microsites_local_alerts())
        return false;
    return true;
}

/* microsites_frontpage_has_intro
 * Whether or not the "tell the world" motivation intro box is present on the
 * front page. That page is defined by microsites_frontpage_intro below.
 */
function microsites_frontpage_has_intro() {
    global $microsite;
    if ($microsite == 'global-cool')
        return false;
    return true;
}

/* microsites_frontpage_intro 
 * Introduction text to show on front page of site. The is only
 * called if microsites_frontpage_has_intro() above returns true.
 */
function microsites_frontpage_intro() {
    global $microsite, $lang;
    $tom = false;
    $audio_intro = true;
    if ($microsite == 'interface') {
        $tom = "Hello, and welcome to the demo version of PledgeBank we've built for
            internal use at Interface. PledgeBank is a handy tool which is good at
            getting people to do social or environmental things they
            want to do but normally never get round to.";
    } elseif ($microsite == '365act') {
        ?><h2>Pledge to change the world today!</h2>

        <p>If you agree to do something to change the world... and if lots of
        others also agree to do the same thing... then this will create a much
        bigger change.</p>

        <p>This is the spirit of “365 Ways to Change the World” &mdash; lots of people
        all over the world doing lots of things to make a better world.</p>

        <p>So, make a pledge now to change the world. And ask all your friends and
        colleagues to do the same.</p>

        See all of the <a href="http://365act.com">365 ways to change the world...</a>
        <?
    } elseif ($microsite == 'london') {
        ?><h2>Tell Londoners &#8220;I&#8217;ll do it, but only if you&#8217;ll help me do it&#8221;</h2>
        <p>In the summer of 2012 the eyes of the world will be on London for a
        fortnight as the Olympics games return to the capital for the third
        time in its history. This site is collecting pledges encouraging
        Londoners to work together on projects to turn London from a great city
        to the greatest city in the world by 2012.</p>
        <?
    } elseif ($microsite == 'catcomm') {
        ?><h2>
    
    Tell the world &#8220;I&#8217;ll support communities working to solve local problems, but only if you will too!&#8221;</h2>
    <p>Catalytic Communities (CatComm) develops, inspires and empowers
    communities worldwide to generate and share their own local
    solutions. Imagine a world where community-generated solutions are
    just a mouse-click away, where anyone, anywhere, confronting a local
    problem, can find the inspiration and tools they need to implement
    the solution, learning from their peers. This site brings people
    together, forming a network of support for building this work...</p>
        <?
    } elseif ($microsite == 'livesimply') {
        ?>

    <p>Want to make the
    world a better place for everyone? Then start here by taking a <em>live</em>simply:
    promise. Make a simple change in your life and get others to do the same! </p>

    <p>livesimply is a challenge to look hard at our lifestyles and to choose
    to live simply, sustainably and in solidarity with people in poverty. Be
    the best you can be, and help other people do the same. </p>

    <p><a href="/explain">How does <em>live</em>simply:promise work?</a>

        <?
        $audio_intro = false;
    } elseif ($microsite == 'o2') { ?>
<h2>Welcome to O2&#8217;s Promise Bank!</h2>

<p>Welcome to the Promise Bank! It&#8217;s your chance to make a promise
(or several!) to support one or more elements of the People Promise.</p>

<p>Over a thousand conversations between O2 people took place last year
asking what would make O2 the best place to work.  Now we&#8217;ve
captured everyone&#8217;s ideas &mdash; in their own words &mdash; as
our People Promise.  It describes the O2 we want to be.</p>

<p>While we don&#8217;t live up to all of it yet, we&#8217;re clear about
where we&#8217;re going.  So how do we start the journey?  Quite simply
by living the People Promise.</p>

<p>And that&#8217;s where Promise Bank comes in.  You&#8217;re in the
right place to promise to do your bit &mdash; big or small &mdash; to
bring the People Promise to life around you.  You&#8217;ll notice the
difference and so will others.</p>

<p>Just read the People Promise on Vital o2 (insert link) or in the
latest strategy booklet and think about what you can do to support
one of the elements:</p>

<ul>
<li>A warm welcome
<li>Part of something special
<li>A great place to work
<li>The opportunity to get on
<li>Trusted to do a great job
<li>Managing with a human touch
<li>Thanks for a job well done
</ul>

<p>Then, just use the Promise Bank to make your Promise. It&#8217;s easy!</p>

<p>You can make a Promise for yourself or on behalf of your team. The
most important thing is that you make a Promise and then keep it &mdash;
if 11,000 people each promise to do something to bring the People Promise
to life, you really will be part of something special.</p>

It&#8217;s your chance to make a difference, and you really can!</p>

<?
        $audio_intro = false;
    } else {
        # Main site
        $tom = _('"We all know what it is like to feel powerless, that our own
        actions can\'t really change the things that we want to change.
        PledgeBank is about beating that feeling..."');
    }
    
    # Quote from Tom, and his photo
    if ($tom) {
?><h2><?=_('Tell the world &#8220;I&#8217;ll do it, but only if you&#8217;ll help me do it&#8221;') ?></h2>
<blockquote class="noindent"><a href="<?

        if ($lang=='en-gb') print 'tom-on-pledgebank-vbr.mp3';
        else print '/explain';

?>"><img src="tomsteinberg_small.jpg"
alt="" style="vertical-align: top; float:left; margin:0 0.5em 0 0; border: solid 2px #9C7BBD;
"></a>
<?=$tom?>
</blockquote>
<?  
    }

    # Give how it works explanation
    if ($audio_intro) {
        if ($lang == 'en-gb') { ?>
    <p><a href="tom-on-pledgebank-vbr.mp3"><?=_('Listen to how PledgeBank
    works</a>, as explained by mySociety\'s director Tom Steinberg.
    Or <a href="/explain">read a full transcript') ?></a>.</p>
    <? } else { ?>
    <p><?=_('<a href="/explain">Find out how PledgeBank
    works</a>, as explained by mySociety\'s director Tom Steinberg.')?></p>
    <? }  
    }
    
    # Extra end text
    if ($microsite == 'catcomm') {
?> For technical help, contact us at <a href="mailto:techhelp@catcomm.org">techhelp@catcomm.org</a>.<?
    }

}

/* microsites_frontpage_has_start_your_own
 * Whether or not the "start your own pledge" box is present on the front page.
 */
function microsites_frontpage_has_start_your_own() {
    global $microsite;
    if ($microsite == 'global-cool' || $microsite == 'livesimply')
        return false;
    return true;
}

/* microsites_frontpage_extra_blurb
 * Extra box of text to put in right hand side. */
function microsites_frontpage_extra_blurb() {
    global $microsite;

    if ($microsite == 'livesimply') {
        // Use the "startblurb" here, as we microsites_frontpage_has_start_your_own is false
        // and we want it in the same place as that. XXX tidy that up by making
        // microsites_frontpage_has_start_your_own display its content?
?><div id="startblurb"><?
        # Count the number of signatures, including pledge creators.
        # (We don't try to count distinct people using person_id as that can
        # give privacy leaks, as indeed could doing it my distinct person.name
        # since some signers' names are hidden)
        $na = db_getOne('select count(*) from signers');
        $nb = db_getOne('select count(*) from pledges');
        ?> <div id="simplycounter"><strong>Together, we've made <?=$na + $nb?> promises. What are you going to promise?</strong></div> </div> <?
    } elseif ($microsite == 'catcomm') {
?>
<div id="extrablurb">
<h2>About CatComm</h2>
<p>Catalytic Communities (<a href="http://www.catcomm.org">www.catcomm.org</a>) leverages peer-to-peer
support by gathering community leaders together to make change in
communities around the world. The organization offers, online and in
three languages (Portuguese, English and Spanish) a unique Community
Solutions Database, featuring over 125 community programs from 9
countries and visited by over 20,000 people each month. "Leaders in
squatter communities around the world don't have to reinvent the
wheel if they can locate and learn from peers elsewhere who've
tackled significant challenges like sewerage, unemployment, and HIV,"
explains Executive Director Theresa Williamson. They also run a
community technology center, the "Casa" in downtown Rio de Janeiro
that serves as a space for exchange among local leaders.</p>

<p>To learn more: visit our <a href="http://www.catcomm.org/">homepage</a>,
<a href="http://casacomcat.blogspot.com/">Casa Blog</a>, and latest
<a href="http://www.comcat.org/articles/e-news/english/02.htm">e-newsletter</a>.
</p>

<h2>You can help!</h2>

<p>Thanks to a partnership with PledgeBank,  you can post your own
pledge in support of communities working to solve local problems, worldwide!</p>

<ul>
<li> Pledge your financial commitment to grow this effort and
leverage your pledge to get others to join you! (Be a CatComm Champion!)</li>
<li> Pledge to provide tools and build capacity in support of
specific community projects that excite you, but only if others do, too!</li>
<li> Pledge to bring exposure to local solutions from your area by
posting them in the Community Solutions Database, if others will, too!</li>
<li> Pledge to research and reach out to community solutions in
regions still uncovered by the CatComm site, if others will join you!</li>
<li> Pledge to translate community projects into languages where
they could be inspirational, if others do, too!</li>
</ul>

</div>

<?
    }

}

/* microsites_frontpage_sign_invitation_text
 * Title text above the list of current pledges. */
function microsites_frontpage_sign_invitation_text() {
    global $microsite;

    print "<h2>";
    if ($microsite == 'livesimply') {
        print 'Act now. Sign up to a promise.';
    } else {
        print _('Why not sign a live pledge?');
    }
    print "</h2>";
}

/* microsites_frontpage_has_offline_secrets
 * Whether or not the "scissors and phone" box is present on the front page.
 */
function microsites_frontpage_has_offline_secrets() {
    global $microsite;
    if ($microsite == 'global-cool' || $microsite == 'livesimply'
        || $microsite=='o2')
        return false;
    return true;
}

/* microsites_frontpage_credit_footer
 * Display extra text at the bottom of the front page.
 */
function microsites_frontpage_credit_footer() {
    global $microsite;
    if ($microsite == 'london') {
?>
        <div id="sponsor"><img src="/microsites/pearsfoundation_solid.jpg" border="0" alt="Supported by The Pears Foundation"></div>
<?  } 

}

/* microsites_allpage_credit_footer
 * Display extra text at the bottom of every page.
 */
function microsites_allpage_credit_footer() {
    global $microsite;
}

/* microsites_new_pledges_toptips
 * Tips on making a pledge that will work, for top of new pledge page.
 */
function microsites_new_pledges_toptips() {
    global $microsite;
    if ($microsite == 'livesimply') {
        ?>
        <div id="tips">
        <h2>Top Tips for Successful Promises</h2>
        <ol>

        <li>Keep your targets modest — We recommend you pick a low target, but
        with enough people to motivate you to carry out your side of the
        bargain! This makes it most likely your pledge will succeed, and more
        people than you expected can always sign up. </li>

        <li>Get ready to sell your promise, hard. Promises don't sell
        themselves just by sitting on this site. In fact your promise won't
        even appear to general site visitors until you've got a few people to
        sign up to it yourself. Think hard about whether people you know would
        want to sign up to your promise! </li>

        <li>Think about how your promise reads. How will it look to someone who
        picks up a flyer from their doormat? Read your promise to the person
        next to you, or to your mother, and see if they understand what you're
        talking about. If they don't, you need to rewrite it. </li>

        </ol>
        </div>
        <?
    } elseif ($microsite == 'o2') {
        /* No top tips at the top */
    } else {
        print microsites_toptips_normal();
    }
}

function microsites_toptips_normal() {
    $percent_successful_above_100 = percent_success_above(100);
    $out = '<div id="tips">';
    $out .= '<h2>' . _('Top Tips for Successful Pledges') . '</h2>';
    $out .= '<ol>';
    $out .= '<li>' . sprintf(_('<strong>Keep your ambitions modest</strong> &mdash; why ask for 50 people
        to do something when 5 would be enough? Every extra person makes your pledge
        harder to meet. Only %0.0f%% of pledges asking for more than 100 people succeed.'), $percent_successful_above_100) . '</li>';
    $out .= '<li>' . _("<strong>Get ready to sell your pledge, hard</strong>. Pledges don't
        sell themselves just by sitting on this site. In fact your pledge won't even
        appear to general site visitors until you've got a few people to sign up to it
        yourself. Think hard about whether people you know would want to sign up to
        your pledge!") . '</li>';
    $out .= '<li>' . _("<strong>Think about how your pledge reads.</strong> How will it look to
        someone who picks up a flyer from their doormat? Read your pledge to the person
        next to you, or to your mother, and see if they understand what you're talking
        about. If they don't, you need to rewrite it.") . '</li>';
    $out .= '</ol></div>';
    return $out;
}

function microsites_new_pledges_toptips_bottom() {
    global $microsite;
    if ($microsite == 'o2') {
        return <<<EOF
<div id="tips">
<h2>Top Promise Tips</h2>
<ol>
<li><strong>Really think about what you can do to make your People Promise
come alive</strong> &mdash; this is your commitment, promise to do
something that you think will make O2 a better place to work</li>
<li><strong>You're not on your own!</strong> Your Promise can be
individual or you can share it with others. You might agree as a team to
do something together. It's up to you!</li>
<li><strong>You can promise more than once</strong> &mdash; there's no
limit to the number of Promises you make</li>
</ol>
</div>
EOF;

    } else {
        print _("Did you read the tips at the top of the page? They'll help you make a successful pledge.");
    }
}

/* microsites_contact_intro
 * Description at top of contact page.
 */
function microsites_contact_intro() {
    global $microsite;
    if ($microsite == 'livesimply') { ?>
<p>If you need help with your promise or you have other questions or comments use this form to contact us.</p>
<?
    } else {
        print "<p>";
        print _('Was it useful?  How could it be better?
    We make PledgeBank and thrive off feedback, good and bad.
    Use this form to contact us.');
        $contact_email = str_replace('@', '&#64;', OPTION_CONTACT_EMAIL);
        printf(_('If you prefer, you can email %s instead of using the form.'), '<a href="mailto:' . $contact_email . '">' . $contact_email . '</a>');
        print "</p>";
    }
}

/* microsites_pledge_closed_text
 * Text to display in red box when a pledge is closed.
 */
function microsites_pledge_closed_text() {
    global $microsite;
    if ($microsite == 'livesimply') {
        return "The deadline for this promise has passed. Check if this promise has been set up with a new deadline &mdash; check out the \"All promises list\". You can always carry out your promise anyway. Or why not <a href=\"/new\">start your own promise</a>?";
    }
    return _('This pledge is now closed, as its deadline has passed.');
}

/* microsites_signup_extra_fields
 * Add any extra input fields or text to the pledge signup box.
 */
function microsites_signup_extra_fields($errors) {
    global $microsite;
    if ($microsite == 'livesimply') {
        $agreeterms = '';
        if (get_http_var('agreeterms'))
            $agreeterms = ' checked';
?>
    <strong><input type="checkbox" name="agreeterms" value="1" <?=$agreeterms?> <?=array_key_exists('agreeterms', $errors) ? ' class="error"' : ''?> > 
    I have read the <a href="/terms">terms and conditions</a>. I have the
    permission of my parent, guardian or teacher to sign up, or I am at least
    18 years old. 
    </strong>
<?
    }
}

/* microsites_signup_extra_fields_validate
 * Validate the values of any extra fields during signup.
 */
function microsites_signup_extra_fields_validate(&$errors) {
    global $microsite;
    if ($microsite == 'livesimply') {
        if (!get_http_var('agreeterms')) {
            $errors['agreeterms'] = 'Please confirm that you have read the terms and conditions and that you have permission to sign the pledge or are old enough not to need it.';
        }
    }

}

function microsites_new_pledges_terms_and_conditions($data, $v, $local, $errors) {
    global $microsite;

    if ($microsite == 'livesimply') {
        $agreeterms = '';
        if (get_http_var('agreeterms'))
            $agreeterms = ' checked';
?>    
    <p>When you're happy with your promise, confirm that you agree to the terms
    and conditions and click "Create promise".</p>
    <strong><input type="checkbox" name="agreeterms" value="1" <?=$agreeterms?> <?=array_key_exists('agreeterms', $errors) ? ' class="error"' : ''?> > 
    I have read the <a href="/terms">terms and conditions</a>. I have the
    permission of my parent, guardian or teacher to sign up, or I am at least
    18 years old. </strong>
<p style="text-align: right;">
<input type="submit" name="tocreate" value="<?=_('Create pledge') ?>">
</p>
<?
        return;
    } elseif ($microsite == 'o2') { ?>
<input type="hidden" name="agreeterms" value="1">
<p>When you're happy with your promise, <strong>click "Create"</strong> to confirm that you wish us to display the promise at the top of this page in your name.
<p style="text-align: right;">
<input type="submit" name="tocreate" value="<?=_('Create pledge') ?>">
</p>
<?
        return;
    }

    print '<p>' . _('When you\'re happy with your pledge, <strong>click "Create"</strong> to confirm that you wish PledgeBank.com to display the pledge at the top of this page in your name, and that you agree to the terms and conditions below.');
?>
<p style="text-align: right;">
<input type="submit" name="tocreate" value="<?=_('Create pledge') ?>">
</p>
<?
    print h3(_('The Dull Terms and Conditions'));
    print '<input type="hidden" name="agreeterms" value="1">';

    print "<p>";
    if ($v == 'pin') { ?>
<!-- no special terms for private pledge -->
<?  } else {
        print _('By creating your pledge you also consent to the syndication of your pledge to other sites &mdash; this means that other people will be able to display your pledge and your name');
        if ($data['country'] == "GB" && $local) {
            print _(', and use (but not display) your postcode to locate your pledge in the right geographic area');
        }
        print '. ';
        print _('The purpose of this is simply to give your pledge
greater publicity and a greater chance of succeeding.');
        print ' ';
    }
    print _("Rest assured that we won't ever give or sell anyone your email address.");
}

#############################################################################
# Features

function microsites_location_allowed() {
    global $microsite;
    if ($microsite == 'o2') return false;
    return true;
}

/* microsites_private_allowed
 * Returns whether private pledges are offered in new pledge dialog. */
function microsites_private_allowed() {
    global $microsite;
    if ($microsite == 'interface' || $microsite == 'global-cool' || $microsite == 'livesimply' || $microsite == 'o2')
        return false;
    return true;
}

/* microsites_categories_allowed
 * Returns whether categories are used for this microsite at all */
function microsites_categories_allowed() {
    global $microsite;
    if ($microsite == 'livesimply') return false;
    return true;
}

/* microsites_categories_page3
 * Returns whether categories are offered in the usual place in the new pledge dialog. */
function microsites_categories_page3() {
    global $microsite;
    if ($microsite == 'o2' || !microsites_categories_allowed()) return false;
    return true;
}

/* microsites_postal_address_allowed
 * Returns whether the creator's postal address is asked for in new pledge
 * dialog. */
function microsites_postal_address_allowed() {
    global $microsite;
    if ($microsite == 'livesimply' || $microsite == 'o2')
        return true;
    else
        return false;
}

function o2_postcode_lookup() {
    $pcs = array(''=>'(choose one)',
        'BL9 9QL' => 'Bury', 'G3 8EP' => 'Glasgow',
        'LS11 0NE' => 'Leeds', 'WA7 3QA' => 'Preston Brook',
        'SL1 4DX' => 'Slough');
    return $pcs;
}

/* For displaying the address fetching page (LiveSimply and O2 only) */
function microsites_new_pledges_stepaddr($data, $errors) {
    global $microsite;
    if ($microsite == 'o2') {
        $postcode = isset($data['address_postcode']) ? $data['address_postcode'] : '';
        $directorate = isset($data['address_1']) ? $data['address_1'] : '';
?>

<p>Please could you provide your location and directorate:</p>

<p><strong>Location:</strong>
<select name="address_postcode" style="width:auto">
<?      $pcs = o2_postcode_lookup();
        foreach ($pcs as $pc => $str) {
            print '<option';
            if ($pc == $postcode) print ' selected';
            print ' value="' . $pc . '">' . $str;
        }
?>
</select></p>
<p>or other postcode:
<input type="text" name="address_postcode_override" id="address_postcode_override" value="<? if (isset($data['address_postcode_override'])) print htmlspecialchars($data['address_postcode_override']) ?>" size="20">
</p>

<p><strong>Directorate:</strong>
<select name="address_1" style="width:auto">
<?      $ds = array('(choose one)', 'Capability & Innovation',
            'COO', 'Customer Directorate', 'Finance',
            'Human Resources', 'Marketing', 'Sales & Retail');
        foreach ($ds as $d) {
            print '<option';
            if ($d == $directorate) print ' selected';
            if ($d == '(choose one)') print ' value=""';
            print '>' . htmlspecialchars($d);
        }
?>
</select>
</p>

<?  } else { ?>
<p>Please take a moment to fill in this form. It's not obligatory but the
information you provide us will help us in evaluating the success of the
<em>live</em>simply challenge.

<p><strong><?=_('Your address:') ?></strong> 
<br><input<? if (array_key_exists('address_1', $errors)) print ' class="error"' ?> onblur="fadeout(this)" onfocus="fadein(this)" type="text" name="address_1" id="address_1" value="<? if (isset($data['address_1'])) print htmlspecialchars($data['address_1']) ?>" size="30">
<br><input<? if (array_key_exists('address_2', $errors)) print ' class="error"' ?> onblur="fadeout(this)" onfocus="fadein(this)" type="text" name="address_2" id="address_2" value="<? if (isset($data['address_2'])) print htmlspecialchars($data['address_2']) ?>" size="30">
<br><input<? if (array_key_exists('address_3', $errors)) print ' class="error"' ?> onblur="fadeout(this)" onfocus="fadein(this)" type="text" name="address_3" id="address_3" value="<? if (isset($data['address_3'])) print htmlspecialchars($data['address_3']) ?>" size="30">
<br><strong><?=_('Town:') ?></strong> 
<br><input<? if (array_key_exists('address_town', $errors)) print ' class="error"' ?> onblur="fadeout(this)" onfocus="fadein(this)" type="text" name="address_town" id="address_town" value="<? if (isset($data['address_town'])) print htmlspecialchars($data['address_town']) ?>" size="20">
<br><strong><?=_('County:') ?></strong> 
<br><input<? if (array_key_exists('address_county', $errors)) print ' class="error"' ?> onblur="fadeout(this)" onfocus="fadein(this)" type="text" name="address_county" id="address_county" value="<? if (isset($data['address_county'])) print htmlspecialchars($data['address_county']) ?>" size="20">
<br><strong><?=_('Postcode:') ?></strong> 
<br><input<? if (array_key_exists('address_postcode', $errors)) print ' class="error"' ?> onblur="fadeout(this)" onfocus="fadein(this)" type="text" name="address_postcode" id="address_postcode" value="<? if (isset($data['address_postcode'])) print htmlspecialchars($data['address_postcode']) ?>" size="20">
<br><strong><?=_('Country:') ?></strong> 
<? 
    gaze_controls_print_country_choice(microsites_site_country(), null, $errors, array('noglobal'=>true, 'fieldname' => 'address_country')); ?>
</p>

<?  }
}

/* microsites_new_pledges_prominence
 * Returns prominence that new pledges have by default.
 */
function microsites_new_pledges_prominence() {
    global $microsite;
    if ($microsite == 'livesimply' || $microsite == 'o2')
        return 'frontpage';
    elseif ($microsite == 'global-cool')
        return 'backpage';
    else
        return 'calculated';
}

/* microsites_other_people 
 * Returns text to describe other people by default when making
 * a new pledge.
 */
function microsites_other_people() {
    global $microsite;
    if ($microsite == 'interface')
        return 'other Interfacers'; // deliberately not translated
    elseif ($microsite == 'london')
        return 'other Londoners'; // deliberately not translated
    elseif ($microsite == 'global-cool')
        return 'other cool people'; // deliberately not translated
    elseif ($microsite == 'catcomm')
        return 'other CatComm supporters'; // deliberately not translated
    elseif ($microsite == 'livesimply' || $microsite == 'o2')
        return 'other people'; // deliberately not translated
    else
        return _('other local people');
}

/* microsites_frontpage_comments_allowed
 * Whether or not comments are displayed for the microsite.
 */
function microsites_comments_allowed() {
    global $microsite;
    if ($microsite == 'global-cool')
        return false;
    return true;
}

/* microsites_chivvy_sql
 * SQL fragment for whether a pledge gets any chivvy emails.
 */
function microsites_chivvy_sql() {
    return "microsite is null or (microsite <> 'livesimply' and microsite <> 'o2')";
}

#############################################################################
# Pledge indices

/* microsites_filter_main
 * Criteria for most important pledges to show on front page / list pages.
 */
function microsites_filter_main(&$sql_params) {
    global $microsite;
    if (!$microsite)
        die("Internal error, microsites_filter_main should only be called for microsite");

    if ($microsite == 'everywhere')
        return "(1=1)";
    if ($microsite == 'london')
        return "(pledges.id in (select pledge_id from pledge_find_nearby(51.5,-0.1166667, 25)))";
    $sql_params[] = $microsite;
    return "(microsite = ?)";
}

/* microsites_filter_general
 * Criteria for pledges to show on list pages, in addition to the 
 * microsites_filter_main ones above
 */
function microsites_filter_general(&$sql_params) {
    global $microsite;
    if (!$microsite)
        die("Internal error, microsites_filter_general should only be called for microsite");

    return "(1=0)";
}

/* microsites_filter_foreign
 * Criteria for other pledges to show, if there aren't enough main/general
 * ones for the front page to look busy. */
function microsites_filter_foreign(&$sql_params) {
    global $microsite;
    if (!$microsite)
        die("Internal error, microsites_filter_foreign should only be called for microsite");

    if ($microsite == 'everywhere')
        return "(1=0)";
    if ($microsite == 'london')
        return "(pledges.id not in (select pledge_id from pledge_find_nearby(51.5,-0.1166667, 25)))";
    if ($microsite == 'global-cool')
        return "(1=0)"; # Show nothing else on global cool site
    $sql_params[] = $microsite;
    return "(microsite <> ? or microsite is null)";
}

/* microsites_normal_prominences
 * Returns SQL fragment which selects normal prominence pledges. Normally
 * this is just 'normal', but for some microsites may want to include 'backpage'.
 */
function microsites_normal_prominences() {
    global $microsite;
    if ($microsite == 'global-cool' || $microsite == 'catcomm')
        return " (cached_prominence = 'normal' or cached_prominence = 'backpage') ";
    return " (cached_prominence = 'normal') ";
}

/* microsites_list_views
 * Return an array of views available on the All Pledges page. Array
 * is a dictionary with keys the names of pages in list.php, and values the
 * text to describe them as.
 */
function microsites_list_views() {
    global $microsite;
    if ($microsite == 'livesimply' || $microsite == 'o2') {
        return array('all_open'=>_('Open pledges'), 
        'all_closed'=>_('Closed pledges'));
    } else {
        return array('open'=>_('Open pledges'), 'succeeded_open'=>_('Successful open pledges'), 
        'succeeded_closed'=>_('Successful closed pledges'), 'failed' => _('Failed pledges'));
    }
}

# Valid sort options for the All Pledges page
function microsites_list_sort_options() {
    global $microsite;
    $sort = array(
        'creationtime' => _('Start date'), 
        'date'=>_('Deadline')
    );
    if (!microsites_no_target())
        $sort['percentcomplete'] = _('Percent signed');
    if (microsites_categories_allowed())
        $sort['category'] = _('Category');
    if (microsites_sort_by_signers())
        $sort['signers'] = 'Signers';
    if ($microsite == 'o2') {
        $sort['site'] = 'Site';
        $sort['directorate'] = 'Directorate';
    }
    return $sort;
}

#############################################################################
# Login - some microsites get authentication from other sites

/* Person object, stores logged in user who is externally authenticated */
$microsites_external_auth_person = null;

/* microsites_read_external_auth
 * Peform any authentication for microsite. Should create appropriate person
 * record in the database using person_get_or_create, if the authentication
 * succeeds. And store the person object in $microsites_external_auth_person.
 * Return true if authentication is overriden for this microsite.
 * Return false if normal PledgeBank should be used if external one fails.
 */ 
function microsites_read_external_auth() {
    global $microsite, $microsites_external_auth_person;

    if ($microsites_external_auth_person)
        return true;

    if ($microsite == 'global-cool') {
        if (false) {
            $params = array('email' => 'francis@flourish.org', 'name' => 'Francis Irving', 'remember' => 'yes', 'signedIn' => 'yes'); // for testing
        } else {
            // Read cookie
            if (!array_key_exists('auth', $_COOKIE))
                 return true;
            $cool_cookie = $_COOKIE['auth'];
            $cool_cookie = base64_decode($cool_cookie);

            // Decrypt cookie
            $td = mcrypt_module_open('tripledes', '', 'ecb', '');
            if (!$td) err('Failed to mcrypt_module_open');
            $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
            mcrypt_generic_init($td, OPTION_GLOBALCOOL_SECRET, $iv);
            $cool_cookie = mdecrypt_generic($td, $cool_cookie);
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);

            // Read parameters out of Global Cool cookie
            $raw_params = split("\|", $cool_cookie);
            $params = array();
            foreach ($raw_params as $raw_param) {
                list($param, $value) = split("=", $raw_param, 2);
                $params[$param] = trim($value);
            }
        }

        if ($params['signedIn'] != "yes") {
            // They have logged out from Global Cool
            return true;
        }
        if (!validate_email($params['email'])) {
            error_log("Invalid email '" . $params['email']. "' in global-cool cookie");
            return true;
        }

        // Create user, or get existing user from database
        $microsites_external_auth_person = person_get_or_create($params['email'], $params['name']);
        // TODO: record that a login via global cool auth happened here (something analogous to like $P->inc_numlogins())
        db_commit();
        return true;
    }

    // Use normal authentication
    return false;
}

/* microsites_redirect_external_login
 * Return true if auth has been redirected to elsewhere.
 * Return false if normal auth is to be used.*/
function microsites_redirect_external_login() {
    global $microsite;
    if ($microsite == 'global-cool') {
        // See if we are on redirect back from Global Cool login system
        if (get_http_var('stashpost')) {
            if (!pb_person_if_signed_on())
                err('Sorry! Something went wrong while logging into Global Cool. Please check that you have cookies enabled on your browser.');
            stash_redirect(get_http_var('stashpost'));
            exit;
        }
        // Otherwise, redirect to Global Cool login system, with stash key to get back here
        $url = "http://".$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"];
        $st = stash_new_request('POST', $url, $_POST);
        db_commit();
        if (strstr($_SERVER["REQUEST_URI"], '?'))
            $url .= "&stashpost=$st";
        else
            $url .= "?stashpost=$st";
        header("Location: http://www.global-cool.com/auth/?next=" . urlencode($url));
        exit;
    }
    return false;
}

/* microsites_invalid_email_address
 * Returns whether an email address is valid to use for the microsite or not.
 * Return false if it is valid, or a string containing an error message for the
 * user if it is invalid.
 */
function microsites_invalid_email_address($email) {
    global $microsite;
    if ($microsite != 'o2') return false;
    if (preg_match('#@o2\.com$#', $email)) return false;
    return 'You must enter an email address @o2.com.';
}

# Display the More Details box during pledge creation
function microsites_new_pledges_detail_textarea($data) {
    global $microsite;
    $detail = isset($data['detail']) ? htmlspecialchars($data['detail']) : '';
    if ($microsite == 'o2') {
        return '<input type="text" size="60" name="detail" value="' . $detail . '">';
    } else {
        return '<textarea name="detail" rows="10" cols="60">' . $detail . '</textarea>';
    }
}

# Extra checks for step 1 of pledge creation for microsites;
function microsites_step1_error_check($data) {
    global $microsite;
    $error = array();
    if ($email_err = microsites_invalid_email_address($data['email']))
        $error['email'] = $email_err;
    $detail = preg_replace('#\W#', '', $data['detail']);
    if ($microsite == 'o2') {
        if (strlen($detail)>100)
            $error['detail'] = 'Please limit your more information to 100 characters';
        if (!isset($data['category']) || !$data['category'] || $data['category'] == -1)
            $error['category'] = 'Please select a part of the People Promise';
    }
    return $error;
}

# For displaying extra bits on the preview pledge page
function microsites_new_pledges_preview_extras($data) {
    global $microsite;
    if ($microsite != 'o2') return;
    $loc = $data['address_postcode'];
    $lookup = o2_postcode_lookup();
    if (isset($lookup[$loc])) $loc = $lookup[$loc];
    print '<li>Location: <strong>' . $loc . '</strong></li>';
    print '<li>Directorate: <strong>' . $data['address_1'] . '</strong></li>';
}

/* microsites_display_login
 * Return whether or not to display the "Hello, Victor Papanek" message at
 * the top of the page. If you are overriding the login functions above,
 * the microsite header may be displaying this, so shouldn't be duplicated
 * in the PledgeBank header.
 */
function microsites_display_login() {
    global $microsite;
    if ($microsite == 'global-cool')
        return false;
    return true;
}

/* microsites_change_microsite_allowed
 * Returns whether or not you should display the "change/choose site"
 * links around the place.
 */
function microsites_change_microsite_allowed() {
    global $microsite;
    if ($microsite == 'o2' || $microsite == 'livesimply')
        return false;
    return true;
}

/* microsites_show_translate_blurb()
 * Returns whether or not we should display the available languages
 * and "translate into your own language" at the bottom of every page.
 */
function microsites_show_translate_blurb() {
    global $microsite;
    if ($microsite == 'o2' || $microsite == 'livesimply')
        return false;
    return true;
}

/* microsites_show_alert_advert()
 * Returns whether or not we should display a HFYMP advert when you've
 * just signed up for an email alert
 */
function microsites_show_alert_advert() {
    global $microsite;
    if ($microsite == 'o2' || $microsite == 'livesimply')
        return false;
    return true;
} 

/* microsites_display_favicon
 * If a microsite has a special favicon, output it now.
 */
function microsites_display_favicon() {
    global $microsite;
    if ($microsite == 'livesimply')
        print '<link rel="shortcut icon" href="/microsites/livesimply/favicon.ico">';
}

/* microsites_sort_by_signers
 * If a microsite has an extra sort-by-signers option on list pages
 */
function microsites_sort_by_signers() {
    global $microsite;
    if ($microsite == 'o2') return true;
    return false;
}

/* For if a microsite has a special example date on the new pledge page
 */
function microsites_example_date() {
    global $microsite, $pb_time, $lang;
    if ($microsite == 'o2')
        print date('d/m/Y', $pb_time+60*60*24*28);
    else {
        print '"';
        if ($lang=='en-gb')
            print date('jS F Y', $pb_time+60*60*24*28); // 28 days
        elseif ($lang=='eo')
            print strftime('la %e-a de %B %Y', $pb_time+60*60*24*28);
        elseif ($lang=='de')
            print strftime('%e. %B %Y', $pb_time+60*60*24*28);
        elseif ($lang=='zh')
            print strftime('%Y&#24180;%m&#26376;%d&#26085;', $pb_time+60*60*24*28);
        else
            print strftime('%e %B %Y', $pb_time+60*60*24*28);
        print '"';
    }
}

# Return true if this is an intranet installed site
function microsites_intranet_site() {
    global $microsite;
    if ($microsite == 'o2') return true;
    return false;
}

# Return true if all target functionality should be disabled
function microsites_no_target() {
    global $microsite;
    if ($microsite == 'o2') return true;
    return false;
}

# Return true if microsite has SMS at all
function microsites_has_sms() {
    global $microsite;
    if ($microsite == 'o2') return false;
    return true;
}

# Return true if microsite has flyers
function microsites_has_flyers() {
    global $microsite;
    if ($microsite == 'o2') return false;
    return true;
}

# Help for blank searches
function microsites_search_help() {
    global $microsite;
    if ($microsite == 'o2') {
        print p(_('You can search for:'));
        print "<ul>";
        print li(_("<strong>Any words</strong>, to find pledges and comments containing those words"));
        print li("The name or email address of <strong>a person</strong>, to find pledges they have made or signed");
        print "</ul>";
    } else {
        print p(_('You can search for:'));
        print "<ul>";
        print li(_("The name of a <strong>town or city</strong> near you, to find pledges in your area"));
        if (!microsites_site_country() || microsites_site_country() == 'GB')
            print li(_("A <strong>postcode</strong> or postcode area, if you are in the United Kingdom"));
        print li(_("<strong>Any words</strong>, to find pledges and comments containing those words"));
        print li(_("The name of <strong>a person</strong>, to find pledges they made or signed publically"));
        print "</ul>";
    }
}

?>
