#!/usr/bin/perl -w -I../../perllib
#
# test.pl:
# Test harness for PledgeBank.  Makes sure we haven't broken the code.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: run.pl,v 1.1 2005-02-24 12:18:02 francis Exp $';

use strict;
require 5.8.0;

use WWW::Mechanize;
use File::Find;
use Data::Dumper;

use mySociety::Logfile;
use mySociety::Config;
mySociety::Config::set_file('../conf/general');

our $base_url = mySociety::Config::get('BASE_URL');
our $httpd_error_log = mySociety::Config::get('HTTPD_ERROR_LOG');
our @emails = ( 'test+1@owl', 'test+2@owl', 'test+3@owl' );

# Syntax check all .php files
find(\&check_php_syntax, "../../pb/"); # TODO reenable
sub check_php_syntax {
    if (m/\.php$/) {
        my $syntax_result = qx#php -l $_#;
        die $syntax_result if ($syntax_result ne "No syntax errors detected in $_\n");
    }
}

# Clear postgresql database and reload the schema    
#    dropdb -q -U $PSQL_SCHEMATEST_USER schematest || warn "schematest already dropped, continuing"
#    createdb -q -U $PSQL_SCHEMATEST_USER schematest
#    psql --file=$2 -U $PSQL_SCHEMATEST_USER -q schematest 2>&1 | grep -v "will create implicit" && echo -n 
#    pg_dump -s -U $PSQL_SCHEMATEST_USER schematest | egrep -v "^--|SET SESSION AUTHORIZATION|\\connect - |GRANT ALL|REVOKE ALL" | grep -v "SET search_path = public, pg_catalog;" | sed "s/,$//;" > $CVS_SCHEMA_FILE

# Create web browsing agent, check for errors automatically
our $b = new WWW::Mechanize(autocheck => 1);

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
$b->content =~ m/An email has been sent/ or die "New pledge submitted page not recognised";

# See if there were any errors
my $errors = check_for_log_errors();
print $errors if ($errors);

