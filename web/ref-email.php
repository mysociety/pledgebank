<?
// email.php:
// Sending out pledge adverts by email
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-email.php,v 1.32 2007-11-06 14:28:35 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../commonlib/phplib/utility.php';

page_check_ref(get_http_var('ref'));
$p = new Pledge(get_http_var('ref'));
microsites_redirect($p);
deal_with_pin($p->url_email(), $p->ref(), $p->pin());

$title = _("Emailing friends");
page_header($title, array('ref'=>$p->ref(),'pref' => $p->url_typein() ));

$track = get_http_var('track');

# fromname, fromemail, frommessage
# email as an array
$fromname = get_http_var('fromname', true);
$fromemail = trim(get_http_var('fromat'));
$frommessage = get_http_var('frommessage', true);
$errors = array();

if (0 && get_http_var('submit')) {
    $emails = array();
    if (preg_match('#[;, ]#', get_http_var('e1'))) {
        $email = preg_split('#[;,\s]+#', get_http_var('e1'), -1, PREG_SPLIT_NO_EMPTY);
	$i = 1;
        foreach ($email as $e) {
	    $_GET['e' . $i++] = $e;
        }
    }
    for ($i = 1; $i <= 5; $i++) {
        if (get_http_var("e$i")) {
            $email = trim(get_http_var("e$i"));
            $emails[] = $email;
            if (!validate_email($email)) {
                $errors['e'.$i] = sprintf(_("Please correct the email address '%s', which is not a valid address."), htmlspecialchars($email));
            }
        }
    }
    $emails = array_unique($emails);
    if (count($emails) < 1) $errors['e1'] = _("Please enter the email addresses of the people you want to tell about the pledge");
    if (!$fromname) $errors['fromname'] = _("Please enter your name");
    if (!$fromemail) $errors['fromat'] = _("Please enter your email address");
    elseif (!validate_email($fromemail)) $errors['fromat'] = _("Please enter a valid address for your email");

    if (!$errors) {
        if (sizeof($emails)>5)
            err(_("Trying to use us for SPAMMING!?!?!"));
        $success = 1;
        foreach ($emails as $email) {
            if (!$email)
                continue;
            global $microsite;
            $success &= pb_send_email_template($email, 'email-friends',
                array_merge($p->data, array(
                    'from_name' => $fromname, 
                    'from_email' => $fromemail, 
                    'from_message' => $frommessage ? sprintf(_('%s added this message:'), $fromname) . " \"$frommessage\"\n\n" : "",
                )), array('From' => array($fromemail, $fromname))
            );
        }
        if ($success) {
            print p(_('Your message has been sent.  Thanks very much for spreading the word of this pledge.'));
            if ($track)
                $track .= '; sent=' . sizeof($emails);
        } else {
            $errors[] = _('Unfortunately, something went wrong when trying to send the emails. Please check that all the email addresses are correct.');
            view_friends_form($p, $errors, $track);
        }
    } else {
        view_friends_form($p, $errors, $track);
    }
} else {
    view_friends_form($p, $errors, $track);
}

$params = array();
if ($track)
    $params['extra'] = $track;
page_footer($params);

?>
