<?
// alert.php:
// Alert and notification features.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: alert.php,v 1.10 2005-07-04 22:24:56 francis Exp $

require_once '../../phplib/mapit.php';

/* alert_signup PERSON_ID EVENT_CODE PARAMS
 * 
 * Signs person PERSON_ID up to receive alerts to an event.  PARAMS contains
 * extra info as described for each EVENT_CODE below.  
 * 
 * EVENT_CODE can be any of:
 * comments/ref - comments on a pledge, 'pledge_id' must be in PARAMS.
 * pledges/local/GB - new pledges near a location in the UK, 'postcode' must be in PARAMS.
 *
 */
function alert_signup($person_id, $event_code, $params) {
    if ($event_code == "comments/ref") {
        /* Alert when a comment is added to a particular pledge */

        $already = db_getOne("select id from alert where person_id = ? and event_code = ?
            and pledge_id = ?", array($person_id, $event_code, $params['pledge_id']));
        if (is_null($already)) {
            db_query("insert into alert (person_id, event_code, pledge_id)
                values (?, ?, ?)", array($person_id, $event_code, $params['pledge_id']));
        }
    } elseif ($event_code == "pledges/local/GB") {
        /* Alert when a new pledge appears near a particular area in country GB (the UK) */

        /* Canonicalise postcode form, so more likely to detect it is already in the table */
        $params['postcode'] = canonicalise_postcode($params['postcode']);

        /* Find out where on earth it is */
        $location = mapit_get_location($params['postcode']);
        if (mapit_get_error($location)) {
            /* This error should never happen, as earlier postcode validation in form will stop it */
            err('Invalid postcode while setting alert, please check and try again.');
        }
        $already = db_getOne("select id from alert where person_id = ? and event_code = ?
            and postcode = ?", array($person_id, $event_code, $params['postcode']));
        if (is_null($already)) {
            db_query("insert into alert (
                        person_id, event_code, 
                        latitude, longitude, postcode
                    )
                    values (?, ?, ?, ?, ?)", array(
                        $person_id, $event_code, 
                        $location['wgs84_lat'], $location['wgs84_lon'], $params['postcode']
                    ));
        }
    } else {
        err("Unknown alert event '$event_code'");
    }

}

/* alert_unsubscribe PERSON_ID ALERT_ID
 * Remove the subscription to the alert, checks the alert is owned
 * by the given person.
 */
function alert_unsubscribe($person_id, $alert_id) {
    $row = db_getRow("select * from alert where id = ?", $alert_id);
    if (!$row) 
        err(sprintf(_("Unknown alert %d"), intval($alert_id)));

    if ($person_id != $row['person_id'])   
        err(sprintf(_("Alert %d does not belong to person %d"), intval($alert_id), intval($person_id)));

    db_query("delete from alert_sent where alert_id = ?", $alert_id);
    db_query("delete from alert where id = ?", $alert_id);
    db_commit();
}

/* alert_h_description ALERT_ID
 * Returns a textual description of an alert.
 */
function alert_h_description($alert_id) {
    $row = db_getRow("select * from alert where id = ?", $alert_id);
    if (!$row) 
        return false;

    if ($row['event_code'] == "comments/ref") { 
        $pledge = new Pledge(intval($row['pledge_id']));
        return sprintf(_("new comments on the pledge '%s'"), $pledge->ref() );
    } elseif ($row['event_code'] == "pledges/local/GB") { 
        return sprintf(_("new UK pledges near postcode %s"), $row['postcode'] );
    } else {
        err(sprintf(_("Unknown event code '%s'"), $row['event_code']));
    }
}

/* alert_unsubscribe_link EMAIL ALERT_ID
 * Returns a URL for unsubscribing to this alert.  EMAIL is
 * the email address of the person who the caller is sending
 * an email to that will contain the URL.
 */
function alert_unsubscribe_link($alert_id, $email) {
    $url = person_make_signon_url(null, $email, 
                "POST", OPTION_BASE_URL . "/alert", array('direct_unsubscribe'=>$alert_id));
    return $url;
}

/* Returns true if the signed on user is already subscribed */
function local_uk_alert_subscribed() {
    $P = person_if_signed_on();
    if (!$P)
        return false;
    
    $already_signed = db_getOne("select count(*) from alert where event_code = 'pledges/local/GB' 
            and person_id = ?", array($P->id()));
    if ($already_signed == 0)
        return false;
    elseif ($already_signed == 1)
        return true;
    else
        err("already_signed $already_signed times");
}


/* Stuff to loop through / display all of someone's alerts
// not used yet
$s = db_query('SELECT alert.* from alert where person_id = ?', $P->id());
print "<h2>Alerts</h2>";
if (0 != db_num_rows($s)) {
    print "<p>People who signed the pledges you created or signed also signed these...</p>";
    while ($row = db_fetch_array($s)) {
        if ($row['event_code'] == "comments/ref") {
            $pledge = new Pledge(intval($row['pledge_id']));
?>
<form accept-charset="utf-8" class="pledge" name="alertsetup" action="/alert" method="post">
<input type="hidden" name="alter_alert" value="1">
<?=$pledge->h_sentence(array('firstperson'=>true))?>
<br>Comment alerts:
<select id="country" name="country">
  <option>subscribed</option>
  <option>not subscribed</option>
</select>
<input type="submit" name="submit" value="Update">
</form>
<?
        } else {
            err("Unknown event code '".$row['event_code']."'");
        }
    }
} else {
    print "You have no alerts";
}
*/

