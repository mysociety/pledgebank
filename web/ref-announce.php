<?php
/*
 * announce.php:
 * Send an announcement to the pledge signers.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-announce.php,v 1.24 2005-06-24 13:39:19 matthew Exp $
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
    err(_("Missing pledge reference"));

page_check_ref($q_ref);
$p = new Pledge($q_ref);

/* Lock the pledge here, before we do any other checks. */
$p->lock();

$P = person_if_signed_on();
if (!$P) {
    $P = person_signon(array(
                    "reason_web" => _("Before you can send a message to all the signers, we need to check that you created the pledge."),
                    "reason_email" => _("Then you will be able to send a message to everyone who has signed your pledge."),
                    "reason_email_subject" => _("Send a message to your pledge signers at PledgeBank.com"))

                );
}
if ($P->id() != $p->creator_id()) {
    page_header(_("Pledge creator's page"));
    print _("You must be the pledge creator to send a message to all signers.
        Please <a href=\"/logout\">log out</a> and try again.");
    page_footer();
    exit;
}


$descr = array(
                'failure-announce' => _('failure announcement message'),
                'success-announce' => _('success announcement message'),
                'success-followup' => _('second or subsequent success message'),
                'general-announce' => _('general message')
            );

$has_sms = array(
                'failure-announce' => 1,
                'success-announce' => 1
            );

/* refuse_announce PLEDGE CIRCUMSTANCE
 * Page explaining that punter may not send another message of this type. */
function refuse_announce($p, $c) {
    global $descr;
    page_header(_("Send Announcement"));
    $n = db_getOne('select count(id) from message where pledge_id = ? and circumstance = ?', array($p->id(), $c));
    print "<strong>";
    printf(ngettext('You have already sent %d %s, which is all that you\'re allowed.', 'You have already sent %d %s, which is all that you\'re allowed.', $n), $n, $descr[$c]);
    print "</strong> ";
    print _("Think of your signers' poor inboxes, crumbling under the load of all the mail you want to send them.");
    page_footer();
    exit();
}

/* message_success
 * Page thanking punter for sending message. */
function message_success() {
    page_header(_("Announcement sent"));
    print p(_("<strong>Thank you!</strong> Your message will now be sent to all the people who signed your pledge."));
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
        refuse_announce($p, 'failure-announce');
    else {
        $circumstance = 'failure-announce';
        $email_subject = sprintf(_("Sorry - pledge failed - '%s'"), $p->title() );
    }
} else if ($p->succeeded()) {
    $n = db_getOne("select id from message where pledge_id = ? and circumstance = 'success-announce'", $p->id());
    if (is_null($n))
        $circumstance = 'success-announce'; /* also send SMS */
    else
        $circumstance = 'success-followup'; /* do not send SMS */
    $email_subject = sprintf(_("Pledge success! - '%s' at PledgeBank.com"), $p->title() );
} else {
    $circumstance = 'general-announce';
    $email_subject = sprintf(_("Update on pledge - '%s' at PledgeBank.com"), $p->title() );
}

$circumstance_count = db_getOne('select count(id) from message where pledge_id = ? and circumstance = ?', array($p->id(), $circumstance));

$do_sms = array_key_exists($circumstance, $has_sms) ? true : false;

$fill_in = _("ADD INSTRUCTIONS FOR PLEDGE SIGNERS HERE, INCLUDING YOUR CONTACT INFO");

/* All OK. */
page_header(sprintf(_("Send %s to signers of '%s'"), $descr[$circumstance], $p->title()), array());

$sentence = $p->sentence();

