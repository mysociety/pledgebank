--
-- schema.sql:
-- Schema for PledgeBank database.
--
-- Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
-- Email: francis@mysociety.org; WWW: http://www.mysociety.org/
--
-- $Id: schema.sql,v 1.125 2005-07-04 22:24:56 francis Exp $
--

-- secret
-- A random secret.
create table secret (
    secret text not null
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

-- categories in which pledges can lie
create table category (
    id serial not null primary key,
    parent_category_id integer references category(id),
    name text not null,
    ican_id integer             -- integer ID shared with iCan
);

create unique index category_ican_id_idx on category(ican_id);

-- users, but call the table person rather than user so we don't have to quote
-- its name in every statement....
create table person (
    id serial not null primary key,
    name text,
    email text not null,
    password text,
    website text,
    numlogins integer not null default 0
);

create unique index person_email_idx on person(email);

-- information about each pledge
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
    -- actual text entered, just in case parse_date() goes wrong
    datetext text not null,
    -- extra detail by pledge setter
    detail text not null default '',
    -- URL of accompanying image
    picture text,

    -- pledge setter
    person_id integer not null references person(id),
    name text not null,
    identity text not null default '',
    -- metadata
    creationtime timestamp not null,

    -- confirmation stuff
    -- XXX now unused because of login mechanism, but there may still be
    -- unconfirmed pledges floating about so it has to remain for a decent
    -- interval.
    token text,
    confirmed boolean not null default true,

    -- PIN for private pledges
    pin text check (pin <> ''),

    -- XXX optionally, add a flag which prohibits signup by SMS?

    -- "at least" vs. "exactly"
    comparison text not null check (
            comparison = 'atleast' 
            -- or comparison = 'exactly' -- exactly is disabled for now, as not clear we need it
        ),

    -- Country. At the moment this is 'UK' for 'GB' and 'Global' for none
    -- specified. Later we should change this to an ISO country code (or
    -- perhaps a list of them?) with null meaning "global".
    -- XXX what about, e.g., pledges which anyone in the EU can sign? Add
    -- regions too?
    country text not null check(country = 'UK' or country = 'Global'),
    -- Postcode or ZIP-code or whatever. Later we will want to check this for
    -- validity wrt the pledge's specific country.
    postcode text check(postcode is null or postcode <> ''),
    -- XXX add place field looked up in hierarchical gazeteer of
    -- countries+cities, for countries where we can't do postcode->coordinates
    -- translation.

    -- Geographical coordinates for pledges where we know them. Use lat/lon in
    -- the WGS84 system so that this still works when we make it possible to
    -- locate pledges in other countries.
    latitude double precision,      -- north-positive, degrees
    longitude double precision,     -- east-positive, degrees
        -- NB use double precision not real since real has probably only six
        -- digits of accuracy or about ~30m over the whole globe. If we're
        -- going to have missile coordinates, let's have *proper* missile
        -- coordinates!

    -- It's possible (hopefully rare) for subscribers to be removed from a
    -- pledge after it's been marked as successful. But once a pledge has
    -- been marked as successful we can't undo that (because success emails
    -- have already been sent out and so forth). So we instead note this here.
    removedsigneraftersuccess boolean not null default false,

    -- Record when a pledge succeeded.
    whensucceeded timestamp,
    -- Cancelled by creator, takes no more signers.  Text (in same format as a 
    -- comment) is displayed at the top of the pledge page, and the pledge 
    -- counts as "finished".
    cancelled text,

    -- Which lists of pledges this one is shown in
    prominence text not null default 'normal' check (
        prominence = 'normal' or        -- default
        prominence = 'frontpage' or     -- pledge appears on front page
        prominence = 'backpage'         -- pledge doesn't appear in "all pledges" list, RSS etc.
    ),

    check ((latitude is null and longitude is null)
            or (latitude is not null and longitude is not null)),
    check (latitude is null or (latitude >= -90 and latitude <= +90)),
    check (longitude is null or (longitude >= -180 and longitude < 180))
);

-- Make connections-finding faster.
create index pledges_person_id_idx on pledges(person_id);

-- Make finding recently successful pledges faster.
create index pledges_whensucceeded_idx on pledges(whensucceeded);

-- Speculative that this will help, as we often look up confirmed
create index pledges_confirmed_idx on pledges(confirmed);

-- 
-- Geographical stuff
-- 
create index pledges_latitude_idx on pledges(latitude);
create index pledges_longitude_idx on pledges(longitude);

