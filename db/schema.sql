-- schema.sql:
-- Schema for PledgeBank database.
--
-- Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
-- Email: francis@mysociety.org; WWW: http://www.mysociety.org/
--
-- $Id: schema.sql,v 1.197 2006-07-10 10:20:52 francis Exp $
--

-- LLL - means that field requires storing in potentially multiple languages
--       (for simplicity, for now, names of people have not been marked like
--       this, even though they theoretically should be, e.g. Chinese vs.
--       Western name)

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
create function ms_current_date()
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
create function ms_current_timestamp()
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
    name text not null, -- LLL
    ican_id integer             -- integer ID shared with iCan
);

create unique index category_ican_id_idx on category(ican_id);

-- locations of pledges, centers of local alerts, etc., abstracted out so that
-- we can use a variety of location services.  A location can be either:
-- * A country, with no specific place in that country
-- * A country, with a specific point in that country
-- For specific points a 'latitude', 'longitude' and 'description' are always
-- available.  These are made via 'method' and 'input'; either a postcode or
-- gazetter lookup.
create table location (
    id serial not null primary key,
    -- Information which was presented by the user to identify the location.
    country char(2) not null,       -- ISO country code
    state char(2),                  -- US state
    method text check (method in ('MaPit', 'Gaze')), -- NULL means whole country
    input text,     -- whatever the user gave, whether a postcode or
                    -- a placename or whatever

    -- Geographical coordinates in WGS84.
    latitude double precision,      -- north-positive, degrees
    longitude double precision,     -- east-positive, degrees
        -- NB use double precision not real since real has probably only six
        -- digits of accuracy or about ~30m over the whole globe. If we're
        -- going to have missile coordinates, let's have *proper* missile
        -- coordinates!

    -- Textual description of the location which we can show back to the user.
    -- The country and state is not included in this.  Only present if method is.
    description text, -- LLL

    -- A location can represent either a point or a whole country.
    check (
            (method is null and input is null
                and latitude is null and longitude is null
                and description is null)
            or (method is not null and input is not null
                and latitude is not null and longitude is not null
                and description is not null)
          ),

    -- If coordinates are given they must be valid.
    check (latitude is null or (latitude >= -90 and latitude <= +90)),
    check (longitude is null or (longitude >= -180 and longitude < 180))
);

create index location_latitude_idx on location(latitude);
create index location_longitude_idx on location(longitude);
create index location_country_idx on location(country);

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
    title text not null, -- LLL
    -- number of people to reach
    target integer not null check (target > 0),
    -- type of target
    target_type text not null default 'overall' check (
        target_type = 'overall' or -- one global target
        target_type = 'byarea'     -- target applies separately per local area
    ),
    -- type of person (e.g. "local people")
    type text not null, -- LLL
    -- display verb for joining the pledge
    signup text not null default 'sign up', -- LLL
    -- target deadline, midnight at end of this day
    date date not null,
    -- actual text entered, just in case parse_date() goes wrong
    datetext text not null,
    -- extra detail by pledge setter
    detail text not null default '', -- LLL
    -- URL of accompanying image
    picture text,

    -- pledge creator
    person_id integer not null references person(id),
    name text not null,
    identity text not null default '', -- LLL
    -- metadata
    creationtime timestamp not null,
    -- changes which are not caught by signtime, comment whenposted etc.
    changetime timestamp,

    -- PIN for private pledges
    pin text check (pin <> ''),

    -- XXX optionally, add a flag which prohibits signup by SMS?

    -- "at least" vs. "exactly"
    comparison text not null check (
            comparison = 'atleast' 
            -- or comparison = 'exactly' -- exactly is disabled for now, as not clear we need it
        ),

    -- Human language the pledge is in 
    lang text not null check(length(lang) = 2 or length(lang) = 5),
    -- Where the pledge is. Null means that it is not in any specific location.
    location_id integer references location(id),
    -- Microsite, for example Glastonbury. Null means not a microsite, but main site.
    microsite text,

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
    -- Notice puts an extra text banner at the top of the pledge (like cancelled
    -- but without stopping signups)
    notice text, -- LLL
    -- No longer taking new comments
    closed_for_comments boolean not null default false,

    -- Which lists of pledges this one is shown in.  This value is set by
    -- the administrator, and tested only via pb_pledge_prominence().
    prominence text not null default 'calculated' check (
        prominence = 'calculated' or    -- based on number of signers, see pb_pledge_prominence
        prominence = 'normal' or        -- normal, appears in "all pledges" list
        prominence = 'frontpage' or     -- pledge appears on front page
        prominence = 'backpage'         -- pledge doesn't appear on any index page
    ),

    -- Cached version of computed prominence.
    cached_prominence text not null default 'backpage' check (
        cached_prominence = 'normal' or
        cached_prominence = 'frontpage' or
        cached_prominence = 'backpage'
    )
);

