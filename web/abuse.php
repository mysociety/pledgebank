<?
// abuse.php
// Abuse reporting page for PledgeBank.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: abuse.php,v 1.1 2005-04-29 15:14:12 francis Exp $

require_once '../phplib/pb.php';
require_once '../phplib/fns.php';
require_once '../phplib/pledge.php';
require_once '../phplib/comments.php';
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
    else if ($q_what == 'comment')
        $pledge_id = db_getOne('select pledge_id from comment where id = ?', $q_id);
    else if ($q_what == 'signer') {
        $w = 'signature';
        $pledge_id = db_getOne('select pledge_id from signers where id = ?', $q_id);
    }

    if (is_null($pledge_id))
        err("Bad ID value");

    if (!is_null($q_reason)) {
        db_query('insert into abusereport (what, what_id, reason, ipaddr) values (?, ?, ?, ?)', array($q_what, $q_id, $q_reason, $_SERVER["REMOTE_ADDR"]));
        db_commit();
        print <<<EOF
<p><strong>Thank you!</strong> One of our team will investigate that $w as soon
as possible. <a href="./">Return to the home page</a>.</p>
EOF;
        return;
    }

    $title = htmlspecialchars(db_getOne('select title from pledges where id = ?', $q_id));

    print <<<EOF
<form accept-charset="utf-8" action="./" method="post">
<h2>Report abusive $w</h2>
EOF;

    if ($q_what == 'pledge') {
        print <<<EOF
<p>You are reporting the pledge</p>
<blockquote>$title</blockquote>
EOF;
    } else if ($q_what == 'signer') {
        $name = htmlspecialchars(db_getOne('select name from signers where id = ?', $q_id));
        print <<<EOF
<p>You are reporting the signer</p>
<blockquote>$name</blockquote>
<p>on the pledge <strong>$title</strong>.</p>
EOF;
    } else if ($q_what == 'comment') {
        print <<<EOF
<p>You are reporting the comment</p>
EOF;
        comments_show_one(db_getRow('select * from comment where id = ?', $q_id));
        print <<<EOF
<p>on the pledge <strong>$title</strong>.</p>
EOF;
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


