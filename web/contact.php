<?
// contact.php:
// Contact us form for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: contact.php,v 1.14 2005-06-14 23:46:10 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../../phplib/utility.php';

page_header("Contact Us");

if (get_http_var('contactpost')) {
    contact_form_submitted();
} else {
    contact_form();
}

page_footer();

function contact_form($errors = array()) { ?>
<h2>Contact Us</h2>
<p>Was it useful?  How could it be better?  
We make PledgeBank and thrive off feedback, good and bad.
Use this form to contact us.  
If you prefer, you can email <a href="mailto:<?=OPTION_CONTACT_EMAIL?>"><?=OPTION_CONTACT_EMAIL?></a> instead of using the form.
<p><a href="/faq">Read the FAQ</a> first, it might be a quicker
way to answer your question.  
</p>
<?	if (sizeof($errors)) {
		print '<ul id="errors"><li>';
		print join ('</li><li>', $errors);
		print '</li></ul>';
	} ?>
<form class="pledge" name="contact" accept-charset="utf-8" action="/contact" method="post"><input type="hidden" name="contactpost" value="1">
<div class="fr"><label for="name">Your name</label>: <input type="text" id="name" name="name" value="<?=htmlentities(get_http_var('name')) ?>" size="32" onblur="fadeout(this)" onfocus="fadein(this)" ></div>
<div class="fr"><label for="email">Your email</label>: <input type="text" id="email" name="email" value="<?=htmlentities(get_http_var('email')) ?>" size="32"></div>
<div class="fr"><label for="subject">Subject</label>: <input type="text" id="subject" name="subject" value="<?=htmlentities(get_http_var('subject')) ?>" size="50"></div>
<div><label for="message">Message</label>:<br><textarea rows="7" cols="60" name="message" id="message"><?=htmlentities(get_http_var('message')) ?></textarea></div>
<p>
Did you <a href="/faq">read the FAQ</a> first?
--&gt; <input type="submit" name="submit" value="Send"></p>
</form>
<? }

function contact_form_submitted() {
    $name = get_http_var('name');
    if ($name == '<Enter your name>') $name = '';
    $email = get_http_var('email');
    $subject = get_http_var('subject');
    $message = get_http_var('message');
    $errors = array();
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
    $postfix = 'Sent by contact.php from IP address ' . $_SERVER['REMOTE_ADDR'] . (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) ? ' (forwarded from '.$_SERVER['HTTP_X_FORWARDED_FOR'].')' : '') . "\n\n";
    $headers = array();
    $headers['From'] = '"' . str_replace(array('\\','"'), array('\\\\','\"'), $name) . '" <' . $email . '>';
    $success = pb_send_email(OPTION_CONTACT_EMAIL, $subject, $message . "\n\n" . $postfix, $headers);
    if (!$success) 
        err("Failed to send message.  Please try again, or <a href=\"mailto:team@pledgebank.com\">email us</a>.");
?>
    Thanks for your feedback.  We'll get back to you as soon as we can!
<?
}

?>
