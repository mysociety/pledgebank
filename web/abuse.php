<?
// abuse.php
// Abuse reporting page for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: abuse.php,v 1.19 2005-07-31 22:29:48 matthew Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/utility.php';

page_header(_('Report Abuse'));
report_abusive_thing();
page_footer();

/* report_abusive_thing
 * Reporting of abusive comments, signatures, and pledges. */
function report_abusive_thing() {
    $what = get_http_var('what');
    global $q_what, $q_id, $q_reason;
    global $q_h_what, $q_h_id, $q_h_reason;
    $errors = importparams(
                array('what',       '/^(comment|pledge|signer)$/',  ''),
                array('id',         '/^[1-9]\d*$/',                 ''),
                array('reason',     '//',                           '', null)
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

    if (!is_null($q_reason)) {
        $ip = $_SERVER["REMOTE_ADDR"];
        db_query('insert into abusereport (what, what_id, reason, ipaddr) values (?, ?, ?, ?)', 
            array($q_what, $q_id, $q_reason, $ip));
        db_commit();
        $admin_url = OPTION_ADMIN_URL . "/?page=pbabusereports&what=" . $q_what;
        pb_send_email(OPTION_CONTACT_EMAIL, _("PledgeBank abuse report"), _(<<<EOF
New abuse report for $q_what id $q_id from IP $ip.

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

    print '<form accept-charset="utf-8" action="abuse" method="post" name="abuse" class="pledge">';
    printf(h2(_('Report something wrong with a %s')), $w);
    printf(p(_('You are reporting the following %s as being abusive, suspicious or having something wrong with it.')), $w);
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

    print <<<EOF
<input type="hidden" name="abusive" value="1">
<input type="hidden" name="what" value="$q_h_what">
<input type="hidden" name="id" value="$q_h_id">
EOF;
    print '<p>';
    printf(_('Please give a short reason for reporting this %s.'), $w);
    print '<br><input type="text" name="reason" size="60"></p>
<p><input name="submit" type="submit" value="' . _('Submit') . '"></p>
</form>';

}

