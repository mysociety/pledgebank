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
 * $Id: microsites.php,v 1.126 2008-09-15 13:34:29 matthew Exp $
 * 
 */

#############################################################################
# Name and domain information

/* Codes of microsites, and name displayed next to PledgeBank logo */
$microsites_list = array('everywhere' => _('Everywhere'),
                         'london' => 'London',
                         'interface' => 'Interface',
                         'catcomm' => 'CatComm',
);

/* Other domains which refer to microsites (must be one-to-one as reverse map used to make URLs) */
if (OPTION_PB_STAGING) {
    # Francis's local test domains
    $microsites_from_extra_domains = array();
} else {
    # If you alter this, also alter web/poster.cgi which has a microsites_from_extra_domains variable
    $microsites_from_extra_domains = array();
}
$microsites_to_extra_domains = array_flip($microsites_from_extra_domains);

/* These are listed on /where */
$microsites_public_list = array('everywhere' => _('Everywhere &mdash; all countries in all languages'),
                                'london' => _('London (United Kingdom)'),
                                'catcomm' => _('Catalytic Communities')
                                );

/* As sometimes microsites.php is included before the locale is set... */
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
    if (OPTION_PB_STAGING) {
        return false;
    }

    global $microsite;
    if (!$microsite) 
        return true;
    if ($microsite == 'everywhere' || $microsite == 'london')
        return true;

    /* Don't do any cross site tracking on other sites, to avoid breaking
     * any privacy policies of organisations using the microsites. */
    return false;
}

/* microsites_google_conversion_tracking LABEL
 * Whether to use Google AdWords conversion tracking for signers/new pledges.
 * Outputs Javascript code if yes.  Label is the code used by adverts, things
 * like "signup", "default", "lead".
 */
function microsites_google_conversion_tracking($label) {
    global $microsite;

    // Only do it on the main site.
    if (OPTION_BASE_URL != "http://www.pledgebank.com")
        return false;

    if (!$microsite || $microsite == 'everywhere' || $microsite == 'london') {
?>
<!-- Google Code for signup Conversion Page -->
<script language="JavaScript" type="text/javascript">
<!--
var google_conversion_id = 1067468161;
var google_conversion_language = "en_GB";
var google_conversion_format = "1";
var google_conversion_color = "666666";
if (1) {
  var google_conversion_value = 1;
}
var google_conversion_label = "<?=$label?>";
//-->
</script>
<script language="JavaScript" src="http://www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<img height=1 width=1 border=0 src="http://www.googleadservices.com/pagead/conversion/1067468161/imp.gif?value=1&label=signup&script=0">
</noscript>
<?
    return true;
}

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
    $redirect_microsites = array();
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
    global $microsite, $lang;
    if ($microsite && $microsite == 'interface') {
        return '
<h1><a id="logo" href="/"><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></a><span id="beta">Beta</span>
<span id="countrytitle"><img src="/microsites/interface-logo.gif" alt="interface">
<a href="/where">' . _('(change)') . '</a></span>
<span id="tagline"><small><br>' . _('I&rsquo;ll do it, but <strong>only</strong> if you&rsquo;ll help') . '</small></span></h1>';
    } elseif ($microsite && $microsite == 'london') {
        return '
<h1><a id="logo" href="/"><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span> <span id="logo_pledge">London</span></a>
<span id="countrytitle"><a href="/where">' . _('(change)') . '</a></span>
<span id="tagline"><small><br>' . _('I&rsquo;ll do it, but <strong>only</strong> if you&rsquo;ll help') . '</small></span></h1>';

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
<a id="logo" href="/"><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></a><span id="beta">Beta</span>
<span id="countrytitle"><a href="/where">' . _('(other PledgeBanks)') . '</a></span>
<span id="tagline"><small><br>' . _('I&rsquo;ll do it, but <strong>only</strong> if you&rsquo;ll help') . '</small></span>
</h1>
';
    } elseif ($lang == 'zh') {
        $country_name = pb_site_country_name();
        return '
<h1 style="padding-bottom:0.25em;"><a id="logo" href="/"><span id="logo_zh">' . _('Pledge') . _('Bank') . '</span>
<small><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></small></a>
<span id="countrytitle"><a href="/where">' . $country_name . '</a></span>
<span id="tagline"><small><br>' . _('I&rsquo;ll do it, but <strong>only</strong> if you&rsquo;ll help') . '</small></span></h1>';
    } else {
        $country_name = pb_site_country_name();
        return '
<h1><a id="logo" href="/"><span id="logo_pledge">' . _('Pledge') . '</span><span id="logo_bank">' . _('Bank') . '</span></a>
<span id="countrytitle"><a href="/where">' . $country_name . '</a></span>
<span id="tagline"><small><br>' . _('I&rsquo;ll do it, but <strong>only</strong> if you&rsquo;ll help') . '</small></span></h1>';
    }
}

