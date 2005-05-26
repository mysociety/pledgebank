<?php
/*
 * ref-creator.php:
 * Various tools for the pledge creator.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: ref-creator.php,v 1.2 2005-05-26 16:58:36 chris Exp $
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
        print <<<EOF
        $q_email $q_LogIn
<p>To access this page, please type in your email address and click "Continue".</p>
<form class="pledge" method="POST">
<div class="form_row">
    <label for="email">Email address</label>
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
