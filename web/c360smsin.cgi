#!/usr/bin/perl -w -I../perllib -I../../perllib
#
# c360smsin.cgi:
# Process received SMS messages.
#
# The protocol used here is that agreed with Greg Jackson at C360. Individual
# SMSs result in POST requests to this form with parameters as follows:
# 
#   intSequence                 C360 message sequence number
#   intTransactionID            ID assigned by their partner
#   intTime                     YYYYMMDDHHMMSS timestamp for message
#   intDestination              number to which message was sent
#   intOriginatingNumber        sending phone
#   intDeliverer                network
#   strData                     message data (IA5)
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#

my $rcsid = ''; $rcsid .= '$Id: c360smsin.cgi,v 1.8 2008-02-04 22:50:29 matthew Exp $';

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
use HTML::Entities;
use Net::Netmask;
use utf8;

use mySociety::DBHandle qw(dbh);
use PB;
use PB::SMS;

my $mask = new Net::Netmask(mySociety::Config::get('PB_SMS_C360_SOURCE_IP'));

my $D = new DateTime::Format::Strptime(
                pattern => '%Y%m%d%H%M%S'
                # XXX locale and timezone?
            );

while (my $q = new mySociety::CGIFast()) {
    binmode(STDOUT, ':utf8');
    try {
        throw PB::Error("No REQUEST_METHOD; this program must be run in a CGI/FastCGI environment")
            if (!exists($ENV{REQUEST_METHOD}));
        my $ip = $ENV{REMOTE_ADDR};
        throw PB::Error('REMOTE_ADDR not defined; this program must be run in a CGI/FastCGI environment')
            unless (defined($ip));
        throw PB::Error("Attempt at access from prohibited address $ip")
            unless ($mask->match($ip));

        throw PB::Error("Script must be called by POST, not " . $q->request_method())
            unless ($q->request_method() eq 'POST');

        my %P;
        foreach (qw(intSequence intTransactionID intTime intDestination intOriginatingNumber intDeliverer strData)) {
            throw PB::Error("Client did not supply required parameter '$_'")
                unless (defined($q->param($_)));

            # Sometimes we get whitespace in these; trim it.
            $P{$_} = $q->param($_);
            $P{$_} =~ s/^\s+//;
            $P{$_} =~ s/\s+$//;

            throw PB::Error("Bad value '" . $q->param($_) . "' for required parameter '$_'")
                if ($_ =~ /^int/ && $P{$_} =~ /[^\d]/);
        }

        # Start by checking whether we've seen this message before.
        my $resp = 'bad';
        my $smsid = $P{intSequence};
        
        if (!defined(dbh()->selectrow_array('select id from incomingsms where foreignid = ? for update', {}, $smsid))) {
            my $message = $P{strData};

            # Parse the date.
            my $smsdate = $P{intTime};
            my $whensent;
            if (!defined($whensent = $D->parse_datetime($smsdate))) {
                throw PB::Error("Bad value '$smsdate' for smsdate parameter in request");
            }
            $whensent = $whensent->epoch();

            my $sender = $P{intOriginatingNumber};
            $sender =~ s#^([^+])#+$1#;
            my $recipient = $P{intDestination};
#            $recipient =~ s#^([^+])#+$1#;      # destination is a short code

            HTML::Entities::decode_entities($message);
            PB::SMS::receive_sms($sender, $recipient, $P{intDeliverer}, $message, $smsid, $whensent);

            $resp = "OK";
        } else {
            $resp = "OK";
        }

        dbh()->commit();

        print $q->header(
                    -type => 'text/plain; charset=utf-8',
                    -content_length => length($resp)
                ), $resp;
    } catch PB::Error with {
        my $E = shift;
        my $t = $E->text();
        print STDERR "$t\n";
        print $q->header(
                    -status => "500 Internal Error: $t",
                    -type => 'text/plain; charset=utf-8',
                    -content_length => length($t) + 1
                ), $t, "\n";
#        warn "Error: $t\n";
    };  # any other kind of error will kill the script and return HTTP 500 
        # to the client, which is what we want. - XXX No it isn't!
}
