<?php
/*
 * ref-creator.php:
 * Various tools for the pledge creator.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: logout.php,v 1.1 2005-05-26 18:19:11 francis Exp $
 * 
 */

require_once '../phplib/pb.php';

require_once '../phplib/page.php';
require_once '../phplib/person.php';

$P = person_if_signed_on();
if ($P) {
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
