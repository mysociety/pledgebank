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
# $Id: SMS.pm,v 1.6 2005-03-08 11:26:49 chris Exp $
#

package PB::SMS;

use strict;

use mySociety::DBHandle qw(dbh);
use mySociety::Util qw(print_log);

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
        PB::DB::dbh()->do('
                insert into outgoingsms
                    (id, recipient, message, whensubmitted)
                values (?, ?, ?, ?)',
                {},
                $id, $recipient, $msg, time());
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
    dbh()->do('
            insert into incomingsms
                (id, sender, recipient, message, foreignid,
                    whenreceived, whensent)
            values (?, ?, ?, ?, ?, ?, ?)',
            {},
            $id, $sender, $receiver, $msg, $foreignid, time(), $whensent);
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
        ['sms-signup',
        sub ($$$$$) {
            my ($id) = @_;
            # Confirm signer who has signed up by SMS and been sent a 
            # conversion-to-email message.
            my $signer_id = dbh()->selectrow_array('
                                    select signer_id
                                    from outgoingsms_signers
                                    where outgoingsms_id = ?
                                    for update
                                ', {}, $id);
            return 0 if (!defined($signer_id));

            dbh()->do('
                    update signers
                    set confirmed = true
                    where id = ?
                ', {}, $signer_id);

            # Now delete any other records of SMSs sent to this mobile number
            # for this signer.
            dbh()->do('
                    delete from outgoingsms_signers
                    where signer_id = ?
                ', {}, $signer_id);

            return 1;
        }]
    );

# @failed_handlers
# Handlers which are called for outgoing messages for which delivery has
# failed. Other details as for @delivered_handlers.
@PB::SMS::failed_handlers = (
        ['sms-signup',
        sub ($$$$$) {
            my ($id) = @_;
            # Delivery of this SMS has failed, so remove any link between it
            # and a signer. If that signer is not confirmed and has no other
            # outstanding SMSs, then drop the signer too.
            dbh()->do('
                    delete from outgoingsms_signers
                    where outgoingsms_id = ?
                ');
            dbh()->do('
                    delete from signers
                    where not confirmed
                        and email is null
                        and (
                            select count(outgoingsms_id)
                            from outgoingsms_signers
                            where signer_id = signers.id
                        ) = 0
                ');
            return 1;
        }]
    );

# @received_handlers
# Handlers called for received messages.
@PB::SMS::received_handlers = (
        ['sms-signup',
        sub ($$$$$) {
            my ($id, $message, $sender, $recipient) = @_;
            my $keyword = mySociety::Config::get('PB_SMS_PREFIX', '');
            if ($message =~ m#^\s*\Q$keyword\E\s*([^\s]+)#) {
                # Could be a signup request.
                my $ref = $1;
                my $pledge_id = dbh()->selectrow_array('
                                        select id from pledges
                                        where ref = ? and confirmed
                                    ', {}, $ref);
                if (!defined($pledge_id)) {
                    my $ids = dbh()->selectall_arrayref("
                                            select id, ref
                                            from pledges
                                            where ref ilike '%' || ? || '%'
                                                and confirmed
                                        ", {}, $ref);
                    if (@$ids != 1) {
                        # XXX should we send a warning SMS here?
                        print_log('warning', "incoming message #$id was request for unknown/ambiguous ref '$ref'");
                        return 0;
                    } else {
                        # Replace ref with its actual value.
                        ($pledge_id, $ref) = @{$ids->[0]};
                    }
                }
                print_log('debug', "incoming message #$id is signup request for pledge $pledge_id ($ref)");

                # Must check that they haven't signed up before. If they have,
                # and the subscription is confirmed, send an error report; or
                # if it's unconfirmed, send them a reminder of the token.
                my ($signer_id, $c, $token)
                            = dbh()->selectrow_array('
                                    select id, confirmed, token from signers
                                    where pledge_id = ? and mobile = ?
                                    for update', {}, $pledge_id, $sender);

                if (defined($signer_id) && $c) {
                    print_log('debug', "user at $sender has already signed and confirmed on $pledge_id as signer $signer_id");
                    send_sms(
                            $sender,
                            "You're already signed up to this pledge!"
                        );
                }
                
                # OK, we've got a signup request. Send them a conversion
                # message and add them to the list.
                # XXX birthday probability; this will be OK until we have ~64K
                # or more outstanding conversion SMSs.

                # Only create a new token if they haven't already been sent
                # one.
                my $token ||= unpack('h*', mySociety::Util::random_bytes(2))
                                . "-"
                                . unpack('h*', mySociety::Util::random_bytes(2));

                if (!defined($signer_id)) {
                    # At this point we have to check that they can sign this
                    # pledge, and, if they can, create a new signer record.
                    my ($date, $comparison, $target) =
                        dbh()->selectrow_array('
                                select date, comparison, target
                                from pledges
                                where id = ?',
                                {}, $pledge_id
                            );

                    if ($date lt strftime('%Y-%m-%d', localtime())) {
                        # Pledge has closed.
                        send_sms(
                                $sender,
                                sprintf('Sorry! The pledge %s has now closed.',
                                    $ref)
                            );
                        print_log('debug', "pledge $pledge_id is closed");
                        return 1;
                    } elsif ($comparison eq 'exactly') {
                        # Lock signers table, count sigers.
                        dbh()->do('lock table signers in row share mode');
                        my $num = dbh()->selectrow_array('
                                        select count(id)
                                        from signers
                                        where pledge_id = ? and confirmed
                                    ', {}, $pledge_id);
                        if ($num >= $target) {
                            # Pledge has reached target.
                            send_sms(
                                    $sender,
                                    sprintf('Sorry! The pledge "%s" has now reached its target',
                                        $ref)
                                );
                            print_log('debug', "pledge $pledge_id has already reached its target");
                            return 1;
                        }
                    } else {
                        # We're OK; create a new unconfirmed signer.
                        $signer_id = dbh()->selectrow_array("select nextval('signers_id_seq')");
                        dbh()->do('
                                insert into signers
                                    (id, pledge_id, mobile, signtime, token)
                                values (?, ?, ?, current_timestamp, ?)
                            ', {}, $signer_id, $pledge_id, $sender, $token, $new_id);
                        print_log('debug', "signing up $sender to $pledge_id as signer $signer_id");
                    }
                } elsif (scalar(dbh()->selectrow_array('
                                    select count(outgoingsms_id)
                                    from outgoingsms_signers
                                    where signer_id = ?
                                ', {}, $signer_id)) > 2) {
                    # We've already sent several replies to this; don't send
                    # another.
                    print_log('debug', "have already sent 3 SMSs to $sender for pledge $pledge_id as signer $signer_id");
                    return 1;
                }

                # Send conversion message.
                my ($new_id) = send_sms(
                                    $sender,
                                    sprintf('Thanks for pledging! Visit %s%s to sign up for email and more.',
                                        mySociety::Config::get('BASE_URL'),
                                        $token
                                ));

                dbh()->do('
                        insert into outgoingsms_signers
                            (signer_id, outgoingsms_id)
                        values (?, ?)', {}, $signer_id, $new_id);

                print_log('debug', "sending conversion request #$new_id to $sender as signer $signer_id for $pledge_id");

                return 1;
            } else {
                # Not a subscription request.
                return 0;
            }
        }]
    );

1;