/* microsites_html_title_slogan
 * On the front page, slogon to display before site name */
function microsites_html_title_slogan() {
    global $microsite;

    # TRANS: 'PledgeBank' here is the first part of the HTML title which appears on browser windows, and search engines
    return _("Tell the world \"I'll do it, but only if you'll help\"");
}
 
/* microsites_css_files
 * Return array of URLs for CSS files */
function microsites_css_files() {
    global $microsite;

    $styles = array();
    // Microsite PledgeBank style sheet
    if ($microsite && in_array($microsite, array(
                'interface', 
                'london', 
                'catcomm',
            ))) {
        $styles[] = "/microsites/autogen/$microsite.css";
    } else {
        $styles[] = "/pb.2.css";
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

    $P = pb_person_if_signed_on(true); /* Don't renew any login cookie. */
    debug_timestamp(true, "retrieved person record");

    $menu = array();
    $menu[_('Start a Pledge')] = "/new";
    $menu[_('All Pledges')] = "/list";
    if ($P)
        $menu[_('My Pledges')] = "/my";
    else
        $menu[_('Login')] = "/my";
    $menu[_('About')] = "/faq";

    return $menu;
}

# Whether a site has local alerts at all!
function microsites_local_alerts() {
    global $microsite;
    return true;
}

/* microsites_frontpage_has_local_emails
 * Whether or not the local alert signup box is present on the
 * top of the front page.
 */
function microsites_frontpage_has_local_emails() {
    global $microsite;
    if ($microsite == 'catcomm' || !microsites_local_alerts())
        return false;
    return true;
}

/* microsites_frontpage_has_intro
 * Whether or not the "tell the world" motivation intro box is present on the
 * front page. That page is defined by microsites_frontpage_intro below.
 */
function microsites_frontpage_has_intro() {
    global $microsite;
    return true;
}

/* microsites_frontpage_intro 
 * Introduction text to show on front page of site. The is only
 * called if microsites_frontpage_has_intro() above returns true.
 */
function microsites_frontpage_intro() {
    global $microsite, $lang;
    $tom = false;
    if ($microsite == 'interface') {
        echo h2(_('&#8220;I&#8217;ll do it, but only if you&#8217;ll help me do it&#8221;'));
?>
<p>Hello, and welcome to the demo version of PledgeBank we've built for
internal use at Interface. PledgeBank is a handy tool which is good at
getting people to do social or environmental things they
want to do but normally never get round to.</p><?
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
    For technical help, contact us at <a href="mailto:techhelp@catcomm.org">techhelp@catcomm.org</a>.
        <?
    } else {
        # Main site
        echo h2(_('PledgeBank successes'));
        global $success_summary;
        shuffle($success_summary);
        echo $success_summary[0];
        echo '<p align="right"><a href="/success">' . _('More success stories') . '</a></p>';
    }
}

/* microsites_frontpage_has_start_your_own
 * Whether or not the "start your own pledge" box is present on the front page.
 */
function microsites_frontpage_has_start_your_own() {
    global $microsite;
    return true;
}

/* microsites_frontpage_extra_blurb
 * Extra box of text to put in right hand side. */
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
    }

}

/* microsites_frontpage_sign_invitation_text
 * Title text above the list of current pledges. */
function microsites_frontpage_sign_invitation_text() {
    global $microsite;

    print '<h2 class="head_with_mast">';
    print _('Sign a pledge');
    print "</h2>";
}

/* microsites_frontpage_has_offline_secrets
 * Whether or not the "scissors and phone" box is present on the front page.
 */