-- Contains an entry for each town for which a pledge with target "by area" has
-- been created
create table byarea_location (
    pledge_id integer not null references pledges(id),
    byarea_location_id integer references location(id),

    whensucceeded timestamp
);

create unique index byarea_location_pledge_id_byarea_location_id_idx on byarea_location(pledge_id, byarea_location_id);

-- Create a trigger to update the last-change-time for the pledge on any
-- update to the table. This should cover manual edits only; anything else
-- (signers, comments, ...) should be covered by pledge_last_change_time or by
-- the individual implementing functions.
create function pledges_update_changetime()
    returns trigger as '
    begin
        new.changetime := pledge_last_change_time(new.id);
        return new;
    end;
' language 'plpgsql';

create trigger pledges_changetime_trigger after update on pledges
    for each row execute procedure pledges_update_changetime();

-- Index by reference.
create unique index pledges_ref_idx on pledges(ref);

-- Make connections-finding faster.
create index pledges_person_id_idx on pledges(person_id);
create index pledges_location_id_idx on pledges(location_id);
create index pledges_date_idx on pledges(date);

-- Make finding recently successful pledges faster.
create index pledges_whensucceeded_idx on pledges(whensucceeded);

-- Prominence.
create index pledges_cached_prominence_idx on pledges(cached_prominence);

-- Logos and so on for pledges
create table picture (
    id serial not null primary key,
    filename text not null,
    data bytea not null,
    uploaded timestamp not null default ms_current_timestamp()
);

-- 
-- Geographical stuff
-- 

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

create type location_nearby_match as (
    location_id integer,
    distance double precision   -- km
);

-- location_find_nearby LATITUDE LONGITUDE DISTANCE
-- Find locations within DISTANCE (km) of (LATITUDE, LONGITUDE).
create function location_find_nearby(double precision, double precision, double precision)
    returns setof location_nearby_match as
    -- Write as SQL function so that we don't have to construct a temporary
    -- table or results set in memory. That means we can't check the values of
    -- the parameters, sadly.
    -- Through sheer laziness, just use great-circle distance; that'll be off
    -- by ~0.1%:
    --  http://www.ga.gov.au/nmd/geodesy/datums/distance.jsp
    -- We index locations on lat/lon so that we can select the locations which lie
    -- within a wedge of side about 2 * DISTANCE. That cuts down substantially
    -- on the amount of work we have to do.
'
    -- trunc due to inaccuracies in floating point arithmetic
    select location.id,
           R_e() * acos(trunc(
                (sin(radians($1)) * sin(radians(latitude))
                + cos(radians($1)) * cos(radians(latitude))
                    * cos(radians($2 - longitude)))::numeric, 14)
            ) as distance
        from location
        where
            longitude is not null and latitude is not null
            and radians(latitude) > radians($1) - ($3 / R_e())
            and radians(latitude) < radians($1) + ($3 / R_e())
            and (abs(radians($1)) + ($3 / R_e()) > pi() / 2     -- case where search pt is near pole
                    or angle_between(radians(longitude), radians($2))
                            < $3 / (R_e() * cos(radians($1 + $3 / R_e()))))
            -- ugly -- unable to use attribute name "distance" here, sadly
            and R_e() * acos(trunc(
                (sin(radians($1)) * sin(radians(latitude))
                + cos(radians($1)) * cos(radians(latitude))
                    * cos(radians($2 - longitude)))::numeric, 14)
                ) < $3
        order by distance desc
