CREATE TABLE `city` (
  `city_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `zip` int(5) unsigned zerofill NOT NULL,
  `lat` decimal(8,6) unsigned NOT NULL,
  `lng` decimal(9,6) unsigned NOT NULL,
  `city_name` varchar(90) NOT NULL,
  `country_code` varchar(2) NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`city_id`),
  KEY `idx_city_name` (`city_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;