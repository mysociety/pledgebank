--
-- schema.sql:
-- Schema for PledgeBank database.
--
-- Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
-- Email: francis@mysociety.org; WWW: http://www.mysociety.org/
--
-- $Id: schema.sql,v 1.43 2005-03-29 08:58:00 francis Exp $
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
    password text default '',

    -- "at least" vs. "exactly"
    comparison text not null check (
            comparison = 'atleast' 
            -- or comparison = 'exactly' -- exactly is disabled for now, as not clear we need it
        ),

    country text not null default '',
    postcode text not null default '',

    -- It's possible (hopefully rare) for subscribers to be removed from a
    -- pledge after it's been marked as successful. But once a pledge has
    -- been marked as successful we can't undo that (because success emails
    -- have already been sent out and so forth). So we instead note this here.
    removedsigneraftersuccess boolean not null default false,

    -- When a pledge is successful, we mark it as successful here.
    success boolean not null default false,
    -- We must only notify people that the pledge has been completed (either 
    -- success or failure) once.  This flag is set when we first notice that 
    -- that has happened.
    completionnotified boolean not null default false
);

-- pledge_is_valid_to_sign PLEDGE EMAIL MOBILE
-- Whether the given PLEDGE is valid for EMAIL or MOBILE to sign. One of EMAIL
-- or MOBILE may be null. Returns one of:
--      ok          pledge is OK to sign
--      none        no such pledge exists
--      finished    pledge has expired
--      full        pledge is full
--      signed      signer has already signed this pledge
create function pledge_is_valid_to_sign(integer, text, text)
    returns text as '
    declare
        p record;
    begin
        select into p *
            from pledges
            where id = $1 and confirmed
            for update;

        if not found then
            return ''none'';
        end if;

        if p.date < pb_current_date() then
            return ''finished'';
        end if;
        
        -- Lock the signers table, so that a later insert within this
        -- transaction would succeed.
        -- NOTE that "exactly" is disabled for now, so this code is not used
        lock table signers in share mode;
        if p.comparison = ''exactly'' then 
            if p.target <=
                (select count(id) from signers where pledge_id = $1) then
                return ''full'';
            end if;
        end if;

        if $2 is not null then
            if $2 = p.email then
                return ''signed'';
            end if;
            perform id from signers where pledge_id = $1 and email = $2 for update;
            if found then
                return ''signed'';
            end if;
        end if;

        if $3 is not null then
            perform id from signers where pledge_id = $1 and mobile = $3 for update;
            if found then
                return ''signed'';
            end if;
        end if;

        return ''ok'';
    end;
    ' language 'plpgsql';

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
    
    -- XXX add extra fields for billing
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

    -- whether they want their name public
    showname boolean not null default false,
      
    -- when they signed
    signtime timestamp not null,
  
    -- Name has been reported
    reported boolean not null default false,

    check (
        (name is not null and email is not null and mobile is null)
        or (name is null and email is null and mobile is not null)
    )
);

-- There may be only one signature on any given pledge from any given mobile
-- phone number.
create unique index signers_pledge_id_mobile_idx on signers(pledge_id, mobile);
-- Ditto emails.
create unique index signers_pledge_id_email_idx on signers(pledge_id, email);

-- signers_combine_2 ID1 ID2
-- Given the IDs ID1 and ID2 of two signers of a pledge, coalesce them into one
-- signer combining the two. One signer should have a name and email address,
-- and the other a mobile phone number only. The ID of the remaining signer is
-- the ID of the signer with the email address. If the pledge was successful
-- before this combination took place, set the removedsigneraftersuccessflag to
-- record this fact. This function has no return value, and raises an exception
-- if any of its preconditions are not met.
create function signers_combine_2(integer, integer)
    returns void as '
    declare
        id1 integer;
        id2 integer;
        t_pledge_id integer;
        t_mobile text;
        p record;
    begin
        -- Lock the signers table.
        lock table signers in share mode;

        if (select pledge_id from signers where id = $1)
            <> (select pledge_id from signers where id = $2) then
            raise exception ''ID1 and ID2 must be signers to the same pledge'';
        end if;
        
        t_pledge_id := (select pledge_id from signers where id = $1);

        -- lock pledges table in case we need to update it later
        select into p success
            from pledges
            where id = t_pledge_id
            for update;

        if (select email from signers where id = $1) is not null
            and (select email from signers where id = $2) is null then
            id1 := $1;
            id2 := $2;
        elsif (select email from signers where id = $1) is null
            and (select email from signers where id = $2) is not null then
            id1 := $2;
            id2 := $1;
        else
            raise exception ''exactly one of ID1, ID2 must have an email address set'';
        end if;

        t_mobile = (select mobile from signers where id = id2);
        
        delete from smssubscription where signer_id = id2;
        delete from signers where id = id2;

        update signers
            set mobile = t_mobile
            where id = id1;

        if success then
            -- pledge was successful and now we''re removing a signer, so record
            -- this fact
            update pledges set removedsigneraftersuccess = true
                where id = t_pledge_id;
        end if;

        return;
    end;
' language 'plpgsql';

