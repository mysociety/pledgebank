<?php
/*
 * sms.php:
 * Convert SMS to email subscription, when user supplies a token.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: sms.php,v 1.3 2005-03-09 18:10:21 francis Exp $
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
    print <<<EOF
            <p>
        Sorry, we can't make sense of the code '$q_h_unchecked_token'. Please
        could you re-check the address you typed in; the last part of it should
        be two groups of four letters and numbers, joined by a hyphen ("-"),
        something like "1234-abcd"
            </p>'
EOF;
} else if (is_null($errs)) {
    /* Check that we can actually sign up. */
    $p = preg_replace("/[^\d]/", '', $q_phone);
    $ss = db_getRow('select id, mobile, pledge_id from signers where token = ?', $q_token);
    
    /* Compare last few characters of the phone numbers, so that we avoid
     * having to know anything about their format. */
    if ($ss && substr($p, -6) == substr($ss['mobile'], -6)) {
        /* Token and phone number match. Now see whether that email address
         * has been registered in the database before. That determines whether
         * we send a confirmation or a confirmation reminder mail. */
         
        $existingid = db_getOne('
                            select id
                            from signers
                            where email = ? and pledge_id = ? for update
                        ', $q_email, $ss['pledge_id']);
                        

    } else {
        $errs['phone'] = "That phone number doesn't match our records";
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
    if ($q_f && array_key_exists('phone', $errs))
        print "<em>" . htmlspecialchars($errs['phone']) . "</em><br/>";
    print <<<EOF
Email: <input type="text" name="email" value="$q_unchecked_h_email"><br/>
EOF;
    if ($q_f && array_key_exists('email', $errs))
        print "<em>" . htmlspecialchars($errs['email']) . "</em><br/>";
    print <<<EOF
Name: <input type="text" name="name" value="$q_unchecked_h_name"><br/>
EOF;
    if ($q_f && array_key_exists('name', $errs))
        print "<em>" . htmlspecialchars($errs['name']) . "</em><br/>";
    print <<<EOF
Show my name on this pledge: <input name="showname" value="1" checked="checked" type="checkbox"><br/>
<input type="submit" name="submit" value="Submit">
EOF;

?>
