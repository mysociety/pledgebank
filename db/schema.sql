--
-- schema.sql:
-- Schema for PledgeBank database.
--
-- Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
-- Email: francis@mysociety.org; WWW: http://www.mysociety.org/
--
-- $Id: schema.sql,v 1.6 2005-02-28 09:47:52 chris Exp $
--

create table pledges (
  id serial not null primary key,
  -- short name of pledge for URLs
  ref varchar(20) not null,

  -- summary of pledge
  title varchar(255) not null,
  -- number of type ("people" etc.) to reach
  target varchar(50) not null,
  type varchar(50) not null,
  -- display verb for joining the pledge
  signup varchar(100) not null default 'sign up',
  -- target deadline, midnight at end of this day
  date date not null,
  -- extra detail by pledge setter
  detail text not null default '',

  -- pledge setter
  name varchar(50) not null,
  email varchar(100) not null,
  -- metadata
  creationtime timestamp not null,
  -- cancelled 
  open int not null default 1,

  -- confirmation stuff
  token varchar(50) not null,
  confirmed int not null,

  -- password for private pledges
  password varchar(50) not null default '',

  -- "at least" vs. "exactly"
  comparison boolean not null default false
);

create table signers (
  id serial not null primary key,
  pledge_id int not null,

  -- who has signed the pledge
  signname varchar(50) not null,
  signemail varchar(100) not null,
  -- whether they want their name public
  showname int not null,
  -- when they signed
  signtime timestamp not null,
  -- confirmation stuff
  token varchar(50) not null,
  confirmed int not null
);

