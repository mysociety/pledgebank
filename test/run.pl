#!/usr/bin/perl -w -I../../perllib
#
# test.pl:
# Test harness for PledgeBank.  Makes sure we haven't broken the code.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: run.pl,v 1.2 2005-02-28 13:52:30 francis Exp $';

use strict;
require 5.8.0;

use WWW::Mechanize;
use File::Find;
use File::Slurp;
use Data::Dumper;
use DBI;

use mySociety::Logfile;
use mySociety::Config;
mySociety::Config::set_file('../conf/general');
use mySociety::DBHandle qw(dbh);

our $base_url = mySociety::Config::get('BASE_URL');
our $httpd_error_log = mySociety::Config::get('HTTPD_ERROR_LOG');
our @emails = ( 'test+1@owl', 'test+2@owl', 'test+3@owl' );

our $dbhost = mySociety::Config::get('PB_DB_HOST', undef);
our $dbport = mySociety::Config::get('PB_DB_PORT', undef);
our $dbname  = mySociety::Config::get('PB_DB_NAME');
our $dbuser = mySociety::Config::get('PB_DB_USER');
our $dbpass = mySociety::Config::get('PB_DB_PASS');

# Drop and recreate database from schema
die "Database will be dropped, so for safety must be called '_testharness'" if ($dbname !~ m/_testharness$/);
# ... make connection with no database name and drop and remake databse
my $connstr = 'dbi:Pg:';
$connstr .= "host=$dbhost;" if ($dbhost);
$connstr .= "port=$dbport;" if ($dbport);
my $db_remake_db = DBI->connect($connstr, undef, $dbpass, {
                        RaiseError => 1, AutoCommit => 1, PrintError => 0, PrintWarn => 1, });
$db_remake_db->do("drop database $dbname");
$db_remake_db->do("create database $dbname");
$db_remake_db->disconnect();
# ... load in schema
mySociety::DBHandle::configure(Name => $dbname, User => $dbuser, Password => $dbpass, 
        Host => $dbhost, Port => $dbport);
my $schema = read_file("../db/schema.sql");
dbh()->do($schema);
dbh()->commit();

# Syntax check all .php files
find(\&check_php_syntax, "../../pb/"); # TODO reenable
sub check_php_syntax {
    if (m/\.php$/) {
        my $syntax_result = qx#php -l $_#;
        die $syntax_result if ($syntax_result ne "No syntax errors detected in $_\n");
    }
}

# Create web browsing agent, check for errors automatically
our $b = new WWW::Mechanize(autocheck => 1);
sub check_content_contains {
    my $check = shift;
    if ($b->content !~ m/$check/) {
        print $b->content;
        print "\n\n";
        die "URL " . $b->uri() . " does not contain '" . $check . "'";
    }
}

# Set up HTTP log file watcher
our $http_logobj = new mySociety::Logfile($httpd_error_log);
our $http_logoffset = $http_logobj->lastline();
# Returns error text if there are new errors since last call,
# or empty string (which is false) otherwise.
sub check_for_log_errors {
    my $error = "";
    $http_logobj->_update();
    while ($http_logobj->nextline($http_logoffset)) {
        $http_logoffset = $http_logobj->nextline($http_logoffset);
        $error .= $http_logobj->getline($http_logoffset) . "\n";
    }
    return $error;
}

# Check that we can detect PHP errors
$b->get($base_url . "/test.php?error=1" );
die "Unable to detect errors from PHP" if !check_for_log_errors();

# Create a new pledge, starting at the home page
$b->get($base_url);
$b->follow_link(text_regex => qr/Start your own pledge/) or die "Start your own pledge link missing";
$b->content =~ m/New Pledge/ or die "Start own pledge page not recognised";
$b->submit_form(form_name => 'pledge',
    fields => { action => 'finish running this test harness', 
        people => '3', type => 'automated lines of code', signup => 'sign up',
        date => 'tomorrow', ref => 'automatedtest',
        name => 'Peter Setter', email => $emails[0] },
    button => 'submit') or die "failed to submit form";
check_content_contains("An email has been sent");

# See if there were any errors
my $errors = check_for_log_errors();
print $errors if ($errors);

