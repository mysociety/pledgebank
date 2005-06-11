<?
// ref-alert.php:
// Signing up for alerts.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: alert.php,v 1.6 2005-06-11 19:54:01 chris Exp $

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

/* Display form for email alert sign up. */
function local_alert_subscribe_box() {
    $email = get_http_var('email');
    $postcode = get_http_var('postcode');

    $P = person_if_signed_on();
    if (!is_null($P)) {
        if (is_null($email) || !$email)
            $email = $P->email();
    }

?>
<form accept-charset="utf-8" class="pledge" name="pledge" action="/alert" method="post">
<input type="hidden" name="subscribe_alert" value="1">
<h2>Get emails about local pledges</h2>
<p>Fill in the form, and we'll email you when someone creates a new pledge near you.</p>
<p>
<label for="email"><strong>Your email:</strong></label> 
<input type="text" size="20" name="email" id="email" value="<?=htmlspecialchars($email) ?>">
<br>
<label for="postcode"><strong>Postcode:</strong></label> 
<input type="text" size="15" name="postcode" id="postcode" value="<?=htmlspecialchars($postcode) ?>">
(e.g. OX1 3DR)
</p>

</p>
<p><input type="submit" name="submit" value="Subscribe"> </p>
</form>

<? 

}

?>
