<?php
/*
 * login.php:
 * Identification and authentication of users.
 * 
 * The important thing here is that we mustn't leak information about whether
 * a given email address has an account or not. That means that, until we have
 * either processed a password, or have had the user click through from an
 * email token, we can't give any indication of whether the user has an account
 * or not.
 * 
 * There are a number of pages here:
 * 
 *  login
 *      Shown when the user doesn't have a cookie and login is needed. Either
 *      solicit a password or allow the user to click a button to get sent an
 *      email with a token in it. Supplied with parameters: stash, the stash
 *      key for the request which should complete once the user has logged in;
 *      email, the user's email address; and optionally name, the user's real
 *      name.
 *
 *  login-error
 *      Shown when the user enters an incorrect password or an unknown email
 *      address on the login page.
 *
 *  create-password
 *      Shown when a user logs in by means of an emailed token and has already
 *      created or signed a pledge, or posted a comment, to ask them to give a
 *      password for future logins.
 *
 *  change-name
 *      Shown when a user logs in but their name is significantly different
 *      from the name shown on their account. Gives them the options of
 *      changing the name recorded, or continuing with the old name.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: login.php,v 1.1 2005-05-18 15:05:18 chris Exp $
 * 
 */

require_once '../phplib/pb.php';

require_once '../phplib/auth.php';
require_once '../phplib/fns.php';
require_once '../phplib/page.php';
require_once '../phplib/person.php';
require_once '../phplib/stash.php';
require_once '../../phplib/importparams.php';

function do_login_cookie($id, $stash) {
    setcookie('pb_person_id', person_cookie_token($id));
    stash_redirect($stash);
        /* NOTREACHED */
}

/* If we've been given an email token, then check it and log in as
 * specified. */
if ($token = get_http_var('t')) {
    $d = auth_token_retrieve('login', $token);
    if (is_null($d))
        err('A required parameter was missing');
    $P = person_get($d['email']);
    if (is_null($P)) {
        if (is_null($d['name']))
            err('A required parameter was missing');
        else
            $P = person_get_or_create($d['email'], $d['name']);
    }
    db_commit();
    do_login_cookie($P->id(), $d['stash']);
        /* XXX should offer the user the option of defining a password */
}

if (!is_null(importparams(
        array('email',      '/^[^@]+@[^@]+$/',  ''),
        array('name',       '//',               '', null),
        array('password',   '/[^\s]/',          '', null),
        array('stash',      '/^[0-9a-f]+$/',    '')
    )))
    err('A required parameter was missing');

if (!is_null($q_password)) {
    /* User is trying to log in with a password. */
    $P = person_get($q_email);
    if (!is_null($P) && $P->check_password($q_password))
        do_login_cookie($P->id(), $q_stash);
    else {
        page_header('Incorrect Password');
        ?>
<p><strong>Incorrect password</strong></p>
<?
        page_footer(array('nonav' => 1));
        exit();
    }
} else if (get_http_var('sendEmail')) {
    /* XXX check for valid name */
    $token = auth_token_store('login', array(
                        'email' => $q_email,
                        'name'  => $q_name,
                        'stash' => $q_stash
                    ));
    $reason = stash_get_extra($q_stash);
    $url = OPTION_BASE_URL . "/login.php?t=$token";
    pb_send_email($q_email, "Please confirm your address", <<<EOF

$reason

Please click on this link:

$url

EOF
                    , array());

    db_commit();

    page_header("Now check your email");

    ?>

<p style="font-size: 150%; font-weight: bold; text-align: center;">
Now check your email!<br>
We've sent you an email, and you'll need to click the link in it before you can
continue
</p>
<?

    page_footer(array('nonav' => 1));

    exit();
}

/* User has supplied neither an emailed token nor a password. */
page_header('Checking your email address');

if (is_null($q_name))
    $q_name = $q_h_name = '';

print <<<EOF
<form method="POST" accept-charset="utf-8">
<input type="hidden" name="stash" value="$q_h_stash">

<p>Name:
<input type="text" size="20" name="email" value="$q_h_name"></p>

<p>Email address:
<input type="text" size="20" name="email" value="$q_h_email"></p>
<input type="hidden" name="name" value="$q_h_name">
<p>If you've used PledgeBank before, and have a password, please type it
here:<br>
<input type="password" value=""><br>
<input type="submit" name="logIn" value="Let me in!"></p>

<p>Otherwise,<br>
<input type="submit" name="sendEmail" value="Click here to continue"><br>
<small>(we'll send an email to confirm your address)</small></p>
</form>
EOF;

page_footer(array('nonav' => 1));

?>
