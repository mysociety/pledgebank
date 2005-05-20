<?php
/*
 * confirm.php:
 * Confirm a user's subscription to a pledge, or creation of a pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: confirm.php,v 1.35 2005-05-20 13:37:13 matthew Exp $
 * 
 */

require_once "../phplib/db.php";
require_once "../phplib/pb.php";
require_once "../phplib/fns.php";
require_once "../phplib/pledge.php";
require_once "../phplib/auth.php";

require_once "../../phplib/importparams.php";

$err = importparams(
            array('token',      '/.+/',             ""),
            array('type',      '/(pledge|signature)/',             "")
        );

if (!is_null($err))
    err("Sorry -- something seems to have gone wrong");

$local_pledge = false;

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
    $local_pledge = ($r['country'] == 'UK' && $r['postcode']) ? true : false;

    if ($local_pledge) {
        page_header("PRINT THIS - CUT IT UP - DELIVER LOCALLY", array('nonav' => true, 'ref'=>'/'.$r['ref']));
    } else {
        page_header('Pledge Confirmation', array('ref'=>'/'.$r['ref']) );
    }
    db_commit();
    $url = htmlspecialchars(OPTION_BASE_URL . "/" . urlencode($r['ref']));
?>
    <p class="noprint" align="center"><strong>Thank you for confirming your pledge.</strong></p>
    <p class="noprint" align="center">It is now live at <strong><a href="<?=$url?>"><?=$url?></a></strong> and people can sign up to it there.</p>
<?  advertise($local_pledge, $r);
} elseif ($q_type == 'signature') {
    /* OK, that wasn't a pledge confirmation token. So we must be signing a
     * pledge. */
    $data = auth_token_retrieve('signup-web', $q_token);
    if (!$data) {
        err("Your signature hasn't been recognised.  Please check the URL is copied correctly from your email.");
    }
    # Note that we do NOT delete the token, so it doesn't give an error if they
    # double confirm.
    # auth_token_destroy('signup-web', $q_token);

    $row = db_getRow('select * from pledges where id = ?',
                        $data['pledge_id']);
    $local_pledge = ($row['country'] == 'UK' && $row['postcode']) ? true : false;
    if ($local_pledge) {                        
        page_header("Sign up to '$row[title]'", array('nonav' => true, 'ref'=>'/'.$row['ref']));
    } else {
        page_header("Sign up to '$row[title]'", array('ref'=>'/'.$row['ref']) );
    }

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
        print '<p class="noprint" align="center">Thanks for signing up to this pledge!</p>';

        if ($f1 === false && pledge_is_successful($data['pledge_id']))
            /* Has this completed the pledge? */
            print "<p><strong>Your signature has made this pledge reach its target! Woohoo!</strong></p>";
        else {
            /* Otherwise advertise */
            advertise($local_pledge, $row);
        }
        db_commit();
    } else {
        if (pledge_is_permanent_error($r)) {
            db_rollback();  /* just in case -- shouldn't matter though */
            # Note that we do NOT delete the token, so they can get the error
            # again.
            # auth_token_destroy('signup-web', $q_token);
            db_commit();
        }
        if ($r == PLEDGE_SIGNED) {
            print "<p align=\"center\">You've already signed up to this pledge, there's no need
                    to sign it again.</p>";
            advertise($local_pledge, $row);
        } else {
            oops($r);
        }
    }
}
if ($local_pledge) {
    page_footer(array('nonav'=>true));
} else {
    page_footer();
}

/* advertise LOCAL PLEDGE_ROW
   Print relevant advertising */
function advertise($local, $r) {
    if ($local) {
        advertise_flyers($r);
    } else {
        advertise_sms($r);
        $p = new Pledge($r);
        view_friends_form($p);
    }
}

/* advertise_flyers PLEDGE_ROW
 * Print some stuff advertising flyers for PLEDGE. */
function advertise_flyers($r) {
    $png_flyers8_url = new_url("/flyers/{$r['ref']}_A4_flyers8.png", false);

    ?>
<p class="noprint" align="center"><big><strong>
You will massively increase the chance of this pledge succeeding if you
<?
    if (!$r['password']) {
        print_this_link("print this page out", "");
        ?>
    (or use <a href="/<?=htmlspecialchars($r['ref']) ?>/flyers">these more attractive PDF and RTF (Word) versions</a>),
<?
   } else {
        // TODO - we don't have the password raw here, but really want it on
        // form to pass on for link to flyers page.  Not sure how best to fix
        // this up.
        print_link_with_password("/".htmlspecialchars($r['ref'])."/flyers", "", "print these pages out");
        print ",";
   }
?>
 cut up the flyers and stick them through your neighbours' letterboxes. We
cannot emphasise this enough &mdash; print them NOW and post them next time you
go out to the shops or your pledge is unlikely to succeed.</strong></big>
</p>
<? 
    // Show inline graphics only for passwordless pledges (as PNG doesn't
    // work for the password protected ones, you can't POST a password
    // into an IMG SRC= link)
    if (!$r['password']) { ?>
<p align="center"><a href="<?=$png_flyers8_url?>"><img width="595" height="842" src="<?=$png_flyers8_url?>" border="0" alt="Graphic of flyers for printing"></a></p>
<?  }
    advertise_sms($r);
}

/* advertise_sms PLEDGE_ROW
 * Prints some stuff about SMS for PLEDGE.
 * Only for passwordless pledges, since private pledges can't be signed by SMS. */
function advertise_sms($r) {
    if (!$r['password']) {
?>
<p class="noprint"><strong>Take your Pledge to the pub</strong> &ndash; next time you're out and about,
get your friends to sign up by having them text
<strong>pledge&nbsp;<?=htmlspecialchars($r['ref'])?></strong> to <strong>60022</strong>.
The text costs your normal rate, and we'll keep them updated about progress via their
mobile.</p>

<? # Below not needed as we're currently not charging premium rate
/* <p class="noprint" style="text-align: center;"><small>The small print: operated by
mySociety, a project of UK Citizens Online Democracy. Sign-up message costs
your normal text rate. Further messages from us are free.
Questions about this SMS service? Call us on 08453&nbsp;330&nbsp;160 or
email <a href="mailto:team@pledgebank.com">team@pledgebank.com</a>.</small></p> */
?>
<?   }
}

/* oops CODE
 * Print a message explaining CODE. */
function oops($r) {
    global $q_f;
    page_header("Sorry, we couldn't sign you up to that pledge");
    if ($r == PLEDGE_FULL || $r == PLEDGE_FINISHED) {
        /* Print a fuller explanation in this (common) case */
        print "<p><strong>Sorry, we couldn't sign you up to that pledge:</strong></p>";
        $what = $q_f ? 'filling in the form' : 'waiting for our email to arrive'; /* XXX l18n */
        $how = ($r == PLEDGE_FULL ?
                    "somebody else beat you to the last place on that pledge"
                    : "the pledge finished");
        print <<<EOF
<p>Unfortunately, while you were $what, $how.
We're very sorry &mdash; better luck next time!</p>
EOF;
    } else {
        print "<p><strong>Sorry, we couldn't sign you up.</strong></p>";
        print "<p>" . htmlspecialchars(pledge_strerror($r)) . "</p>";
        if (!pledge_is_permanent_error($r))
            print "<p><strong>Please try again a bit later.</strong></p>";
    }
    page_footer();
}

?>
