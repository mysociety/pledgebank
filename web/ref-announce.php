<?php
/*
 * announce.php:
 * Send an announcement to the pledge signers.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-announce.php,v 1.4 2005-05-31 10:20:35 francis Exp $
 * 
 */

require_once "../phplib/db.php";
require_once "../phplib/pb.php";
require_once "../phplib/fns.php";
require_once "../phplib/pledge.php";
require_once "../phplib/auth.php";

require_once "../../phplib/importparams.php";
require_once "../../phplib/evel.php";

$err = importparams(
            array('ref',   '/./',   '')
        );
if (!is_null($err))
    err("Missing pledge reference");

$p = new Pledge($q_ref);

/* Lock the pledge here, before we do any other checks. */
$p->lock();

$P = person_if_signed_on();
if (!$P || $P->id() != $p->creator_id()) {
    /* Nobody is logged in or the logged in person isn't the owner of this
     * pledge. So just redirect back to the creators' page where they can
     * log in with their email address. */
    header("Location: /$q_ref/creator");
    exit();
}

$descr = array(
                'failure-announce' => 'failure announcement message',
                'success-announce' => 'success announcement message',
                'success-followup' => 'second or subsequent success message',
                'general-announce' => 'general message'
            );

$has_sms = array(
                'failure-announce' => 1,
                'success-announce' => 1
            );

/* refuse_announce CIRCUMSTANCE
 * Page explaining that punter may not send another message of this type. */
function refuse_announce($c) {
    page_header("Send Announcement");
    $n = db_getOne('select count(id) from message where pledge_id = ? and circumstance = ?', array($p->id(), $c));
    print "<strong>You have already sent ";
    if ($n == 1)
        print "a ${descr[$c]}";
    else if ($n > 1)
        print "$n ${descr[$c]}s";   /* XXX i18n */
    print ", which is all that you're allowed.</strong> Think of your signers' poor inboxes, crumbling under the load of all the mail you want to send them";
    page_footer(array('nonav' => 1));
    exit();
}

/* message_success
 * Page thanking punter for sending message. */
function message_success() {
    page_header("Announcement sent");
    print "<p><strong>Thank you!</strong> Your message will now be sent to all the people who signed your pledge.</p>";
    page_footer();
    exit();
}

/* Obtain an ID for the message ahead of time, to guard against repeat
 * insertion. */
importparams(array('message_id', '/^[1-9]\d*$/',      '',     null));
if (is_null($q_message_id))
    $q_message_id = $q_h_message_id = db_getOne("select nextval('message_id_seq')");
else if (!is_null(db_getOne('select id from message where id = ?', $q_message_id)))
    /* Message already sent. */
    message_success();

/* Figure out which circumstance we should do a message for, and hence the
 * subject of the email. */
if ($p->failed()) {
    $n = db_getOne("select id from message where pledge_id = ? and circumstance = 'failure-announce'", $p->id());
    if (!is_null($n))
        /* Only get to send one announcement on failure. */
        refuse_announce('failure-announce');
    else {
        $circumstance = 'failure-announce';
        $email_subject = "Sorry - pledge failed - '" . $p->title() . "'";
    }
} else if ($p->succeeded()) {
    $n = db_getOne("select id from message where pledge_id = ? and circumstance = 'success-announce'", $p->id());
    if (is_null($n))
        $circumstance = 'success-announce'; /* also send SMS */
    else
        $circumstance = 'success-followup'; /* do not send SMS */
    $email_subject = "Pledge success! - '" . $p->title() . "' at PledgeBank.com";
} else {
    $circumstance = 'general-announce';
    $email_subject = "Update on pledge - '" . $p->title() . "' at PledgeBank.com";
}

$circumstance_count = db_getOne('select count(id) from message where pledge_id = ? and circumstance = ?', array($p->id(), $circumstance));

$do_sms = array_key_exists($circumstance, $has_sms) ? true : false;

$fill_in = "ADD INSTRUCTIONS FOR PLEDGE SIGNERS HERE, INCLUDING YOUR CONTACT INFO";

/* All OK. */
page_header("Send ${descr[$circumstance]} to signers of '" . $p->title() . "'", array());

$sentence = $p->sentence();

$name = $p->creator_name();
if ($p->succeeded()) {
    $default_message = <<<EOF

Hello, and thank you for signing our successful pledge!

'$sentence'

<$fill_in>

Yours sincerely,

$name

EOF;

    $default_sms = "$name here. The " . $p->ref() . " pledge has been successful! <$fill_in>.";
} else {
    $default_message = <<<EOF

Hello,

<$fill_in>

Yours sincerely,

$name

Pledge says: '$sentence'

EOF;

    $default_sms = null;
}

$err = importparams(
            array('message_body', '//', "", $default_message),
            array('message_sms', '//', "", $default_sms),
            array('submit', '//', "", null)
        );

