<?php
/*
 * confirm.php:
 * Confirm a user's subscription to a pledge, or creation of a pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: confirm.php,v 1.4 2005-03-10 18:53:00 chris Exp $
 * 
 */

require_once "../phplib/db.php";
require_once "../phplib/pb.php";
require_once "../phplib/fns.php";
require_once "../phplib/pledge.php";

require_once "../../phplib/importparams.php";

$err = importparams(
            array('token',      '/.+/',             "")
        );

if (!is_null($err))
    err("Sorry -- something seems to have gone wrong");

/* Pledges are confirmed by saving a token in the database and sending it to
 * the user. So we can just try confirming the pledge here. */
$pledge_id = pledge_confirm($q_token);
if (!pledge_is_error($pledge_id)) {
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
    return true;
} else {
    /* OK, that wasn't a pledge confirmation token. So we must be signing a
     * pledge. */
    $err = importparams(
                array('email',      '/^[^@]+@.+/',      "", ''),
                array('pledge',     '/^[1-9][0-9]*$/',  "", '')
            );

    if (pledge_email_token($q_email, $q_pledge, $q_token) != $q_token) 
        err("Sorry -- something seems to have gone wrong");

    $err = importparams(
                array('name',       '/[a-z]/i',     "Please give your name",
                                                            ''),
                array('showname',   '/^[01]$/',     "",     '0'),
                array('f',          '/^1$/',        "",     '0')
            );
            
    page_header(
            db_getOne('select title from pledges where id = ?', $pledge_id)
            . ' - Sign Up'
        );

    if ($q_f && is_null($err)) {
        /* Have all the information we need; sign them up. */
        $f1 = pledge_is_successful($q_pledge);
        $r = pledge_sign($q_pledge, $q_name, $q_showname, $q_email);
        if (!pledge_is_error($r)) {
            print "<p>Thank you for confirming your signature!</p>";

            if (!$f1 && pledge_is_successful($q_pledge))
                /* Has this completed the pledge? */
                print "<p><strong>Your signature has made this pledge reach its target! Woohoo!</strong></p>";
            else {
                /* Otherwise advertise flyers. */
                advertise_flyers($q_pledge);
            }
            db_commit();
        } else
            oops($r);
    } else {
        /* Check that there's still a space available on the pledge. */
        $r = pledge_is_valid_to_sign($q_pledge, $q_email);
        if ($r == PLEDGE_OK) {
            /* Produce a form for the punter to sign. */
            $R = array_map('htmlspecialchars', db_getRow('select * from pledges where id = ?', $q_pledge));
            print <<<EOF
<p>Here is the pledge you are signing:</p>

<form class="pledge" name="pledge" method="post">
<input type="hidden" name="pledge" value="$q_h_pledge">
<input type="hidden" name="email" value="$q_h_email">
<input type="hidden" name="token" value="$q_h_token">
<input type="hidden" name="f" value="1">

<p style="margin-top: 0">
EOF
                . '"'
                    . pledge_sentence($q_pledge, true, true)
                    . '"'
                . <<<EOF
<div style="text-align: left; margin-left: 50%;">
<h2 style="margin-top: 1em; font-size: 120%">Sign me up</h2>
<p style="text-align: left">

Name: <input type="text" name="name" value="$q_unchecked_h_name"><br />
EOF;
            if ($q_f && array_key_exists('name', $err))
                print "<span class=\"error\">" . htmlspecialchars($err['name']) . "</span><br />";

            $checked = $q_showname ? ' checked' : '';
            print <<<EOF
Show my name on this pledge: <input type="checkbox" name="showname" value="1"$checked>
<input type="submit" name="submit" value="Submit">
EOF;
/*?>
<p>Deadline: <strong><?=prettify($date) ?></strong></p>
<p style="text-align: center; font-style: italic;"><?=prettify($curr) ?> <?=make_plural($curr,'person has','people have') ?> signed up<?=($left<0?' ('.prettify(-$left).' over target :) )':', '.prettify($left).' more needed') ?></p>*/

?>
<?
        } else
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
    if ($r == PLEDGE_FULL) {
        /* Print a fuller explanation in this (common) case */
        $what = $q_f ? 'filling in the form' : 'waiting for our email to arrive'; /* XXX l18n */
        print <<<EOF
<p>Unfortunately, while you were $what, somebody else beat you to the last
place on that pledge. We're very sorry &mdash; better luck next time!</p>
EOF;
    } else {
        print "<p>" . htmlspecialchars(pledge_strerror($r)) . "</p>";
        if (!pledge_is_permanent_error($r))
            print "<p><strong>Please try again a bit later.</strong></p>";
    }
}

?>
