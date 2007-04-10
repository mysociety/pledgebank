<?php
/*
 * announce.php:
 * Send an announcement to the pledge signers.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-announce.php,v 1.55 2007-04-10 15:31:01 matthew Exp $
 * 
 */

require_once "../../phplib/db.php";
require_once "../phplib/pb.php";
require_once "../phplib/fns.php";
require_once "../phplib/pledge.php";
require_once '../phplib/pbperson.php';

require_once "../../phplib/importparams.php";
require_once "../../phplib/evel.php";

$err = importparams(
            array('ref',   '/./',   '')
        );
if (!is_null($err))
    err(_("Missing pledge reference"));

page_check_ref($q_ref);
$p = new Pledge($q_ref);
microsites_redirect($p);

/* Lock the pledge here, before we do any other checks. */
$p->lock();

/* For success/failure in one place for a byarea type pledge */
$byarea_location_id = intval(get_http_var('location'));
if ($byarea_location_id)
    $byarea_location_test = " byarea_location_id =  " . $byarea_location_id;
else {
    $byarea_location_id = null;
    $byarea_location_test = " byarea_location_id is null ";
}
$succeeded = $p->succeeded();
$failed = $p->failed();
if ($p->byarea()) {
    if ($byarea_location_id) {
        $p->byarea_validate_location($byarea_location_id);
        $byarea_location_description = db_getOne("select description from
                    location where location.id = ?", $byarea_location_id);
        $byarea_location_whensucceeded = db_getOne("select whensucceeded from
                    byarea_location where pledge_id = ? and $byarea_location_test", $p->id());
        if ($byarea_location_whensucceeded) {
            $succeeded = true;
        } else {
            $succeeded = false;
        }
    } else {
        $failed = false;
        $succeeded = false;
    }
}


$P = pb_person_if_signed_on();
if (!$P) {
    $P = pb_person_signon(array(
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
                # TRANS: These all appear as part of a heading: "Send <phrase>", e.g. "Send success announcement message"
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
    global $descr, $byarea_location_test;
    page_header(_("Send announcement"));
    $n = db_getOne('select count(id) from message where pledge_id = ? and circumstance = ? and '.$byarea_location_test, array($p->id(), $c));
    print "<strong>";
    printf(ngettext('You have already sent %d %s, which is all that you\'re allowed.', 'You have already sent %d %s, which is all that you\'re allowed.', $n), $n, $descr[$c]);
    print "</strong> ";
    print _("Think of your signers' poor inboxes, crumbling under the load of all the mail you want to send them.");
    page_footer();
    exit();
}

/* message_success
 * Page thanking punter for sending message. */
function message_success($p) {
    global $byarea_location_id, $byarea_location_description;
    page_header(_("Announcement sent"));
    if ($byarea_location_id) 
        printf(p(_("<strong>Thank you!</strong> Your message will now be sent to all the people who signed your pledge in %s. A copy will also be sent to you for your records.")), $byarea_location_description);
    else
        print p(_("<strong>Thank you!</strong> Your message will now be sent to all the people who signed your pledge. A copy will also be sent to you for your records."));
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
    message_success($p);

/* Figure out which circumstance we should do a message for, and hence the
 * subject of the email. */
if ($failed) {
    $n = db_getOne("select id from message where pledge_id = ? and circumstance = 'failure-announce' and $byarea_location_test", $p->id());
    if (!is_null($n))
        /* Only get to send one announcement on failure. */
        refuse_announce($p, 'failure-announce');
    else {
        $circumstance = 'failure-announce';
        if ($byarea_location_id)
            $email_subject = sprintf(_("Sorry - pledge failed in %s - '%s'"), $byarea_location_description, $p->title() );
        else 
            $email_subject = sprintf(_("Sorry - pledge failed - '%s'"), $p->title() );
    }
} else if ($succeeded) {
    $n = db_getOne("select id from message where pledge_id = ? and circumstance = 'success-announce' and $byarea_location_test", $p->id());
    if (is_null($n))
        $circumstance = 'success-announce'; /* also send SMS */
    else
        $circumstance = 'success-followup'; /* do not send SMS */
    if ($byarea_location_id)
        $email_subject = sprintf(_("Pledge success in %s! - '%s' at PledgeBank.com"), $byarea_location_description, $p->title() );
    else
        $email_subject = sprintf(_("Pledge success! - '%s' at PledgeBank.com"), $p->title() );
} else {
    $circumstance = 'general-announce';
    if ($byarea_location_id)
        $email_subject = sprintf(_("Update on pledge in %s - '%s' at PledgeBank.com"), $byarea_location_description, $p->title() );
    else 
        $email_subject = sprintf(_("Update on pledge - '%s' at PledgeBank.com"), $p->title() );
}

$circumstance_count = db_getOne('select count(id) from message where pledge_id = ? and circumstance = ? and '.$byarea_location_test, array($p->id(), $circumstance));

$do_sms = array_key_exists($circumstance, $has_sms) ? true : false;
if ($byarea_location_id) // byarea pledges don't do SMS at the moment
    $do_sms = false;
if (!microsites_has_sms())
    $do_sms = false;
if ($do_sms) {
    if ($p->is_global()) 
        $country_has_sms = true;
    else
        $country_has_sms = in_array($p->country_code(), sms_countries());
}

$fill_in = _("ADD INSTRUCTIONS FOR PLEDGE SIGNERS HERE, INCLUDING YOUR CONTACT INFO");
if ($byarea_location_id) 
    $fill_in = sprintf(_("ADD INSTRUCTIONS FOR PLEDGE SIGNERS IN %s HERE, INCLUDING YOUR CONTACT INFO"), strtoupper($byarea_location_description));

/* All OK. */
page_header(sprintf(_("Send %s to signers of '%s'"), $descr[$circumstance], $p->title()), array('ref'=>$p->ref(),'pref'=>$p->url_typein()));

$sentence = $p->sentence(array('firstperson'=>'includename'));

$name = $p->creator_name();
if ($succeeded) {
    $default_message = sprintf(_("\nHello, and thank you for signing our successful pledge!\n\n'%s'\n\n<%s>\n\nYours sincerely,\n\n%s\n\n"), $sentence, $fill_in, $name);
	// TRANS: The first %s is a pledge creator's name; the second %s is the pledge reference; the third %s is the instructions entered by the pledge creator, to be sent to signers' mobile phones as a text message
    $default_sms = sprintf(_("%s here. The %s pledge has been successful! <%s>."), $name, $p->ref(), $fill_in);
} elseif ($failed) {
    $default_message = sprintf(_("\nHello, and sorry that our pledge has failed.\n\n'%s'\n\n<%s>\n\nYours sincerely,\n\n%s\n\n"), $sentence, $fill_in, $name);
    $default_sms = sprintf(_("%s here. The %s pledge has failed. <%s>."), $name, $p->ref(), $fill_in);
} else {
    $default_message = sprintf(_("\nHello,\n\n<%s>\n\nYours sincerely,\n\n%s\n\nPledge says: '%s'\n\n%s\n\n"), $fill_in, $name, $sentence, $p->url_main());
    $default_sms = null;
}

$err = importparams(
            array(array('message_body',true), '//', "", $default_message),
            array(array('message_subject',true), '//', "", $email_subject),
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
            (id, pledge_id, circumstance, circumstance_count, fromaddress, byarea_location_id,
            sendtocreator, sendtosigners, sendtolatesigners,
            emailsubject, emailbody, sms)
        values
            (?, ?, ?, ?, 'creator', ?,
            true, true, true,
            ?, ?, ?)",
        array(
            $q_message_id, $p->id(), $circumstance, $circumstance_count, $byarea_location_id,
            $q_message_subject, $q_message_body, $do_sms ? $q_message_sms : null));

    db_commit();

    message_success($p);
} else {
 

    // Display errors or success header
    if (sizeof($errors))
        print '<div id="errors"><ul><li>'
                . join('</li><li>', array_map('htmlspecialchars', $errors))
                . '</li></ul></div>';
    elseif ($p->succeeded())
        print _('<p id="success">Your pledge is successful!</p>');
 
    $p->render_box(array('showdetails'=>false));
        
    // Display form
    $howmany = $p->signers();

?>

<form action="announce" accept-charset="utf-8" class="pledge" name="pledge" id="pledgeaction" method="post">
<h2><?=_('Send') . ' ' . $descr[$circumstance] . ($byarea_location_id ? " for " . $byarea_location_description : '')?></h2>
<input type="hidden" name="message_id" value="<?=$q_h_message_id?>">
<input type="hidden" name="location" value="<?=$byarea_location_id?>">
<p>
<?
    if ($succeeded) {
        if ($byarea_location_id) 
            printf(_('Write a message to tell the %d %s who have signed your pledge in %s what to do next.'), $howmany, htmlspecialchars($p->type()), $byarea_location_description);
        else
            printf(_('Write a message to tell the %d %s who have signed your pledge what to do next.'), $howmany, htmlspecialchars($p->type()));
    } else {
        if ($byarea_location_id) 
            printf(_('Write a message to the %d %s who have signed your pledge in %s.'), $howmany, htmlspecialchars($p->type()), $byarea_location_description);
        else
            printf(_('Write a message to the %d %s who have signed your pledge.'), $howmany, htmlspecialchars($p->type()));
    }
    if ($p->open()) {
        if ($byarea_location_id) 
            printf(' '._('A copy of your message will also be sent to anybody who signs your pledge there later.'));
        else
            printf(' '._('A copy of your message will also be sent to anybody who signs your pledge later.'));
    }
?>
</p>

<?  print _('<h3>Email message</h3>');
    print p(_('The message will be sent from your email address, so the people
who signed your pledge can reply directly to you. <strong>You may want to also
give your phone number or website</strong>, so they can contact you in other
ways.')); ?>

<p><label for="message_subject"><?=_('Subject') ?>:</label> <input name="message_subject" id="message_subject" size="40" value="<?=$q_h_message_subject?>"></p>

<p><textarea style="max-width: 100%"
    name="message_body"
    id="message_body"
    cols="72"
    rows="20"><?=$q_h_message_body?></textarea></p>

<?  if ($do_sms) {
        if ($country_has_sms) {
            print _('<h3>SMS message</h3>');
            print p(_('Enter a short (160 or fewer characters) summary of your main message,
    which can be sent to anyone who has signed up to your pledge by SMS only.
    <strong>Include contact details, such as your phone number or email address.</strong>
    Otherwise people who signed up by text won\'t be able to contact you again.'));
    ?>
<p><textarea style="max-width: 100%"
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
<?      } else {
            // Country is not an SMS one, would be annoying to request SMS text
            // of them just for rare case when a foreigner may have signed up
            // by SMS. So we set a default message. (Keep it short too)
	    // TRANS: This is part of a standard text message sent to mobile phones. The first %s is the pledge name, and the second is the pledge creator.
            $foreign_sms_case = sprintf("Pledge %s success! Email %s for more", $p->ref(), $p->creator_email());
            print '<input type="hidden" name="message_sms" value="'.htmlspecialchars($foreign_sms_case).'">';
        }
    }

    print _('<h3>Send announcement</h3>');
    print '<p>';
    print _('(Remember, when you send this message <strong>your email address will be given to everyone</strong> who has already, or who will in the future, sign up to your pledge by email)');
    print ' <input type="submit" name="submit" value="' . _('Send') . '"></p>';
    print '</form>';
}

page_footer();

?>
