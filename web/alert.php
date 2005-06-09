<?
// ref-alert.php:
// Signing up for alerts.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: alert.php,v 1.1 2005-06-09 11:22:42 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/auth.php';
require_once '../phplib/alert.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';

$title = 'New pledge alerts';
page_header($title);
if (get_http_var('subscribe_alert')) {
    $errors = do_local_alert_subscribe();
    if (is_array($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
        local_alert_subscribe_box();
    }
} else {
    local_alert_subscribe_box();
}
page_footer();

function do_local_alert_subscribe() {
    global $q_email, $q_name, $q_showname, $q_ref, $q_pin;
    $errors = importparams(
                array('email',      '/^[^@]+@.+/',      'Please give your email'),
                array('postcode',      "importparams_validate_postcode")
            );
    if (!is_null($errors))
        return $errors;

    /* Get the user to log in. */
    $r = array();
    $r['reason'] = 'subscribe to local pledge alerts';
    $person = person_signon($r, $q_email);

    db_query('insert into local_alert (person_id, postcode) values (?, ?)', array($person->id(), $q_email));
    db_commit();
        ?>
<p id="loudmessage" align="center">Thanks for subscribing!  You'll get emailed when there are new pledges in your area.  <a href="/">PledgeBank home page</a></p>
<?
}

?>