-- Subscription by SMS. The punter sends us a message, and we reply with a
-- reverse-billed one, recording the mapping from pledge to outgoing SMS in
-- this table. We also send them a token (part of a URL) which they may use
-- to convert their subscription to a normal email subscription. If we get a
-- delivery report for the outgoing SMS, or if the user gives us the token
-- before we receive any such report, we sign them up and remove the
-- references to pledges and outgoingsms here. The token then remains only
-- for the purpose of converting an SMS to a normal subscription.
--
-- We may send out multiple SMSs to one mobile phone for one pledge. But we
-- only allow one subscription per mobile phone. So we need to check that when
-- signing up a user from SMS.
create table smssubscription (
    token text not null,
    pledge_id integer references pledges(id),
    signer_id integer references signers(id),
    outgoingsms_id integer references outgoingsms(id),
    check (
        (pledge_id is not null
            and signer_id is null
            and outgoingsms_id is not null)
        or (pledge_id is null
            and signer_id is not null
            and outgoingsms_id is null)
    )
);

create index smssubscription_token_idx on smssubscription(token);
create index smssubscription_outgoingsms_id_idx on smssubscription(outgoingsms_id);

-- smssubscription_sign ID TOKEN
-- Sign up from an SMS subscription. Supply either the ID of an outgoing SMS
-- subscription message; or a TOKEN sent in such a message. Returns a code as
-- from pledge_is_valid_to_sign.
create function smssubscription_sign(integer, text)
    returns text as '
    declare
        p record;
        t_outgoingsms_id integer;
        t_pledge_id integer;
        t_signer_id integer;
        t_token text;
        status text;
        t_mobile text;
    begin
        t_outgoingsms_id := $1;
        t_token := $2;
    
        -- If we have a token not an ID, then get the ID.
        if t_outgoingsms_id is null then
            if t_token is null then
                raise exception ''must supply a signer ID or a token'';
            end if;
        
            select into p outgoingsms_id, signer_id
                from smssubscription
                where "token" = token
                for update;

            if not found then
                raise exception ''bad token %'', t_token;
            end if;

            if p.signer_id is not null then
                -- Already signed this pledge; mark all subscription requests
                -- with this token with the appropriate signer ID, and return
                -- ok.
                update smssubscription
                    set signer_id = p.signer_id, outgoingsms_id = null,
                        pledge_id = null
                    where token = t_token;
                return ''ok'';
            else
                t_outgoingsms_id := p.outgoingsms_id;
            end if;
        end if;
        
        -- Find out the mobile phone number for this user
        t_mobile := (
            select recipient from outgoingsms
            where id = t_outgoingsms_id
        );
        
        if t_mobile is null then
            raise exception ''bad outgoing SMS ID #%'', t_outgoingsms_id;
        end if;

        -- select ... for update is not allowed in a subselect
        select into p pledge_id from smssubscription
            where outgoingsms_id = t_outgoingsms_id
            for update;
        t_pledge_id = p.pledge_id;

        -- we use the token to identify all SMS subscription requests to the
        -- same pledge and phone number.
        if t_token is null then
            t_token = (
                select token from smssubscription
                where outgoingsms_id = t_outgoingsms_id
                -- already grabbed lock above
            );
        end if;

        -- Check whether we can sign up under this number
        status = pledge_is_valid_to_sign(t_pledge_id, null, t_mobile);

        if status <> ''ok'' then
            -- If we have already signed this, then we should update this
            -- subscription record to point at the existing subscription.
            if status = ''signed'' then
                delete from signers where outgoingsms_id = t_outgoingsms_id;
                select into p id
                    from signers
                    where mobile = t_mobile
                    for update;
                -- XXX repeated code
                update smssubscription
                    set signer_id = p.id, outgoingsms_id = null,
                        pledge_id = null
                    where token = t_token;
            end if;
            return status;
        end if;
        
        t_signer_id := (
            select nextval(''signers_id_seq'')
        );

        -- showname = true here so that they will appear as a "person whose
        -- name we do not know" rather than an anonymous person
        insert into signers (id, pledge_id, mobile, showname, signtime)
            values (t_signer_id, t_pledge_id, t_mobile, true, pb_current_timestamp());

       
        update smssubscription
            set signer_id = t_signer_id, outgoingsms_id = null,
                pledge_id = null
            where token = t_token;

        return ''ok'';
    end;
' language 'plpgsql';

-- Stores randomly generated tokens and serialised hash arrays associated
-- with them.
create table token (
    scope text not null,        -- what bit of code is using this token
    token text not null,
    data bytea not null,
    created timestamp not null,
    primary key (scope, token)
);

-- If a row is present, that is date which is "today".  Used for debugging
-- to advance time without having to wait.
create table debugdate (
    override_today date
);

-- Returns the date of "today", which can be overriden for testing.
create function pb_current_date()
    returns date as '
    declare
        today date;
    begin
        today = (select override_today from debugdate);
        if today is not null then
           return today;
        else
           return current_date;
        end if;

    end;
' language 'plpgsql';

-- Returns the timestamp of current time, but with possibly overriden "today".
create function pb_current_timestamp()
    returns timestamp as '
    declare
        today date;
    begin
        today = (select override_today from debugdate);
        if today is not null then
           return today + current_time;
        else
           return current_timestamp;
        end if;
    end;
' language 'plpgsql';




