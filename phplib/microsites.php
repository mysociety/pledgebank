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
 * $Id: microsites.php,v 1.3 2005-10-18 10:23:37 francis Exp $
 * 
 */

$microsites_list = array('glastonbury' => 'Glastonbury',
                         '365ways' => '365 Ways',
                         'interface' => 'Interface');

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
<a href="/"><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></a><span id="beta">Beta</span>
<span id="countrytitle"><img src="interface-logo.gif" alt="interface">
<a href="/where">(change)</a></span>';

    } else {
        $country_name = pb_site_country_name();
        return '
<a href="/"><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></a><span id="beta">Beta</span>
<span id="countrytitle">'.$country_name.'
<a href="/where">(change)</a></span>';
    }
}

/* microsites_css_file
 * Return path and filename of URL for CSS file */
function microsites_css_file() {
    global $microsite;
    if ($microsite && $microsite == 'interface') {
        return "/interface.css";
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
        return _('other Interfacers');
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



?>
