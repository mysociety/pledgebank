--
-- schema.sql:
-- Schema for PledgeBank database.
--
-- Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
-- Email: francis@mysociety.org; WWW: http://www.mysociety.org/
--
-- $Id: schema.sql,v 1.3 2005-01-28 15:04:09 matthew Exp $
--

create table pledges (
  id serial not null primary key,

  title varchar(255) not null,
  target varchar(50) not null,
  type varchar(50) not null,
  date date not null,
  name varchar(50) not null,
  email varchar(100) not null,
  ref varchar(20) not null,
  token varchar(50) not null,
  confirmed int not null,
  creationtime timestamp not null,
  signup varchar(100) not null default 'sign up'
);

create table signers (
  id serial not null primary key,
  pledge_id int not null,

  signname varchar(50) not null,
  signemail varchar(100) not null,
  showname int not null,
  signtime timestamp not null,
  token varchar(50) not null,
  confirmed int not null
);

