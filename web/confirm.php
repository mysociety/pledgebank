<?php
/*
 * confirm.php:
 * Confirm a user's subscription to a pledge, or creation of a pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: confirm.php,v 1.15 2005-03-17 09:33:52 sandpit Exp $
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
#    page_header("${r['title']} - Confirm", array('nonav' => true));
    page_header("PRINT THIS - CUT IT UP - DELIVER LOCALLY", array('nonav' => true));

    db_commit();
    $url = "/" . urlencode($r['ref']);
    ?>
    <div class="noprint">
    <p>Thank you for confirming your pledge. It is now live, and people can 
    <a href="<?=$url?>">sign up to it</a>.</p>
    </div>
    <?  advertise_flyers($pledge_id);
} elseif ($q_type == 'signature') {
    /* OK, that wasn't a pledge confirmation token. So we must be signing a
     * pledge. */
    $data = pledge_token_retrieve('signup-web', $q_token);
    if (!$data)
        err("No such token");
    pledge_token_destroy('signup-web', $q_token);

    $title = db_getOne('select title from pledges where id = ?',
                        $data['pledge_id']);
                        
    page_header("$title - Sign up", array('nonav' => true));

    $r = PLEDGE_ERROR;
    $f1 = null;
    if (array_key_exists('signer_id', $data)) {
        /* If the data contain a signer ID, then we're converting an SMS
         * subscription. */
        $r = pledge_is_valid_to_sign($data['pledge_id'], $data['email']);
        if (!pledge_is_error($r)) {
            /* Fine. */
            db_query('
                    update signers
                    set email = ?, name = ?, showname = ?
                    where id = ?',
                    array(
                        $data['email'], $data['name'], $data['showname'],
                        $data['signer_id']
                    )
                );
        } else {
            /* Two possibilities:
             *  1. signer has given same email address as another signer
             *  2. signer has given email address of pledge creator
            $id = db_getOne('select id from signers where pledge_id = ? and email = ?', array($data['pledge_id'], $data['signer_id']));
                /* There's already a signer with that email address; combine them. */
                db_query('select signers_combine_2(?, ?)', array($id, $data['signer_id']));
                /* In the other case (where we discover this before sending the
                 * confirm email, we send a special "hey, you've signed up twice"
                 * mail. But I don't think that's worth doing here, since we'll
                 * only get into this condition when the user has already received
                 * two confirmation mails in short order. Presumably they have
                 * some idea what they're doing! */
                $r = PLEDGE_OK;
            }
    } else {
        /* Else this is a new subscription. */
        $f1 = pledge_is_successful($data['pledge_id']);
        $r = pledge_sign($data['pledge_id'], $data['name'], $data['showname'], $data['email']);
    }

    if (!pledge_is_error($r)) {
        print '<div class="noprint">';
        print "<p>Thanks for subscribing to this pledge!</p>";

        if ($f1 === false && pledge_is_successful($data['pledge_id']))
            /* Has this completed the pledge? */
            print "</div><p><strong>Your signature has made this pledge reach its target! Woohoo!</strong></p>";
        else {
            /* Otherwise advertise flyers. */
            print "</div>";
            advertise_flyers($data['pledge_id']);
        }
        db_commit();
    } else {
        if (pledge_is_permanent_error($r)) {
            db_rollback();  /* just in case -- shouldn't matter though */
            pledge_token_destroy('signup-web', $q_token);
            db_commit();
        }
        oops($r);
    }
}
page_footer(array('nonav'=>true));

/* advertise_flyers PLEDGE
 * Print some stuff advertising flyers for PLEDGE. */
function advertise_flyers($pledge_id) {
    $r = db_getRow('select * from pledges where id = ?', $pledge_id);
?>

<div class="noprint">
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

</div> <!-- noprint -->

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
    
    for ($rows = 0; $rows<3; $rows++) {
        print '<tr align="center">';
        for ($cols=0; $cols<2; $cols++) {
            print '<td>';
            print get_flyer_text($r, array());
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