-- angle_between A1 A2
-- Given two angles A1 and A2 on a circle expressed in radians, return the
-- smallest angle between them.
create function angle_between(double precision, double precision)
    returns double precision as '
select case
    when abs($1 - $2) > pi() then 2 * pi() - abs($1 - $2)
    else abs($1 - $2)
    end;
' language sql;

-- R_e
-- Radius of the earth, in km. This is something like 6372.8 km:
--  http://en.wikipedia.org/wiki/Earth_radius
create function R_e()
    returns double precision as 'select 6372.8::double precision;' language sql;

create type pledge_nearby_match as (
    pledge_id integer,
    distance double precision   -- km
);

-- pledge_find_nearby LATITUDE LONGITUDE DISTANCE
-- Find pledges within DISTANCE (km) of (LATITUDE, LONGITUDE).
create function pledge_find_nearby(double precision, double precision, double precision)
    returns setof pledge_nearby_match as
    -- Write as SQL function so that we don't have to construct a temporary
    -- table or results set in memory. That means we can't check the values of
    -- the parameters, sadly.
    -- Through sheer laziness, just use great-circle distance; that'll be off
    -- by ~0.1%:
    --  http://www.ga.gov.au/nmd/geodesy/datums/distance.jsp
    -- We index pledges on lat/lon so that we can select the pledges which lie
    -- within a wedge of side about 2 * DISTANCE. That cuts down substantially
    -- on the amount of work we have to do.
'
    select id,
            R_e() * acos(
                sin(radians($1)) * sin(radians(latitude))
                + cos(radians($1)) * cos(radians(latitude))
                    * cos(radians($2 - longitude))
            ) as distance
        from pledges
        where
            latitude is not null
            and radians(latitude) > radians($1) - ($3 / R_e())
            and radians(latitude) < radians($1) + ($3 / R_e())
            and (abs(radians($1)) + ($3 / R_e()) > pi() / 2     -- case where search pt is near pole
                    or angle_between(radians(longitude), radians($2))
                            < $3 / (R_e() * cos(radians($1 + $3 / R_e()))))
        order by distance desc
' language sql;

-- index of pledge reference
create table pledge_ref_part (
    pledge_id integer not null references pledges(id),
    refpart char(3) not null,
    count integer not null
);

create index pledge_ref_part_pledge_id_idx on pledge_ref_part(pledge_id);
create index pledge_ref_part_refpart_idx on pledge_ref_part(refpart);

create function index_pledge_ref_parts(integer)
    returns void as '
    declare
        t_pledge_id integer;
        t_ref text;
        t_part text;
        o integer;
    begin
        t_pledge_id = $1;
        -- do not index private pledges 
        if (select pin from pledges where id = t_pledge_id) is not null then
            return;
        end if;
        select into t_ref lower(ref) from pledges where id = t_pledge_id;
        if not found then
            raise exception ''bad pledge ID %'', t_pledge_id;
        end if;
        delete from pledge_ref_part where pledge_id = t_pledge_id;
        for o in 1 .. length(t_ref) - 2 loop
            t_part = substring(t_ref from o for 3);
            update pledge_ref_part
                set count = count + 1
                where pledge_id = t_pledge_id
                    and refpart = t_part;
            if not found then
                insert into pledge_ref_part (pledge_id, refpart, count)
                    values (t_pledge_id, t_part, 1);
            end if;
        end loop;
        return;
    end;
' language 'plpgsql';

create function pledges_ref_index()
    returns trigger as '
    begin
        perform index_pledge_ref_parts(new.id);
        return null;
    end;
' language 'plpgsql';

create trigger pledges_change_trigger after insert or update on pledges
    for each row execute procedure pledges_ref_index();

-- "type" used as return from pledge_find_fuzzily().
create type pledge_ref_fuzzy_match as (
    pledge_id integer,  -- primary key references pledges(id)
    score integer       -- not null
);

