<?
// alert.php:
// Alert and notification features.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: alert.php,v 1.4 2005-06-11 19:54:01 chris Exp $

/* Returns true if the signed on user is already subscribed */
function local_alert_subscribed() {
    $P = person_if_signed_on();
    if (!$P)
        return false;
    
    $already_signed = db_getOne("select count(*) from local_alert where person_id = ?", array($P->id()));
    if ($already_signed == 0)
        return false;
    elseif ($already_signed == 1)
        return true;
    else
        err("already_signed $already_signed times");
}

