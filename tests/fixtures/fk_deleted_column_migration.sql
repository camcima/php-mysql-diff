# Disable Foreign Keys Check
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

# Deleted Tables

# Changed Tables

-- changed table `table_1`

ALTER TABLE `table_1`
  DROP FOREIGN KEY `FK_table_1_table_2`,
  DROP INDEX `FK_table_1_table_2`,
  DROP COLUMN `foreignId`;
ALTER TABLE `table_1`
  ADD COLUMN `foreignId2` int(11) DEFAULT NULL AFTER `id`,
  ADD KEY `FK_table_1_table_2` (`foreignId2`),
  ADD CONSTRAINT `FK_table_1_table_2` FOREIGN KEY (`foreignId2`) REFERENCES `table_2` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

# New Tables

# Disable Foreign Keys Check
SET FOREIGN_KEY_CHECKS = 1;
