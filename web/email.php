<?
// email.php:
// Sending out pledge adverts by email
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: email.php,v 1.4 2005-04-04 09:33:07 francis Exp $

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
$emails = array();
for ($i = 1; $i <= 5; $i++) {
    $emails[] = get_http_var("email$i");
}
if ($fromname && $fromemail && $emails) {
    if (sizeof($emails)>5)
        err("Trying to use us for SPAMMING!?!?!");
    $success = 1;
    foreach ($emails as $email) {
        if (!$email)
            continue;
        $success &= pb_send_email_template($email, 'email-friends',
            array_merge($r, array(
                'from_name'=>$fromname, 
                'from_email' => $fromemail, 
                'from_message' => $frommessage ? "They added this message: \"$frommessage\"\n\n" : "",
            ))
        );
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
?>
<p align="center">Here's a reminder of the pledge you're telling people about:</p>
<div class="pledge">
<div class="c">
<p style="margin-top: 0">&quot;<?=pledge_sentence($r, array('firstperson'=>true, 
'html'=>true)) ?>&quot;</p>
<p>Deadline: <strong><?=prettify($r['date']) ?></strong></p>

<p style="font-style: italic;"><?=prettify($curr) ?> <?=make_plural($curr,'person has','people have') ?> signed up<?=($left<0?' ('.prettify(-$left).' over target :) )':', '.prettify($left).' more needed') ?></p>

<?  if ($r['detail']) {
        print '<p><strong>More details</strong><br>' . htmlspecialchars($r['detail']) . '</p>';
    }
?>
</div>
</div>
<p></p>
<form class="tips" name="pledge" action="email" method="post"><input type="hidden" name="ref" value="<?=$h_ref ?>">
<? if (get_http_var('pw')) print '<input type="hidden" name="pw" value="'.htmlspecialchars(get_http_var('pw')).'">'; ?>
<h2>Email this pledge</h2>
<p>
Please enter these details so that we can send your message to your contacts.
We will not give or sell either your or their email address to anyone else.
</p>
<div class="formrow">Your name: <input type="text" name="fromname" value="" size="18">
Email: <input type="text" name="fromemail" value="" size="26"></div>
<p>Add a message, if you want:</p>
<div class="formrow"><textarea name="frommessage" rows="5" cols="60"></textarea></div>
<p>Other people's email addresses:</p>
<style type="text/css">.formrow { margin-bottom: 3px; margin-left: 5em; }</style>
<div class="formrow"><input type="text" name="email1" value="" size="40"></div>
<div class="formrow"><input type="text" name="email2" value="" size="40"></div>
<div class="formrow"><input type="text" name="email3" value="" size="40"></div>
<div class="formrow"><input type="text" name="email4" value="" size="40"></div>
<div class="formrow"><input type="text" name="email5" value="" size="40"></div>
<p><input name="submit" type="submit" value="Send"></p>
</form>

<?
}
?>