' language sql;

create type pledge_nearby_match as (
    pledge_id integer,
    distance double precision   -- km
);

-- pledge_find_nearby LATITUDE LONGITUDE DISTANCE
-- Find pledges within DISTANCE (km) of (LATITUDE, LONGITUDE).
create function pledge_find_nearby(double precision, double precision, double precision)
    returns setof pledge_nearby_match as
'
    select pledges.id, nearby.distance
        from location_find_nearby($1, $2, $3) as nearby, pledges 
        where nearby.location_id = pledges.location_id

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
        delete from pledge_ref_part where pledge_id = t_pledge_id;
        -- do not index private pledges 
        if (select pin from pledges where id = t_pledge_id) is not null then
            return;
        end if;
        -- deliberately NOT pb_pledge_prominence, so mistyped calculated backpage pledges can be found easily
        if (select prominence from pledges where id = t_pledge_id) = ''backpage'' then
            return;
        end if;
        select into t_ref lower(ref) from pledges where id = t_pledge_id;
        if not found then
            raise exception ''bad pledge ID %'', t_pledge_id;
        end if;
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
        -- does not exist. But because the temporary table may have a different
        -- OID on different invocations, that means we need to use execute to
        -- issue each statement.
        perform relname from pg_class
            where relname = ''pledge_ref_fuzzy_match_tmp''
              and case when has_schema_privilege(relnamespace, ''USAGE'')
                    then pg_table_is_visible(oid) else false end;
        if not found then
            create temporary table pledge_ref_fuzzy_match_tmp (
                pledge_id integer primary key,
                score integer
            );
        end if;

        for o in 1 .. length(t_ref) - 2 loop
            t_part = substring(t_ref from o for 3);
            for r in
                select pledge_id from pledge_ref_part where refpart = t_part
                loop
                
                execute
                ''update pledge_ref_fuzzy_match_tmp
                    set score = score + 1
                    where pledge_id = '' || r.pledge_id;

                -- would normally use "if not found", but this does not work
                -- (reliably?) when using execute.
                get diagnostics l = row_count;
                if l = 0 then
                    execute
                    ''insert into pledge_ref_fuzzy_match_tmp (pledge_id, score)
                        values ('' || r.pledge_id || '', 1)'';
               end if;
            end loop;
        end loop;

        -- now want to return all the rows collected
        for f in
            execute
            ''select pledge_id, score
                from pledge_ref_fuzzy_match_tmp
                order by score desc''
            loop
            return next f;
        end loop;

        execute ''delete from pledge_ref_fuzzy_match_tmp'';

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

        if p.date < ms_current_date() or p.cancelled is not null then
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
    message text not null, -- LLL
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

    -- if target_type for the pledge is 'byarea' then this is the id
    -- of the location which the signature is for
    byarea_location_id integer references location(id),
      
    -- when they signed
    signtime timestamp not null,

    -- IP address of browser at time of signing
    ipaddr varchar(15),     -- nullable since added late
  
    check (
        (showname and name is not null and person_id is not null)
        or (not showname and person_id is not null)
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

-- Used to make pledge change time calculation faster.
create index signers_signtime_idx on signers(signtime);

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
            values (t_signer_id, t_pledge_id, t_mobile, true, ms_current_timestamp());

       
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

-- pb_pledge_prominence_calculated SIGNERS LOCAL TARGET
-- Return the effective prominence of a pledge with calculated prominence.  --
-- The given number of SIGNERS and a LOCAL co-ordinate and TARGET target signers
-- as follows:
--
--  number of     effective
--  signers       local       prominence 
--  ------------- ----------- -----------
--  < 3           false       backpage
--  < 1           true        backpage
--  < 2.5% target false       backpage
--  otherwise              normal
create function pb_pledge_prominence_calculated(integer, boolean, integer)
    returns text as '
select case
    when (not $2) and $1 < 3 then ''backpage''
    when $2 and $1 < 1 then ''backpage''
    when $1 < ($3 * 0.025) then ''backpage''
    else ''normal''
    end;
' language sql;

-- pb_pledge_prominence ID
-- Return the effective prominence of the pledge with the given ID.
create function pb_pledge_prominence(integer)
    -- Point of the short-circuiting design is to avoid doing the expensive
    -- select count ... when we can. As time goes on (i.e. when the majority
    -- of pledges have prominence 'calculated') this will start to suck a bit
    -- more and we'll have to look in to setting a flag explicitly.
    returns text as '
select case
    when (select prominence from pledges where id = $1) = ''backpage''
        then ''backpage''
    when (select prominence from pledges where id = $1) = ''frontpage''
        then ''frontpage''
    when (select prominence from pledges where id = $1) = ''normal''
        then ''normal''
    else -- calculated
        pb_pledge_prominence_calculated( (select count(id) from signers where pledge_id = $1)::integer, (select longitude from pledges, location where pledges.location_id = location.id and pledges.id = $1) is not null, (select target from pledges where id = $1) )
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
    whencreated timestamp not null default ms_current_timestamp(),
    fromaddress text not null default 'pledgebank'
        check (fromaddress in ('pledgebank', 'creator')),

    -- who should receive it
    sendtocreator boolean not null,
    sendtosigners boolean not null,
    sendtolatesigners boolean not null,
    -- if set, then messages only go to signers who signed with given location
    -- (this is for byarea type pledges)
    byarea_location_id integer references location(id),

    -- content of message
    emailtemplatename text,
    emailsubject text, -- LLL
    emailbody text, -- LLL
    sms text, -- LLL

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

create unique index message_pledge_id_circumstance_idx on message(pledge_id, byarea_location_id, circumstance, circumstance_count);

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
    whenposted timestamp not null default ms_current_timestamp(),
    text text not null,                     -- as entered by comment author
    ishidden boolean not null default false -- hidden from view
    -- other fields? one to indicate whether this was written by the pledge
    -- creator and should be highlighted in the display?
);

