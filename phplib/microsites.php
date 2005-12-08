<?php
/*
 * microsites.php:
 * Microsites are special sub-sites for Glastonbury festival etc.
 * This file contains lots of functions which return the values appropriate
 * to each microsite.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: microsites.php,v 1.12 2005-12-08 20:33:08 francis Exp $
 * 
 */

/* Codes of microsites, and name displayed next to PledgeBank logo */
$microsites_list = array('everywhere' => _('Everywhere'),
                         '365act' => '365 Ways',
                         'glastonbury' => 'Glastonbury',
                         'interface' => 'Interface');

/* These are listed on /where */
$microsites_public_list = array('everywhere' => _('Everywhere &mdash; all countries in all languages'),
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

    } else {
        $country_name = pb_site_country_name();
        return '
<h1><a href="/"><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></a><span id="beta">Beta</span>
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
    }
    return "/pb.css";
}

/* microsites_private_allowed
 * Returns whether private pledges are offered in new pledge dialog */
function microsites_private_allowed() {
    global $microsite;
    if ($microsite == 'interface')
        return false;
    else
        return true;
}

/* microsites_new_pledges_frontpage 
 * Returns true if new pledges are to be made 'frontpage' rather 
 * than 'calculated' by default */
function microsites_new_pledges_frontpage() {
    global $microsite;
    if ($microsite == 'interface')
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

?>