-- pledge_find_fuzzily QUERY
-- Given QUERY, a pledge reference which did not exactly match any pledge,
-- return possible matches to that string. This should be used as a table
-- function, i.e. "select * from pledge_find_fuzzily('...')".
create function pledge_find_fuzzily(text)
    returns setof pledge_ref_fuzzy_match as '
    declare
        t_ref text;
        o integer;
        l integer;
        t_part text;
        r record;
        f pledge_ref_fuzzy_match%rowtype;
    begin
        t_ref := $1;

        -- We need a temporary table to accumulate results in. Create it if it
        -- does not exist. (The alternative, dropping it on return from this
        -- function, is no good because PL/PGSQL caches query plans, so it will
        -- get all confused that the table has gone away and been recreated on
        -- the second call to this function.)
        perform relname from pg_class
            where relname = ''pledge_ref_fuzzy_match_tmp'';
        if not found then
            create temporary table pledge_ref_fuzzy_match_tmp (
                pledge_id integer,
                score integer
            );
        end if;

        for o in 1 .. length(t_ref) - 2 loop
            t_part = substring(t_ref from o for 3);
            for r in
                select pledge_id from pledge_ref_part where refpart = t_part
                loop
                update pledge_ref_fuzzy_match_tmp
                    set score = score + 1
                    where pledge_id = r.pledge_id;
                if not found then
                    insert into pledge_ref_fuzzy_match_tmp (pledge_id, score)
                        values (r.pledge_id, 1);
                end if;
            end loop;
        end loop;

        -- now want to return all the rows collected
        for f in
            select pledge_id, score
                from pledge_ref_fuzzy_match_tmp
                order by score desc
            loop
            return next f;
        end loop;

        delete from pledge_ref_fuzzy_match_tmp;

        return;
    end;
' language 'plpgsql';

