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
 * $Id: login.php,v 1.81 2007-04-23 10:37:16 francis Exp $
 * 
 */

require_once '../phplib/pb.php';

require_once '../../phplib/auth.php';
require_once '../phplib/fns.php';
require_once '../phplib/page.php';
require_once '../phplib/pbperson.php';
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

    header("Location: /your");
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

    stash_redirect($q_stash);
    /* NOTREACHED */
}

$P = pb_person_if_signed_on();
if (!is_null($P)) {
    /* Person is already signed in. */
    if ($q_name && !$P->matches_name($q_name))
        /* ... but they have specified a name which differs from their recorded
         * name. Change it. */
        $P->name($q_name);
    if (!is_null($q_stash))
        /* No name change, just pass them through to the page they actually
         * wanted. */
        stash_redirect($q_stash);
    else
        /* This happens if you are logged in and type (or go in browser history)
         * to /login. May as well redirect to login as a new person. */
        header("Location: /login?now=1");
} elseif (is_null($q_stash)) {
    header("Location: /login?now=1");
} else {
    /* Main login page. */
    login_page();
}

/* login_page
 * Render the login page, or respond to a button pressed on it. */
function login_page() {
    global $q_stash, $q_email, $q_name, $q_rememberme;

    if (is_null($q_stash)) {
        err(_("Required parameter was missing"));
    }

    if (get_http_var("loginradio") == 'LogIn') {
        /* User has tried to log in. */
        if (is_null($q_email)) {
            login_form(array('email'=>_('Please enter your email address')));
            exit();
        }
        if (!validate_email($q_email)) {
            login_form(array('email'=>_('Please enter a valid email address')));
            exit();
        }
        global $q_password;
        $P = person_get($q_email);
        if (is_null($P) || !$P->check_password($q_password)) {
            login_form(array('badpass'=>_('Either your email or password weren\'t recognised.  Please try again.')));
            exit();
        } else {
            /* User has logged in correctly. Decide whether they are changing
             * their name. */
            set_login_cookie($P, $q_rememberme ? 28 * 24 * 3600 : null); // one month
            if ($q_name && !$P->matches_name($q_name))
                $P->name($q_name);
            $P->inc_numlogins();
            db_commit();
            stash_redirect($q_stash);
            /* NOTREACHED */
        }
    } else if (get_http_var("loginradio") == 'SendEmail' ||
            get_http_var("loginradio") == 'SendEmailForgotten') {
        /* User has asked to be sent email. */
        if (is_null($q_email)) {
            login_form(array('email'=>_('Please enter your email address')));
            exit();
        }
        if (!validate_email($q_email)) {
            login_form(array('email'=>_('Please enter a valid email address')));
            exit();
        }
        $token = auth_token_store('login', array(
                        'email' => $q_email,
                        'name' => $q_name,
                        'stash' => $q_stash
                    ));
        db_commit();
        $url = pb_domain_url(array("path" => "/L/$token"));
        $template_data = rabx_unserialise(stash_get_extra($q_stash));
        $template_data['url'] = $url;
        $template_data['user_name'] = $q_name;
        if (is_null($template_data['user_name']))
            $template_data['user_name'] = 'Pledge signer';
        $template_data['user_email'] = $q_email;
        pb_send_email_template($q_email, 
            array_key_exists('template', $template_data) 
                ?  $template_data['template'] : 'generic-confirm', 
            $template_data);
        page_header(_("Now check your email!"));
        /* XXX show message only for Hotmail users? Probably not worth it. */
    ?>
<p class="loudmessage">
<?=_('Now check your email!') ?><br>
<?=_("We've sent you an email, and you'll need to click the link in it before you can
continue") ?>
<p class="loudmessage">
<small><?=_('If you use web-based email or have
"junk mail" filters, you may wish to check your bulk/spam mail folders:
sometimes, our messages are marked that way.') ?></small>
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

/* login_form ERRORS
 * Print the login form. ERRORS is a list of errors encountered when the form
 * was processed. */
function login_form($errors = array()) {
    /* Just render the form. */
    global $q_h_stash, $q_h_email, $q_h_name, $q_stash, $q_email, $q_name, $q_rememberme;

    page_header(_('Checking Your Email Address'));

    if (is_null($q_name))
        $q_name = $q_h_name = '';   /* shouldn't happen */

    $template_data = rabx_unserialise(stash_get_extra($q_stash));
    $reason = htmlspecialchars($template_data['reason_web']);

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', array_values($errors));
        print '</li></ul></div>';
    }

    /* Split into two forms to avoid "do you want to remember this
     * password" prompt in, e.g., Mozilla. */
?>

<div class="pledge">
<form action="/login" name="login" class="login" method="POST" accept-charset="utf-8">
<input type="hidden" name="stash" value="<?=$q_h_stash?>">
<input type="hidden" name="name" id="name" value="<?=$q_h_name?>">

<p><strong><?=$reason?></strong></p>

<? if (is_null($q_email) || $errors) { ?>

<ul>

<li> <?=_('What is your email address?') ?> <input<? if (array_key_exists('email', $errors) || array_key_exists('badpass', $errors)) print ' class="error"' ?> type="text" size="30" name="email" id="email" value="<?=$q_h_email?>">

</ul>

<? } else { ?>

<input type="hidden" name="email" value="<?=$q_h_email?>">

<? } ?>

<p><strong><?=_('Have you used PledgeBank before?') ?></strong></p>

<div id="loginradio">

<p><input type="radio" name="loginradio" value="SendEmail" id="loginradio1" <?=get_http_var("loginradio") == '' || get_http_var('loginradio') == 'SendEmail' ? 'checked' : ''?>><label for="loginradio1"><?=strip_tags(_("I've never used PledgeBank before")) ?></label>
<br>
<small><?=_("(we'll send an email, click the link in it to confirm your email is working)") ?></small>

<p><input type="radio" name="loginradio" id="loginradio2" value="LogIn" <?=get_http_var("loginradio") == 'LogIn' ? 'checked' : ''?>><label for="loginradio2"><?=_('I have a PledgeBank <strong>password</strong>') ?>:</label>
<input type="password" name="password" id="password" value="" <? if (array_key_exists('badpass', $errors)) print ' class="error"' ?> onchange="check_login_password_radio()">
<br>
<label for="rememberme"><?=_('Remember me') ?></label>
<input type="checkbox" name="rememberme" id="rememberme" <?=$q_rememberme ? "checked" : ""?> onchange="check_login_password_radio()"><strong>
</strong>
<small>(<?=_("don't use this on a public or shared computer") ?>)</small>
</p>

<p>
<input type="radio" name="loginradio" value="SendEmailForgotten" id="loginradio3" <?=get_http_var("loginradio") == 'SendEmailForgotten' ? 'checked' : ''?>><label for="loginradio3"><?=_("I've forgotten or didn't set a password") ?></label>
<br>
<small><?=_("(we'll send an email, click the link in it to confirm your email is working.<br>if you like, you can then set a password on Your Pledges page)") ?></small>
<br>
</p>

<p><input type="submit" name="loginsubmit" value="<?=_('Continue')?>">
</p>

</div>

</form>
</div>
<?

    page_footer();
}

/* set_login_cookie PERSON [DURATION]
 * Set a login cookie for the given PERSON. If set, EXPIRES is the time which
 * will be set for the cookie to expire; otherwise, a session cookie is set. */
function set_login_cookie($P, $duration = null) {
    // error_log('set cookie');
    setcookie('pb_person_id', person_cookie_token($P->id(), $duration), is_null($duration) ? null : time() + $duration, '/', person_cookie_domain(), false);
}

?>