$name = $p->creator_name();
if ($p->succeeded()) {
    $default_message = sprintf(_("\nHello, and thank you for signing our successful pledge!\n\n'%s'\n\n<%s>\n\nYours sincerely,\n\n%s\n\n"), $sentence, $fill_in, $name);
    $default_sms = sprintf(_("%s here. The %s pledge has been successful! <%s>."), $name, $p->ref(), $fill_in);
} elseif ($p->failed()) {
    $default_message = sprintf(_("\nHello, and sorry that our pledge has failed.\n\n'%s'\n\n<%s>\n\nYours sincerely,\n\n%s\n\n"), $sentence, $fill_in, $name);
    $default_sms = sprintf(_("%s here. The %s pledge has failed. <%s>."), $name, $p_.ref(), $fill_in);
} else {
    $default_message = sprintf(_("\nHello,\n\n<%s>\n\nYours sincerely,\n\n%s\n\nPledge says: '%s'\n\n"), $fill_in, $name, $sentence);
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
            array_push($errors, _("Please edit the text of the SMS message"));
        else if (stristr($q_message_sms, "$fill_in"))
            array_push($errors, _("Please add instructions for the pledge signers to the SMS message."));
        if (mb_strlen($q_message_sms, "UTF-8") > 160) /* XXX */
            array_push($errors, _("Please shorten the text of the SMS message to 160 characters or fewer"));

        /* Now check that the text is representable in IA5. See the table in
         * perllib/PB/SMS.pm. */

        /* XXX this would be the place to, e.g., convert "smart" to ASCII
         * quotes, if that turns out to be a problem. */
        if (preg_match_all('/([^@\x{00a3}$\x{00a5}\x{00e8}\x{00e9}\x{00f9}\x{00ec}\x{00f2}\x{00c7}\x{000a}\x{00d8}\x{00f8}\x{000d}\x{00c5}\x{00e5}\x{0394}\x{005f}\x{03a6}\x{0393}\x{039b}\x{03a9}\x{03a0}\x{03a8}\x{03a3}\x{0398}\x{039e}\x{001b}\x{00c6}\x{00e6}\x{00df}\x{00c9} !"#\x{00a4}%&\'()*+,-.\/0123456789:;<=>?\x{00a1}ABCDEFGHIJKLMNOPQRSTUVWXYZ\x{00c4}\x{00d6}\x{00d1}\x{00dc}\x{00a7}\x{00bf}abcdefghijklmnopqrstuvwxyz\x{00e4}\x{00f6}\x{00f1}\x{00fc}\x{00e0}])/u', $q_message_sms, $m)) {
            $badchars = $m[1];
            if (sizeof($badchars) == 1) {
                array_push($errors, sprintf(_("Unfortunately, we can't send the character '%s' in an SMS message; please rewrite your message without it"), $badchars[0]));
            } else if (sizeof($badchars) > 1) {
                $str = "'${badchars[0]}'";
                for ($i = 1; $i < sizeof($badchars) - 1; ++$i)
                    $str .= ", '${badchars[$i]}'";
                $str .= " and '${badchars[$i]}'";
                array_push($errors, sprintf(_("Unfortunately, we can't send the characters %s in an SMS message; please rewrite your message without them"), $str));
            }
        }
    }

    if (trim(merge_spaces($q_message_body)) == trim(merge_spaces($default_message)))
        array_push($errors, _("Please edit the text of the email message."));
    else if (stristr($q_message_body, "$fill_in"))
        array_push($errors, _("Please add instructions for the pledge signers to the email message."));
    if (strlen($q_message_body) < 50)
        array_push($errors, _("Please enter a longer message."));

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
 

    // Display errors or success header
    if (sizeof($errors))
        print '<div id="errors"><ul><li>'
                . join('</li><li>', array_map('htmlspecialchars', $errors))
                . '</li></ul></div>';
    elseif ($p->succeeded())
        print _('<p class="success">Your pledge is successful!</p>');
 
    $p->render_box(array('showdetails'=>false));
        
    // Display form
    $howmany = $p->signers();

?>

<form action="announce" accept-charset="utf-8" class="pledge" name="pledge" id="pledgeaction" method="post">
<h2>Send <?=$descr[$circumstance]?></h2>
<input type="hidden" name="message_id" value="<?=$q_h_message_id?>">
<div class="c">
<p><?
    printf(_('Write a message to the %d %s who have signed your pledge.'), $howmany, htmlspecialchars($p->type()));
    if ($p->succeeded()) {
        print _('This is to tell them what to do next.');
    } ?>
</p>

<?  print _('<h3>Email message</h3>');
    print p(_('The message will be sent from your email address, so the people
who signed your pledge can reply directly to you. <strong>You may want to also
give your phone number or website</strong>, so they can contact you in other
ways.')); ?>

<p><textarea
    name="message_body"
    id="message_body"
    cols="72"
    rows="20"><?=$q_h_message_body?></textarea></p>

<?  if ($do_sms) {
        print _('<h3>SMS message</h3>');
        print p(_('Enter a short (160 or fewer characters) summary of your main message,
which can be sent to anyone who has signed up to your pledge by SMS only.
<strong>Include contact details, such as your phone number or email address.</strong>
Otherwise people who signed up by text won\'t be able to contact you again.'));
?>
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
<? }

    print _('<h3>Send Announcement</h3>');
    print '<p>';
    print _('(Remember, when you send this message <strong>your email address will be given to everyone who has signed up or will sign up</strong> to your pledge by email)');
    print '<input type="submit" name="submit" value="' . _('Send') . ' &gt;&gt;"></p>';
    print '</form></div>';
}

page_footer();

?>
