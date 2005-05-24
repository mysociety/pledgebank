<?php
/*
 * confirm.php:
 * Confirm a user's subscription to a pledge, or creation of a pledge.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: confirm.php,v 1.36 2005-05-24 11:52:14 francis Exp $
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
    # TODO: Remove all this now pledge confirmation happens in new system
    /* Pledges are confirmed by saving a token in the database and sending it to
     * the user. */
    $pledge_id = pledge_confirm($q_token);
    if (pledge_is_error($pledge_id)) {
        err("That pledge hasn't been recognised.  Please check the URL is copied correctly from your email.");
    }

    /* Success. */
    $q = db_query('select * from pledges where id = ?', $pledge_id);
    $r = db_fetch_array($q);
    $local_pledge = pledge_is_local($row);

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
<?  post_confirm_advertise($r);
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
    $local_pledge = pledge_is_local($row);
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
            /* Otherwise post_confirm_advertise */
            post_confirm_advertise($row);
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
            post_confirm_advertise($row);
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
