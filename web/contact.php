<?
// contact.php:
// Contact us form for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: contact.php,v 1.66 2007-10-12 13:12:48 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../phplib/pbperson.php';
require_once '../phplib/abuse.php';
require_once '../commonlib/phplib/utility.php';

$params = array();
if (get_http_var('ref') || get_http_var('pledge_id') || get_http_var('comment_id'))
    $params['robots']='noindex,nofollow';

$pledge_id = get_http_var('pledge_id');
$comment_id = get_http_var('comment_id');
if ($pledge_id && !preg_match('/^[1-9]\d*$/', $pledge_id)
    || $comment_id && !preg_match('/^[1-9]\d*$/', $comment_id)) {
    header("Location: /");
    exit();
}
    
page_header(_("Contact Us"), $params);

$errors = array();
if (get_http_var('contactpost')) {
    $data = contact_form_submitted();
    $errors = $data['errors'];
}

if (isset($data['message'])) {
    # Shortcut and only display success message if we have one.
    print $data['message'];
} else {

    $name = get_http_var('name', true);
    $email = get_http_var('e');
    $ref = get_http_var('ref');
    $pledge_id = get_http_var('pledge_id');
    $comment_id = get_http_var('comment_id');
    $referrer = get_http_var('referrer');
    if (!$referrer && array_key_exists('HTTP_REFERER', $_SERVER) && isset($_SERVER['HTTP_REFERER']))
        $referrer = $_SERVER['HTTP_REFERER'];
    $P = pb_person_if_signed_on();
    if (!is_null($P)) {
        if (is_null($name) || !$name)
            $name = $P->name_or_blank();
        if (is_null($email) || !$email)
            $email = $P->email();
    }

    $site = $microsite;
    if (!$site) $site = 'website';
    include_once "../templates/$site/contact.php";
}
page_footer();

function contact_form_submitted() {
    global $microsite;
    $name = get_http_var('name', true);
    if ($name == _('<Enter your name>')) $name = '';
    $email = get_http_var('e');
    $subject = get_http_var('subject', true);
    $message = get_http_var('message', true);
    $ref = get_http_var('ref');
    if (!$ref && get_http_var('pledge_id'))
        $ref = db_getOne('select ref from pledges where id = ?', get_http_var('pledge_id'));
    $referrer = get_http_var('referrer');
    $comment_id = get_http_var('comment_id');
    $errors = array();
    if (!$name) $errors[] = _('Please enter your name');
    if (!$email) $errors[] = _('Please enter your email address');
    elseif (!validate_email($email)) $errors[] = _('Please enter a valid email address');
    if (!$subject && $microsite!='barnet') $errors[] = _('Please enter a subject');
    if (!$message) $errors[] = _('Please enter your message');
    if (!sizeof($errors)) {
        $vars = array(
            'name' => array($name, "User's name"),
            'email' => array($email, "User's email address"),
            'subject' => array($subject, 'Subject entered'),
            'message' => array($message, 'Message entered'),
            'ref' => array($ref, 'Pledge reference'),
        );
        $result = abuse_test($vars);
        if ($result)
            $errors[] = _("I'm afraid that we rate limit the usage of the contact form to prevent abuse.");
    }

    $out = array( 'errors' => $errors );
    if (!sizeof($errors)) {
        $out['message'] = send_contact_form($name, $email, $subject, $message, $ref, $referrer, $comment_id);
    }
    return $out;
}

function send_contact_form($name, $email, $subject, $message, $ref, $referrer, $comment_id) {
    global $lang;

    # See if we have someone special to send the email to
    $to = db_getOne('SELECT email FROM translator WHERE lang=?', $lang);
    if (!$to)
        $to = OPTION_CONTACT_EMAIL;

    /* User mail must be submitted with \n line endings. */
    $message = str_replace("\r\n", "\n", $message);

    $postfix = '[ ';
    $postfix .= 'Sent by ';
    $postfix .= 'contact.php ' . ($ref ? ('for pledge ' . $ref . ' ') : '')  .  
    'on ' . $_SERVER['HTTP_HOST'] . '. ' .
    "IP address " . $_SERVER['REMOTE_ADDR'] . 
    (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) ? ' (forwarded from '.$_SERVER['HTTP_X_FORWARDED_FOR'].')' : '') . '. ' .
    ($referrer ? ("\n  Referrer: " . $referrer) : '') . 
    ($ref ? ("\n  Admin: ".OPTION_ADMIN_URL.'?page=pb&pledge='.$ref
        .($comment_id ? ('#comment_' . $comment_id) : '')
        ) : '') . 
    ($comment_id ? ("\n  Comment author: " . db_getOne('select name from comment where id = ?', $comment_id)
                . "\n  Comment website: " . db_getOne('select website from comment where id = ?', $comment_id)
                . "\n  Comment text: " . db_getOne('select text from comment where id = ?', $comment_id)) : '') . 
    ' ]';
    $headers = array();
    $headers['From'] = array($email, $name);
    if ($subject == $name)
        $success = true;
    else
        $success = pb_send_email($to, $subject, $message . "\n\n-- \n" . $postfix, $headers);
    if (!$success)
        err(_("Failed to send message.  Please try again, or <a href=\"mailto:team&#64;pledgebank.com\">email us</a>."));
    if ($comment_id) 
        return p(_('<strong>Thank you!</strong> One of our team will investigate that comment as soon as possible'));
    else 
        return _('Thanks for your feedback.  We\'ll get back to you as soon as we can!');
}

