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
 * $Id: microsites.php,v 1.22 2006-07-26 16:20:31 francis Exp $
 * 
 */

/* Codes of microsites, and name displayed next to PledgeBank logo */
$microsites_list = array('everywhere' => _('Everywhere'),
                         'london' => 'London',
                         '365act' => '365 Ways',
                         'glastonbury' => 'Glastonbury',
                         'interface' => 'Interface',
                         'global-cool' => 'Global Cool');

/* Other domains which refer to microsites */
if (OPTION_PB_STAGING) {
    $microsites_from_extra_domains = array('pledge.global-cool' => 'global-cool'); # Francis's local test domain
} else {
    $microsites_from_extra_domains = array('pledge.global-cool.com' => 'global-cool');
}
$microsites_to_extra_domains = array_flip($microsites_from_extra_domains);

/* These are listed on /where */
$microsites_public_list = array('everywhere' => _('Everywhere &mdash; all countries in all languages'),
                                'london' => _('London (United Kingdom)'),
                                '365act' => _('365 Ways to Change the World'));

/* microsites_get_name 
 * Returns display name of microsite if we are on one. e.g. Glastonbury */
function microsites_get_name() {
    global $microsite, $microsites_list;
    if (array_key_exists($microsite, $microsites_list))
        return $microsites_list[$microsite];
    return null;
}

/* microsites_logo
 * Returns HTML to use for logo of microsite, or country. */
function microsites_logo() {
    global $microsite;
    if ($microsite && $microsite == 'interface') {
        return '
<h1><a href="/"><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></a><span id="beta">Beta</span>
<span id="countrytitle"><img src="/interface-logo.gif" alt="interface">
<a href="/where">' . _('(change)') . '</a></span></h1>';

    } elseif ($microsite && $microsite == '365act') {
        return '
<a href="http://www.365act.com"><img src="/365-logo-small.png" alt="365 Act" align="left"
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
        $country_name = pb_site_country_name();
        return '
<h1><a href="/"><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span> <span id="logo_pledge">London</span></a>
<span id="countrytitle"><a href="/where">' . _('(change)') . '</a></span></h1>';

    } else {
        $country_name = pb_site_country_name();
        return '
<h1><a href="/"><span id="logo_pledge">' . _('Pledge') . '</span><span id="logo_bank">' . _('Bank') . '</span></a>
<span id="countrytitle">'.$country_name.'
<a href="/where">' . _('(change)') . '</a></span></h1>';
    }
}

/* microsites_css_file
 * Return path and filename of URL for CSS file */
function microsites_css_file() {
    global $microsite;
    if ($microsite && $microsite == 'interface') {
        return "/interface.css";
    } elseif ($microsite && $microsite == 'glastonbury') {
        return "/glastonbury.css";
    } elseif ($microsite && $microsite == '365act') {
        return "/365act.css";
    } elseif ($microsite && $microsite == 'london') {
        return "/london.css";
    } elseif ($microsite && $microsite == 'global-cool') {
        return "/globalcool.css";
    }
    return "/pb.css";
}

/* microsites_private_allowed
 * Returns whether private pledges are offered in new pledge dialog */
function microsites_private_allowed() {
    global $microsite;
    if ($microsite == 'interface' || $microsite == 'global-cool')
        return false;
    else
        return true;
}

/* microsites_new_pledges_frontpage 
 * Returns true if new pledges are to be made 'frontpage' rather 
 * than 'calculated' by default */
function microsites_new_pledges_frontpage() {
    global $microsite;
    if ($microsite == 'interface' || $microsite == 'global-cool')
        return true;
    else
        return false;
}

/* microsites_other_people 
 * Returns text to describe other people by default when making
 * a new pledge
 */
function microsites_other_people() {
    global $microsite;
    if ($microsite == 'interface')
        return 'other Interfacers'; // deliberately not translated
    elseif ($microsite == 'london')
        return 'other Londoners'; // deliberately not translated
    elseif ($microsite == 'global-cool')
        return 'other cool people'; // deliberately not translated
    else
        return _('other local people');
}

/* microsites_syndication_warning
 * Do terms and conditions need to warn that we'll syndicate?
 */
function microsites_syndication_warning() {
    global $microsite;
    if ($microsite == 'interface')
        return false;
    else
        return true;
}

