<?php
/*
 * announce.php:
 * Send an announcement to the pledge signers.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: announce.php,v 1.5 2005-04-04 14:34:17 chris Exp $
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

$data = pledge_token_retrieve('announce-post', $q_token);
if (!$data)
    err("No such token");
# Note, we destroy the token later when the message is sent

$pledge = db_getRow('
    select *,
        (select count(id) from signers
        where signers.pledge_id = pledges.id
            and signers.email is not null) as signers,
        (select count(id) from signers
        where signers.pledge_id = pledges.id
            and signers.email is null) as signers_sms
    from pledges
    where id = ?', $data['pledge_id']);

page_header("Send announcement to '${pledge['title']}", array());

$need_sms = ($data['signers_sms'] > 0);
if ($need_sms)
    $default_sms = "${data['name']} here. The ${data['ref']} pledge has been successful! <ADD INSTRUCTIONS FOR PLEDGE SIGNERS HERE>";

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
        array_push($errors, "Please edit the text of the message.");
    }
    if ($need_sms) {
        if (merge_spaces($q_message_sms) == merge_spaces($default_sms))
            array_push($errors, "Please edit the text of the SMS message");
        else if (mb_strlen($q_message_sms, "UTF-8") > 160) /* XXX */
            array_push($errors, "Please shorten the text of the SMS message to 160 characters or fewer");
        /* XXX else we must check that the text is representable in IA5; if it
         * isn't, we must get the user to fix it, since otherwise it cannot be
         * transmitted. */
    }
    if (strlen($q_message_body) < 50) {
        array_push($errors, "Please enter a longer message.");
    }
    if (stristr($q_message_body, "ENTER INSTRUCTIONS FOR PLEDGE SIGNERS HERE")) {
        array_push($errors, "Please enter instructions for the pledge signers.");
    }
}

if (!sizeof($errors) && $q_submit) {
    // Send message
    $q = db_query('select email from signers where pledge_id = ?', $pledge['id']);
    $addrs = array();
    while ($r = db_fetch_array($q))
        array_push($addrs, $r['email']);

    if (!pb_send_email($addrs, "Announcement from ${pledge['name']} about '${pledge['title']}' at PledgeBank.com", $q_message_body))
        print "<p>Sorry, we failed to send your message properly.  Please try again!</p>";
    else {
        /* We have successfully sent the emails, so now send any outstanding
         * SMSs. */
        if ($need_sms)
            db_query('
                insert into outgoingsms
                select mobile as recipient, ? as message, ? as whensubmitted
                from signers
                where pledge_id = ? and email is null',
                array($q_message_sms, time(), $data['pledge_id']));
        // Last of all, destroy the token
        pledge_token_destroy('signup-web', $q_token);
        db_commit();

        print "<p>Your message has been sent to all the people who signed your pledge.  
        Thanks, and enjoy carrying out you pledge!</p>";
    }
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
    print <<<EOF
<p></p>

<form class="pledge" name="pledge" id="pledge" method="post" action="/M/<?=$q_h_token?>">
<h2>Send Announcement</h2>
<div class="c">
<p>Write a message to the <?=$pledge['signers']?> <?=$pledge['type']?> who
signed your pledge.  This is to tell them what to do next.  Remember
to give your own email address, phone number or website so they can contact
you again.</p>
EOF

    if ($need_sms) {
        print <<<EOF
<p><em>We only have the mobile phone numbers for some of the people who have
signed your pledge &mdash; not their email addresses</em>. To reach them,
please enter a short (160 or fewer characters) summary of your main message,
below:</p>
<script type="text/javascript">
<!--
function count_sms_characters() {
    n = document.getElementById("message_sms").value.length;
    /* XXX should really use the DOM for that but that requires a little
     * appendChild/removeChild dance that might not even work in old
     * browsers. So do it lazily instead: */
    document.getElementById("smslengthcounter").innerHTML = "Used " + n + " characters; " + (160 - n) + " remain.";
}
//-->
</script>
<p><input
        type="text"
        name="message_sms"
        size="72"
        maxlength="160"
        value="$q_h_message_sms"
        onKeyUp="count_sms_characters()"></p>
<p><small><span id="smslengthcounter"></span>
<script type="text/javascript">
<!--
count_sms_characters();
//-->
</script>
EOF
    }

    ?>

<p><textarea name="message_body" id="message_body" cols="72" rows="20">
<?=$q_h_message_body?>
</textarea></p>

<p>(Did you remember to give your email address or phone number?) <input type="submit" name="submit" value="Send &gt;&gt;"></p>
</form>
</div>

<?php
}

page_footer();

?>
