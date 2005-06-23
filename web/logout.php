<?php
/*
 * logout.php:
 * Log user out.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: logout.php,v 1.6 2005-06-23 23:32:32 matthew Exp $
 * 
 */

require_once '../phplib/pb.php';

require_once '../phplib/page.php';
require_once '../phplib/person.php';

if (person_if_signed_on(true)) {
    person_signoff();
    header("Location: /logout");
    exit;
}

page_header(_('Logged out'));
print _('<p>You\'re now logged out.  Thanks for using PledgeBank!</p>');
page_footer();

?>
