<?php
/*
 * confirm.php:
 * Confirm a user's subscription to a pledge, or creation of a pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: confirm.php,v 1.10 2005-03-15 19:09:38 sandpit Exp $
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


if ($q_type == 'pledge') {
    /* Pledges are confirmed by saving a token in the database and sending it to
     * the user. */
    $pledge_id = pledge_confirm($q_token);
    if (pledge_is_error($pledge_id)) {
        err("That pledge hasn't been recognised.  Please check the URL is copied correctly from your email.");
    }

    /* Success. */
    $q = db_query('select * from pledges where id = ?', $pledge_id);
    $r = db_fetch_array($q);
    page_header($r['title'] . ' - Confirm', array('nonav'=>true));
    db_commit();
    $url = "/" . urlencode($r['ref']);
    ?>
    <p>Thank you for confirming your pledge. It is now live, and people can 
    <a href="<?=$url?>">sign up to it</a>.</p>
    <?  advertise_flyers($pledge_id);
} elseif ($q_type == 'signature') {
    /* OK, that wasn't a pledge confirmation token. So we must be signing a
     * pledge. */
    $data = pledge_random_token_retrieve($q_token);
    print_r($data);

    page_header(
            db_getOne('select title from pledges where id = ?', $data['pledge_id'])
            . ' - Sign Up', array('nonav'=>true)
        );

    /* Sign them up. */
    $f1 = pledge_is_successful($data['pledge_id']);
    $r = pledge_sign($data['pledge_id'], $data['name'], $data['showname'], $data['email']);
    if (!pledge_is_error($r)) {
        print "<p>Thanks for subscribing to this pledge!</p>";

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
}
page_footer(array('nonav'=>true));

/* advertise_flyers PLEDGE
 * Print some stuff advertising flyers for PLEDGE. */
function advertise_flyers($pledge_id) {
    $r = db_getRow('select * from pledges where id = ?', $pledge_id);
?>

<p align="center">
<strong>Important Notice</strong> - You will massively increase the chance of this pledge being
a success if 
<script type="text/javascript">
    document.write('<a href="javascript: window.print()">print this page out</a>,');
</script>
<noscript>
print this page out,
</noscript> 
cut up the flyers and stick them through some
of your neighbours letterboxes. We cannot emphasise this enough - print them
now and post them next time you go out to the shops. We also have more
<a href="/<?=htmlspecialchars($r['ref']) ?>/flyers"> attractive PDF versions</a>.</p>



<style type="text/css">
table {
    margin: 10pt;
    max-width: 90%;
    border-collapse: collapse;
    border: dashed 1px black;
}
td {
    font-size: 83%;
    border: dashed 1px black;
    padding: 10pt;
}
</style>
<table><?
    
    for ($rows = 0; $rows<4; $rows++) {
        print '<tr align="center">';
        for ($cols=0; $cols<2; $cols++) {
            print '<td>';
            print pledge_sentence($r, array('firstperson'=>'includename', 'html'=>true));
            print '<p>Please support me by signing up, and by encouraging
                other people to do the same. I am using the charitable service
                PledgeBank.com to gather support.</p>
            
                <p>It will only take you a few seconds - sign up free at ';
            print '<strong>www.pledgebank.com/' .  htmlspecialchars($r['ref']) . "</strong>";
            print '<p>Or text <strong>';
            print 'pledge ' . htmlspecialchars($r['ref']);
            print '</strong>  to <strong>12345</strong> (cost 25p)';
            print '<p>This pledge closes on ' . prettify($r['date']). '. ';
            print 'Thanks!';
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
