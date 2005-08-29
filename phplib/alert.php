<?
// alert.php:
// Alert and notification features.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: alert.php,v 1.19 2005-08-29 10:48:53 francis Exp $

require_once '../../phplib/mapit.php';
require_once '../../phplib/person.php';

/* alert_signup PERSON EVENT PARAMS
 * Signs PERSON up to receive alerts to an event. EVENT may be:
 *
 *  - comments/ref for alerts when a new comment is posted on a pledge; PARAMS
 *    must contain pledge_id, the ID of the pledge concerned; or
 *
 *  - pledges/localfor alerts when new pledges are created near a location.
 *    'country' - two letter country code (compulsory)
 *    'state' - two letter region-within-country code (optional)
 *    either 'place' and 'gaze_place' - input place name, and selected place triple of 
 *                                      "latitude, longitude, input" in a string
 *    or 'postcode' - UK postcode
 *
 * The contents of PARAMS must be verified before calling this function; if
 * not, it will abort by calling err(). */
function alert_signup($person_id, $event_code, $params) {
    if (is_object($person_id))
        $person_id = $person_id->id();
    if ($event_code == "comments/ref") {
        /* Alert when a comment is added to a particular pledge */

        $already = db_getOne("select id from alert where person_id = ? and event_code = ?
            and pledge_id = ? for update", array($person_id, $event_code, $params['pledge_id']));
        if (is_null($already)) {
            db_query("insert into alert (person_id, event_code, pledge_id)
                values (?, ?, ?)", array($person_id, $event_code, $params['pledge_id']));
        }
    } elseif ($event_code == "pledges/local") {
        /* Alert when a new pledge appears near a particular area */
        /* XXX extend this for worldwide alerts. */

        if ($params['postcode']) {
            if ($params['country'] != "GB")
                err("Postcode only available for the UK");

            /* Canonicalise postcode form, so more likely to detect it is already in the table */
            $params['postcode'] = canonicalise_postcode($params['postcode']);

            /* Find out where on earth it is */
            $location = mapit_get_location($params['postcode']);
            if (mapit_get_error($location)) {
                /* This error should never happen, as earlier postcode validation in form will stop it */
                err('Invalid postcode while setting alert, please check and try again.');
            }
            $location['input'] = $params['postcode'];
            $location['description'] = $params['postcode'];
            $location['method'] = "MaPit";
        } elseif ($params['gaze_place']) {
            $location = array();    
            list($lat, $lon, $desc) = explode(',', $params['gaze_place'], 3);
            $location['wgs84_lat'] = $lat;
            $location['wgs84_lon'] = $lon;
            $location['description'] = $desc;
            $location['input'] = $params['place'];
            $location['method'] = "Gaze";
        } else {
            err('Please choose place');
        }

        /* Guard against double-insertion. */
        db_query('lock table alert in share mode');
        $already = db_getOne("select alert.id from alert left join location on location.id = alert.location_id
                where person_id = ? and event_code = ?
                and country = ? 
                and method = ? and input = ? and latitude = ? and longitude = ?",
                array($person_id, $event_code, 
                $params['country'], /* deliberately no state, as can be null */
                $location['method'], $location['input'],
                $location['wgs84_lat'], $location['wgs84_lon'],
                ));
        if (is_null($already)) {
            $location_id = db_getOne("select nextval('location_id_seq')");
            db_query("
                    insert into location
                        (id, country, state, method, input, latitude, longitude, description)
                    values (?, ?, ?, ?, ?, ?, ?, ?)", array(
                        $location_id,
                        $params['country'], $params['state'],
                        $location['method'], $location['input'],
                        $location['wgs84_lat'], $location['wgs84_lon'],
                        $location['description']
                    ));
            db_query("
                    insert into alert
                        (person_id, event_code, location_id)
                    values (?, ?, ?)", array(
                        $person_id, $event_code, $location_id
                    ));
        }
    } else {
        err("Unknown alert event '$event_code'");
    }
}

/* alert_unsubscribe PERSON_ID ALERT_ID
 * Remove the subscription to the alert, checks the alert is owned
 * by the given person. */
function alert_unsubscribe($person_id, $alert_id) {
    $row = db_getRow("select * from alert where id = ?", $alert_id);
    if (!$row) 
        err(sprintf(_("Unknown alert %d"), intval($alert_id)));

    if ($person_id != $row['person_id'])   
        err(sprintf(_("Alert %d does not belong to person %d"), intval($alert_id), intval($person_id)));

    db_getOne('select id from alert where id = ? for update', $alert_id);
    db_query("delete from alert_sent where alert_id = ?", $alert_id);
    db_query("delete from alert where id = ?", $alert_id);
    db_commit();
}

/* alert_h_description ALERT_ID
 * Returns a textual description of an alert.
 */
function alert_h_description($alert_id) {
    $row = db_getRow("select * from alert left join location on location.id = alert.location_id
             where alert.id = ?", $alert_id);
    if (!$row) 
        return false;

    if ($row['event_code'] == "comments/ref") { 
        $pledge = new Pledge(intval($row['pledge_id']));
        return sprintf(_("new comments on the pledge '%s'"), $pledge->ref() );
    } elseif ($row['event_code'] == "pledges/local") { 
        if ($row['method'] == 'MaPit') 
            return sprintf(_("new UK pledges near postcode %s"), $row['description'] );
        else 
            return sprintf(_("new pledges near %s"), $row['description'] );
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

/* Stuff to loop through / display all of someone's alerts
// not used yet
$s = db_query('SELECT alert.* from alert left join location on location.ild = alert.location_id
                      where person_id = ?', $P->id());
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