function microsites_frontpage_has_offline_secrets() {
    global $microsite;
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

/* microsites_new_pledges_toptips
 * Tips on making a pledge that will work, for top of new pledge page.
 */
function microsites_new_pledges_toptips() {
    global $microsite;
    print '<div id="tips">';
    print microsites_toptips_normal();
    print '</div>';
}

function microsites_toptips_normal() {
    global $site_country;
    $percent_successful_above_100 = percent_success_above(100);
    $out = '<h2>' . _('Top Tips for Successful Pledges') . '</h2>';
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
    /* $out .= '<li>' . _("<strong>Search first</strong>. Enter a keyword or two related to your pledge
in the search box in the top righthand corner. If there's already a pledge related to yours, consider
joining in on that pledge.") . '</li>'; */
    $out .= '<li>' . _("<strong>A picture &ndash; or audio clip, or video &ndash; is worth a thousand
words</strong>. You can add a picture to your pledge once you've created it, or consider including a
link in your pledge to a picture, audio, or video if you have one.") . '</li>';
    /* if ($site_country == 'US') {
        $out .= '<li>' . "If your pledge is about raising money and you want people to be able to
donate straight away, think about using <a href=\"http://www.changingthepresent.org/PledgeBank\">Changing
the Present</a> if you're giving to a registered non-profit or <a href=\"http://www.chipin.com/\">ChipIn</a>
if you're raising money for something else. Pledges are more successful when signers can process a donation
online, rather than sending a check." . '</li>';
    } */
    $out .= '</ol>';
    return $out;
}

/* microsites_contact_intro
 * Description at top of contact page.
 */
function microsites_contact_intro() {
    global $microsite;
    print "<p>";
    print _('Was it useful?  How could it be better?
    We make PledgeBank and thrive off feedback, good and bad.
    Use this form to contact us.');
    $contact_email = str_replace('@', '&#64;', OPTION_CONTACT_EMAIL);
    print ' ';
    printf(_('If you prefer, you can email %s instead of using the form.'), '<a href="mailto:' . $contact_email . '">' . $contact_email . '</a>');
    print "</p>";
}

/* microsites_pledge_closed_text
 * Text to display in red box when a pledge is closed.
 */
function microsites_pledge_closed_text() {
    global $microsite;
    return strong(_('This pledge is now closed, as its deadline has passed.'));
}

/* microsites_signup_extra_fields
 * Add any extra input fields or text to the pledge signup box.
 */
function microsites_signup_extra_fields($errors) {
    global $microsite;
}

/* microsites_signup_extra_fields_validate
 * Validate the values of any extra fields during signup.
 */
function microsites_signup_extra_fields_validate(&$errors) {
    global $microsite;
}

function microsites_new_pledges_terms_and_conditions($data, $v, $local, $errors) {
    global $microsite;

    $P = person_if_signed_on();
    if (!$P) {
        print p(_('Do you have a PledgeBank password?'));
        print '<p><input type="radio" name="loginradio" id="loginradio2"> <label for="password">' . _('Yes, please enter it:') . '</label> <input type="password" name="password" id="password" value=""
onchange="check_login_password_radio()" onfocus="check_login_password_radio()"></p>
<p id="email_row"><input type="radio" name="loginradio" id="loginradio1"> <label for="loginradio1">' . _('No, or you&rsquo;ve forgotten it') . '</label>'.
    '<p id="email_blurb"><small>&mdash; ' . _('we&rsquo;ll send you a confirmation email instead.') . '</small>';
    }
?>
<p style="text-align: right;">
<input id="next_step" type="submit" name="tocreate" value="<?=_('Create pledge') ?>">
</p>
<?
    print '<p><small>' . _('You confirm that you wish PledgeBank to display the pledge in your name, and that you agree to the terms and conditions below.') . '</small></p>';;

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
    return true;
}

/* microsites_private_allowed
 * Returns whether private pledges are offered in new pledge dialog. */
function microsites_private_allowed() {
    global $microsite;
    if ($microsite == 'interface')
        return false;
    return true;
}

/* microsites_categories_allowed
 * Returns whether categories are used for this microsite at all */
function microsites_categories_allowed() {
    global $microsite;
    return true;
}

/* microsites_categories_page3
 * Returns whether categories are offered in the usual place in the new pledge dialog. */
function microsites_categories_page3() {
    global $microsite;
    if (!microsites_categories_allowed()) return false;
    return true;
}

/* microsites_postal_address_allowed
 * Returns whether the creator's postal address is asked for in new pledge
 * dialog. */
function microsites_postal_address_allowed() {
    global $microsite;
    return false;
}

/* For displaying the address fetching page (LiveSimply and O2 only) */
function microsites_new_pledges_stepaddr($data, $errors) {
    global $microsite;
?>
<p>Please take a moment to fill in this form. It's not obligatory but the
information you provide us will help us in evaluating the success of the
<em>live</em>simply challenge.

<p><strong><?=_('Your address:') ?></strong> 
<br><input<? if (array_key_exists('address_1', $errors)) print ' class="error"' ?> type="text" name="address_1" id="address_1" value="<? if (isset($data['address_1'])) print htmlspecialchars($data['address_1']) ?>" size="30">
<br><input<? if (array_key_exists('address_2', $errors)) print ' class="error"' ?> type="text" name="address_2" id="address_2" value="<? if (isset($data['address_2'])) print htmlspecialchars($data['address_2']) ?>" size="30">
<br><input<? if (array_key_exists('address_3', $errors)) print ' class="error"' ?> type="text" name="address_3" id="address_3" value="<? if (isset($data['address_3'])) print htmlspecialchars($data['address_3']) ?>" size="30">
<br><strong><?=_('Town:') ?></strong> 
<br><input<? if (array_key_exists('address_town', $errors)) print ' class="error"' ?> type="text" name="address_town" id="address_town" value="<? if (isset($data['address_town'])) print htmlspecialchars($data['address_town']) ?>" size="20">
<br><strong><?=_('County:') ?></strong> 
<br><input<? if (array_key_exists('address_county', $errors)) print ' class="error"' ?> type="text" name="address_county" id="address_county" value="<? if (isset($data['address_county'])) print htmlspecialchars($data['address_county']) ?>" size="20">
<br><strong><?=_('Postcode:') ?></strong> 
<br><input<? if (array_key_exists('address_postcode', $errors)) print ' class="error"' ?> type="text" name="address_postcode" id="address_postcode" value="<? if (isset($data['address_postcode'])) print htmlspecialchars($data['address_postcode']) ?>" size="20">
<br><strong><?=_('Country:') ?></strong> 
<br><? 
    gaze_controls_print_country_choice(microsites_site_country(), null, $errors, array('noglobal'=>true, 'fieldname' => 'address_country')); ?>
</p>

<?
}

/* microsites_new_pledges_prominence
 * Returns prominence that new pledges have by default.
 */
function microsites_new_pledges_prominence() {
    global $microsite;
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
    elseif ($microsite == 'catcomm')
        return 'other CatComm supporters'; // deliberately not translated
    else
        return _('other local people');
}

/* microsites_frontpage_comments_allowed
 * Whether or not comments are displayed for the microsite.
 */
function microsites_comments_allowed() {
    global $microsite;
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
    $sql_params[] = $microsite;
    return "(microsite <> ? or microsite is null)";
}

/* microsites_normal_prominences
 * Returns SQL fragment which selects normal prominence pledges. Normally
 * this is just 'normal', but for some microsites may want to include 'backpage'.
 */
function microsites_normal_prominences() {
    global $microsite;
    if ($microsite == 'catcomm')
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
    return array('open'=>_('Pledges which need signers'), 'succeeded_open'=>_('Successful open pledges'), 
        'succeeded_closed'=>_('Successful closed pledges'), 'failed' => _('Failed pledges'));
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

    // Use normal authentication
    return false;
}

/* microsites_redirect_external_login
 * Return true if auth has been redirected to elsewhere.
 * Return false if normal auth is to be used.*/
function microsites_redirect_external_login() {
    global $microsite;
    return false;
}

/* microsites_invalid_email_address
 * Returns whether an email address is valid to use for the microsite or not.
 * Return false if it is valid, or a string containing an error message for the
 * user if it is invalid.
 */
function microsites_invalid_email_address($email) {
    global $microsite;
    return false;
}

# Display the More Details box during pledge creation
function microsites_new_pledges_detail_textarea($data) {
    global $microsite;
    $detail = isset($data['detail']) ? htmlspecialchars($data['detail']) : '';
    return '<textarea name="detail" rows="10" cols="40">' . $detail . '</textarea>';
}

# Extra checks for step 1 of pledge creation for microsites;
function microsites_step1_error_check($data) {
    global $microsite;
    $error = array();
    if ($email_err = microsites_invalid_email_address($data['email']))
        $error['email'] = $email_err;
    $detail = preg_replace('#\W#', '', $data['detail']);
    return $error;
}

# For displaying extra bits on the preview pledge page
function microsites_new_pledges_preview_extras($data) {
    global $microsite;
    return;
}

/* microsites_display_login
 * Return whether or not to display the "Hello, Victor Papanek" message at
 * the top of the page. If you are overriding the login functions above,
 * the microsite header may be displaying this, so shouldn't be duplicated
 * in the PledgeBank header.
 */
function microsites_display_login() {
    global $microsite;
    return true;
}

/* microsites_change_microsite_allowed
 * Returns whether or not you should display the "change/choose site"
 * links around the place.
 */
function microsites_change_microsite_allowed() {
    global $microsite;
    return true;
}

/* microsites_show_translate_blurb()
 * Returns whether or not we should display the available languages
 * and "translate into your own language" at the bottom of every page.
 */
function microsites_show_translate_blurb() {
    global $microsite;
    return true;
}

/* microsites_show_alert_advert()
 * Returns whether or not we should display a HFYMP advert when you've
 * just signed up for an email alert
 */
function microsites_show_alert_advert() {
    global $microsite;
    return true;
} 

/* microsites_display_favicon
 * If a microsite has a special favicon, output it now.
 */
function microsites_display_favicon() {
    global $microsite;
}

/* microsites_sort_by_signers
 * If a microsite has an extra sort-by-signers option on list pages
 */
function microsites_sort_by_signers() {
    global $microsite;
    return false;
}

/* For if a microsite has a special example date on the new pledge page
 */
function microsites_example_date() {
    global $microsite, $pb_time, $lang;
    print '"';
    if ($lang=='en-gb')
        print date('jS F Y', $pb_time+60*60*24*28); // 28 days
    elseif ($lang=='eo')
        print strftime('la %e-a de %B %Y', $pb_time+60*60*24*28);
    elseif ($lang=='de' || $lang=='sk')
        print strftime('%e. %B %Y', $pb_time+60*60*24*28);
    elseif ($lang=='zh')
        print strftime('%Y&#24180;%m&#26376;%d&#26085;', $pb_time+60*60*24*28);
    else
        print strftime('%e %B %Y', $pb_time+60*60*24*28);
    print '"';
}

# Return true if this is an intranet installed site
function microsites_intranet_site() {
    global $microsite;
    return false;
}

# Return true if all target functionality should be disabled
function microsites_no_target() {
    global $microsite;
    return false;
}

# Return true if microsite has SMS at all
function microsites_has_sms() {
    global $microsite;
    return true;
}

# Return true if microsite has flyers
function microsites_has_flyers() {
    global $microsite;
    return true;
}

# Help for blank searches
function microsites_search_help() {
    global $microsite;
    print p(_('You can search for:'));
    print "<ul>";
    print li(_("The name of a <strong>town or city</strong> near you, to find pledges in your area"));
    if (!microsites_site_country() || microsites_site_country() == 'GB')
        print li(_("A <strong>postcode</strong> or postcode area, if you are in the United Kingdom"));
    print li(_("<strong>Any words</strong>, to find pledges and comments containing those words"));
    print li(_("The name of <strong>a person</strong>, to find pledges they made or signed publicly"));
    print "</ul>";
}

function microsites_has_survey() {
    global $microsite;
    return true;
}

function microsites_new_breadcrumbs($num) {
    $steps = array(_('Basics'));
    if (microsites_location_allowed())
        $steps[] = _('Location');
    if (microsites_categories_page3() || microsites_private_allowed())
        $steps[] = _('Category/Privacy');
    if (microsites_postal_address_allowed())
        $steps[] = _('Address');
    $steps[] = _('Preview');

    $str = '<ol id="breadcrumbs">';
    for ($i = 0; $i < sizeof($steps); ++$i) {
        if ($i == $num - 1)
            $str .= "<li class=\"hilight\"><em>";
        else
            $str .= "<li>";
        $str .= '<!--[if lte IE 6]>' . ($i+1) . '. <![endif]-->';
        $str .= htmlspecialchars($steps[$i]);
        if ($i == $num - 1)
            $str .= "</em>";
        $str .= "</li>";
    }
    $str .= "</ol>";
    print $str;
}

