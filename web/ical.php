<?
// ical.php:
// Sending out iCalendar entries
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ical.php,v 1.3 2005-04-11 13:32:15 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/db.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/utility.php';

if (!is_null(importparams(
            array('ref',     '/^[a-z0-9-]+$/i',  '')
        )))
    err("A required parameter was missing");

$q = db_query('SELECT * FROM pledges WHERE confirmed AND ref ILIKE ?', array($q_ref));
if (!db_num_rows($q))
    err('Illegal PledgeBank reference!');

$r = db_fetch_array($q);

if (!check_password($q_ref, $r['password']))
    err('Correct password required');

header('Content-Type: text/calendar');
output_ical($r);

function output_ical($r) {
    $ct = preg_replace('#(\d{4})-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d).*#', '$1$2$3T$4$5$6Z', $r['creationtime']);
?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//MySociety/PledgeBank//NONSGML v1.0//EN
BEGIN:VEVENT
CREATED:<?=$ct ?>

DTSTAMP:<?=date('Ymd\THis\Z') ?>

DTSTART:<?=date('Ymd', strtotime($r['date'])) ?>T235959Z
SUMMARY:Deadline for pledge "<?=$r['title'] ?>"
<? if ($r['detail']) print "DESCRIPTION:$r[detail]\r\n"; ?>
GEO:LAT?;LON?
LOCATION:?
RESOURCES:At least <?=$r['target']. ' ' . $r['type'] ?> ?
CLASS:<? if ($r['password']) print 'CONFIDENTIAL'; else print 'PUBLIC'; ?>

TRANSP:TRANSPARENT
URL:http://pledgebank.com/<?=$r['ref'] ?>

UID:<?=$r['ref'] ?>@pledgebank.com
STATUS:CONFIRMED
END:VEVENT
END:VCALENDAR
<?
}

?>
