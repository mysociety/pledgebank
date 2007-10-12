<?  
// pbperson.php:
// Wrapper for mysociety/phplib/person.php, which adds hooks for PledgeBank
// specific login stuff. e.g. Authentication via external services for
// some microsites.
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: pbperson.php,v 1.5 2007-10-12 13:12:47 matthew Exp $

require_once 'microsites.php';
require_once '../phplib/login.php';

require_once '../../phplib/person.php';

// Special version of person_make_signon_url which uses fancy PledgeBank domain URLs
function pb_person_make_signon_url($data, $email, $method, $url, $params, $microsite = '') { # XXX Ugh
     $url_params = array('path'=>'/');
     if ($microsite) $url_params['microsite'] = $microsite;
     return person_make_signon_url($data, $email, $method, $url, $params, pb_domain_url($url_params));
}
// Special version of person_if_signed_on for microsite remote authentication hooks
function pb_person_if_signed_on($norenew = false) {
    global $microsites_external_auth_person;

    $done = microsites_read_external_auth();
    if ($microsites_external_auth_person)
        return $microsites_external_auth_person;
    if ($done)
        return null;

    return person_if_signed_on($norenew);
}
// Special version of person_signon for microsite remote authentication hooks
function pb_person_signon($template_data, $email = null, $name = null) {
    $P = person_already_signed_on($email, $name, "pb_person_if_signed_on");
    if ($P)
        return $P;

    if (microsites_redirect_external_login()) {
        err("Returned from microsites_redirect_external_login with truth");
    }

    return person_signon($template_data, $email, $name, "pb_person_if_signed_on");
}

# XXX: This is rather horrible. Done so we can have a password
# box within a completely different form
function pb_person_signon_without_redirect($template_data, $email, $name, $password) {
    $P = pb_person_if_signed_on();
    if ($P)
        return $P;

    global $q_stash, $q_name, $q_email, $q_h_email;
    $q_email = $email;
    $q_h_email = htmlspecialchars($email);
    $q_name = $name;
    $q_stash = stash_request(rabx_serialise($template_data), $email);
    db_commit();

    if ($password) {
        global $q_password;
        $q_password = $password;
        $_GET['loginradio'] = 'LogIn';
        login_page();
    } else {
        $_GET['loginradio'] = 'SendEmail';
        login_page();
    }
    exit;
}

