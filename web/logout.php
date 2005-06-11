<?php
/*
 * logout.php:
 * Log user out.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: logout.php,v 1.3 2005-06-11 19:01:14 matthew Exp $
 * 
 */

require_once '../phplib/pb.php';

require_once '../phplib/page.php';
require_once '../phplib/person.php';

if ($signed_on_person) {
    person_signoff();
    header("Location: /logout");
    exit;
}

page_header('Logged out');

?>
<p>You're now logged out.  Thanks for using PledgeBank!</p>
<?

page_footer();

?>
