<?
// email.php:
// Sending out pledge adverts by email
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: email.php,v 1.2 2005-03-14 15:28:58 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/db.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/utility.php';

$title = '';

if (!is_null(importparams(
            array('ref',     '/^[a-z0-9-]+$/i',  '')
        )))
    err("A required parameter was missing");

$q = db_query('SELECT * FROM pledges WHERE confirmed AND ref=?', array($q_ref));
if (!db_num_rows($q))
    err('Illegal PledgeBank reference!');

$r = db_fetch_array($q);
if (!deal_with_password('email', $q_ref, $r['password']))
    err('Hmm, can\'t really err() as deal_with_password() prints stuff. Oh, and deal_with_password doesn\'t
    currently work with non index.php links. XXX');

$title = "Emailing 'I will $r[title]'";

page_header($title);

# fromname, fromemail, frommessage
# email as an array
$fromname = get_http_var('fromname');
$fromemail = get_http_var('fromemail');
$frommessage = get_http_var('frommessage');
$emails = get_http_var('email');
if ($fromname && $fromemail && $emails) {
    if (sizeof($emails)>5)
        err("Trying to use us for SPAMMING!?!?!");
    $subject = 'I saw this pledge and thought of you';
    $body = "$fromname thought you would be interested in the following pledge from PledgeBank.com:\n\n";
    $body .= pledge_sentence($r)."\nDeadline: ".prettify($r['date'], false)."\nURI: ".OPTION_BASE_URL.$r['ref']."\n\n";
    $body .= "They added the following message:\n\n--------\n$frommessage\n--------\n\n";
    $body .= "All the best,\nPledgeBank.com\n";
    $success = 1;
    foreach ($emails as $email) {
        if (!$email) continue;
        $success &= pb_send_email($email, $subject, $body, "From: $fromname <$fromemail>\r\n");
    }
    if ($success) {
        print '<p>Thanks very much for spreading the word of this pledge.</p>';
    } else {
        print '<p>Unfortunately, something went wrong when trying to send the emails.</p>';
    }
} else {
    view_pledge($q_ref, $r);
}
page_footer();

# Individual pledge page
function view_pledge($ref, $r) {
    global $today;
    $h_ref = htmlspecialchars($ref);
    $q = db_query('SELECT * FROM signers WHERE pledge_id=? ORDER BY id', array($r['id']));
    $curr = db_num_rows($q);
    $left = $r['target'] - $curr;

    $finished = 0;
    if ($r['date'] < $today) {
        $finished = 1;
	print '<p class="finished">This pledge is now closed, its deadline has passed.</p>';
    }
    if ($left <= 0) {
        if ($r['comparison'] == 'exactly') {
            $finished = 1;
            print '<p class="finished">This pledge is now closed, its target has been reached.</p>';
        } else {
            print '<p class="success">This pledge has been successful!</p>';
        }
    }
?>
<p>Here's a reminder of the pledge you're telling people about:</p>
<form class="pledge" name="pledge" action="email" method="post"><input type="hidden" name="ref" value="<?=$h_ref ?>">
<? if (get_http_var('pw')) print '<input type="hidden" name="pw" value="'.htmlspecialchars(get_http_var('pw')).'">'; ?>
<div class="c">
<p style="margin-top: 0">&quot;<?=pledge_sentence($r, array('firstperson'=>true, 
'html'=>true)) ?>&quot;</p>
<p>Deadline: <strong><?=prettify($r['date']) ?></strong></p>

<p style="font-style: italic;"><?=prettify($curr) ?> <?=make_plural($curr,'person has','people have') ?> signed up<?=($left<0?' ('.prettify(-$left).' over target :) )':', '.prettify($left).' more needed') ?></p>
</div>

<?  if ($r['detail']) {
        print '<p><strong>More details</strong><br>' . htmlspecialchars($r['detail']) . '</p>';
    }
?>
<h2>Email this pledge</h2>
<p>Blurble blurble blurb. We're not spammers, trust us.</p>
<div class="formrow">Your name: <input type="text" name="fromname" value="" size="40"></div>
<div class="formrow">Your email address: <input type="text" name="fromemail" value="" size="40"></div>
<p>Add a message, if you want:</p>
<div class="formrow"><textarea name="frommessage" rows="5" cols="40"></textarea></div>
<p>Other people's email addresses:</p>
<style type="text/css">.formrow { margin-bottom: 3px; margin-left: 5em; }</style>
<div class="formrow"><input type="text" name="email[]" value="" size="40"></div>
<div class="formrow"><input type="text" name="email[]" value="" size="40"></div>
<div class="formrow"><input type="text" name="email[]" value="" size="40"></div>
<div class="formrow"><input type="text" name="email[]" value="" size="40"></div>
<div class="formrow"><input type="text" name="email[]" value="" size="40"></div>
<p><input type="submit" value="Send"></p>
</form>

<p style="text-align: center"><a href="/<?=$h_ref ?>">Pledge main page</a> | <a href="./<?=$h_ref ?>/flyers" title="Stick them places!">Print out customised flyers</a> | <a href="" onclick="return false">Comment on this Pledge</a><? if (!$finished) { ?> | <strong>Email this Pledge</strong><? } ?></p>
<!-- <p><em>Need some way for originator to view email addresses of everyone, needs countdown, etc.</em></p> -->

<?
}
?>
