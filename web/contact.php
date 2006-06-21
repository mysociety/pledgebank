<?
// contact.php:
// Contact us form for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: contact.php,v 1.38 2006-06-21 17:30:59 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/person.php';

page_header(_("Contact Us"));

if (get_http_var('contactpost')) {
    contact_form_submitted();
} else {
    contact_form();
}

page_footer();

function contact_form($errors = array()) {
    $name = get_http_var('name', true);
    $email = get_http_var('email');
    $ref = get_http_var('ref');
    $referrer = get_http_var('referrer');
    if (!$referrer && array_key_exists('HTTP_REFERER', $_SERVER) && isset($_SERVER['HTTP_REFERER']))
        $referrer = $_SERVER['HTTP_REFERER'];
    $P = person_if_signed_on();
    if (!is_null($P)) {
        if (is_null($name) || !$name)
            $name = $P->name_or_blank();
        if (is_null($email) || !$email)
            $email = $P->email();
    }
 
    print h2(_('Contact Us'));
    printf(p(_('Was it useful?  How could it be better?
We make PledgeBank and thrive off feedback, good and bad.
Use this form to contact us.
If you prefer, you can email %s instead of using the form.')), '<a href="mailto:' . OPTION_CONTACT_EMAIL . '">' . OPTION_CONTACT_EMAIL . '</a>');
    print p(_("If you would like to contact the Pledge Creator, please use the 'comments' section on the pledge. The form below is for messages to the PledgeBank Team only, <strong>not</strong> the Pledge Creator."));
    print p(_('<a href="/faq">Read the FAQ</a> first, it might be a quicker way to answer your question.'));
    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    } ?>
<form style="text-align: center" class="pledge" name="contact" accept-charset="utf-8" action="/contact" method="post"><input type="hidden" name="contactpost" value="1"><input type="hidden" name="ref" value="<?=htmlspecialchars($ref)?>"><input type="hidden" name="referrer" value="<?=htmlspecialchars($referrer)?>">
<p><?=_('Message to')?>: <strong><?=_("PledgeBank Team")?></strong></p>

<p><label for="name"><strong><?=_('Your name') ?></strong></label>: <input type="text" id="name" name="name" onblur="fadeout(this)" onfocus="fadein(this)" value="<?=htmlspecialchars($name) ?>" size="25">
<br><label for="email"><strong><?=_('Your email') ?></strong></label>: <input type="text" id="email" name="email" value="<?=htmlspecialchars($email) ?>" size="30"></p>

<p><label for="subject"><strong><?=_('Subject') ?></strong></label>: <input type="text" id="subject" name="subject" value="<?=htmlspecialchars(get_http_var('subject', true)) ?>" size="48"></p>

<p><label for="message"><strong><?=_('Write your message') ?></strong></label>
<br><textarea rows="7" cols="60" name="message" id="message"><?=htmlspecialchars(get_http_var('message', true)) ?></textarea></p>

<?  print '<p>' . _('Did you <a href="/faq">read the FAQ</a> first?') . '
--&gt; <input type="submit" name="submit" value="' . _('Send') . '"></p>';
    print '</form>';
}

function contact_form_submitted() {
    $name = get_http_var('name', true);
    if ($name == _('<Enter your name>')) $name = '';
    $email = get_http_var('email');
    $subject = get_http_var('subject', true);
    $message = get_http_var('message', true);
    $ref = get_http_var('ref');
    $referrer = get_http_var('referrer');
    $errors = array();
	if (!$name) $errors[] = _('Please enter your name');
	if (!$email) $errors[] = _('Please enter your email address');
	if (!validate_email($email)) $errors[] = _('Please enter a valid email address');
	if (!$subject) $errors[] = _('Please enter a subject');
	if (!$message) $errors[] = _('Please enter your message');
	if (sizeof($errors)) {
		contact_form($errors);
	} else {
		send_contact_form($name, $email, $subject, $message, $ref, $referrer);
	}
}

function send_contact_form($name, $email, $subject, $message, $ref, $referrer) {
    global $lang;

    # See if we have someone special to send the email to
    $to = db_getOne('SELECT email FROM translator WHERE lang=?', $lang);
    if (!$to)
        $to = OPTION_CONTACT_EMAIL;

    /* User mail must be submitted with \n line endings. */
    $message = str_replace("\r\n", "\n", $message);

    if ($_SERVER['REMOTE_ADDR'] == '202.71.106.121') {
        # TODO: Use a database instead, but this'll do for now
        print _('Please stop sending us spam. Thank you.');
        return;
    }
    $postfix = '[ Sent by contact.php ' . ($ref ? ('for pledge ' . $ref . ' ') : '')  .  
    'on ' . $_SERVER['HTTP_HOST'] . '. ' .
    "IP address " . $_SERVER['REMOTE_ADDR'] . 
    (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) ? ' (forwarded from '.$_SERVER['HTTP_X_FORWARDED_FOR'].')' : '') . '. ' .
    ($referrer ? ("\n  Referrer: " . $referrer) : '') . 
    ($ref ? ("\n  Admin: ".OPTION_ADMIN_URL.'?page=pb&amp;pledge='.$ref) : '') . 
    ' ]';
    $headers = array();
    $headers['From'] = array($email, $name);
    $success = pb_send_email($to, $subject, $message . "\n\n" . $postfix, $headers);
    if (!$success)
        err(_("Failed to send message.  Please try again, or <a href=\"mailto:team@pledgebank.com\">email us</a>."));
    print _('Thanks for your feedback.  We\'ll get back to you as soon as we can!');
}

?>
