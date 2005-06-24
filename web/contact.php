<?
// contact.php:
// Contact us form for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: contact.php,v 1.20 2005-06-24 11:27:41 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../../phplib/utility.php';

page_header(_("Contact Us"));

if (get_http_var('contactpost')) {
    contact_form_submitted();
} else {
    contact_form();
}

page_footer();

function contact_form($errors = array()) {
    print '<h2>' . _('Contact Us') . '</h2>';
    print _('<p>Was it useful?  How could it be better?
We make PledgeBank and thrive off feedback, good and bad.
Use this form to contact us.
If you prefer, you can email <a href="mailto:<?=OPTION_CONTACT_EMAIL?>"><?=OPTION_CONTACT_EMAIL?></a> instead of using the form.</p>');
    print _('<p><a href="/faq">Read the FAQ</a> first, it might be a quicker
way to answer your question.</p>');
    print _("<p>If you would like to contact the Pledge Creator, please use the 'comments' section on the pledge: these messages go to the PledgeBank Team, <strong>not</strong> the Pledge Creator.</p>");
    if (sizeof($errors)) {
        print '<ul id="errors"><li>';
        print join ('</li><li>', $errors);
        print '</li></ul>';
    } ?>
<form class="pledge" name="contact" accept-charset="utf-8" action="/contact" method="post"><input type="hidden" name="contactpost" value="1">
<div class="fr"><label for="name"><?=_('Your name') ?></label>: <input type="text" id="name" name="name" value="<?=htmlentities(get_http_var('name')) ?>" size="32" onblur="fadeout(this)" onfocus="fadein(this)" ></div>
<div class="fr"><label for="email"><?=_('Your email') ?></label>: <input type="text" id="email" name="email" value="<?=htmlentities(get_http_var('email')) ?>" size="32"></div>
<div class="fr"><label for="subject"><?=_('Subject') ?></label>: <input type="text" id="subject" name="subject" value="<?=htmlentities(get_http_var('subject')) ?>" size="50"></div>
<div><label for="message"><?=_('Message') ?></label>:<br><textarea rows="7" cols="60" name="message" id="message"><?=htmlentities(get_http_var('message')) ?></textarea></div>
<?  print _('<p>Did you <a href="/faq">read the FAQ</a> first?
--&gt; <input type="submit" name="submit" value="Send"></p>');
    print '</form>';
}

function contact_form_submitted() {
    $name = get_http_var('name');
    if ($name == '<Enter your name>') $name = '';
    $email = get_http_var('email');
    $subject = get_http_var('subject');
    $message = get_http_var('message');
    $errors = array();
	if (!$name) $errors[] = _('Please enter your name');
	if (!$email) $errors[] = _('Please enter your email address');
	if (!$subject) $errors[] = _('Please enter a subject');
	if (!$message) $errors[] = _('Please enter your message');
	if (sizeof($errors)) {
		contact_form($errors);
	} else {
		send_contact_form($name, $email, $subject, $message);
	}
}

function send_contact_form($name, $email, $subject, $message) {
    /* User mail must be submitted with \n line endings. */
    $message = str_replace("\r\n", "\n", $message);

    $postfix = '[ Sent by contact.php from IP address ' . $_SERVER['REMOTE_ADDR'] . (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) ? ' (forwarded from '.$_SERVER['HTTP_X_FORWARDED_FOR'].')' : '') . ' ]';
    $headers = array();
    $headers['From'] = '"' . str_replace(array('\\','"'), array('\\\\','\"'), $name) . '" <' . $email . '>';
    $success = pb_send_email(OPTION_CONTACT_EMAIL, $subject, $message . "\n\n" . $postfix, $headers);
    if (!$success)
        err(_("Failed to send message.  Please try again, or <a href=\"mailto:team@pledgebank.com\">email us</a>."));
    print _('Thanks for your feedback.  We\'ll get back to you as soon as we can!');
}

?>
