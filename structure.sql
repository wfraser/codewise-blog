DROP TABLE IF EXISTS blogs;
CREATE TABLE blogs (
  blogid int(5) unsigned NOT NULL auto_increment,
  name varchar(32) NOT NULL default '',
  email varchar(63) NOT NULL default '',
  realname varchar(63) default NULL,
  birthday varchar(10) default NULL,
  location varchar(255) default NULL,
  interests text,
  links text,
  photo varchar(255) default NULL,
  homepage varchar(255) default NULL,
  title varchar(64) NOT NULL default '',
  password varchar(32) NOT NULL default '',
  joindate int(10) NOT NULL default '0',
  custom_url varchar(128) default NULL,
  UNIQUE KEY blogid (blogid,name)
) TYPE=MyISAM;

DROP TABLE IF EXISTS replies;
CREATE TABLE replies (
  pid int(8) unsigned NOT NULL auto_increment,
  tid int(5) unsigned NOT NULL default '0',
  blogid int(5) unsigned NOT NULL default '0',
  name varchar(128) NOT NULL default '',
  tripcode varchar(22) default NULL,
  timestamp int(10) unsigned NOT NULL default '0',
  link tinytext,
  text text NOT NULL,
  extra text,
  UNIQUE KEY pid (pid)
) TYPE=MyISAM PACK_KEYS=0;

DROP TABLE IF EXISTS shoutbox;
CREATE TABLE shoutbox (
  blogid int(5) unsigned NOT NULL default '0',
  name varchar(128) NOT NULL default '',
  timestamp int(10) unsigned NOT NULL default '0',
  link tinytext,
  text text NOT NULL,
  extra text,
  UNIQUE KEY timestamp (timestamp)
) TYPE=MyISAM;

DROP TABLE IF EXISTS skin;
CREATE TABLE skin (
  blogid int(5) unsigned NOT NULL default '0',
  UNIQUE KEY blogid (blogid)
) TYPE=MyISAM;

DROP TABLE IF EXISTS subscriptions;
CREATE TABLE subscriptions (
  blogid int(5) unsigned NOT NULL default '0',
  email varchar(255) NOT NULL default '',
  password varchar(37) NOT NULL default ''
) TYPE=MyISAM;

DROP TABLE IF EXISTS topics;
CREATE TABLE topics (
  tid int(5) unsigned NOT NULL auto_increment,
  blogid int(5) unsigned NOT NULL default '0',
  title varchar(128) NOT NULL default '',
  timestamp int(10) unsigned NOT NULL default '0',
  text text NOT NULL,
  extra text,
  UNIQUE KEY id (tid)
) TYPE=MyISAM PACK_KEYS=0;
