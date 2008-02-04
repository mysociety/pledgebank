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

my $rcsid = ''; $rcsid .= '$Id: test-sms.cgi,v 1.5 2008-02-04 22:50:29 matthew Exp $';

use strict;

require 5.8.0;

BEGIN {
    use mySociety::Config;
    mySociety::Config::set_file('../conf/general');
}

use CGI;
use mySociety::CGIFast;
use DateTime::Format::Strptime;
use Encode;
use Error qw(:try);
use Net::Netmask;
use utf8;

use mySociety::DBHandle qw(dbh);
use PB;
use PB::SMS;

while (my $q = new mySociety::CGIFast()) {
    binmode(STDOUT, ':utf8');
    try {
        throw PB::Error("No REQUEST_METHOD; this program must be run in a CGI/FastCGI environment")
            if (!exists($ENV{REQUEST_METHOD}));
        my $ip = $ENV{REMOTE_ADDR};
        throw PB::Error('REMOTE_ADDR not defined; this program must be run in a CGI/FastCGI environment')
            unless (defined($ip));

        throw PB::Error("Script must be called by POST, not " . $q->request_method())
            unless ($q->request_method() eq 'POST');

        # Find type of call
        my @param_list;
        my $method = $q->param("strMethod");
        if ($method eq "sendSMS") {
            @param_list = qw(strShortcode strMobile strMessage intTransactionID intPremium);
        } elsif ($method eq "getReceipts") {
            @param_list = qw(strWhere);
        } else {
            throw PB::Error("Unknown strMethod '$method'");
        }

        # Check all parameters are present and valid
        my %P;
        foreach (@param_list) {
            throw PB::Error("Client did not supply required parameter '$_'")
                unless (defined($q->param($_)));
            $P{$_} = $q->param($_);
            throw PB::Error("Bad value '" . $q->param($_) . "' for required parameter '$_'")
                if ($_ =~ /^int/ && $P{$_} =~ /[^\d]/);
        }
        
        # Carry out appropriate action
        my $resp;
        if ($method eq "sendSMS") {
            throw PB::Error("strShortcode must be 60022") unless ($P{strShortcode} eq "60022");

            # Store the outgoing message for checking by the test harness script
            dbh()->do("insert into testharness_sms
                    (mobile, message, premium) values (?, ?, ?)", {}, $P{strMobile}, $P{strMessage}, $P{intPremium});
            my $id = dbh()->selectrow_array("select currval('testharness_sms_id_seq')");
            dbh()->commit();
            $resp = "ID=$id";
        } elsif ($method eq "getReceipts") {
            # See if we have reached this number
            throw PB::Error("Test script only supports 'BatchID >= <number>' syntax") 
                unless $P{strWhere} =~ m#^BatchID >= (\d+)$#;
            my $minid = $1;
            # you can't do currval without doing nextval first, but doesn't matter here
            dbh()->selectrow_array("select nextval('testharness_sms_id_seq')");
            my $lastid = dbh()->selectrow_array("select currval('testharness_sms_id_seq')");
            dbh()->commit();
            my @ids_to_return = $minid..$lastid;
            foreach (@ids_to_return) {
                $resp .= "BatchID=\"$_\",DeliveryStatus=\"Delivered\",DeliveryTime=\"notyetimplemented\"\r\n";
            }
        }

        # Return the response
        print $q->header(
                    -type => 'text/plain; charset=utf-8',
                    -content_length => length($resp)
                ), $resp;
    } 
}

