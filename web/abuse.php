<?
// abuse.php
// Abuse reporting page for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: abuse.php,v 1.33 2006-05-30 09:01:12 matthew Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/utility.php';

page_header(_('Report Abuse'), array('robots'=>'noindex,nofollow'));
report_abusive_thing();
page_footer();

/* report_abusive_thing
 * Reporting of abusive comments, signatures, and pledges. */
function report_abusive_thing() {
    global $q_what, $q_id, $q_reason, $q_email, $q_magicword;
    global $q_h_what, $q_h_id, $q_h_reason, $q_h_magicword, $q_h_email;
    $errors = importparams(
                array('what',       '/^(comment|pledge|signer)$/',  ''),
                array('id',         '/^[1-9]\d*$/',                 ''),
                array(array('reason', true),     '//',                           '', null),
                array('email',      '//',                           '', null),
                array('magicword',      '//',                           '', null)
            );
    if (!is_null($errors)) {
        print p(_("A required parameter was missing.") . ' ' . join(" ",$errors));
        return;
    }

    /* Find information about the associated pledge. */
    $w = $q_what;
    $more = '';
    if ($q_what == 'pledge') {
        $w = _('pledge');
        $pledge_id = db_getOne('select id from pledges where id = ?', $q_id);
        $P = new Pledge(intval($q_id));
        $more = "Pledge ref: " . $P->ref()
                . "\nPledge: " . $P->title() . "\n";
    } elseif ($q_what == 'comment') {
        $w = _('comment');
        $pledge_id = db_getOne('select pledge_id from comment where id = ?', $q_id);
        $more = "Pledge ref: " . db_getOne('select ref from pledges where id = ?', $pledge_id)
                . "\nAuthor: " . db_getOne('select name from comment where id = ?', $q_id)
                . "\nText: " . db_getOne('select text from comment where id = ?', $q_id); /* XXX */
    } elseif ($q_what == 'signer') {
        $w = _('signature');
        $pledge_id = db_getOne('select pledge_id from signers where id = ?', $q_id);
        $more = "Signer: " . db_getOne('select name from signers where id = ?', $q_id) . "\n";
    }

    if (is_null($pledge_id)) {
        print h2(_('Report Abuse'));
        printf(_("The %s couldn't be found.  It has probably been deleted already."), $w);
        return;
    }

    $errors = array();
    if (!is_null($q_reason) && !validate_email($q_email)) {
        $errors['email'] = _('Please enter a valid email address');
    }
    if (!is_null($q_reason) && $q_magicword != _('together')) {
        $errors['magicword'] = _("Please enter the word 'together' in the <strong>magic word</strong> box. We ask you to do this because there are people who run software which crawls the internet and automatically posts adverts. Asking you to enter the word is a reliable and unobtrusive way of making sure you are a human, not a piece of software.");
    }

    if (!is_null($q_reason) && !$errors) {
        $ip = $_SERVER["REMOTE_ADDR"];
        $host = $_SERVER['HTTP_HOST'];
        db_query('insert into abusereport (what, what_id, reason, ipaddr, email) values (?, ?, ?, ?, ?)', 
            array($q_what, $q_id, $q_reason, $ip, $q_email));
        db_commit();
        $admin_url = OPTION_ADMIN_URL . "?page=pbabusereports&what=" . $q_what;

        global $lang;
        # See if we have someone special to send the email to
        $to = db_getOne('SELECT email FROM translator WHERE lang=?', $lang);
        if (!$to)
            $to = OPTION_CONTACT_EMAIL;

        pb_send_email($to, _("PledgeBank abuse report"), _(<<<EOF
New abuse report for $q_what id $q_id from $host, IP $ip, email $q_email

$more

$admin_url

Reason given: $q_reason
EOF
));
        printf(p(_('<strong>Thank you!</strong> One of our team will investigate that %s as soon
as possible.')), $w);
        print p(_('<a href="./">Return to the home page</a>.'));
        return;
    }

    $title = htmlspecialchars(db_getOne('select title from pledges where id = ?', $pledge_id));

    if ($errors) {
        print '<div id="errors"><ul><li>';
        print join("</li><li>", $errors);
        print '</li></ul></div>';
    }

    print '<form accept-charset="utf-8" action="abuse" method="post" name="abuse" class="pledge">';
    # TRANS: %s is singular, one of "pledge", "comment", or "signature" 
    printf(h2(_('Report abusive, suspicious or wrong %s')), $w);
    printf(p(_('You are reporting the following %s:')), $w);
    print '<blockquote>';
    if ($q_what == 'pledge') {
        print $title;
    } elseif ($q_what == 'signer') {
        $name = htmlspecialchars(db_getOne('select name from signers where id = ?', $q_id));
        print $name;
    } elseif ($q_what == 'comment') {
        comments_show_one(db_getRow('select *,extract(epoch from whenposted) as whenposted from comment where id = ?', $q_id), true);
    }
    print '</blockquote>';
    if ($q_what != 'pledge') {
        printf(p(_("This is on the pledge <strong>%s</strong>.")), $title);
    }
    printf(p(_('(if you would like to send us a general comment, rather than an abuse report, <a href="/contact/">try here</a>)')));

    print <<<EOF
<input type="hidden" name="abusive" value="1">
<input type="hidden" name="what" value="$q_h_what">
<input type="hidden" name="id" value="$q_h_id">
EOF;

    /* XXX we should add a drop-down for category of abuse, to drive home the
     * point that this is an *abuse* report. */

    print '<p>';
    printf(_('<strong>Your email</strong>:'));
    print ' <input type="text" name="email" '.(array_key_exists('email', $errors) ? 'class="error" ' : '').'size="20" value="'.$q_h_email.'">';
    #print ' <small>'._('(optional, if you want us to get back to you)').'</small>';
    print '<br>';
    printf(_('<strong>Reason for reporting this %s</strong>:'), $w);
    print '<br><textarea style="max-width: 100%" name="reason" cols="60" rows="3">'.$q_h_reason.'</textarea>';
    print '<br>';
    printf(_('<strong>Enter the magic word</strong>, which is \'together\':'));
    print ' <input type="text" name="magicword" '.(array_key_exists('magicword', $errors) ? 'class="error" ' : '').'size="20">';
    print '</p>';
    print '<p>';
    print '<input name="submit" type="submit" value="' . _('Submit') . '"></p>';
    print '</form>';

}

