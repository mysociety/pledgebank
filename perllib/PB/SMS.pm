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
# $Id: SMS.pm,v 1.23 2005-05-24 23:18:39 francis Exp $
#

package PB::SMS;

use strict;

use mySociety::DBHandle qw(dbh);
use mySociety::Util qw(print_log ordinal);

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

#
# We need to be able to process messages in the wretched IA5 character set
# which mobile phones apparently speak. This is like ASCII, but with all the
# control codes replaced with random NLS characters in a completely arbitrary
# order.
#
my @ia5_to_unicode = (0 .. 127);

@ia5_to_unicode[0 .. 31] = (
        #  0
        0x40, 0xa3, 0x24, 0xa5, 0xe8, 0xe9, 0xf9, 0xec,
        #  8
        0xf2, 0xc7, 0x0a, 0xd8, 0xf8, 0x0d, 0xc5, 0xe5,
        # 10
        0x394, 0x5f, 0x3a6, 0x393, 0x39b, 0x3a9, 0x3a0, 0x3a8,
        # 18
        0x3a3, 0x398, 0x39e, 0x1b, 0xc6, 0xe6, 0xdf, 0xc9
    );

$ia5_to_unicode[0x40] = 0xa1;
@ia5_to_unicode[0x5b .. 0x60] = (0xc4, 0xd6, 0xd1, 0xdc, 0xa7, 0xbf);
@ia5_to_unicode[0x7b .. 0x7f] = (0xe4, 0xf6, 0xf1, 0xfc, 0xe0);

my %unicode_to_ia5 = map { $ia5_to_unicode[$_] => $_ } (0 .. 127);

=item decode_ia5 DATA

Convert DATA, which is in the bastardised variant of IA5 spoken by mobile
'phones, into UTF8.

=cut
sub decode_ia5 ($) {
    my @octets = unpack('C*', $_[0]);
    my $ret = '';
    foreach (@octets) {
        throw PB::Error("Character in IA5 input had value $_; should be <128")
            if ($_ > 127);
        $ret .= sprintf('%c', $ia5_to_unicode[$_]);
    }
    return $ret;
}

=item encode_ia5 TEXT

Convert TEXT, in UTF8, to the bastardised variant of IA5 spoken by mobile
'phones.

=cut
sub encode_ia5 ($) {
    my @chars = map { ord($_) } split(//, $_[0]);
    my $ret = '';
    foreach (@chars) {
        throw PB::Error(sprintf("UNICODE character \\x{%04x} cannot be expressed in IA5", $_))
            if (!exists($unicode_to_ia5{$_}));
        $ret .= pack('C', $unicode_to_ia5{$_});
    }
    return $ret;
}

=item send_sms RECIPIENTS MESSAGE [ISPREMIUM]

Send the MESSAGE to RECIPIENTS (an international-format phone number or
reference to an array of same). If ISPREMIUM is true, the message is sent as a
25p "premium" (reverse-billed) message. Returns in list context the IDs of the
outgoing messages created or dies on error. Does not commit.

=cut
sub send_sms ($$;$) {
    my ($r, $msg, $ispremium) = @_;
    $ispremium ||= 0;
    $ispremium = 0;         # XXX chris 20050419 -- we may turn premium back on
                            # later, but for the moment make all SMSs free.
                            
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
                    (id, recipient, message, whensubmitted, ispremium)
                values (?, ?, ?, ?, ?)',
                {},
                $id, $recipient, $msg, time(), $ispremium ? 't' : 'f');
    }
    return @ids;
}

=item receive_sms SENDER RECIPIENT NETWORK MESSAGE FOREIGNID WHENSENT

Record a received SMS message in the database. Returns the ID of the received
message. Does not commit.

=cut
sub receive_sms ($$$$$$) {
    my ($sender, $receiver, $network, $msg, $foreignid, $whensent) = @_;
    my $id = dbh()->selectrow_array("select nextval('incomingsms_id_seq')");
    dbh()->do('
            insert into incomingsms
                (id, sender, recipient, network, message, foreignid,
                    whenreceived, whensent)
            values (?, ?, ?, ?, ?, ?, ?, ?)',
            {},
            $id, $sender, $receiver, $network, $msg, $foreignid, time(), $whensent);
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
            my ($id, $text, $sender, $recipient) = @_;
            # Add signer who has signed up by SMS and received a 
            # conversion-to-email message.
            my ($pledge_id, $token) =
                dbh()->selectrow_array('
                        select pledge_id, token
                        from smssubscription, outgoingsms
                        where smssubscription.outgoingsms_id = outgoingsms.id
                            and outgoingsms.id = ?
                        for update',
                        {}, $id);

            return 0 if (!defined($pledge_id));

            # Call into the database to do the actual work.
            my $r = dbh()->selectrow_array('
                        select smssubscription_sign(?, null)',
                        {}, $id);

            if ($r eq 'signed') {
                # Already signed, but that's OK.
                print_log('debug', "#$id delivered, but $recipient already signed up to pledge id $pledge_id");
                return 1;
            } elsif ($r ne 'ok') {
                my %errormsg = (
                    finished => "Sorry, in between your texting us and our reply reaching you, that pledge finished. Better luck next time!",
                    full => "Sorry, in between your texting us and our reply reaching you, the last place on that pledge was taken. Better luck next time!"
                        # we've already tested for 'none' and 'signed'
                    );
                die "smssubscription returned unexpected result '$r' for $sender on pledge $pledge_id"
                    unless (exists($errormsg{$r}));
                print_log('debug', "#$id delivered, but $recipient cannot sign up because pledge $pledge_id is $r");
                send_sms(
                        $sender,
                        $errormsg{$r}
                    );
                return 1;
            } else {
                print_log('debug', "#$id delivered, and $recipient signed up to pledge id $pledge_id");
                return 1;
            }
        }]
    );

