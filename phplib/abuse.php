<?
// abuse.php
// Abuse checking code for PledgeBank
//
// Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: abuse.php,v 1.2 2007-10-24 23:03:27 matthew Exp $

require_once '../../phplib/utility.php';
require_once '../../phplib/ratty.php';

function abuse_test($vars) {
    if (array_key_exists('message', $vars)) {
        $text_click = ms_make_clickable($vars['message'][0]);
        $text_no_links = preg_replace('#<a.*?</a>#s', '', $text_click);
        $links = substr_count($text_click, '<a');
        $weblinks = substr_count($vars['message'][0], '[url=');
        $percent_no_links = strlen($text_no_links) / strlen($vars['message'][0]);
        $vars['links'] = array($links, 'Number of web links');
        $vars['forum_links'] = array($weblinks, 'Number of forum web links');
        $vars['percent_no_links'] = array($percent_no_links, "Percentage of text that isn't links");
    }
    $vars['IPADDR'] = array($_SERVER['REMOTE_ADDR'], "IP address");
    $vars['SERVER'] = array($_SERVER['SERVER_NAME'], "Web server");
    $vars['PAGE'] = array($_SERVER['SCRIPT_NAME'], "Web page");

    # If test suite, bypass so we can not be rate limited.
    # But not staging, as they can get spam...
    if (OPTION_WEB_HOST == 'testharness')
        return false;

    $result = ratty_test('pb-abuse', $vars);
    if ($result)
        return true;

    return false;
}
