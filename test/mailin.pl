#!/usr/bin/perl -w
#
# mailin.pl:
# Incoming mail for test harness.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

# Horrible boilerplate to set up appropriate library paths.
use FindBin;
use lib "$FindBin::Bin/../perllib";
use lib "$FindBin::Bin/../../perllib";
chdir $FindBin::Bin;

use File::Slurp;

use mySociety::Config;
mySociety::Config::set_file('../conf/general');
use mySociety::DBHandle qw(dbh);

our $dbhost = mySociety::Config::get('PB_DB_HOST', undef);
our $dbport = mySociety::Config::get('PB_DB_PORT', undef);
our $dbname  = mySociety::Config::get('PB_DB_NAME');
our $dbuser = mySociety::Config::get('PB_DB_USER');
our $dbpass = mySociety::Config::get('PB_DB_PASS');

mySociety::DBHandle::configure(Name => $dbname, User => $dbuser, Password => $dbpass, 
        Host => $dbhost, Port => $dbport);
my $slurped = read_file(\*STDIN);
dbh()->do("insert into testharness_mail (content) values (?)", {}, $slurped);
dbh()->commit();

