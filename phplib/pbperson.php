<?  
// pbperson.php:
// Wrapper for mysociety/phplib/person.php, which adds hooks for PledgeBank
// specific login stuff. e.g. Authentication via external services for
// some microsites.
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: pbperson.php,v 1.3 2006-07-27 18:25:01 francis Exp $

require_once 'microsites.php';

require_once '../../phplib/person.php';

// Special version of person_make_signon_url which uses fancy PledgeBank domain URLs
function pb_person_make_signon_url($data, $email, $method, $url, $params) {
     return person_make_signon_url($data, $email, $method, $url, $params, pb_domain_url(array('path'=>'/')));
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


