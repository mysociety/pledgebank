
-- pb_pledge_prominence ID
-- Return the effective prominence of the pledge with the given ID.
create OR REPLACE function pb_pledge_prominence(integer)
    -- Point of the short-circuiting design is to avoid doing the expensive
    -- select count ... when we can. As time goes on (i.e. when the majority
    -- of pledges have prominence 'calculated') this will start to suck a bit
    -- more and we'll have to look in to setting a flag explicitly.
    returns text as '
select case
    when (select ishidden from pledges where id = $1)
        then ''backpage''
    when (select prominence from pledges where id = $1) = ''backpage''
        then ''backpage''
    when (select prominence from pledges where id = $1) = ''frontpage''
        then ''frontpage''
    when (select prominence from pledges where id = $1) = ''normal''
        then ''normal''
    else -- calculated
        pb_pledge_prominence_calculated( (select count(id) from signers where pledge_id = $1)::integer, (select longitude from pledges, location where pledges.location_id = location.id and pledges.id = $1) is not null, (select target from pledges
 where id = $1) )
    end;
' language sql stable;