create index comment_pledge_id_idx on comment(pledge_id);
create index comment_pledge_id_whenposted_idx on comment(pledge_id, whenposted);
create index comment_ishidden_idx on comment(ishidden);

-- Used for pledge change time calculation.
create index comment_whenposted_idx on comment(whenposted);

-- Alerts and notifications

-- get emailed when various events happen
create table alert (
    id serial not null primary key,
    person_id integer not null references person(id),
    event_code text not null,

    -- ref indicates a pledge reference
    check (
            event_code = 'comments/ref' or    -- new comments on a particular pledge
            event_code = 'pledges/local'   -- new pledge near a particular area
    ),

    -- extra parameters for different types of alert
    pledge_id integer references pledges(id), -- specific pledge for ".../ref" event codes
    location_id integer references location(id), -- specific location for ".../local" event codes

    whensubscribed timestamp not null default ms_current_timestamp(),
    whendisabled timestamp default null -- set if alert has been turned off
);

create index alert_person_id_idx on alert(person_id);
create index alert_event_code_idx on alert(event_code);
create index alert_pledge_id_idx on alert(pledge_id);
create index alert_whendisabled_idx on alert(whendisabled);
create unique index alert_unique_idx on alert(person_id, event_code, pledge_id, location_id);

create table alert_sent (
    alert_id integer not null references alert(id),
    
    -- which pledge for event codes "pledges/"
    pledge_id integer references pledges(id),
    -- which comment for event code "/comments"
    comment_id integer references comment(id),

    whenqueued timestamp not null default ms_current_timestamp()
);

