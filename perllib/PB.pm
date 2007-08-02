#!/usr/bin/perl
#
# PB.pm:
# Various PledgeBank bits.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: PB.pm,v 1.12 2007-08-02 11:45:03 matthew Exp $
#

package PB::Error;

use strict;
use Error qw(:try);
@PB::Error::ISA = qw(Error::Simple);

package PB::DB;

use strict;

use mySociety::Config;
use mySociety::DBHandle qw(dbh);
use DBI;

BEGIN {
    mySociety::DBHandle::configure(
            Name => mySociety::Config::get('PB_DB_NAME'),
            User => mySociety::Config::get('PB_DB_USER'),
            Password => mySociety::Config::get('PB_DB_PASS'),
            Host => mySociety::Config::get('PB_DB_HOST', undef),
            Port => mySociety::Config::get('PB_DB_PORT', undef),
            OnFirstUse => sub {
                if (!dbh()->selectrow_array('select secret from secret')) {
                    local dbh()->{HandleError};
                    dbh()->do('insert into secret (secret) values (?)',
                                {}, unpack('h*', random_bytes(32)));
                    dbh()->commit();
                }
            }
        );
}

=item secret

Return the site shared secret.

=cut
sub secret () {
    return scalar(dbh()->selectrow_array('select secret from secret'));
}

package PB;

use strict;

sub pledge_is_valid_to_sign ($$$$) {
    my ($pledge, $email, $mobile, $facebook_id) = @_;
    return scalar(PB::DB::dbh()->selectrow_array('select pledge_is_valid_to_sign(?, ?, ?, ?)', {}, $pledge, $email, $mobile, $facebook_id));
}

my $time_offset;
sub Time () {
    if (!defined($time_offset)) {
        $time_offset =
            PB::DB::dbh()->selectrow_array('
                        select extract(epoch from
                                ms_current_timestamp() - current_timestamp)');
    }
    return time() + int($time_offset);
}

# Extract language from URL.
# OPTION_WEB_HOST . OPTION_WEB_DOMAIN - default
# xx . OPTION_WEB_DOMAIN - xx is an ISO 639-1 country code
# xx . yy . OPTION_WEB_DOMAIN - xx is a country code, yy a language code (either aa or aa-bb)
sub extract_domain_lang {
    my $domain_lang;
    my $host = lc $ENV{HTTP_HOST};
    my $web = mySociety::Config::get('WEB_HOST');
    my $re_lang = '(..(?:-..)?)';
    if ($web eq 'www') {
        $domain_lang = $1 if $host =~ /^(?:[^.]+|www)\.$re_lang\./;
    } else {
        $domain_lang = $1 if $host =~ /^(?:[^.]+)\.$re_lang\.$web\./;
    }
    return $domain_lang;
}
1;
