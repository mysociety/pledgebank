<?
// abuse.php
// Abuse reporting page for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: abuse.php,v 1.7 2005-05-31 17:59:44 matthew Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/utility.php';

page_header('Report Abuse');
report_abusive_thing();
page_footer();

/* report_abusive_thing
 * Reporting of abusive comments, signatures, and pledges. */
function report_abusive_thing() {
    $what = get_http_var('what');
    global $q_what, $q_id, $q_reason;
    global $q_h_what, $q_h_id, $q_h_reason;
    if (!is_null(importparams(
                array('what',       '/^(comment|pledge|signer)$/',  ''),
                array('id',         '/^[1-9]\d*$/',                 ''),
                array('reason',     '//',                           '', null)
            )))
        err("A required parameter was missing");

    /* Find information about the associated pledge. */
    $w = $q_what;
    if ($q_what == 'pledge')
        $pledge_id = $q_id;
    elseif ($q_what == 'comment')
        $pledge_id = db_getOne('select pledge_id from comment where id = ?', $q_id);
    elseif ($q_what == 'signer') {
        $w = 'signature';
        $pledge_id = db_getOne('select pledge_id from signers where id = ?', $q_id);
    }

    if (is_null($pledge_id))
        err("Bad ID value");

    if (!is_null($q_reason)) {
        $ip = $_SERVER["REMOTE_ADDR"];
        db_query('insert into abusereport (what, what_id, reason, ipaddr) values (?, ?, ?, ?)', 
            array($q_what, $q_id, $q_reason, $ip));
        db_commit();
        $admin_url = OPTION_ADMIN_URL . "/?page=pbabusereports&what=" . $q_what;
        pb_send_email(OPTION_CONTACT_EMAIL, "PledgeBank abuse report", <<<EOF
New abuse report for $q_what id $q_id from IP $ip.

$admin_url

Reason given: $q_reason
EOF
);
        print <<<EOF
<p><strong>Thank you!</strong> One of our team will investigate that $w as soon
as possible. </p>

<p><a href="./">Return to the home page</a>.</p>
EOF;
        return;
    }

    $title = htmlspecialchars(db_getOne('select title from pledges where id = ?', $pledge_id));

    print <<<EOF
<form accept-charset="utf-8" action="abuse" method="post" name="abuse" class="pledge">
<h2>Report abusive $w</h2>
<p>You are reporting the $w:</p> 
<blockquote>
EOF;

    if ($q_what == 'pledge') {
        print $title;
    } elseif ($q_what == 'signer') {
        $name = htmlspecialchars(db_getOne('select name from signers where id = ?', $q_id));
        print $name;
    } elseif ($q_what == 'comment') {
        comments_show_one(db_getRow('select * from comment where id = ?', $q_id), true);
    }
    print '</blockquote>';
    if ($q_what != 'pledge') {
        print "<p>on the pledge <strong>$title</strong>.</p>";
    }

    print <<<EOF
<input type="hidden" name="abusive" value="1">
<input type="hidden" name="what" value="$q_h_what">
<input type="hidden" name="id" value="$q_h_id">
<p>Please give a short reason for reporting this $w<br>
<input type="text" name="reason" size="60"></p>
<p><input name="submit" type="submit" value="Submit"></p>
</form>
EOF;

}


