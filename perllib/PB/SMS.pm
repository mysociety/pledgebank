#!/usr/bin/perl
#
# PB/SMS.pm:
# PledgeBank SMS apparatus.
#
# This contains an interface for submitting messages and code which is run for
# each message which is received, or for each sent message which is delivered
# or for which delivery fails.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: chris@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: SMS.pm,v 1.3 2005-03-03 01:21:50 chris Exp $
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
messages created or dies on error. Does not commit.

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
        PB::DB::dbh()->do('insert into outgoingsms (id, recipient, message, whensubmitted) values (?, ?, ?)', {}, $id, $recipient, $msg, time());
    }
    return @ids;
}

=item receive_sms SENDER RECIPIENT MESSAGE FOREIGNID WHENSENT

Record a received SMS message in the database. Returns the ID of the received
message. Does not commit.

=cut
sub receive_sms ($$$$$) {
    my ($sender, $receiver, $msg, $foreignid, $whensent) = @_;
    my $id = dbh()->selectrow_array("select nextval('incomingsms_id_seq')");
    dbh()->do('insert into incomingsms (id, sender, recipient, message, foreignid, whenreceived, whensent) values (?, ?, ?, ?, ?, ?, ?)', {}, $id, $sender, $receiver, $msg, $foreignid, time(), $whensent);
    return $id;
}


#
# Handlers for messages
#
# Each handler is passed the id of a message, its text, sender and recipient.
# Handlers for outgoing messages are also passed the final status of the
# message (see schema for examples). A handler should return true if it
# processes the message, in which case the message will be deleted and no
# further handlers will be called; or false if it does not handle the message
# and the message should be passed to subsequent handlers. On error it should
# die; the exception will be caught and logged, and the handler will be called
# again for that message later on. This should only be used in case of serious
# but transient errors; in particular, rather than repeatedly failing a handler
# should opt to abandon the message.
#
# A message which is not processed by any handler will be deleted uncared-for.
#
# If a handler uses the global database handle (recommended) it should not
# commit or roll back any transaction. This will be done by the enclosing
# scope.
#

# @delivered_handlers
# Handlers which are called for outgoing messages which have been successfully
# delivered. Each element should contain a descriptive name of the handler and
# a code reference.
@PB::SMS::delivered_handlers = (
        ['sms-signup', sub ($$$$$) {
            my ($id) = @_;
            # Confirm pledger who has signed up by SMS and been sent a 
            # conversion-to-email message.
            my $signers_id = dbh()->selectrow_array('select id from signers where outgoingsms_id = ? for update', {}, $id);
            return 0 if (!defined($signers_id));
            dbh()->do('update signers set confirmed = true where id = ?', {}, $signers_id);
            return 1;
        }]
    );

# @failed_handlers
# Handlers which are called for outgoing messages for which delivery has
# failed. Other details as for @delivered_handlers.
@PB::SMS::failed_handlers = (
        ['sms-signup', sub ($$$$$) {
            # Drop pledger who has signed up by SMS but is unable to receive a
            # conversion-to-email message.
            my ($id) = @_;
            dbh()->do('delete from signers where outgoingsms_id = ?', {}, $id):
            return 1;
        }]
    );

# @received_handlers
# Handlers called for received messages.
@PB::SMS::received_handlers = (
        ['sms-signup', sub ($$$$$) {
            my ($id, $message, $sender, $recipient) = @_;
            my $keyword = mySociety::Config::get('PB_SMS_PREFIX', '');
            if ($message =~ m#^\s*\Q$keyword\E\s*([^\s]+)#) {
                # Could be a signup request.
                my $ref = $1;
                my $pledge_id = dbh()->selectrow_array('select id from pledges where ref = ?', {}, $ref);
                if (!defined($pledges_id)) {
                    my $ids = dbh()->selectcol_arrayref("select id from pledges where ref ilike '%' || ? || '%'", {}, $ref);
                    if (@$ids != 1) {
                        print_log('warning', "incoming message #$id was request for unknown/ambiguous ref '$ref'");
                        return 0;
                    } else {
                        $pledge_id = $ids->[0];
                    }
                }
                # OK, we've got a signup request. Send them a conversion
                # message and add them to the list.
                my $token = unpack('h*', random_bytes(2)) . "-" . unpack('h*', random_bytes(2));
                my $new_id = send_sms(
                                $sender,
                                "Thanks for pledging! Visit http://pledgebank.org/sms/$token to sign up for email and more."
                            );
                dbh()->do('
                        insert into signers
                            (pledge_id, mobile, signtime, token, outgoingsms_id)
                        values (?, ?, ?, ?, ?)', 
                        {},
                        $pledge_id, $sender, time(), $token, $new_id);
                return 1;
            } else {
                # Not a subscription request.
                return 0;
            }
        }]
    );

1;
