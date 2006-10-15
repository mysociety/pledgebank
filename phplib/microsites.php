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
 * $Id: microsites.php,v 1.46 2006-10-15 10:29:02 francis Exp $
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
                         'livesimply' => 'Live Simply Promise');

/* Other domains which refer to microsites (must be one-to-one as reverse map used to make URLs) */
if (OPTION_PB_STAGING) {
    $microsites_from_extra_domains = array('pledge.global-cool' => 'global-cool'); # Francis's local test domain
} else {
    $microsites_from_extra_domains = array('pledge.global-cool.com' => 'global-cool');
}
$microsites_to_extra_domains = array_flip($microsites_from_extra_domains);

/* These are listed on /where */
$microsites_public_list = array('everywhere' => _('Everywhere &mdash; all countries in all languages'),
                                'london' => _('London (United Kingdom)'),
                                'global-cool' => _('Global Cool (One by One, Ton by Ton)'),
                                'catcomm' => _('Catalytic Communities')
                                );

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

#############################################################################
# Styling

/* microsites_logo
 * Returns HTML to use for logo of microsite, or country. */
function microsites_logo() {
    global $microsite;
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
<a href="http://www.catcomm.org"><img src="/microsites/livesimply/promise_banner.jpg" alt="Live Simply Promise" align="left"
    style="
    background-color: #ffffff;
    float: center;
    padding: 0px;
    margin: 0px;
    border: none;
    "></a>
';
    } else {
        $country_name = pb_site_country_name();
        return '
<h1><a href="/"><span id="logo_pledge">' . _('Pledge') . '</span><span id="logo_bank">' . _('Bank') . '</span></a>
<span id="countrytitle">'.$country_name.'
<a href="/where">' . _('(change)') . '</a></span></h1>';
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

/* microsites_frontpage_has_local_emails
 * Whether or not the local alert signup box is present on the
 * top of the front page.
 */
function microsites_frontpage_has_local_emails() {
    global $microsite;
    if ($microsite == 'global-cool')
        return false;
    if ($microsite == 'catcomm')
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
    global $microsite;
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
        ?><h2>Promise to live simply!</h2>
        <p>Text needed here</p>

        <?
        $tom = false;
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

    # Give how it works explanation
    global $lang; if ($lang == 'en-gb') { ?>
<p><a href="tom-on-pledgebank-vbr.mp3"><?=_('Listen to how PledgeBank
works</a>, as explained by mySociety\'s director Tom Steinberg.
Or <a href="/explain">read a full transcript') ?></a>.</p>
<? } else { ?>
<p><?=_('<a href="/explain">Find out how PledgeBank
works</a>, as explained by mySociety\'s director Tom Steinberg.')?></p>
<? }  
    
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
    if ($microsite == 'global-cool')
        return false;
    return true;
}

/* microsites_frontpage_extra_blurb
 * Extra box of text to put in right hand side */
function microsites_frontpage_extra_blurb() {
    global $microsite;

    if ($microsite == 'catcomm') {
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
    } elseif ($microsite == 'livesimply') {
?>
<div id="extrablurb">
<h2>About Live Simply</h2>
<p>Text needed here</p>
</div>
<?
    }

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

/* microsites_credit_footer
 * Display extra text at the bottom of the page.
 */
function microsites_credit_footer() {
    global $microsite;
    if ($microsite == 'london') {
?>
<div id="sponsor"><img src="/microsites/pearsfoundation_solid.jpg" border="0" alt="Supported by The Pears Foundation"></div>
<?  }
}

#############################################################################
# Features

/* microsites_syndication_warning
 * Do terms and conditions need to warn that we'll syndicate?
 */
function microsites_syndication_warning() {
    global $microsite;
    if ($microsite == 'interface' || $microsite == 'livesimply')
        return false;
    else
        return true;
}

/* microsites_private_allowed
 * Returns whether private pledges are offered in new pledge dialog */
function microsites_private_allowed() {
    global $microsite;
    if ($microsite == 'interface' || $microsite == 'global-cool' || $microsite == 'livesimply')
        return false;
    else
        return true;
}

/* microsites_new_pledges_prominence
 * Returns prominence that new pledges have by default
 */
function microsites_new_pledges_prominence() {
    global $microsite;
    if ($microsite == 'interface' || $microsite == 'livesimply')
        return 'frontpage';
    elseif ($microsite == 'global-cool')
        return 'backpage';
    else
        return 'calculated';
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
    elseif ($microsite == 'catcomm')
        return 'other CatComm supporters'; // deliberately not translated
    elseif ($microsite == 'livesimply')
        return 'other people who want to live simply'; // deliberately not translated
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

#############################################################################
# Pledge indices

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



?>
