#!/usr/bin/perl
#
# PB.pm:
# Various PledgeBank bits.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: PB.pm,v 1.2 2005-03-11 17:11:49 chris Exp $
#

package PB::Error;

use strict;
use Error qw(:try);
@PB::Error::ISA = qw(Error::Simple);

package PB::DB;

use strict;

use mySociety::Config;
use mySociety::DBHandle qw(dbh);
use mySociety::Util;
use DBI;

BEGIN {
    mySociety::DBHandle::configure(
            Name => mySociety::Config::get('PB_DB_NAME'),
            User => mySociety::Config::get('PB_DB_USER'),
            Password => mySociety::Config::get('PB_DB_PASS'),
            Host => mySociety::Config::get('PB_DB_HOST', undef),
            Port => mySociety::Config::get('PB_DB_PORT', undef)
        );

    if (!dbh()->selectrow_array('select secret from secret for update of secret')) {
        dbh()->do('insert into secret (secret) values (?)', {}, unpack('h*', mySociety::Util::random_bytes(32)));
    }
    dbh()->commit();
}

=item secret

Return the site shared secret.

=cut
sub secret () {
    return scalar(dbh()->selectrow_array('select secret from secret'));
}

package PB;

use secret;

use PB::DB;

sub pledge_is_valid_to_sign ($$$) {
    my ($pledge, $email, $mobile) = @_;
    return scalar(dbh()->selectrow_array('select pledge_is_valid_to_sign(?, ?, ?)', {}, $pledge, $email, $mobile);
}

1;
