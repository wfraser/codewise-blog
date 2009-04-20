CREATE TABLE `blogs` (
  `blogid` int(5) unsigned NOT NULL auto_increment,
  `name` varchar(32) NOT NULL default '',
  `email` varchar(63) NOT NULL default '',
  `realname` varchar(63) default NULL,
  `birthday` varchar(10) default NULL,
  `location` varchar(255) default NULL,
  `interests` text,
  `links` text,
  `photo` varchar(255) default NULL,
  `homepage` varchar(255) default NULL,
  `title` varchar(64) NOT NULL default '',
  `password` varchar(32) NOT NULL default '',
  `joindate` int(10) NOT NULL default '0',
  `custom_url` varchar(128) default NULL,
  `skinid` varchar(32) NOT NULL default '',
  `status` enum('validating','active','banned','closed') NOT NULL default 'validating',
  UNIQUE KEY `blogid` (`blogid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 PACK_KEYS=0;

CREATE TABLE `imageverify` (
  `id` int(32) unsigned NOT NULL auto_increment,
  `text` varchar(4) NOT NULL default '',
  `timestamp` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE `replies` (
  `pid` int(8) unsigned NOT NULL auto_increment,
  `tid` int(5) unsigned NOT NULL default '0',
  `blogid` int(5) unsigned NOT NULL default '0',
  `name` varchar(128) NOT NULL default '',
  `tripcode` varchar(22) default NULL,
  `timestamp` int(10) unsigned NOT NULL default '0',
  `link` tinytext,
  `text` text NOT NULL,
  `extra` text,
  UNIQUE KEY `pid` (`pid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 PACK_KEYS=0;

CREATE TABLE `shoutbox` (
  `blogid` int(5) unsigned NOT NULL default '0',
  `name` varchar(128) NOT NULL default '',
  `timestamp` int(10) unsigned NOT NULL default '0',
  `link` tinytext,
  `text` text NOT NULL,
  `extra` text,
  UNIQUE KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `skins` (
  `skinid` varchar(32) NOT NULL default '',
  `blogid` int(5) unsigned NOT NULL default '0',
  `name` text NOT NULL,
  `description` text NOT NULL,
  `css` text,
  `main` text,
  `postcalendar` text,
  `loginform` text,
  `welcomeback` text,
  `topic` text,
  `controlpanel` text,
  `replyform` text,
  `pagelink` text,
  `post` text,
  `subscribeform` text,
  `error` text,
  `shoutbox` text,
  `controlpanel_write` text,
  `controlpanel_settings` text,
  `controlpanel_edit` text,
  `controlpanel_userinfo` text,
  `controlpanel_skin` text,
  `controlpanel_adduser` text,
  `controlpanel_skin_multi` text,
  `register` text,
  `controlpanel_manage` text,
  UNIQUE KEY `skinid` (`skinid`),
  KEY `blogid` (`blogid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `subscriptions` (
  `blogid` int(5) unsigned NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `password` varchar(37) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `topics` (
  `tid` int(5) unsigned NOT NULL auto_increment,
  `blogid` int(5) unsigned NOT NULL default '0',
  `title` varchar(128) character set utf8 NOT NULL,
  `timestamp` int(10) unsigned NOT NULL default '0',
  `text` text character set utf8 collate utf8_unicode_ci NOT NULL,
  `extra` text character set utf8,
  UNIQUE KEY `id` (`tid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 PACK_KEYS=0;
