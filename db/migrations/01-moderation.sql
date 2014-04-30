alter table pledges add column ishidden boolean not null default false; -- hidden from view
alter table pledges add column moderated_time timestamp null;
alter table pledges add column moderated_by integer null references person(id);
alter table pledges add column moderated_comment text null;

-- unsure we actually need all these indexes, but other similar fields
-- are indexed in this way in schema.sql
create index pledges_ishidden_idx on pledges(ishidden);
create index pledges_moderated_by_idx on pledges(moderated_by);
create index pledges_moderated_time on pledges(moderated_time);

alter table comment add column moderated_time timestamp null;
alter table comment add column moderated_by integer null references person(id);
alter table comment add column moderated_comment text null;

create index comment_moderated_by_idx on comment(moderated_by);
create index comment_moderated_time on comment(moderated_time);
