<?
// ref=contact.php:
// Getting in touch with the pledge creator
//
// Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: ref-contact.php,v 1.5 2007-10-12 13:12:48 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/abuse.php';
require_once '../commonlib/phplib/utility.php';

page_check_ref(get_http_var('ref'));
$p = new Pledge(get_http_var('ref'));
microsites_redirect($p);
deal_with_pin($p->url_email(), $p->ref(), $p->pin());

$title = _("Contact the pledge creator");
page_header($title, array('ref'=>$p->ref(),'pref' => $p->url_typein() ));

$data = array(
    'name' => get_http_var('name', true),
    'email' => get_http_var('e'),
    'message' => get_http_var('message', true),
);
$data = array_map('trim', $data);
if ($data['name'] == _('<Enter your name>')) $data['name'] = '';

$errors = array();
$success = false;
if (get_http_var('submit')) {
    if (!$data['name']) $errors['name'] = _("Please enter your name");
    if (!$data['email']) $errors['e'] = _("Please enter your email address");
    elseif (!validate_email($data['email'])) $errors['e'] = _("Please enter a valid address for your email");
    if (!$data['message']) $errors['message'] = _('Please enter your message');

    if (!count($errors)) {
        $vars = array(
            'name' => array($data['name'], "User's name"),
            'email' => array($data['email'], 'Email address'),
            'ref' => array($p->ref(), 'Pledge reference'),
            'message' => array($data['message'], 'Message entered'),
        );
        $result = abuse_test($vars);
        if ($result) {
            $errors[] = _("I'm afraid that to prevent abuse we rate limit the usage of the
Contact the pledge creator feature. Please try again tomorrow.");
        } else {
            $success = pb_send_email_template(array(array($p->creator_email(), $p->creator_name())),
                'email-creator', array_merge($p->data, array(
                'from_name' => $data['name'], 
                'message' => $data['message'],
                )), array('From'=>array($data['email'], $data['name']))
            );
            if ($success) {
                print '<p class="success">' . _('Your message has been sent to the pledge creator.') . '</p>';
                $p->render_box(array('showdetails' => true));
            } else {
                $errors[] = _('Unfortunately, something went wrong when trying to send the email. Please check that everything is correct.');
            }
        }
    }
}

if (!$success)
    contact_creator_form($p, $errors, $data);

page_footer();

# ---

function contact_creator_form($p, $errors, $data) {
    global $title;

    $P = pb_person_if_signed_on();
    if (!is_null($P)) {
        if (!$data['name']) $data['name'] = $P->name_or_blank();
        if (!$data['email']) $data['email'] = $P->email();
    }

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    }

    $p->render_box(array('showdetails' => true));

?>
<form name="pledge" action="<?=$p->url_contact_creator() ?>" method="post">
<?
    if (get_http_var('pin', true)) print '<input type="hidden" name="pin" value="'.htmlspecialchars(get_http_var('pin', true)).'">';
    print h2($title);
    print p(sprintf(_('Please fill in the form below to send a message to the pledge creator.
Your email address will be given to the pledge creator so that they can reply.
If you do not want to do that, please <a href="%s">leave a comment</a> on the pledge instead.'), $p->url_main() . '#commentform'));

?>

<p><?=_('Your message:') ?>
<br><textarea<? if (array_key_exists('message', $errors)) print ' class="error"' ?> name="message" rows="10" cols="40"><?=htmlspecialchars($data['message']) ?></textarea>
</p>

<p><?=_('Your name:') ?> <input <? if (array_key_exists('name', $errors)) print ' class="error"' ?> type="text" id="name" name="name" value="<?=htmlspecialchars($data['name']) ?>" size="20">
<br><?=_('Your email:') ?> <input <? if (array_key_exists('e', $errors)) print ' class="error"' ?> type="text" name="e" value="<?=htmlspecialchars($data['email']) ?>" size="30">
</p>

<p align="right"><input name="submit" type="submit" value="<?=_('Send message') ?>"></p>

</form>

<?
}
?>
