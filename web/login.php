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
 * $Id: login.php,v 1.8 2005-05-23 16:48:07 francis Exp $
 * 
 */

require_once '../phplib/pb.php';

require_once '../phplib/auth.php';
require_once '../phplib/fns.php';
require_once '../phplib/page.php';
require_once '../phplib/person.php';
require_once '../phplib/stash.php';

require_once '../../phplib/importparams.php';

/* As a first step try to set a cookie and read it on redirect, so that we can
 * warn the user explicitly if they appear to be refusing cookies. */
if (!array_key_exists('pb_test_cookie', $_COOKIE)) {
    if (array_key_exists('pb_test_cookie', $_GET)) {
        page_header("Please enable cookies");
        ?>
<p>It appears that you don't have "cookies" enabled in your browser.
<strong>To continue, you must enable cookies</strong>. Please
read <a href="http://www.google.com/cookies.html">this page from Google
explaining how to do that</a>, and then click the "back" button and
try again</p>
<?
        page_footer(array('nonav' => 1));
        exit();
    } else {
        setcookie('pb_test_cookie', '1');
        header("Location: /login.php?" . $_SERVER['QUERY_STRING'] . "&pb_test_cookie=1\n");
        exit();
    }
}

/* Get all the parameters which we might use. */
importparams(
        array('stash',          '/^[0-9a-f]+$/',    '', null),
        array('email',          '/^[^@]+@[^@]+$/',  '', null),
        array('name',           '//',               '', null),
        array('password',       '/[^\s]/',          '', null),
        array('t',              '/^.+$/',           '', null),

        /* Buttons on login page. */
        array('LogIn',          '/^.+$/',           '', false),
        array('SendEmail',      '/^.+$/',           '', false),

        array('SetPassword',    '/^.+$/',           '', false),
        array('NoPassword',     '/^.+$/',           '', false),

        /* Buttons on name change page */
        array('KeepName',       '/^.+$/',            '', false),
        array('ChangeName',     '/^.+$/',            '', false)
    );

/* Do token case first because if the user isn't logged in *and* has a token
 * (unlikely but possible) the other branch would fail for lack of a stash
 * parameter. */
if (!is_null($q_t)) {
    /* Process emailed token */
    $d = auth_token_retrieve('login', $q_t);
    if (is_null($d))
        /* Bad token -- should do a friendlier message */
        err('A required parameter was missing');
    $P = person_get($d['email']);
    if (is_null($P)) {
        if (is_null($d['name']))
            err('A required parameter was missing');
        else
            $P = person_get_or_create($d['email'], $d['name']);
    }
    db_commit();

    /* Now give the user their cookie. */
    set_login_cookie($P);

    /* Recover "parameters" from token. */
    auth_token_destroy('login', $q_t);
    $q_h_email = htmlspecialchars($q_email = $d['email']);
    $q_h_name = htmlspecialchars($q_name = $d['name']);
    $q_h_stash = htmlspecialchars($q_stash = $d['stash']);

    /* See whether this user has used pledgebank before. If they have, offer to
     * set or reset their password. */
/*    
    if (db_getOne('select count(id) from pledges where creator_person_id = ?', $P->id()) > 0
        || db_getOne('select count(id) from signers where signer_person_id = ?', $P->id()) > 0)
*/
    if (true)
        change_password_page($P);
    else if (!$P->matches_name($q_name))
        $P->name($q_name);
    stash_redirect($q_stash);
        /* NOTREACHED */
}

$P = person_if_signed_on();
if (!is_null($P)) {
    /* Person is already signed in. */
    if ($q_SetPassword)
        change_password_page($P);
    if (!is_null($q_name) && !$P->matches_name($q_name))
        /* ... but they have specified a name which differs from their recorded
         * name. Change it. */
        $P->name($q_name);
    if (!is_null($q_stash))
        /* No name change, just pass them through to the page they actually
         * wanted. */
        stash_redirect($q_stash);
    else
        err('A required parameter was missing');
} else if (!is_null($q_stash) && !is_null($q_email) && !is_null($q_name))
    /* Main login page. */
    login_page();

/* login_page
 * Render the login page, or respond to a button pressed on it. */
function login_page() {
    global $q_stash, $q_email, $q_name, $q_LogIn, $q_SendEmail;
    if (is_null($q_stash) || is_null($q_email) || is_null($q_name))
        err('A required parameter was missing');

    if ($q_LogIn) {
        /* User has tried to log in. */
        global $q_password;
        $P = person_get($q_email);
        if (is_null($P) || !$P->check_password($q_password)) {
            login_form(array('badpassword'=>'Either your email or password weren\'t recognised.  Please try again.'));
            exit();
        } else {
            /* User has logged in correctly. Decide whether they are changing
             * their name. */
            set_login_cookie($P);
            if (!$P->matches_name($q_name))
                $P->name($q_name);
            stash_redirect($q_stash);
                /* NOTREACHED */
        }
    } else if ($q_SendEmail) {
        /* User has asked to be sent email. */
        $token = auth_token_store('login', array(
                        'email' => $q_email,
                        'name' => $q_name,
                        'stash' => $q_stash
                    ));
        db_commit();
        $url = OPTION_BASE_URL . "/login.php?t=$token";
        pb_send_email_template($q_email, 'generic-confirm', array('reason' => stash_get_extra($q_stash), 'url' => $url));
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
            /* NOTREACHED */
    } else {
        login_form();
        exit();
    }
}

