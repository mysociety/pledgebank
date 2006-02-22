<?
// test.php:
// Part of test harness.  See ../bin/test-run.pl for where this is called.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francsi@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: test.php,v 1.12 2006-02-22 17:22:25 francis Exp $

require_once "../phplib/pb.php";

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

if (get_http_var('general')) {
    print "hello";
}
