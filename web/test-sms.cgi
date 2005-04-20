#!/usr/bin/perl -w -I../perllib -I../../perllib
#
# test-smsin.cgi:
#
# Pretends to be C360 aggregator receiving a text message from PledgeBank to a
# person.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: test-sms.cgi,v 1.1 2005-04-20 13:06:16 francis Exp $';

use strict;

require 5.8.0;

BEGIN {
    use mySociety::Config;
    mySociety::Config::set_file('../conf/general');
}

use CGI;
use CGI::Fast;
use DateTime::Format::Strptime;
use Encode;
use Error qw(:try);
use Net::Netmask;
use utf8;

use mySociety::DBHandle qw(dbh);
use PB;
use PB::SMS;

while (my $q = new CGI::Fast()) {
    binmode(STDOUT, ':utf8');
    try {
        throw PB::Error("No REQUEST_METHOD; this program must be run in a CGI/FastCGI environment")
            if (!exists($ENV{REQUEST_METHOD}));
        my $ip = $ENV{REMOTE_ADDR};
        throw PB::Error('REMOTE_ADDR not defined; this program must be run in a CGI/FastCGI environment')
            unless (defined($ip));

        throw PB::Error("Script must be called by POST, not " . $q->request_method())
            unless ($q->request_method() eq 'POST');

        my %P;
        foreach (qw(strMethod strShortcode strMobile strMessage intTransactionID intPremium)) {
            throw PB::Error("Client did not supply required parameter '$_'")
                unless (defined($q->param($_)));
            $P{$_} = $q->param($_);
            throw PB::Error("Bad value '" . $q->param($_) . "' for required parameter '$_'")
                if ($_ =~ /^int/ && $P{$_} =~ /[^\d]/);
        }
        
        # Check parameter values
        throw PB::Error("strMethod must be sendSMS") unless ($P{strMethod} eq "sendSMS");
        throw PB::Error("strShortcode must be 60022") unless ($P{strShortcode} eq "60022");

        # Start by checking whether we've seen this message before.
        dbh()->do("insert into testharness_sms
                (mobile, message, premium) values (?, ?, ?)", {}, $P{strMobile}, $P{strMessage}, $P{intPremium});
        my $id = dbh()->selectrow_array("select currval('testharness_sms_id_seq')");
        dbh()->commit();

        my $resp = $id;
        print $q->header(
                    -type => 'text/plain; charset=utf-8',
                    -content_length => length($resp)
                ), $resp;
    } 
}

