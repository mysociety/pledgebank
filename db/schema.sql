--
-- schema.sql:
-- Schema for PledgeBank database.
--
-- Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
-- Email: francis@mysociety.org; WWW: http://www.mysociety.org/
--
-- $Id: schema.sql,v 1.22 2005-03-08 11:26:49 chris Exp $
--

-- secret
-- A random secret.
create table secret (
    secret text not null
);

create table pledges (
    id serial not null primary key,
    -- short name of pledge for URLs
    ref text not null,

    -- summary of pledge
    title text not null,
    -- number of type ("people" etc.) to reach
    target integer not null check (target > 0),
    type text not null,
    -- display verb for joining the pledge
    signup text not null default 'sign up',
    -- target deadline, midnight at end of this day
    date date not null,
    -- extra detail by pledge setter
    detail text not null default '',

    -- pledge setter
    name text not null,
    email text not null,
    -- metadata
    creationtime timestamp not null,

    -- confirmation stuff
    token text not null,
    confirmed boolean not null default false,

    -- password for private pledges
    password text not null default '',

    -- "at least" vs. "exactly"
    comparison text not null check (
            comparison = 'atleast' 
            or comparison = 'exactly'
        ),

    country text not null default '',
    postcode text not null default '',

    -- We must only notify people that the pledge has been completed once.
    -- This flag is set when we first notice that that has happened.
    completionnotified boolean not null default false
);

create table outgoingsms (
    id serial not null primary key,
    -- Recipient, as an international-format number, and text of message.
    recipient text not null,
    -- Message, as UTF-8.
    message text not null,
    -- when the message was submitted.
    whensubmitted integer not null,
    
    -- When we tried to submit the message to the server last, how many times
    -- we've attempted this, and what the result was on the most recent
    -- occasion.
    numsendattempts integer not null default 0,
    lastsendattempt integer,    -- UNIX time not timestamp because of now()
                                -- issues in transactions, etc.
    lastsendstatus text check (
            lastsendstatus is null
            or lastsendstatus = 'systemerror'
            or lastsendstatus = 'httperror'
            or lastsendstatus = 'success'
        ),
    -- any other information, e.g. errno value or HTTP status line
    lastsendextrastatus text,

    -- ID assigned to the message by the sender. Null indicates that the
    -- message has not been submitted to their server.
    foreignid text,
    
    -- Status reports returned by the sender. Null indicates that no status
    -- report has been received; 'delivered' indicates that the message has
    -- been delivered, 'failed' that it has failed and will not be delivered
    -- (including when the recipient is using PAYG and can't afford to receive
    -- it); 'buffered' that it is still in flight; 'rejected' that the network
    -- has rejected the message without attempting delivery; and 'none' that
    -- the phone is out of coverage or credit, and delivery attempts will
    -- continue.
    status text check (
            status is null
            or status = 'delivered'
            or status = 'failed'
            or status = 'buffered'
            or status = 'rejected'
            or status = 'none'
        )
    
    -- XXX extra fields for billing?
);

create unique index outgoingsms_foreignid_idx on outgoingsms(foreignid);
-- This is for an extra, paranoid check on delivery report messages.
create index outgoingsms_recipient_idx on outgoingsms(recipient);

create table incomingsms (
    id serial not null primary key,
    -- Sender's phone number.
    sender text not null,
    -- Receiving number/short code/whatever.
    recipient text not null,
    -- Text of the message, transcoded to UTF-8.
    message text not null,
    -- ID assigned by the deliverer.
    foreignid text not null,
    -- When we received the message.
    whenreceived integer not null,
    -- When the message says it was sent.
    whensent integer not null
);

create unique index incomingsms_foreignid_idx on incomingsms(foreignid);

create table signers (
    id serial not null primary key,
    pledge_id int not null,

    -- Who has signed the pledge.
    -- Name may be null because we allow users to sign up by SMS without giving
    -- their name.
    name text,
    email text,
    mobile text,

    -- SMS signers may convert to an email subscription. To do this we create
    -- a new, unconfirmed email signer and tie it to this signer through a
    -- reference. When they confirm the email the old signer is removed.
    converts_signer_id integer references signers(id),
  
    -- whether they want their name public
    showname boolean not null default false,
      
    -- when they signed
    signtime timestamp not null,
  
    -- Confirmation stuff. For email signers we send them a token and only
    -- regard them as signed up when they have supplied it back to us. SMS
    -- signers are regarded as confirmed as soon as the reply SMS has been
    -- received by their phone, but we send them a token allowing them to
    -- convert their subscription to an email one later via the
    -- converts_signer_id mechanism.
    token text not null,
    confirmed boolean not null default false,

    -- Name has been reported
    reported boolean not null default false
);

-- There may be only one signature on any given pledge from any given mobile
-- phone number.
create unique index signers_pledge_id_mobile_idx on signers(pledge_id, mobile);
-- Ditto emails.
create unique index signers_pledge_id_email_idx on signers(pledge_id, email);

-- This is a table which records the number of SMSs sent to any individual
-- phone number on any given pledge. The point here is that somebody may send
-- a subscription request, not receive the reply message and then (perhaps
-- impatiently) send a further signup request.
create table outgoingsms_signers (
    signer_id integer not null references signers(id),
    outgoingsms_id integer not null references outgoingsms(id)
);
