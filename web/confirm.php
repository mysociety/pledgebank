<?php
/*
 * confirm.php:
 * Confirm a user's subscription to a pledge, or creation of a pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: confirm.php,v 1.7 2005-03-11 20:14:31 chris Exp $
 * 
 */

require_once "../phplib/db.php";
require_once "../phplib/pb.php";
require_once "../phplib/fns.php";
require_once "../phplib/pledge.php";

require_once "../../phplib/importparams.php";

$err = importparams(
            array('token',      '/.+/',             ""),
            array('type',      '/(pledge|signature)/',             "")
        );

if (!is_null($err))
    err("Sorry -- something seems to have gone wrong");


if ($type == 'pledge') {
    /* Pledges are confirmed by saving a token in the database and sending it to
     * the user. */
    $pledge_id = pledge_confirm($q_token);
    if (pledge_is_error($pledge_id)) {
        err("That pledge hasn't been recognised.  Please check the URL is copied correctly from your email.");
    }

    /* Success. */
    page_header(
            db_getOne('select title from pledges where id = ?', $pledge_id)
            . ' - Confirm'
        );
    db_commit();
    ?>
    <p>Thank you for confirming your pledge. It is now live, and people can sign up
    to it. OTHER STUFF.</p>
    <?  advertise_flyers($pledge_id);
    page_footer();
    exit;
} elseif ($type == 'signature') {
    /* OK, that wasn't a pledge confirmation token. So we must be signing a
     * pledge. */
    $data = pledge_random_token_retrieve($q_token);
    print_r($data);

    page_header(
            db_getOne('select title from pledges where id = ?', $data['pledge_id'])
            . ' - Sign Up'
        );

    /* Sign them up. */
    $f1 = pledge_is_successful($data['pledge_id']);
    $r = pledge_sign($data['pledge_id'], $data['name'], $data['showname'], $data['email']);
    if (!pledge_is_error($r)) {
        print "<p>Thank you for confirming your signature!</p>";

        if (!$f1 && pledge_is_successful($data['pledge_id']))
            /* Has this completed the pledge? */
            print "<p><strong>Your signature has made this pledge reach its target! Woohoo!</strong></p>";
        else {
            /* Otherwise advertise flyers. */
            advertise_flyers($data['pledge_id']);
        }
        db_commit();
    } else {
        oops($r);
    }
    page_footer();
}

/* advertise_flyers PLEDGE
 * Print some stuff advertising flyers for PLEDGE. */
function advertise_flyers($pledge_id) {
    $r = db_getRow('select ref, title, date from pledges where id = ?', $pledge_id);
?><p><a href="<?=htmlspecialchars($r['ref']) ?>/flyers">View and print Customised Flyers for this pledge</a></p>

<p align="center"><big>Why not <strong>
<script type="text/javascript">
    document.write('<a href="javascript: window.print()">HIT PRINT</a>');
</script>
<noscript>
HIT PRINT
</noscript>
</strong> now and get these example cards below, for you to cut out and give
to your friends and neighbours?</big></p>

<style type="text/css">
table {
    border: none;
    margin: 0 auto;
    max-width: 90%;
}
td {
    border: solid 2px black;
}
</style>
<table border="1" cellpadding="10" cellspacing="20"><?
    
    for ($rows = 0; $rows<4; $rows++) {
        print '<tr align="center">';
        for ($cols=0; $cols<2; $cols++) {
            print '<td>';
            print '<strong>"I will ' . htmlspecialchars($r['title']) . '"</strong>';
            print '<br>Deadline: ' . prettify($r['date']);
            print '<br>www.pledgebank.com/' . htmlspecialchars($r['ref']);
            print '</td>';
        }
        print '</tr>';
    }
    print "</table>";
}

/* oops CODE
 * Print a message explaining CODE. */
function oops($r) {
    global $q_f;
    print "<p><strong>Sorry, we couldn't sign you up to that pledge:</strong></p>";
    if ($r == PLEDGE_FULL || $r == PLEDGE_FINISHED) {
        /* Print a fuller explanation in this (common) case */
        $what = $q_f ? 'filling in the form' : 'waiting for our email to arrive'; /* XXX l18n */
        $how = ($r == PLEDGE_FULL ?
                    "somebody else beat you to the last place on that pledge"
                    : "the pledge finished");
        print <<<EOF
<p>Unfortunately, while you were $what, $how.
We're very sorry &mdash; better luck next time!</p>
EOF;
    } else {
        print "<p>" . htmlspecialchars(pledge_strerror($r)) . "</p>";
        if (!pledge_is_permanent_error($r))
            print "<p><strong>Please try again a bit later.</strong></p>";
    }
}

?>
