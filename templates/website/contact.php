<?
// contact.php:
// Contact us template for PledgeBank.
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org

    print '<div id="tips">';
 
    if ($comment_id) {
        print p(_('You are reporting the following comment to the PledgeBank team:'));
        print '<blockquote>';
        $row = db_getRow('select *,extract(epoch from ms_current_timestamp()-whenposted) as whenposted from comment where id = ? and not ishidden', $comment_id);
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
        print ' ';
        printf(_('If you prefer, you can email %s instead of using the form.'), '<a href="mailto:' . $contact_email . '">' . $contact_email . '</a>');
        print "</p>";
    }

    if (!$comment_id) {
        print p(_('<a href="/faq">Read the FAQ</a> first, it might be a quicker way to answer your question.'));
    }

    print "</div>";

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    } ?>
<form name="contact" accept-charset="utf-8" action="/contact" method="post"><input type="hidden" name="contactpost" value="1"><input type="hidden" name="ref" value="<?=htmlspecialchars($ref)?>"><input type="hidden" name="referrer" value="<?=htmlspecialchars($referrer)?>"><input type="hidden" name="pledge_id" value="<?=htmlspecialchars($pledge_id)?>"><input type="hidden" name="comment_id" value="<?=htmlspecialchars($comment_id)?>">
<?  if ($comment_id) {
        print h2(_('Report abusive, suspicious or wrong comment'));
        print p(_("Please let us know exactly what is wrong with the comment, and why you think it should be removed."));
    } else {
        print h2(_("Contact the PledgeBank team"));
        if ($ref) {
            $h_ref = htmlspecialchars($ref);
        printf(p(_('To contact the creator of the <strong>%s</strong> pledge <a href="%s">leave a comment</a> on the pledge, or <a href="%s">contact the pledge creator</a>. The form below is for messages to the PledgeBank team only, <strong>not</strong> the pledge creator.')),
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
<br><textarea rows="7" cols="40" name="message" id="message"><?=htmlspecialchars(get_http_var('message', true)) ?></textarea></p>

<?  print '<p>';
    if (!$comment_id)
        print _('Did you <a href="/faq">read the FAQ</a> first?') . ' --&gt; ';
    print '<input type="submit" name="submit" value="' . _('Send to PledgeBank team') . '"></p>';
    print '</form>';