// Check parameters
$errors = array();
if ($q_submit) {
    
    if ($do_sms) {
        if (trim(merge_spaces($q_message_sms)) == trim(merge_spaces($default_sms)))
            array_push($errors, "Please edit the text of the SMS message");
        else if (stristr($q_message_sms, "$fill_in"))
            array_push($errors, "Please add instructions for the pledge signers to the SMS message.");
        if (mb_strlen($q_message_sms, "UTF-8") > 160) /* XXX */
            array_push($errors, "Please shorten the text of the SMS message to 160 characters or fewer");
        /* XXX else we must check that the text is representable in IA5; if it
         * isn't, we must get the user to fix it, since otherwise it cannot be
         * transmitted. */
    }

    if (trim(merge_spaces($q_message_body)) == trim(merge_spaces($default_message)))
        array_push($errors, "Please edit the text of the email message.");
    else if (stristr($q_message_body, "$fill_in"))
        array_push($errors, "Please add instructions for the pledge signers to the email message.");
    if (strlen($q_message_body) < 50)
        array_push($errors, "Please enter a longer message.");

}

if (!sizeof($errors) && $q_submit) {
    /* User mail must be submitted with \n line endings. */
    $q_message_body = str_replace("\r\n", "\n", $q_message_body);
    
    /* Got all the data we need. Just drop the announcement into the database
     * and let the frequentupdate script pass it to the signers. */
    db_query("
        insert into message
            (id, pledge_id, circumstance, circumstance_count, fromaddress,
            sendtocreator, sendtosigners, sendtolatesigners,
            emailsubject, emailbody, sms)
        values
            (?, ?, ?, ?, 'creator',
            false, true, true,
            ?, ?, ?)",
        array(
            $q_message_id, $p->id(), $circumstance, $circumstance_count,
            $email_subject, $q_message_body, $do_sms ? $q_message_sms : null));

    db_commit();

    message_success();
} else {
 
    if ($p->succeeded())
        print '<p class="success">Your pledge is successful!</p>';
 
    // Display errors or success header
    if (sizeof($errors))
        print '<div id="errors"><ul><li>'
                . join('</li><li>', array_map('htmlspecialchars', $errors))
                . '</li></ul></div>';

    // Display form
    $howmany = $p->signers();

?>
<p></p>

<form accept-charset="utf-8" class="pledge" name="pledge" id="pledge" method="post">
<h2>Send <?=$descr[$circumstance]?></h2>
<input type="hidden" name="message_id" value="<?=$q_h_message_id?>">
<div class="c">
<p>Write a message to the <?=$howmany?> <?=htmlspecialchars($p->type())?> who have signed your pledge.
<? if ($p->succeeded()) { ?>
This is to tell them what to do next.
<? } ?>
</p>

<h3>Email message</h3>

<p>The message will be sent from your email address, so the people
who signed your pledge can reply directly to you. <strong>You may want to also give your
phone number or website</strong>, so they can contact you in other ways.</p>

<p><textarea
    name="message_body"
    id="message_body"
    cols="72"
    rows="20"><?=$q_h_message_body?></textarea></p>

<? if ($do_sms) { ?>

<h3>SMS message</h3>

<p>Enter a short (160 or fewer characters) summary of your main message,
which can be sent to anyone who has signed up to your pledge by SMS only.
<strong>Include contact details, such as your phone number or email address.</strong>
Otherwise people who signed up by text won't be able to contact you again.
</p>


<script type="text/javascript">
<!--
function count_sms_characters() {
    n = document.getElementById("message_sms").value.length;
    /* XXX should really use the DOM for that but that requires a little
     * appendChild/removeChild dance that might not even work in old
     * browsers. So do it lazily instead: */
    if (n <= 160)
        text = "You have used " + n + " characters; " + (160 - n) + " remain.";
    else
        text = "<b>You have used " + n + " characters, which is " + (n - 160) + " more than will fit in an SMS. Please make your message shorter.</b>";
    document.getElementById("smslengthcounter").innerHTML = text;
}
//-->
</script>
<p><textarea
    name="message_sms"
    id="message_sms"
    cols="72"
    rows="3"
    onKeyUp="count_sms_characters()"
    onChange="count_sms_characters()"
    onClick="count_sms_characters()"
    onMouseUp="count_sms_characters()"
    onMouseMove="count_sms_characters()"><?=$q_h_message_sms?></textarea></p>
<p><small><span id="smslengthcounter"></span></small></p>
<script type="text/javascript">
<!--
// One call to show the initial count.
count_sms_characters();
//-->
</script>
<? } ?>

<h3>Send Announcement</h3>

<p>(Remember, when you send this message <strong>your email address will be given to everyone who signed up</strong> by email) <input type="submit" name="submit" value="Send &gt;&gt;"></p>

</form>
</div>
<?

}

page_footer();

?>
