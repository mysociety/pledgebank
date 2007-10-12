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
 * $Id: login.php,v 1.87 2007-10-12 13:12:48 matthew Exp $
 * 
 */

require_once '../phplib/pb.php';

require_once '../../phplib/auth.php';
require_once '../phplib/fns.php';
require_once '../phplib/page.php';
require_once '../phplib/pbperson.php';
require_once '../phplib/login.php';
require_once '../../phplib/stash.php';
require_once '../../phplib/rabx.php';

require_once '../../phplib/importparams.php';

/* As a first step try to set a cookie and read it on redirect, so that we can
 * warn the user explicitly if they appear to be refusing cookies. */
if (!array_key_exists('pb_test_cookie', $_COOKIE)) {
    if (array_key_exists('pb_test_cookie', $_GET)) {
        page_header(_("Please enable cookies"));
        print p(_('It appears that you don\'t have "cookies" enabled in your browser.
<strong>To continue, you must enable cookies</strong>. Please
read <a href="http://www.google.com/cookies.html">this page from Google
explaining how to do that</a>, and then click the "back" button and
try again'));
        page_footer();
        exit();
    } else {
        setcookie('pb_test_cookie', '1', null, '/', person_cookie_domain(), false);
        header("Location: /login.php?" . $_SERVER['QUERY_STRING'] . "&pb_test_cookie=1\n");
        exit();
    }
}

/* Get all the parameters which we might use. */
importparams(
        array('stash',          '/^[0-9a-f]+$/',    '', null),
        array('email',          '/./',              '', null),
        array(array('name',true),           '//',               '', null),
        array('password',       '/[^\s]/',          '', null),
        array('t',              '/^.+$/',           '', null),
        array('rememberme',     '/./',              '', false)
    );
if ($q_name==_('<Enter your name>')) {
    $q_name=null;
}

/* General purpose login, asks for email also. */
if (get_http_var("now")) {
    $P = pb_person_signon(array(
                    'reason_web' => _("To log into PledgeBank, we need to check your email address."),
                    'reason_email' => _("Then you will be logged into PledgeBank, and can set or change your password."),
                    'reason_email_subject' => _('Log into PledgeBank.com')

                ));

    header("Location: /my");
    exit;
}

/* Do token case first because if the user isn't logged in *and* has a token
 * (unlikely but possible) the other branch would fail for lack of a stash
 * parameter. */
if (!is_null($q_t)) {
    $q_t = preg_replace('#</a$#', '', $q_t);
    /* Process emailed token */
    $d = auth_token_retrieve('login', $q_t);
    if (!$d)
        err(sprintf(_("Please check the URL (i.e. the long code of letters and numbers) is copied correctly from your email.  If you can't click on it in the email, you'll have to select and copy it from the email.  Then paste it into your browser, into the place you would type the address of any other webpage. Technical details: The token '%s' wasn't found."), $q_t));
    $P = person_get($d['email']);
    if (is_null($P)) {
        $P = person_get_or_create($d['email'], $d['name']);
    }

    $P->inc_numlogins();
    
    db_commit();

    /* Now give the user their cookie. */
    set_login_cookie($P);

    /* Recover "parameters" from token. */
    $q_h_email = htmlspecialchars($q_email = $d['email']);
    if (array_key_exists('name', $d) && !is_null($d['name'])) {
        $q_h_name = htmlspecialchars($q_name = $d['name']);
    } else {
        $q_h_name = $q_name = null;
    }
    $q_h_stash = htmlspecialchars($q_stash = $d['stash']);

    /* Set name if it has changed */
    if ($q_name && !$P->matches_name($q_name))
        $P->name($q_name);

    stash_redirect($q_stash, $q_email, "pb_stash_email_replacer");
    /* NOTREACHED */
}

$P = pb_person_if_signed_on();
if (!is_null($P)) {
    /* Person is already signed in. */
    if ($q_name && !$P->matches_name($q_name))
        /* ... but they have specified a name which differs from their recorded
         * name. Change it. */
        $P->name($q_name);
    if (!is_null($q_stash)) {
        /* No name change, just pass them through to the page they actually
         * wanted. */
        stash_redirect($q_stash, $q_email, "pb_stash_email_replacer");
    } else {
        /* This happens if you are logged in and type (or go in browser history)
         * to /login. May as well redirect to login as a new person. */
        header("Location: /login?now=1");
    }
} elseif (is_null($q_stash)) {
    header("Location: /login?now=1");
} else {
    /* Main login page. */
    login_page();
}

