<?
// test.php:
// Part of test harness.  See ../test/run.pl for where this is called.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francsi@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: test.php,v 1.2 2005-03-14 14:32:04 francis Exp $

require_once "../phplib/pb.php";
require_once '../phplib/db.php';
require_once '../phplib/fns.php';

if (get_http_var('error')) {
    // Deliberately cause error by looking something up in an array which is not
    // there.
    $some_array = array();
    $some_variable = $some_array['hello'];
}

phpinfo();

