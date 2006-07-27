<?  
// pbperson.php:
// Wrapper for mysociety/phplib/person.php, which adds hooks for PledgeBank
// specific login stuff. e.g. Authentication via external services for
// some microsites.
//
// Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: pbperson.php,v 1.1 2006-07-27 11:15:53 francis Exp $

require_once '../../phplib/person.php';

// Special version of person_make_signon_url which uses fancy PledgeBank domain URLs
function pb_person_make_signon_url($data, $email, $method, $url, $params) {
     return person_make_signon_url($data, $email, $method, $url, $params, pb_domain_url(array('path'=>'/')));
}
// Special version of person_if_signed_on for microsite remote authentication hooks
function pb_person_if_signed_on($norenew = false) {
    return person_if_signed_on($norenew);
}
// Special version of person_signon for microsite remote authentication hooks
function pb_person_signon($template_data, $email = null, $name = null) {
    return person_signon($template_data, $email, $name, "pb_person_if_signed_on");
}