/* login_form */
function login_form($errors = array()) {
    /* Just render the form. */
    global $q_h_stash, $q_h_email, $q_h_name, $q_stash, $q_email, $q_name;

    page_header('Checking your email address');

    if (is_null($q_name))
        $q_name = $q_h_name = '';   /* shouldn't happen */

    $reason = htmlspecialchars(stash_get_extra($q_stash));

	if (sizeof($errors)) {
		print '<div id="errors"><ul><li>';
		print join ('</li><li>', array_values($errors));
		print '</li></ul></div>';
	}  else {
        print "<p>Before we can $reason, we need to confirm your name and email address.</p>";
    }

    /* Split into two forms to avoid "do you want to remember this
     * password" prompt in, e.g., Mozilla. */
    print <<<EOF

<div class="pledge">

<p><strong>Do you have a PledgeBank password?</strong></p>

<ul>

<form class="login" method="POST" accept-charset="utf-8">
<input type="hidden" name="stash" value="$q_h_stash">
<input type="hidden" name="name" id="name" value="$q_h_name">
<input type="hidden" name="email" id="email" value="$q_h_email">

<li>No, I haven't used PledgeBank before.

<input type="submit" name="SendEmail" value="Click here to continue"><br>
<small>(we'll send an email to confirm your address)</small></p>

</li>
</form>

<form class="login" method="POST" accept-charset="utf-8">
<li><p>Yes, I have a password:

<input type="hidden" name="stash" value="$q_h_stash">
<input type="hidden" name="email" value="$q_h_email">
<input type="hidden" name="name" value="$q_h_name">

<input type="password" name="password" id="password" value="">
<input type="submit" name="LogIn" value="Let me in"></p>

<input type="checkbox" name="rememberme" id="rememberme" value="1"><strong>Remember me</strong></input>
<small>(don't use this on a public or shared computer)</small>

</li>
</form>

</ul>

</div>
EOF;

    page_footer(array('nonav' => 1));
}

/* change_password_page PERSON
 * Show the logged-in PERSON a form to allow them to set or reset a password
 * for their account. */
function change_password_page($P) {
    global $q_stash, $q_email, $q_name, $q_SetPassword, $q_NoPassword;
    global $q_h_stash, $q_h_email, $q_h_name;

    $error = null;
    if ($q_SetPassword) {
        global $q_pw1, $q_pw2;
        importparams(
                array('pw1',        '/[^\s]{5,}/',      '', null),
                array('pw2',        '/[^\s]{5,}/',      '', null)
            );
        if (is_null($q_pw1) || is_null($q_pw2))
            $error = "Please type your new password twice";
        else if ($q_pw1 != $q_pw2)
            $error = "Please type the same password twice";
        else {
            $P->password($q_pw1);
            db_commit();
            return;
        }
    } else if ($q_NoPassword)
        return;

    if ($P->has_password()) {
        page_header('Change your password');
        print <<<EOF
<p>There is a password set for your email address on PledgeBank. Perhaps
you've forgotten it? You can set a new password using this form:</p>
EOF;
    } else {
        page_header('Set a password');
        print <<<EOF
<p>We note that you've used PledgeBank in the past. On this page you can set
a password which you can use to identify yourself to PledgeBank, so that you
don't have to check your email in the future.</p>
EOF;
    }

    if (!is_null($error))
        print "<p><strong>$error</strong></p>";

    print <<<EOF
<div class="pledge">

<form class="login" method="POST" accept-charset="utf-8">
<input type="hidden" name="stash" value="$q_h_stash">
<input type="hidden" name="email" value="$q_h_email">
<input type="hidden" name="name" value="$q_h_name">

<div class="form_row">
    <label for="pw1">Type your password</label>
    <input type="password" name="pw1" id="pw1" size="20">
</div>

<div class="form_row">
    <label for="pw2">(again)</label>
    <input type="password" name="pw2" size="20">
</div>

<input type="submit" name="SetPassword" value="Set password">

<p>Alternatively<br>
<input type="submit" name="NoPassword" value="Continue without setting a password"><br>
<small>(you can set a password later if you want)</small></p>
</form>

</div>
EOF;

    page_footer(array('nonav' => 1));
    exit();
}

/* set_login_cookie PERSON [EXPIRES]
 * Set a login cookie for the given PERSON. If set, EXPIRES is the time which
 * will be set for the cookie to expire; otherwise, a session cookie is set. */
function set_login_cookie($P, $expires = null) {
    setcookie('pb_person_id', person_cookie_token($P->id()), $expires, '/', OPTION_WEB_DOMAIN, false);
}

?>