-- categories of which a pledge is member
create table pledge_category (
    pledge_id integer not null references pledges(id),
    category_id integer not null references category(id)
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
        creator_email text;
    begin
        select into p *
            from pledges
            where pledges.id = $1 
            for update;
        select into creator_email email
            from person
            where person.id = p.person_id;

        if not found then
            return ''none'';
        end if;

        -- check for signed by email (before finished, so repeat sign-ups
        -- by same person give the best message)
        if $2 is not null then
            if $2 = creator_email then
                return ''signed'';
            end if;
            perform signers.id from signers, person
                where pledge_id = $1
                    and signers.person_id = person.id
                    and person.email = $2 for update;
            if found then
                return ''signed'';
            end if;
        end if;

        -- check for signed by mobile
        if $3 is not null then
            perform id from signers where pledge_id = $1 and mobile = $3 for update;
            if found then
                return ''signed'';
            end if;
        end if;

        if p.date < pb_current_date() or p.cancelled is not null then
            return ''finished'';
        end if;
        
        -- Lock the signers table, so that a later insert within this
        -- transaction would succeed.
        -- NOTE that "exactly" is disabled for now, so this code is not used
        -- lock table signers in share mode;
        -- if p.comparison = ''exactly'' then 
        --     if p.target <=
        --         (select count(id) from signers where pledge_id = $1) then
        --         return ''full'';
        --     end if;
        -- end if;

        return ''ok'';
    end;
    ' language 'plpgsql';

-- Connections between pledges (to be computed by a "signers who signed this
-- pledge also signed" scheme).
create table pledge_connection (
    a_pledge_id integer references pledges(id),
    b_pledge_id integer references pledges(id),
    strength real not null,     -- number indicating strength of connection;
                                -- higher is stronger
    check (a_pledge_id < b_pledge_id),
    primary key (a_pledge_id, b_pledge_id)
);

create index pledge_connection_a_pledge_id_idx
    on pledge_connection(a_pledge_id);
create index pledge_connection_b_pledge_id_idx
    on pledge_connection(b_pledge_id);

create table outgoingsms (
    id serial not null primary key,
    -- Recipient, as an international-format number, and text of message.
    recipient text not null,
    -- Message, as UTF-8.
    message text not null,
    -- Whether this is a premium reverse-billed message, or a "bulk" one.
    ispremium boolean not null default false,
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
            or lastsendstatus = 'remoteerror'
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
        ),
    
    -- When we last tried to discover the status of this message.
    laststatuscheck integer
    
    -- XXX add extra fields for billing
);

create unique index outgoingsms_foreignid_idx on outgoingsms(foreignid);
create index outgoingsms_lastsendstatus_idx on outgoingsms(lastsendstatus);
create index outgoingsms_status_idx on outgoingsms(status);
create index outgoingsms_laststatuscheck_idx on outgoingsms(laststatuscheck);

create table incomingsms (
    id serial not null primary key,
    -- Sender's phone number.
    sender text not null,
    -- Receiving number/short code/whatever.
    recipient text not null,
    -- Text of the message, transcoded to UTF-8.
    message text not null,
    -- ID assigned by the deliverer.
    foreignid integer not null,
    -- Network code.
    network integer not null,
    -- When we received the message.
    whenreceived integer not null,
    -- When the message says it was sent.
    whensent integer not null
);

create unique index incomingsms_foreignid_idx on incomingsms(foreignid);

-- This table records gaps in the sequence of foreign IDs for incoming SMS
-- messages. Foreign IDs are allocated sequentially by the sender, but they do
-- not try redelivery of messages which could not be delivered owing to a
-- network outage or whatever. So occasionally gaps will appear in the
-- sequence; we request copies of the "gap" messages every so often to ensure
-- that we're not dropping any. Whenever we receive an incoming SMS with ID
-- N, we delete any gap N and insert any integers between the maximum gap
-- present in the table and N - 1 inclusive, and N + 1. The lastpolled and
-- firstpolled fields are there so that we don't keep polling for a missing
-- message forever or too often.
create table incomingsms_foreignid_gap (
    gap integer not null primary key,
    lastpolled integer,
    firstpolled integer
);

create function incomingsms_foreignid_gap_check()
    returns trigger as '
    declare
        maxgap integer;
    begin
        lock table incomingsms_foreignid_gap in share mode;

        maxgap = (select max(gap) from incomingsms_foreignid_gap);
        if maxgap is null then
            insert into incomingsms_foreignid_gap (gap)
                values (new.foreignid + 1);
        elsif new.foreignid >= maxgap then
            insert into incomingsms_foreignid_gap (gap)
                values (new.foreignid + 1);
            for i in maxgap + 1 .. new.foreignid - 1 loop
                insert into incomingsms_foreignid_gap (gap)
                    values (i);
            end loop;
        end if;

        delete from incomingsms_foreignid_gap where gap = new.foreignid;
        return null;
    end;
' language 'plpgsql';

create trigger incomingsms_insert_trigger after insert on incomingsms
    for each row execute procedure incomingsms_foreignid_gap_check();

create table signers (
    id serial not null primary key,
    pledge_id integer not null references pledges(id),

    -- Who has signed the pledge.
    -- Name and person_id may be null because we allow users to sign up by SMS
    -- without giving their name.
    name text,
    person_id integer references person(id),
    mobile text,

    -- whether they want their name public
    showname boolean not null default false,
      
    -- when they signed
    signtime timestamp not null,
  
    check (
        (name is not null and person_id is not null)
        or (name is null and person_id is null and mobile is not null)
    )
);

create index signers_pledge_id_idx on signers(pledge_id);

-- There may be only one signature on any given pledge from any given mobile
-- phone number.
create unique index signers_pledge_id_mobile_idx on signers(pledge_id, mobile);
-- Ditto emails.
create unique index signers_pledge_id_person_id_idx on signers(pledge_id, person_id);

-- Used to make connection-finding faster.
create index signers_person_id_idx on signers(person_id);

-- signers_combine_2 ID1 ID2
-- Given the IDs ID1 and ID2 of two signers of a pledge, coalesce them into one
-- signer combining the two. One signer should have a name and person ID, and
-- the other a mobile phone number only. The ID of the remaining signer is the
-- ID of the signer with the email address. If the pledge was successful before
-- this combination took place, set the removedsigneraftersuccessflag to record
-- this fact. This function has no return value, and raises an exception if any
-- of its preconditions are not met.
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
        select into p whensucceeded
            from pledges
            where id = t_pledge_id
            for update;

        if (select person_id from signers where id = $1) is not null
            and (select person_id from signers where id = $2) is null then
            id1 := $1;
            id2 := $2;
        elsif (select person_id from signers where id = $1) is null
            and (select person_id from signers where id = $2) is not null then
            id1 := $2;
            id2 := $1;
        else
            raise exception ''exactly one of ID1, ID2 must be a logged-in person'';
        end if;

        t_mobile = (select mobile from signers where id = id2);
        
        delete from smssubscription where signer_id = id2;
        delete from signers where id = id2;

        update signers
            set mobile = t_mobile
            where id = id1;

        if p.whensucceeded is not null then
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
                where token = t_token
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

--
-- Prominence of pledges
-- 

-- pb_pledge_prominence PROMINENCE SIGNERS
-- Return the effective prominence of a pledge having assigned PROMINENCE and
-- the given number of SIGNERS, as follows:
--
--  assigned    number of   effective
--  prominence  signers     prominence
--  ----------- ----------- -----------
--  frontpage   n/a         frontpage
--  backpage    n/a         backpage
--  normal      < 4         backpage
--  normal      >= 4        normal
create function pb_pledge_prominence(text, integer)
    returns text as '
select case
    when $1 = ''frontpage'' then ''frontpage''
    when $1 = ''backpage'' then ''backpage''
    when $2 < 4 then ''backpage''
    else ''normal''
    end;
' language sql;

-- pb_pledge_prominence ID
-- Return the effective prominence of the pledge with the given ID.
create function pb_pledge_prominence(integer)
    -- Point of the short-circuiting design is to avoid doing the expensive
    -- select count ... when we can. As time goes on (i.e. when the majority
    -- of pledges have prominence 'normal') this will start to suck a bit more
    -- and we'll have to look in to setting a flag explicitly.
    returns text as '
select case
    when (select prominence from pledges where id = $1) = ''backpage''
        then ''backpage''
    when (select prominence from pledges where id = $1) = ''frontpage''
        then ''frontpage''
    else
        pb_pledge_prominence((select prominence from pledges where id = $1), (select count(id) from signers where pledge_id = $1)::integer)
    end;
' language sql;


-- Stores randomly generated tokens and serialised hash arrays associated
-- with them.
create table token (
    scope text not null,        -- what bit of code is using this token
    token text not null,
    data bytea not null,
    created timestamp not null,
    primary key (scope, token)
);

-- Messages (email or SMS) sent to pledge creators and/or signers.  This is
-- used with message_creator_recipient and message_signer_recipient to make
-- sure that messages are sent exactly once. It is also used to keep 
-- announcement messages to be sent to late signers.
create table message (
    id serial not null primary key,
    pledge_id integer not null references pledges(id),
    circumstance text not null,
    circumstance_count int not null default 0,
    whencreated timestamp not null default pb_current_timestamp(),
    fromaddress text not null default 'pledgebank'
        check (fromaddress in ('pledgebank', 'creator')),

    -- who should receive it
    sendtocreator boolean not null,
    sendtosigners boolean not null,
    sendtolatesigners boolean not null,

    -- content of message
    emailtemplatename text,
    emailsubject text,
    emailbody text,
    sms text,

    check (
        -- SMS-only message
        (emailtemplatename is null
            and emailsubject is null and emailbody is null
            and sms is not null)
        -- Raw email message
        or (emailbody is not null and emailsubject is not null
            and emailtemplatename is null)
        -- Templated email message
        or (emailtemplatename is not null
            and emailsubject is null and emailbody is null)
    ),
    -- We can only send to signers by sms
    check (sms is null or sendtosigners)
);

create unique index message_pledge_id_circumstance_idx on message(pledge_id, circumstance, circumstance_count);

-- To whom have messages been sent?
create table message_creator_recipient (
    message_id integer not null references message(id),
    pledge_id integer not null references pledges(id)
);

create unique index message_creator_recipient_message_id_pledge_id_idx
    on message_creator_recipient(message_id, pledge_id);

create table message_signer_recipient (
    message_id integer not null references message(id),
    signer_id integer not null references signers(id)
);

create unique index message_signer_recipient_message_id_signer_id_idx
    on message_signer_recipient(message_id, signer_id);

-- Comments/q&a on pledges.
create table comment (
    id serial not null primary key,
    pledge_id integer not null references pledges(id),

    person_id integer references person(id),
    name text not null,
    -- email is obsolete, from before we forced login for comments
    email text,
    check ((person_id is null and email is not null) or
           (person_id is not null and email is null)),

    website text,
    -- add a reply_comment_id here if we ever want threading
    whenposted timestamp not null default pb_current_timestamp(),
    text text not null,                     -- as entered by comment author
    ishidden boolean not null default false -- hidden from view
    -- other fields? one to indicate whether this was written by the pledge
    -- creator and should be highlighted in the display?
);

create index comment_pledge_id_idx on comment(pledge_id);
create index comment_pledge_id_whenposted_idx on comment(pledge_id, whenposted);

-- Alerts and notifications

-- OBSOLETE old place for local alerts
create table local_alert (
    person_id integer references person(id), 
    postcode text not null 
);

create unique index local_alert_person_id_idx on local_alert(person_id);

-- get emailed when various events happen
create table alert (
    id serial not null primary key,
    person_id integer not null references person(id),
    event_code text not null,

    -- ref indicates a pledge reference
    check (
            event_code = 'comments/ref' or    -- new comments on a particular pledge
            event_code = 'pledges/local/GB'   -- new pledge near a particular area
    ),

    -- extra parameters for different types of alert
    pledge_id integer references pledges(id), -- specific pledge for "/ref" event codes
    postcode text, -- postcode for /local/GB event codes

    -- specific location for /local/ event codes, should always keep raw data (e.g.
    -- postcode or city) above in table, these fields are just a cache,
    -- generated and regeneratable from that
    latitude double precision,      -- north-positive, degrees
    longitude double precision,     -- east-positive, degrees

    whensubscribed timestamp not null default pb_current_timestamp()
);

create index alert_person_id_idx on alert(person_id);
create index alert_event_code_idx on alert(event_code);
create index alert_pledge_id_idx on alert(pledge_id);
create unique index alert_unique_idx on alert(person_id, event_code, pledge_id, postcode);

create table alert_sent (
    alert_id integer not null references alert(id),
    
    -- which pledge for event codes "pledges/"
    pledge_id integer references pledges(id),
    -- which comment for event code "/comments"
    comment_id integer references comment(id),

    whenqueued timestamp not null default pb_current_timestamp()
);

create index alert_sent_alert_id_idx on alert_sent(alert_id);
create index alert_sent_pledge_id_idx on alert_sent(pledge_id);
create index alert_sent_comment_id_idx on alert_sent(comment_id);

-- table of abuse reports on comments, pledges and signers.
create table abusereport (
    id serial not null primary key,
    what_id integer not null,
    what text not null check (
        what = 'comment' or what = 'pledge' or what = 'signer'
    ),
    reason text,
    whenreported timestamp not null default pb_current_timestamp(),
    ipaddr text
);

create index abusereport_what_id_idx on abusereport(what_id);
create index abusereport_what_idx on abusereport(what);

create function abusereport_id_check()
    returns trigger as '
    begin
        -- cannot use execute for a select in this version so go through each
        -- case manually
        if new.what = ''comment'' then
            perform id from comment where id = new.what_id;
        elsif new.what = ''pledge'' then
            perform id from pledges where id = new.what_id;
        elsif new.what = ''signer'' then
            perform id from signers where id = new.what_id;
        else
            raise exception ''attempt to insert with unknown value of "%" for what'', new.what;
        end if;
        if not found then
            raise exception ''attempt to insert with invalid what_id % for "%"'', new.what_id, new.what;
        end if;
        return new;
    end;
' language 'plpgsql';

create trigger abusereport_insert_trigger before insert on abusereport
    for each row execute procedure abusereport_id_check();

create table requeststash (
    key char(8) not null primary key,
    whensaved timestamp not null default pb_current_timestamp(),
    method text not null default 'GET' check (
            method = 'GET' or method = 'POST'
        ),
    url text not null,
    -- contents of POSTed form
    post_data bytea check (
            (post_data is null and method = 'GET') or
            (post_data is not null and method = 'POST')
        ),
    extra text
);

create function pb_delete_pledge(integer)
    returns void as '
    begin
        delete from abusereport where what_id = $1 and what = ''pledge'';
        -- messages
        delete from message_signer_recipient
            where signer_id in (select id from signers where pledge_id = $1);
        delete from message_creator_recipient where pledge_id = $1;
        delete from message where pledge_id = $1;
        -- signers etc.
        delete from smssubscription where pledge_id = $1;
        delete from smssubscription
            where signer_id in (select id from signers where pledge_id = $1);
        delete from signers where pledge_id = $1;
        -- comments
        delete from comment where pledge_id = $1;
        -- pledge connections
        delete from pledge_connection where a_pledge_id = $1 or b_pledge_id = $1;
        -- reference parts
        delete from pledge_ref_part where pledge_id = $1;
        -- categories
        delete from pledge_category where pledge_id = $1;
        -- alerts
        delete from alert_sent where pledge_id = $1;
        delete from alert where pledge_id = $1;
        -- the pledge itself
        delete from pledges where id = $1;
        return;
    end
' language 'plpgsql';

create function pb_delete_signer(integer)
    returns void as '
    begin
        delete from abusereport where what_id = $1 and what = ''signer'';
        delete from message_signer_recipient where signer_id = $1;
        delete from smssubscription where signer_id = $1;
        delete from alert_sent where signer_id = $1;
        delete from signers where id = $1;
        return;
    end
' language 'plpgsql';

create function pb_delete_comment(integer)
    returns void as '
    begin
        delete from abusereport where what_id = $1 and what = ''comment'';
        delete from alert_sent where comment_id = $1;
        delete from comment where id = $1;
        return;
    end
' language 'plpgsql';





