#!/usr/bin/perl
#
# PB/SMS.pm:
# PledgeBank SMS apparatus.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: SMS.pm,v 1.2 2005-03-02 22:03:19 chris Exp $
#

package PB::SMS;

use strict;

use mySociety::DBHandle qw(dbh);

use PB;


# is_valid_number NUMBER
# Is NUMBER a valid SMS recipient?
sub is_valid_number ($) {
    # This tests for a plausible UK number. Could use Number::Phone::UK or a
    # more general service.
    if ($_[0] =~ m#^\+44\d+#) {
        return 1;
    } else {
        return 0;
    }
}

=item send_sms RECIPIENTS MESSAGE

Send the MESSAGE to RECIPIENTS (an international-format phone number or
reference to an array of same). Returns in list context the IDs of the outgoing
messages created or dies on error.

=cut
sub send_sms ($$) {
    my ($r, $msg) = @_;
    if (ref($r) eq '') {
        $r = [$r];
    } elsif (ref($r) ne 'ARRAY') {
        die "RECIPIENTS must be scalar or reference to list in send_sms";
    } elsif (grep { !is_valid_number($_) } @$r) {
        die "numbers in RECIPIENTS must be valid UK SMS recipients";
    }
    my @ids = ( );
    foreach my $recipient (@$r) {
        my $id = dbh()->selectrow_array("select nextval('outgoingsms_id_seq')");
        push(@ids, $id);
        PB::DB::dbh()->do('insert into outgoingsms (id, recipient, message) values (?, ?, ?)', {}, $id, $recipient, $msg);
    }
    dbh()->commit();
    return @ids;
}

=item receive_sms SENDER RECIPIENT MESSAGE FOREIGNID WHENSENT

Record a received SMS message in the database. Returns the ID of the received
message.

=cut
sub receive_sms ($$$$$) {
    my ($sender, $receiver, $msg, $foreignid, $whensent) = @_;
    my $id = dbh()->selectrow_array("select nextval('incomingsms_id_seq')");
    dbh()->do('insert into incomingsms (id, sender, recipient, message, foreignid, whenreceived, whensent) values (?, ?, ?, ?, ?, ?, ?)', {}, $id, $sender, $receiver, $msg, $foreignid, time(), $whensent);
    dbh()->commit();
    return $id;
}

1;
