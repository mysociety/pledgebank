<?
// contact.php:
// Contact us form for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: contact.php,v 1.5 2005-02-24 12:18:02 francis Exp $

function contact_form($errors = array()) { ?>
<h2>Contact Us</h2>
<p>Fill in this form to send us stuff.</p>
<?	if (sizeof($errors)) {
		print '<ul id="errors"><li>';
		print join ('</li><li>', $errors);
		print '</li></ul>';
	} ?>
<form action="./" method="post"><input type="hidden" name="contactpost" value="1">
<div class="fr"><label for="name">Name</label>: <input type="text" id="name" name="name" value="<?=htmlentities(get_http_var('name')) ?>" size="32"></div>
<div class="fr"><label for="email">Email</label>: <input type="text" id="email" name="email" value="<?=htmlentities(get_http_var('email')) ?>" size="32"></div>
<div class="fr"><label for="subject">Subject</label>: <input type="text" id="subject" name="subject" value="<?=htmlentities(get_http_var('subject')) ?>" size="50"></div>
<div><label for="message">Message</label>:<br><textarea rows="7" cols="60" name="message" id="message"><?=htmlentities(get_http_var('message')) ?></textarea></div>
<p><input type="submit" value="Send"></p>
</form>
<? }

function contact_form_submitted() {
	$name = get_http_var('name'); $email = get_http_var('email'); $subject = get_http_var('subject'); $message = get_http_var('message');
	if (!$name) $errors[] = 'Please enter your name';
	if (!$email) $errors[] = 'Please enter your mail address';
	if (!$subject) $errors[] = 'Please enter a subject';
	if (!$message) $errors[] = 'Please enter your message';
	if (sizeof($errors)) {
		contact_form($errors);
	} else {
		send_contact_form($name, $email, $subject, $message);
	}	
}

function send_contact_form($name, $email, $subject, $message) {
	$header = 'Sent from IP address ' . $_SERVER['REMOTE_ADDR'] . ($_SERVER['HTTP_X_FORWARDED_FOR'] ? ' (forwarded from '.$_SERVER['HTTP_X_FORWARDED_FOR'].')' : '') . "\n\n";
	$success = @mail('matthew-pb-contact-form@dracos.co.uk', $subject, $header . $message, "From: $name <$email>");
}

?>
