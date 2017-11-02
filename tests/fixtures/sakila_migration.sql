# Disable Foreign Keys Check
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

# Deleted Tables

-- deleted table `test1`

DROP TABLE `test1`;

# Changed Tables

-- changed table `test2`

ALTER TABLE `test2`
  DROP PRIMARY KEY,
  DROP FOREIGN KEY `FK__test1`,
  DROP INDEX `FK__test1`;
ALTER TABLE `test2`
  CHANGE COLUMN `id` `id` int(10) NOT NULL AUTO_INCREMENT FIRST,
  CHANGE COLUMN `fk` `fk` int(10) AFTER `id`,
  CHANGE COLUMN `val` `val` decimal(11,3) NOT NULL AFTER `fk`,
  CHANGE COLUMN `texto` `texto` char(60) NOT NULL DEFAULT 'default' AFTER `val`,
  ADD COLUMN `new_field` int(10) AFTER `datade`,
  ADD PRIMARY KEY (`id`,`new_field`),
  ADD UNIQUE KEY `FK__test1` (`datade`),
  ADD CONSTRAINT `FK__test3` FOREIGN KEY (`fk`) REFERENCES `test3` (`id`),
  ROW_FORMAT=COMPRESSED,
  KEY_BLOCK_SIZE=4,
  COMMENT='test1';

# New Tables

-- new table `test3`

CREATE TABLE `test3` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Disable Foreign Keys Check
SET FOREIGN_KEY_CHECKS = 1;
