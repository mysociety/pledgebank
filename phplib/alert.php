<?
// alert.php:
// Alert and notification features.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: alert.php,v 1.1 2005-06-09 11:23:39 francis Exp $

/* Returns true if the signed on user is already subscribed */
function local_alert_subscribed() {
    global $signed_on_person;
    if (!$signed_on_person)
        return false;
    
    $already_signed = db_getOne("select count(*) from local_alert where person_id = ?", array($signed_on_person->id()));
    if ($already_signed == 0)
        return false;
    elseif ($already_signed == 1)
        return true;
    else
        err("already_signed $already_signed times");
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
<form accept-charset="utf-8" id="pledgeaction" name="pledge" action="/alert" method="post">
<input type="hidden" name="subscribe_alert" value="1">
<h2>Pledges in your town!</h2>
<p>We'll email you when there's a pledge to sign in your local
area.</p>
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


