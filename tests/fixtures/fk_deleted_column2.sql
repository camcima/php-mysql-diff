-- Host: 127.0.0.1    Database: test_2

DROP TABLE IF EXISTS `table_1`;
CREATE TABLE `table_1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `foreignId2` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_table_1_table_2` (`foreignId2`),
  CONSTRAINT `FK_table_1_table_2` FOREIGN KEY (`foreignId2`) REFERENCES `table_2` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='.';


DROP TABLE IF EXISTS `table_2`;
CREATE TABLE `table_2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='.';
