<?
// test.php:
// Part of test harness.  See ../bin/test-run.pl for where this is called.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francsi@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: test.php,v 1.10 2005-07-08 11:32:59 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

if (get_http_var('error')) {
    // Deliberately cause error by looking something up in an array which is not
    // there.
    $some_array = array();
    $some_variable = $some_array['deliberate_error_to_test_error_handling'];
}

if (get_http_var('phpinfo')) {
    phpinfo();
}

if (get_http_var('smstest')) {
    print_r($_GET);
    print_r($_POST);
}
