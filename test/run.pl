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
# * email addresses (@emails below) configured to pipe to ./mailin.pl with fast
#   local delivery
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: run.pl,v 1.3 2005-02-28 17:49:21 francis Exp $';

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
our @emails = ( 'pbharness+1@owl', 'pbharness+2@owl', 'pbharness+2@owl' );

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

# Incoming mail, prepare table to store it
dbh()->do("create table testharness_mail (
  id serial not null primary key,
  content text not null default '')");
dbh()->commit();
sub get_email_containing {
    my $check = shift;
    my $mails;
    my $got = 0;
    my $c = 0;
    while ($got == 0) {
        $mails = dbh()->selectall_arrayref("select id, content from testharness_mail
            where content like ?", {}, $check);
        $got = scalar @$mails;
        die "Email containing '$check' not found even after $c sec wait" if ($got == 0 && $c > 4);
        die "Too many emails found containing '$check'" if ($got > 1);
        $c++;
        sleep 1;
    }
    my ($id, $content) = @{$mails->[0]};
    dbh()->do("delete from testharness_mail where id = ?", {}, $id);
    return $content;
}

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
sub get_log_errors {
    my $error = "";
    $http_logobj->_update();
    while ($http_logobj->nextline($http_logoffset)) {
        $http_logoffset = $http_logobj->nextline($http_logoffset);
        $error .= $http_logobj->getline($http_logoffset) . "\n";
    }
    return $error;
}
sub check_for_log_errors {
    my $errors = get_log_errors();
    die $errors if ($errors);
}

# Check that we can detect PHP errors
$b->get($base_url . "/test.php?error=1" );
die "Unable to detect errors from PHP" if !get_log_errors();

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
check_for_log_errors();

# Confirm pledge
my $confirmation_email = get_email_containing('%To confirm your email address%');
print "Confirmation link not found\n" if ($confirmation_email !~ m#^($base_url.*$)#m);
$b->get($1);
check_content_contains("Thank you for confirming that pledge");

# Or any unhandled emails
sleep 2;
my $emails_left = dbh()->selectrow_array("select count(*) from testharness_mail");
die "$emails_left unexpected emails left at the end" if $emails_left > 0;

