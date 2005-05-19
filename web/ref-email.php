<?
// email.php:
// Sending out pledge adverts by email
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-email.php,v 1.3 2005-05-19 12:07:46 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/db.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/utility.php';

$p  = new Pledge(get_http_var('ref'));

$password_box = deal_with_password($p->url_email(), $p->ref(), $p->password());
if ($password_box) {
    page_header("Enter Password"); 
    print $password_box;
    page_footer();
    exit;
}

$title = "Emailing friends";
page_header($title);

# fromname, fromemail, frommessage
# email as an array
$fromname = get_http_var('fromname');
$fromemail = get_http_var('fromemail');
$frommessage = get_http_var('frommessage');
$emails = array();
for ($i = 1; $i <= 5; $i++) {
    if (get_http_var("email$i")) 
        $emails[] = get_http_var("email$i");
}
$errors = array();
if (!$fromname) $errors[] = "Please enter your name";
if (!$fromemail) $errors[] = "Please enter your email";
if (count($emails) < 1) $errors[] = "Please enter the email addresses of the people you want to tell about the pledge";
if (!$errors) {
    if (sizeof($emails)>5)
        err("Trying to use us for SPAMMING!?!?!");
    $success = 1;
    foreach ($emails as $email) {
        if (!$email)
            continue;
        $success &= pb_send_email_template($email, 'email-friends',
            array_merge($p->data, array(
                'from_name'=>$fromname, 
                'from_email' => $fromemail, 
                'from_message' => $frommessage ? "They added this message: \"$frommessage\"\n\n" : "",
            )), array('From'=>'"' . str_replace('"', '\"', $fromname) . '" <' . $fromemail . '>')
        );
    }
    if ($success) {
        print '<p>Your message has been sent.  Thanks very much for spreading the word of this pledge.</p>';
    } else {
        print '<p>Unfortunately, something went wrong when trying to send the emails.</p>';
    }
} else {
    view_friends_form($p, $errors);
}

page_footer();

function view_friends_form($p, $errors) {
	if (sizeof($errors) and get_http_var('submit')) {
		print '<div id="errors"><ul><li>';
		print join ('</li><li>', $errors);
		print '</li></ul></div>';
	} else {
        ?> <p align="center">Here's a reminder of the pledge you're telling people about:</p> <? 
        $p->render_box(array());
    } // errors ?>
<p></p>
<form class="generalform" name="pledge" action="email" method="post"><input type="hidden" name="ref" value="<?=$p->url_main() ?>">
<? if (get_http_var('pw')) print '<input type="hidden" name="pw" value="'.htmlspecialchars(get_http_var('pw')).'">'; ?>
<h2>Email this pledge</h2>
<p>
Please enter these details so that we can send your message to your contacts.
We will not give or sell either your or their email address to anyone else.
</p>

<p><strong>Other people's email addresses:</strong></p>
<div class="formrow"><input type="text" name="email1" value="" size="40"></div>
<div class="formrow"><input type="text" name="email2" value="" size="40"></div>
<div class="formrow"><input type="text" name="email3" value="" size="40"></div>
<div class="formrow"><input type="text" name="email4" value="" size="40"></div>
<div class="formrow"><input type="text" name="email5" value="" size="40"></div>

<p><strong>Add a message, if you want:</strong></p>
<div class="formrow"><textarea name="frommessage" rows="5" cols="60"></textarea></div>

<p>
<div class="formrow"><strong>Your name:</strong> <input type="text" name="fromname" value="" size="18">
<br><strong>Email:</strong> <input type="text" name="fromemail" value="" size="26"></div>

<p><input name="submit" type="submit" value="Send message"></p>

</form>

<?
}
?>
