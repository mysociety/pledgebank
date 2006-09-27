<?
// contact.php:
// Contact us form for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: contact.php,v 1.50 2006-09-27 10:13:06 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../phplib/pbperson.php';
require_once '../../phplib/utility.php';

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
 
    if ($comment_id) {
        print h2(_('Report abusive, suspicious or wrong comment'));
    } else {
        print h2(_('Contact Us'));
    }
    if ($comment_id) {
        print p(_('You are reporting the following comment:'));
        print '<blockquote>';
	$row = db_getRow('select *,extract(epoch from whenposted) as whenposted from comment where id = ? and not ishidden', $comment_id);
        if ($row)
	    print comments_show_one($row, true);
	else
	    print 'Comment no longer exists';
        print '</blockquote>';
    } else {
        print "<p>";
        print _('Was it useful?  How could it be better?
    We make PledgeBank and thrive off feedback, good and bad.
    Use this form to contact us.');
        $contact_email = str_replace('@', '&#64;', OPTION_CONTACT_EMAIL);
        printf(_('If you prefer, you can email %s instead of using the form.'), '<a href="mailto:' . $contact_email . '">' . $contact_email . '</a>');
        print "</p>";
    }

    print p(_("If you would like to contact the Pledge Creator, please use the 'comments' section on the pledge. The form below is for messages to the PledgeBank Team only, <strong>not</strong> the Pledge Creator."));
    if (!$comment_id) 
        print p(_('<a href="/faq">Read the FAQ</a> first, it might be a quicker way to answer your question.'));

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    } ?>
<form class="pledge" name="contact" accept-charset="utf-8" action="/contact" method="post"><input type="hidden" name="contactpost" value="1"><input type="hidden" name="ref" value="<?=htmlspecialchars($ref)?>"><input type="hidden" name="referrer" value="<?=htmlspecialchars($referrer)?>"><input type="hidden" name="pledge_id" value="<?=htmlspecialchars($pledge_id)?>"><input type="hidden" name="comment_id" value="<?=htmlspecialchars($comment_id)?>">
<p><?=_('Message to')?>: <strong><?=_("PledgeBank Team")?></strong></p>

<p><label for="name"><?=_('Your name') ?></label>: <input type="text" id="name" name="name" onblur="fadeout(this)" onfocus="fadein(this)" value="<?=htmlspecialchars($name) ?>" size="25">
<br><label for="email"><?=_('Your email') ?></label>: <input type="text" id="email" name="email" value="<?=htmlspecialchars($email) ?>" size="30"></p>

<p><label for="subject"><?=_('Subject') ?></label>: <input type="text" id="subject" name="subject" value="<?=htmlspecialchars(get_http_var('subject', true)) ?>" size="48"></p>

<p><label for="message"><?=_('Write your message') ?></label>
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
    if (!$ref && get_http_var('pledge_id'))
        $ref = db_getOne('select ref from pledges where id = ?', get_http_var('pledge_id'));
    $referrer = get_http_var('referrer');
    $comment_id = get_http_var('comment_id');
    $errors = array();
	if (!$name) $errors[] = _('Please enter your name');
	if (!$email) $errors[] = _('Please enter your email address');
	if (!validate_email($email)) $errors[] = _('Please enter a valid email address');
	if (!$subject) $errors[] = _('Please enter a subject');
	if (!$message) $errors[] = _('Please enter your message');
	if (sizeof($errors)) {
		contact_form($errors);
	} else {
		send_contact_form($name, $email, $subject, $message, $ref, $referrer, $comment_id);
	}
}

function send_contact_form($name, $email, $subject, $message, $ref, $referrer, $comment_id) {
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
                . "\n  Comment text: " . db_getOne('select text from comment where id = ?', $comment_id)) : '') . 
    ' ]';
    $headers = array();
    $headers['From'] = array($email, $name);
#print "<pre>";    print_r($message . "\n\n" . $postfix);exit;
    $success = pb_send_email($to, $subject, $message . "\n\n-- \n" . $postfix, $headers);
    if (!$success)
        err(_("Failed to send message.  Please try again, or <a href=\"mailto:team&#64;pledgebank.com\">email us</a>."));
    if ($comment_id) 
        print p(_('<strong>Thank you!</strong> One of our team will investigate that comment as soon as possible'));
    else 
        print _('Thanks for your feedback.  We\'ll get back to you as soon as we can!');
}

?>
