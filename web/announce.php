<?php
/*
 * announce.php:
 * Send an announcement to the pledge signers.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: announce.php,v 1.11 2005-04-07 16:15:05 chris Exp $
 * 
 */

require_once "../phplib/db.php";
require_once "../phplib/pb.php";
require_once "../phplib/fns.php";
require_once "../phplib/pledge.php";

require_once "../../phplib/importparams.php";
require_once "../../phplib/evel.php";

$err = importparams(
            array('token', '/.+/', "Missing token"),
            array('message_body', '//', "", null),
            array('message_sms', '//', "", null),
            array('submit', '//', "", null)
        );
if (!is_null($err))
    err("Sorry -- something seems to have gone wrong. "
        . join(",", array_values($err)));

/* Extract data for token and pledge, and lock the latter. */
if (!($data = pledge_token_retrieve('announce-post', $q_token))
    || !($pledge = db_getRow('select * from pledges where id = ? for update',
                    $data['pledge_id'])))
    err("No such token or pledge"); /* XXX make this a single, friendlier error message. */

/* Verify that we haven't already sent the announcement. */
if (!is_null(db_getOne('select id from announcement where pledge_id = ?', $data['pledge_id'])))
    err("Announcement already sent for this pledge");

/* All OK. */
page_header("Send announcement to '${pledge['title']}", array());

$default_sms = "${pledge['name']} here. The ${pledge['ref']} pledge has been successful! <ADD INSTRUCTIONS FOR PLEDGE SIGNERS HERE>";
if (!$q_message_sms) {
    $q_message_sms = $default_sms;
    $q_h_message_sms = htmlspecialchars($q_message_sms);
}

$default_message = 
        "Hello, and thank you for signing our successful pledge!\n\n" .
        "'". pledge_sentence($pledge) . "'\n\n" . 
        "Here's what we're going to do next...\n\n" .
        "\n\n" .
        "<ENTER INSTRUCTIONS FOR PLEDGE SIGNERS HERE>\n\n" . 
        "\n\n" .
        "Yours sincerely,\n\n" .
        $pledge['name'];

if (!$q_message_body) {
    $q_message_body = $default_message;
    $q_h_message_body = htmlspecialchars($q_message_body);
}

// Check parameters
$errors = array();
if ($q_submit) {
    if (merge_spaces($q_message_body) == merge_spaces($default_message)) {
        array_push($errors, "Please edit the text of the email message.");
    }
    
    if (merge_spaces($q_message_sms) == merge_spaces($default_sms))
        array_push($errors, "Please edit the text of the SMS message");
    else if (mb_strlen($q_message_sms, "UTF-8") > 160) /* XXX */
        array_push($errors, "Please shorten the text of the SMS message to 160 characters or fewer");
    /* XXX else we must check that the text is representable in IA5; if it
     * isn't, we must get the user to fix it, since otherwise it cannot be
     * transmitted. */

    if (strlen($q_message_body) < 50) {
        array_push($errors, "Please enter a longer message.");
    }
    if (stristr($q_message_body, "ENTER INSTRUCTIONS FOR PLEDGE SIGNERS HERE")) {
        array_push($errors, "Please enter instructions for the pledge signers.");
    }
}

if (!sizeof($errors) && $q_submit) {
    /* Got all the data we need. Just drop the announcement into the database
     * and let the frequentupdate script pass it to the signers. */
    db_query('
        insert into announcement (pledge_id, whensent, emailbody, sms)
        values (?, current_timestamp, ?, ?)',
        array($pledge['id'], $q_message_body, $q_message_sms));
    pledge_token_destroy('signup-web', $q_token);
    db_commit();

    print "<p>Your message has been sent to all the people who signed your pledge.  
    Thanks, and enjoy carrying out you pledge!</p>";
} else {
    
    // Display errors or success header
    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    }  else {
        if ($data['circumstance'] == 'success') {
            print '<p class="success">Your pledge is successful!</p>';
        }
    }

    // Display form
    ?>
<p></p>

<form accept-charset="utf-8" class="pledge" name="pledge" id="pledge" method="post" action="/M/<?=$q_h_token?>">
<h2>Send Announcement</h2>
<div class="c">
<p>Write a message to the <?=$pledge['signers']?> <?=$pledge['type']?> who
signed your pledge.  This is to tell them what to do next.  Remember to give
your own email address, phone number or website so they can contact you
again.</p>
<?
    print <<<EOF
<h3>SMS message</h3>
<p>Please enter a short (160 or fewer characters) summary of your main message,
which can be sent to anyone who has signed up to your pledge by SMS only:</p>
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
    onMouseMove="count_sms_characters()">$q_h_message_sms</textarea></p>
<p><small><span id="smslengthcounter"></span></small></p>
<script type="text/javascript">
<!--
// One call to show the initial count.
count_sms_characters();
//-->
</script>
<h3>Email message</h3>

    ?>

<p><textarea
    name="message_body"
    id="message_body"
    cols="72"
    rows="20">$q_h_message_body</textarea></p>

<p>(Did you remember to give your email address or phone number?) <input type="submit" name="submit" value="Send &gt;&gt;"></p>
</form>
</div>
EOF;

}

page_footer();

?>