/* microsites_frontpage_has_intro
 * Whether or not the "tell the world" motivation intro box is present on the
 * front page.
 */
function microsites_frontpage_has_intro() {
    global $microsite;
    if ($microsite == 'global-cool')
        return false;
    return true;
}

/* microsites_frontpage_intro 
 * Introduction text to show on front page of site.
 */
function microsites_frontpage_intro() {
    global $microsite, $site_country;
    $tom = null;
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
	In the summer of 2012 the eyes of the world will be on London for a
	fortnight as the Olympics games return to the capital for the third
	time in its history. This site is collecting pledges encouraging
	Londoners to work together on projects to turn London from a great city
	to the greatest city in the world by 2012.
	<?
    } else {
        # Main site
        $tom = _('"We all know what it is like to feel powerless, that our own
        actions can\'t really change the things that we want to change.
        PledgeBank is about beating that feeling..."');
    }
    
    # Quote from Tom, and his photo
    if ($tom) {
?><h2><?=_('Tell the world &#8220;I&#8217;ll do it, but only if you&#8217;ll help me do it&#8221;') ?></h2>
<blockquote class="noindent"><a href="tom-on-pledgebank-vbr.mp3"><img src="tomsteinberg_small.jpg"
alt="" style="vertical-align: top; float:left; margin:0 0.5em 0 0; border: solid 2px #9C7BBD;
"></a>
<?=$tom?>
</blockquote>
<?  
    }

    # Always give how it works explanation
    global $lang; if ($lang == 'en-gb') { ?>
<p><a href="tom-on-pledgebank-vbr.mp3"><?=_('Listen to how PledgeBank
works</a>, as explained by mySociety\'s director Tom Steinberg.
Or <a href="/explain">read a full transcript') ?></a>.</p>
<? } else { ?>
<p><?=_('<a href="/explain">Find out how PledgeBank
works</a>, as explained by mySociety\'s director Tom Steinberg.')?></p>
<? }  ?>
<?
}

/* microsites_frontpage_has_start_your_own
 * Whether or not the "start your own pledge" box is present on the front page.
 */
function microsites_frontpage_has_start_your_own() {
    global $microsite;
    if ($microsite == 'global-cool')
        return false;
    return true;
}

/* microsites_frontpage_has_offline_secrets
 * Whether or not the "scissors and phone" box is present on the front page.
 */
function microsites_frontpage_has_offline_secrets() {
    global $microsite;
    if ($microsite == 'global-cool')
        return false;
    return true;
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

/* microsites_filter_main
 * Criteria for most important pledges to show on front page / list pages.
 */
function microsites_filter_main(&$sql_params) {
    global $microsite;
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
    return "(1=0)";
}

/* microsites_filter_foreign
 * Criteria for other pledges to show, if there aren't enough main/general
 * ones for the front page to look busy. */
function microsites_filter_foreign(&$sql_params) {
    global $microsite;
    if ($microsite == 'everywhere')
        return "(1=0)";
    if ($microsite == 'london')
        return "(pledges.id not in (select pledge_id from pledge_find_nearby(51.5,-0.1166667, 25)))";
    if ($microsite == 'global-cool')
        return "(1=0)"; # Show nothing else on global cool site
    $sql_params[] = $microsite;
    return "(microsite <> ?)";
}

/* microsites_site_country
 * Default country for microsite.  Used for search, and for default country on
 * alerts / new pledge form */
function microsites_site_country() {
    global $site_country, $microsite;
    if ($microsite) {
        if ($microsite == 'london')
            return 'GB';
        return null;
    }
    return $site_country;
}

/* microsites_redirect
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
    # TODO: redirect back again, if on a non-global-cool pledge on global-cool
    # domain

    # If necessary, do the redirect
    if ($microsite != $redirect_microsite) {
        $newurl = pb_domain_url(array('path' => $_SERVER['REQUEST_URI'], 'microsite' => $redirect_microsite));
        header("Location: $newurl");
        exit;
    }
}

/* microsites_credit_footer
 * Display extra text at the bottom of the page.
 */
function microsites_credit_footer() {
    global $microsite;
    if ($microsite == 'london') {
?>
<div id="sponsor"><img src="/pearsfoundation_solid.jpg" border="0" alt="Supported by The Pears Foundation"></div>
<?  }
}


?>
