#!/usr/bin/perl -w -I../../perllib
#
# run.pl:
# Test harness for PledgeBank.  Makes sure we haven't broken the code.
# 
# Requires:
# * ../general/conf file set up for PledgeBank, and matching the below requirements
# * apache configured to serve ../web on OPTION_BASE_URL
# * a database with name ending "_testharness"; this script will drop and remake the
#   database, so make sure it is never used for anything important
# * email addresses (email_n below) configured to pipe to ./mailin.pl with fast
#   local delivery
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: run.pl,v 1.6 2005-03-01 18:04:03 francis Exp $';

use strict;
require 5.8.0;

use Data::Dumper;

use mySociety::Config;
mySociety::Config::set_file('../conf/general');
use mySociety::WebTestHarness;

our $base_url = mySociety::Config::get('BASE_URL');
our $httpd_error_log = mySociety::Config::get('HTTPD_ERROR_LOG');
sub email_n { my $n = shift; return "pbharness+$n\@owl"; }
my $verbose = 1;

# Configure test harness class
print "Set up web test harness...\n" if $verbose > 0;
our $wth = new mySociety::WebTestHarness('PB_');
$wth->log_watcher_setup($httpd_error_log);
$wth->database_drop_reload('../db/schema.sql');
$wth->email_setup();
our $b = $wth->browser_get_agent();

# Syntax check all .php files
print "Syntax check all PHP files...\n" if $verbose > 0;
$wth->php_check_syntax("../../pb/");

# Check that we can detect PHP errors
print "Confirm we can detect errors...\n" if $verbose > 0;
$b->get($base_url . "/test.php?error=1" );
die "Unable to detect errors from PHP" if !$wth->log_watcher_get_errors();

# Create a new pledge, starting at the home page
print "Create new pledge...\n" if $verbose > 0;
$b->get($base_url);
$b->follow_link(text_regex => qr/Start your own pledge/) or die "Start your own pledge link missing";
$wth->browser_check_contents("New Pledge");
$b->submit_form(form_name => 'pledge',
    fields => { action => 'finish running this test harness', 
        people => '3', type => 'automated lines of code', signup => 'sign up',
        date => 'tomorrow', ref => 'automatedtest',
        name => 'Peter Setter', email => email_n(0) },
    button => 'submit') or die "Failed to submit creating form";
$wth->browser_check_contents("An email has been sent");
$wth->log_watcher_check();
print "Confirming new pledge...\n" if $verbose > 0;
my $confirmation_email = $wth->email_get_containing(
    '%To: '.email_n(0).'%To confirm your email address%');
print "Confirmation link not found\n" if ($confirmation_email !~ m#^($base_url.*$)#m);
$b->get($1);
$wth->browser_check_contents("Thank you for confirming that pledge");

# Sign it a few times
for (my $i = 1; $i < 4; ++$i) {
    print "Signing the pledge $i...\n" if $verbose > 0;
    $b->get($base_url);
    $b->follow_link(text_regex => qr/finish running this test harness/) or die "Pledge not appeared on front page";
    $wth->browser_check_contents("Sign me up");
    $b->submit_form(form_name => 'pledge',
        fields => { name => "Siegfried Signer $i", email => email_n($i) },
        button => 'submit') or die "Failed to submit signing form";
    $wth->browser_check_contents("An email has been sent to the address you gave to confirm it is yours");
    $wth->log_watcher_check();
}
for (my $i = 1; $i < 4; ++$i) {
    print "Confirming signature $i...\n" if $verbose > 0;
    $confirmation_email = $wth->email_get_containing(
        '%To: '.email_n($i).'%To confirm your email address%');
    print "Confirmation link not found\n" if ($confirmation_email !~ m#^($base_url.*$)#m);
    $b->get($1);
    if ($i == 3) {
        $wth->browser_check_contents("Your signature has made this Pledge reach its target!");
    } else {
        $wth->browser_check_contents("Thank you for confirming your signature");
    }
    $wth->log_watcher_check();
}
# Check it has completed
print "Final checks...\n" if $verbose > 0;
$b->get($base_url);
$b->follow_link(text_regex => qr/finish running this test harness/) or die "Pledge not appeared on front page";
$wth->browser_check_contents("This pledge has been successful!");
$wth->log_watcher_check();
# Check got success emails
for (my $i = 0; $i < 4; ++$i) {
    $confirmation_email = $wth->email_get_containing( '%To: '.email_n($i).'%Congratulations%');
}

# Or any unhandled emails or errors
$wth->email_check_none_left();
$wth->log_watcher_check();

