<?
// alert.php:
// Alert and notification features.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: alert.php,v 1.2 2005-06-09 14:13:10 francis Exp $

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

