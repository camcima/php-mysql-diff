CREATE TABLE IF NOT EXISTS `jos_extwebdav_properties` (
  `path` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(120) NOT NULL DEFAULT '',
  `ns` varchar(120) NOT NULL DEFAULT 'DAV:',
  `value` text,
  PRIMARY KEY (`ns`(100),`path`(100),`name`(50)),
  KEY `path` (`path`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;