create index alert_sent_alert_id_idx on alert_sent(alert_id);
create index alert_sent_pledge_id_idx on alert_sent(pledge_id);
create index alert_sent_comment_id_idx on alert_sent(comment_id);
create unique index alert_sent_pledge_unique_idx on alert_sent(alert_id, pledge_id);
create unique index alert_sent_comment_unique_idx on alert_sent(alert_id, comment_id);

create table translator (
    lang text not null,
    email text not null
);

create table requeststash (
    key varchar(16) not null primary key check (length(key) = 8 or length(key) = 16),
    whensaved timestamp not null default ms_current_timestamp(),
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

-- make expiring old requests quite quick
create index requeststash_whensaved_idx on requeststash(whensaved);

-- pb_delete_pledge ID
-- Delete the pledge with the given ID, and all associated content.
create function pb_delete_pledge(integer)
    returns void as '
    begin
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
        -- alerts
        delete from alert_sent where pledge_id = $1;
        delete from alert_sent where
            comment_id in (select id from comment where pledge_id = $1);
        delete from alert where pledge_id = $1;
        -- comments
        delete from comment where pledge_id = $1;
        -- pledge connections
        delete from pledge_connection where a_pledge_id = $1 or b_pledge_id = $1;
        -- reference parts
        delete from pledge_ref_part where pledge_id = $1;
        -- categories
        delete from pledge_category where pledge_id = $1;
        -- the pledge itself
        delete from pledges where id = $1;
        return;
    end
' language 'plpgsql';

-- pb_delete_signer ID
-- Delete the signer with the given ID (note *not* person_id).
create function pb_delete_signer(integer)
    returns void as '
    begin
        delete from message_signer_recipient where signer_id = $1;
        delete from smssubscription where signer_id = $1;
        update pledges set changetime = ms_current_timestamp()
            where id = (select pledge_id from signers where id = $1);
        delete from signers where id = $1;
        return;
    end
' language 'plpgsql';

-- pb_delete_person ID
-- Delete the person with the given ID. Fails if the person has created any
-- pledges.
create function pb_delete_person(integer)
    returns void as '
    begin
        -- comments made by the person
        delete from alert_sent where comment_id in (select id from comment where person_id = $1);
        update pledges set changetime = ms_current_timestamp()
            where id in (select pledge_id from comment where person_id = $1);
        delete from comment where person_id = $1;

        -- alerts set up for the person
        delete from alert_sent where alert_id in (select id from alert where person_id = $1);
        delete from alert where person_id = $1;

        -- pledges the person has signed
        delete from message_signer_recipient where signer_id in (select id from signers where person_id = $1);
        delete from smssubscription where signer_id in (select id from signers where person_id = $1);
        update pledges set changetime = ms_current_timestamp()
            where id in (select pledge_id from signers where person_id = $1);
        delete from signers where person_id = $1;

        -- we deliberately don''t do pledges they''ve made; they should be checked first

        delete from person where id = $1;
        return;
    end
' language 'plpgsql';

-- pb_delete_comment ID
-- Delete the comment with the given ID.
create function pb_delete_comment(integer)
    returns void as '
    begin
        update pledges set changetime = ms_current_timestamp()
            where id = (select pledge_id from comment where id = $1);
        delete from alert_sent where comment_id = $1;
        delete from comment where id = $1;
        return;
    end
' language 'plpgsql';


-- pledge_last_change_time PLEDGE
-- Return the time of the last change to PLEDGE.
create function pledge_last_change_time(integer)
    returns timestamp as '
    declare
        t timestamp;
        t2 timestamp;
    begin
        t := (select creationtime from pledges where id = $1);
        t2 := (select changetime from pledges where id = $1);
        if t2 > t then
            t = t2;
        end if;
        t2 := (select signtime from signers where pledge_id = $1 order by signtime desc limit 1);
        if t2 > t then
            t = t2;
        end if;
        t2 := (select whenposted from comment where pledge_id = $1 order by whenposted desc limit 1);
        if t2 > t then
            t = t2;
        end if;
        return t;
    end;
' language 'plpgsql';


