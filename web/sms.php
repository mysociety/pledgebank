<?php
/*
 * sms.php:
 * Convert SMS to email subscription, when user supplies a token.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: sms.php,v 1.1 2005-03-04 12:45:11 chris Exp $
 * 
 */

require_once "../phplib/pb.php";
require_once "../phplib/db.php";

require_once "../../phplib/importparams.php";

$errs = importparams(
            array('token',  '/^[0-9a-f]{4}-[0-9a-f]{4}$/',  "The code you've entered isn't valid"),
            array('phone',  '/[\d\s-+]$/',                  "Please enter your phone number"),
            array('name',   '/[a-z]+/i',                    "Please enter your name"),
            array('email',  '/^[^@]+@[^@]+$/',              "Please enter your email address"),
            array('showname',
                            '/^1$/',                        "", 0),
            array('f',      '/^1$/',                        "", 0)
        );
            
page_header('SMS');


if (is_null($q_token)) {
    print " <p>
        Sorry, we can't make sense of the code '$q_unchecked_h_token'. Please
        could you re-check the URL you typed in; the last part of it should
        consist of two groups of four letters and numbers, joined by a hyphen
        ('-')
            </p>";
} else {
    if (is_null($errs)) {
        /* Check that we can actually sign up. */
        $p = preg_replace("/[^\d]/", '', $q_phone);
        if ($ss = db_getRow('select id, mobile from signers where token = ?', $q_token)
            /* Compare last few characters of the phone numbers, so that we
             * avoid having to know anything about their format. */
            && substr($p, -6) == substr($ss['mobile'], -6)) {
            db_query('update signers set name = ?, email = ?, showname = ? where id = ?', $q_name, $q_email, $q_showname, $ss['id']);
        }
        
    }

    /* Form to supply info for the subscription */
    print <<<EOF
<h2>Thanks for signing up!</h2>

<p>On this page you can let us have your name and email address so that you can
get email from the pledge creator and, if you want, discuss the pledge with
other signers. If you give us your email address we can also email you when the
pledge succeeds, rather than sending an SMS.</p>

<form class="pledge" method="post">
<input type="hidden" name="f" value="1">
<input type="hidden" name="token" value="$q_h_token">
<p>
Phone number: <input type="text" name="phone" value="$q_unchecked_h_phone"><br/>
EOF;
    if ($q_f && is_null($q_phone))
        print "<em>Please enter a valid phone number!</em><br/>";
    print <<<EOF
Email: <input type="text" name="email" value="$q_unchecked_h_email"><br/>
EOF;
    if ($q_f && is_null($q_email))
        print "<em>Please enter a valid email address!</em><br/>";
    print <<<EOF
Name: <input type="text" name="name" value="$q_unchecked_h_name"><br/>
EOF;
    if ($q_f && is_null($q_name))
        print "<em>Please enter your name!</em><br/>";
    print <<<EOF
Show my name on this pledge: <input name="showname" value="1" checked="checked" type="checkbox"><br/>
<input type="submit" name="submit" value="Submit">
EOF;
}

?>
