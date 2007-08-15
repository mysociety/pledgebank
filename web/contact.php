<?
// contact.php:
// Contact us form for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: contact.php,v 1.64 2007-08-15 11:19:04 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../phplib/pbperson.php';
require_once '../phplib/abuse.php';
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

    print '<div id="tips">';
 
    if ($comment_id) {
        print h2(_('Report abusive, suspicious or wrong comment'));
    } else {
        print h2(_('Contact Us'));
    }
    if ($comment_id) {
        print p(_('Thank you for taking the time to report a comment, please fill in the form on the right.'));
    } else {
        microsites_contact_intro();
    }

    if (!$comment_id) {
        # XXX
        $blurb = '';
        global $microsite;
        if ($microsite == 'livesimply')
            $blurb = " If that doesn't help either you can fill in the form below or get in touch with Mark Woods, <em>live</em>simply Project Co-ordinator on 01293 541334.";
        print p(_('<a href="/faq">Read the FAQ</a> first, it might be a quicker way to answer your question.' . $blurb));
    }

    print "</div>";

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    } ?>
<form id="pledgeaction" name="contact" accept-charset="utf-8" action="/contact" method="post"><input type="hidden" name="contactpost" value="1"><input type="hidden" name="ref" value="<?=htmlspecialchars($ref)?>"><input type="hidden" name="referrer" value="<?=htmlspecialchars($referrer)?>"><input type="hidden" name="pledge_id" value="<?=htmlspecialchars($pledge_id)?>"><input type="hidden" name="comment_id" value="<?=htmlspecialchars($comment_id)?>">
<? if ($comment_id) { ?>
<h2><?=_("Report comment to PledgeBank")?></h2>
<?
        print p(_('You are reporting the following comment to the PledgeBank team:'));
        print '<blockquote>';
        $row = db_getRow('select *,extract(epoch from ms_current_timestamp()-whenposted) as whenposted from comment where id = ? and not ishidden', $comment_id);
        if ($row)
            print comments_show_one($row, true);
        else
            print 'Comment no longer exists';
        print '</blockquote>';
        print p("Please let us know exactly what is wrong with the comment, and why you think it should be removed.");
?>
<? } else { ?>
<h2><?=_("Contact the PledgeBank team")?></h2>
<?  if ($ref) {
        $h_ref = htmlspecialchars($ref);
        print sprintf(p(_('To contact the creator of the <strong>%s</strong> pledge <a href="%s">leave a comment</a> on the pledge, or <a href="%s">contact the pledge creator</a>. The form below is for messages to the PledgeBank team only, <strong>not</strong> the pledge creator.')),
            $h_ref, "/$h_ref#comments", "/$h_ref/contact");
    } else { 
        print p(_("To contact a pledge creator, please use the 'comments' section on the pledge, or the 'contact the pledge creator' feature. The form below is for messages to the PledgeBank team only, <strong>not</strong> a pledge creator."));
    }
?>
<? } ?>

<p><label for="name"><?=_('Your name:') ?></label> <input type="text" id="name" name="name" value="<?=htmlspecialchars($name) ?>" size="25">
<br><label for="e"><?=_('Your email:') ?></label> <input type="text" id="e" name="e" value="<?=htmlspecialchars($email) ?>" size="30"></p>

<p><label for="subject"><?=_('Subject') ?></label>: <input type="text" id="subject" name="subject" value="<?=htmlspecialchars(get_http_var('subject', true)) ?>" size="48"></p>

<p><label for="message"><?=_('Your message:') ?></label>
<br><textarea rows="7" cols="60" name="message" id="message"><?=htmlspecialchars(get_http_var('message', true)) ?></textarea></p>

<?  print '<p>' . _('Did you <a href="/faq">read the FAQ</a> first?') . '
--&gt; <input type="submit" name="submit" value="' . _('Send to PledgeBank team') . '"></p>';
    print '</form>';
}

function contact_form_submitted() {
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
    if (!$subject) $errors[] = _('Please enter a subject');
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
        print p(_('<strong>Thank you!</strong> One of our team will investigate that comment as soon as possible'));
    else 
        print _('Thanks for your feedback.  We\'ll get back to you as soon as we can!');
}

?>
