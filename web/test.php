<?
// test.php:
// Part of test harness.  See ../test/run.pl for where this is called.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francsi@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: test.php,v 1.1 2005-02-24 12:18:02 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/db.php';
require_once '../phplib/fns.php';

print page_header("Test harness");

if (get_http_var('error')) {
    // Deliberately cause error by looking something up in an array which is not
    // there.
    $some_array = array();
    $some_variable = $some_array['hello'];
}

print "<p>Just testing</p>";
print page_footer();

