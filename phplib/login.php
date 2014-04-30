<?php

/* Used if email address is changed on login screen to update POST stash. 
   It finds where the email is in the POST, and replaces it with the new email.
   Called from person.php via passed in function pointer */
function pb_stash_email_replacer($params, $old_email, $new_email) {
    // This for most stuff
    if (array_key_exists('email', $params) && $params['email'] == $old_email) {
        $params['email'] = $new_email;
    }
    // This for comments
    if (array_key_exists('author_email', $params) && $params['author_email'] == $old_email) {
        $params['author_email'] = $new_email;
    }
    // This for new pledges
    if (array_key_exists('data', $params)) {
        $inner_data = unserialize(base64_decode($params['data']));
        if (array_key_exists('email', $inner_data) && $inner_data['email'] == $old_email) {
            $inner_data['email'] = $new_email;
        }
        $inner_data = base64_encode(serialize($inner_data));
        $params['data'] = $inner_data;
    }

    return $params;
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
            stash_redirect($q_stash, $q_email, "pb_stash_email_replacer");
            /* NOTREACHED */
        }
    } elseif (get_http_var("loginradio") == 'SendEmail' ||
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
        $tmp = stash_get_extra($q_stash);
        $template_data = rabx_unserialise($tmp);
        $template_data['url'] = $url;
        $template_data['user_name'] = $q_name;
        if (is_null($template_data['user_name']))
            $template_data['user_name'] = 'Pledge signer';
        $template_data['user_email'] = $q_email;
        pb_send_email_template($q_name ? array(array($q_email, $q_name)) : $q_email,
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

    $tmp = stash_get_extra($q_stash);
    $template_data = rabx_unserialise($tmp);
    $reason = htmlspecialchars($template_data['reason_web']);

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', array_values($errors));
        print '</li></ul></div>';
    }

    /* Split into two forms to avoid "do you want to remember this
     * password" prompt in, e.g., Mozilla. */
?>

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
<small><?=_("(we'll send an email, click the link in it to confirm your email is working.<br>if you like, you can then set a password on My Pledges page)") ?></small>
<br>
</p>

<p><input type="submit" name="loginsubmit" value="<?=_('Continue')?>">
</p>

</div>

</form>
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
