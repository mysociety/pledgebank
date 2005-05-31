<?php
/*
 * announce.php:
 * Send an announcement to the pledge signers.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: announce.php,v 1.28 2005-05-31 10:20:35 francis Exp $
 * 
 */

require_once "../phplib/pb.php";
require_once "../phplib/db.php";
require_once "../phplib/fns.php";
require_once "../phplib/pledge.php";
require_once "../phplib/auth.php";

require_once "../../phplib/importparams.php";
require_once "../../phplib/evel.php";

$fill_in = "ADD INSTRUCTIONS FOR PLEDGE SIGNERS HERE, INCLUDING YOUR CONTACT INFO";

$err = importparams(
            array('token', '/.+/', "Missing token")
        );

if (!is_null($err))
    err("Sorry -- something seems to have gone wrong. "
        . join(",", array_values($err)));

/* Extract data for token and pledge, and lock the latter. */
if (!($data = auth_token_retrieve('announce-post', $q_token))
    || !($pledge = db_getRow('select * from pledges where id = ? for update',
                    $data['pledge_id'])))
    err("The link hasn't been recognised.  Please check the URL is copied correctly from your email."); /* XXX make this a single, friendlier error message. */

/* Set flags according to type of announce message which was followed by this token.
 * ('success' is just for backwards compatibility, now the circumstance field in 
 * the token has the same content as the circumstance in the message table.) */
if ($data['circumstance'] == 'success-auto-creator' or $data['circumstance'] == 'success') {
    $message_circumstance = 'success-announce';
    $do_sms = true;
    $success = true;
} elseif ($data['circumstance'] == 'announce-post') {
    $message_circumstance = 'general-announce';
    $do_sms = false;
    $success = false;
} else {
    err("Internal error, unknown announce circumstance '" . $data['circumstance']) . "'";
}
if (array_key_exists('circumstance_count', $data)) {
    $circumstance_count = $data['circumstance_count'];
} else {
    $circumstance_count = 0;
}

/* Verify that we haven't already sent the announcement. */
if (!is_null(db_getOne("select id from message where pledge_id = ? and circumstance = ?
and circumstance_count = ?", array($data['pledge_id'], $message_circumstance, $circumstance_count)))) {
        err("You've already sent an announcement message for this pledge.");
}

/* All OK. */
page_header("Send announcement to '${pledge['title']}", array());

$sentence = pledge_sentence($pledge);

if ($success) {
    $default_message = <<<EOF

Hello, and thank you for signing our successful pledge!

'$sentence'

<$fill_in>

Yours sincerely,

${pledge['name']}

EOF;

    $default_sms = "${pledge['name']} here. The ${pledge['ref']} pledge has been successful! <$fill_in>.";
} else {
    $default_message = <<<EOF

Hello,

<$fill_in>

Yours sincerely,

${pledge['name']}

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
        if (stristr($q_message_sms, "$fill_in"))
            array_push($errors, "Please add instructions for the pledge signers to the SMS message.");
        if (mb_strlen($q_message_sms, "UTF-8") > 160) /* XXX */
            array_push($errors, "Please shorten the text of the SMS message to 160 characters or fewer");
        /* XXX else we must check that the text is representable in IA5; if it
         * isn't, we must get the user to fix it, since otherwise it cannot be
         * transmitted. */
    }

    if (trim(merge_spaces($q_message_body)) == trim(merge_spaces($default_message)))
        array_push($errors, "Please edit the text of the email message.");
    if (strlen($q_message_body) < 50)
        array_push($errors, "Please enter a longer message.");
    if (stristr($q_message_body, "$fill_in"))
        array_push($errors, "Please add instructions for the pledge signers to the email message.");

}

if (!sizeof($errors) && $q_submit) {
    /* User mail must be submitted with \n line endings. */
    $q_message_body = str_replace("\r\n", "\n", $q_message_body);
    
    /* Got all the data we need. Just drop the announcement into the database
     * and let the frequentupdate script pass it to the signers. */
    db_query("
        insert into message
            (pledge_id, circumstance, circumstance_count, fromaddress,
            sendtocreator, sendtosigners, sendtolatesigners,
            emailsubject, emailbody, sms)
        values
            (?, ?, ?, 'creator',
            false, true, true,
            ?, ?, ?)",
        array(
            $pledge['id'], $message_circumstance, $circumstance_count,
            $success ? "Pledge success! - '${pledge['title']}' at PledgeBank.com" : 
                       "Update on the pledge you've signed - '${pledge['title']}' at PledgeBank.com",
                $q_message_body, $do_sms ? $q_message_sms : null));
    # Don't destroy token, so we can give proper errors when trying to resend.
    # auth_token_destroy('announce-post', $q_token);
    db_commit();

    print "<p>Your message will now be sent to all the people who signed your pledge. ";
    if ($success)
        print "Thanks, and enjoy carrying out your pledge!</p>";
    else 
        print "Thanks, and good luck with your pledge!</p>";
} else {
 
    if ($success)
        print '<p class="success">Your pledge is successful!</p>';

 
    // Display errors or success header
    if (sizeof($errors))
        print '<div id="errors"><ul><li>'
                . join('</li><li>', array_map('htmlspecialchars', $errors))
                . '</li></ul></div>';

    // Display form
    $howmany = db_getOne('select count(id) from signers where pledge_id = ?', $pledge['id']);

?>
<p></p>

<form accept-charset="utf-8" class="pledge" name="pledge" id="pledge" method="post" action="/M/<?=$q_h_token?>">
<h2>Send Announcement</h2>
<div class="c">
<p>Write a message to the <?=$howmany?> <?=$pledge['type']?> who have signed your pledge.
<? if ($success) { ?>
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

<h3>Send announcement</h3>

<p>(Remember, when you send this message <strong>your email address will be given to everyone who signed up</strong> by email) <input type="submit" name="submit" value="Send &gt;&gt;"></p>

</form>
</div>
<?

}

page_footer();

?>
