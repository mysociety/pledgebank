<?php
/*
 * confirm.php:
 * Confirm a user's subscription to a pledge, or creation of a pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: announce.php,v 1.2 2005-04-04 11:25:21 francis Exp $
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
            array('submit', '//', "", null)
        );
if (!is_null($err))
    err("Sorry -- something seems to have gone wrong. "
        . join(",", array_values($err)));

/* OK, that wasn't a pledge confirmation token. So we must be signing a
 * pledge. */
$data = pledge_token_retrieve('announce-post', $q_token);
if (!$data)
    err("No such token");
# Note, we destroy the token later when the message is sent

$pledge = db_getRow('select *,
    (select count(*) from signers where signers.pledge_id = pledges.id) as signers
    from pledges where id = ?', $data['pledge_id']);
page_header("Send announcement to '".$pledge['title']."'", array());
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
    $globalsuccess = true;
    while ($r = db_fetch_array($q)) {
        $success = pb_send_email($r['email'], "Announcement from ${pledge['name']} about '${pledge['title']}' at PledgeBank.com", $q_message_body);
        if (!$success)
            $globalsuccess = false;
    }

    if (!$globalsuccess) 
        print "<p>Sorry, we failed to send your message properly.  Please try again!</p>";
    else {
        print "<p>Your message has been sent to all the people who signed your pledge.  
        Thanks, and enjoy carrying out you pledge!</p>";
        // Last of all, destroy the token
        pledge_token_destroy('signup-web', $q_token);
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
?>
<p></p>

<form class="pledge" name="pledge" method="post" action="/M/<?=$q_h_token?>">
<h2>Send Announcement</h2>
<div class="c">
<p>Write a message to the <?=$pledge['signers']?> <?=$pledge['type']?> who
signed your pledge.  This is to tell them what to do next.  Remember
to give your own email address, phone number or website so they can contact
you again.
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
