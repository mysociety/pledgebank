<?php
/*
 * ref-creator.php:
 * Various tools for the pledge creator.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-creator.php,v 1.4 2005-05-31 15:43:58 francis Exp $
 * 
 */

require_once '../phplib/pb.php';

require_once '../phplib/page.php';
require_once '../phplib/person.php';
require_once '../phplib/pledge.php';

require_once '../../phplib/importparams.php';

$err = importparams(
            array('ref',   '/./',   '')
        );
if (!is_null($err))
    err("Missing pledge reference");

$p = new Pledge($q_ref);

$P = person_if_signed_on();
if (!$P || $P->id() != $p->creator_id()) {
    $errs = importparams(
                array('email',  '/^[^@]+@[^@]+$/',  ''),
                array('LogIn',  '/./',              '', false)
            );

    if (!is_null($errs) || !$q_LogIn) {
        page_header('Pledge Creator');
    if ($P) {
?>
    <p>
    The email address you are logged in with does not seem to be the same as
    the pledge creator. Please enter that email address to log in as them.
    </p>
<?
    }  else {
        print <<<EOF
<p>To access the pledge creator's page, please type in your email address
and click "Continue".  You can only access the page if you created the pledge.
</p>
EOF;
    }
        print <<<EOF
<form class="pledge" name="logIn" method="POST">
<div class="form_row">
    <label for="email"><strong>Email address</strong></label>
    <input type="text" size="20" name="email" id="email" value="$q_h_email">
    <input type="submit" name="LogIn" value="Continue &gt;&gt;">
</div>
</form>
EOF;
        /* XXX needs "send me a reminder" email */
        page_footer(array('nonav' => 1));
        exit();
    } else {
        $P = person_signon(array(
                        'reason' => "access the pledge creator's page",
                        'template' => 'creator-confirm'
                    ), $q_email);
    }
}

header("Location: /$q_ref/announce");

?>
