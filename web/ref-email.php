<?
// email.php:
// Sending out pledge adverts by email
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-email.php,v 1.16 2005-07-01 22:35:18 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/db.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/utility.php';

page_check_ref(get_http_var('ref'));
$p  = new Pledge(get_http_var('ref'));

$pin_box = deal_with_pin($p->url_email(), $p->ref(), $p->pin());
if ($pin_box) {
    page_header(_("Enter PIN")); 
    print $pin_box;
    page_footer();
    exit;
}

$title = _("Emailing friends");
page_header($title, array('ref' => $p->url_main() ));

# fromname, fromemail, frommessage
# email as an array
$fromname = get_http_var('fromname');
$fromemail = trim(get_http_var('fromemail'));
$frommessage = get_http_var('frommessage');
$errors = array();
$emails = array();
for ($i = 1; $i <= 5; $i++) {
    if (get_http_var("email$i")) {
        $email = trim(get_http_var("email$i"));
        $emails[] = $email;
        if (!validate_email($email)) {
            $errors['email'.$i] = _("Please correct the email address '".htmlspecialchars($email)."', which is not a valid address.");
        }
    }
}
if (count($emails) < 1) $errors['email1'] = _("Please enter the email addresses of the people you want to tell about the pledge");
if (!$fromname) $errors['fromname'] = _("Please enter your name");
if (!$fromemail) $errors['fromemail'] = _("Please enter your email address");
if (!validate_email($fromemail)) $errors['fromemail'] = _("Please enter a valid address for your email");

if (!$errors) {
    if (sizeof($emails)>5)
        err(_("Trying to use us for SPAMMING!?!?!"));
    $success = 1;
    foreach ($emails as $email) {
        if (!$email)
            continue;
        $success &= pb_send_email_template($email, 'email-friends',
            array_merge($p->data, array(
                'from_name'=>$fromname, 
                'from_email' => $fromemail, 
                'from_message' => $frommessage ? _('They added this message:') . " \"$frommessage\"\n\n" : "",
            )), array('From'=>'"' . str_replace(array('\\','"'), array('\\\\','\"'), $fromname) . '" <' . $fromemail . '>')
        );
    }
    if ($success) {
        print p(_('Your message has been sent.  Thanks very much for spreading the word of this pledge.'));
    } else {
        $errors[] = _('Unfortunately, something went wrong when trying to send the emails. Please check that all the email addresses are correct.');
        view_friends_form($p, $errors);
    }
} else {
    view_friends_form($p, $errors);
}

page_footer();

?>