# @failed_handlers
# Handlers which are called for outgoing messages for which delivery has
# failed. Other details as for @delivered_handlers.
@PB::SMS::failed_handlers = (
        ['sms-signup',
        sub ($$$$$) {
            my ($id) = @_;
            # Delivery of this SMS has failed, so there's no possibility of it
            # being confirmed. Remove the subscription record.
            dbh()->do('
                    delete from smssubscription
                    where outgoingsms_id = ?
                ', {}, $id);
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
            if ($message =~ m#^\s*\Q$keyword\E\s*([^\s]+)#i) {
                # Could be a signup request.
                my $ref = $1;
                # Exact reference match.
                my $pledge_id = dbh()->selectrow_array('
                                        select id from pledges
                                        where ref ilike ? and confirmed
                                        and pin is null
                                    ', {}, $ref);

                # Approximate reference match.
                if (!defined($pledge_id)) {
                    my $ids = dbh()->selectall_arrayref("
                                            select id, ref
                                            from pledges
                                            where ref ilike '%' || ? || '%'
                                                and confirmed
                                                and pin is null
                                        ", {}, $ref);
                    if (@$ids != 1) {
                        send_sms(
                                $sender,
                                "We can't find the pledge '$ref' - please check it carefully and try again"
                            );
                        print_log('warning', "incoming message #$id was request for "
                                                . (@$ids > 1 ? 'ambiguous' : 'unknown')
                                                . " ref '$ref'");
                        return 0;
                    } else {
                        # Replace ref with its actual value.
                        ($pledge_id, $ref) = @{$ids->[0]};
                    }
                }
                print_log('debug', "incoming message #$id is signup request for pledge $pledge_id ($ref)");

                # Three cases:
                #   1. SMS from unknown person
                #   2. SMS from person who has signed but not converted
                #   3. SMS from person who has signed and converted
                #
                # In case 3 we send an error message. Cases 1 and 2 are
                # handled the same way -- we send out a conversion URL. But
                # don't do this in case 1 unless the pledge is still signable
                # -- obviously we're not signing it yet, but we might as well
                # catch the common case here as long as no race condition
                # remains.
                my ($signer_id, $email) = dbh()->selectrow_array('
                                    select id, email from signers
                                    where pledge_id = ? and mobile = ?
                                    for update', {}, $pledge_id, $sender);

                my $send_token = 0;

                if (defined($signer_id)) {
                    if (defined($email)) {
                        # Case 3. Just send an error message.
                        print_log('debug', "user at $sender has already signed up and converted on $pledge_id as signer $signer_id; sending error message");
                        send_sms(
                                $sender,
                                "You're already signed up to this pledge!"
                            );
                        return 1;
                    } # else case 2.
                } else {
                    # Case 1. But we should only send the token in the case
                    # where the pledge is still signable.
                    my $r = PB::pledge_is_valid_to_sign($pledge_id, undef, $sender);
                    my %errormsg = (
                            finished => "Sorry, it's too late to sign up to the '$ref' pledge",
                            full => "Sorry, the '$ref' pledge is now full"
                                # we've already tested for 'none' and 'signed'
                        );
                    if ($r eq 'ok') {
                        $send_token = 1;
                    } else {
                        die "pledge_is_valid_to_sign returned unexpected result '$r' for $sender on pledge $pledge_id"
                            unless (exists($errormsg{$r}));
                        send_sms(
                                $sender,
                                $errormsg{$r}
                            );
                        return 1;
                    }
                }

                # If the have already sent us an SMS, then we should dig out
                # the token we've already sent them.
                my $token;
                
                # 1. Case where there's an outstanding SMS.
                $token =
                    dbh()->selectrow_array('
                            select token
                            from smssubscription, outgoingsms
                            where smssubscription.outgoingsms_id = outgoingsms.id
                                and pledge_id = ?
                                and recipient = ?', {}, $pledge_id, $sender);

                # 2. Case where there isn't, but signer is signed up already.
                $token ||=
                    dbh()->selectrow_array('
                            select token
                            from smssubscription, signers
                            where smssubscription.signer_id = signers.id
                                and signers.pledge_id = ?
                                and mobile = ?', {}, $pledge_id, $sender);

                # 3. No previous token; generate a new one.
                $token ||= unpack('h*', mySociety::Util::random_bytes(2))
                                . "-"
                                . unpack('h*', mySociety::Util::random_bytes(2));

                # Tell the user how many people have signed. This isn't
                # reliable but is Good Enough for these purposes.
                my $numsigned = dbh()->selectrow_array('select count(id) from signers where pledge_id = ?', {}, $pledge_id) + 1;

                # Don't need to waste seven characters on URL scheme....
                my $shorturl = mySociety::Config::get('BASE_URL');
                $shorturl =~ s#^http://##;
                my ($new_id) =
                    send_sms(
                            $sender,
                            sprintf('Thanks. You are the %s person to pledge - now nag your friends! Go to %s/S/%s to get emails instead of texts and for more info.',
                                    ordinal($numsigned),
                                    $shorturl,
                                    $token
                            ),
                            1       # this SMS costs them 25p
                        );

                dbh()->do('
                        insert into smssubscription
                            (token, pledge_id, outgoingsms_id)
                        values (?, ?, ?)', {}, $token, $pledge_id, $new_id);

                print_log('debug', "sending conversion request #$new_id to $sender for $pledge_id");

                return 1;
            } else {
                # Not a subscription request.
                return 0;
            }
        }]
    );

1;
