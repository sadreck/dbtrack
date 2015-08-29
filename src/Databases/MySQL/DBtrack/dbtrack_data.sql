DROP TABLE IF EXISTS `dbtrack_data`;
CREATE TABLE `dbtrack_data` (
  `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
  `actionid` BIGINT(10) NOT NULL DEFAULT 0,
  `columnname` VARCHAR(255) NOT NULL DEFAULT '',
  `databefore` LONGTEXT,
  `dataafter` LONGTEXT,
  PRIMARY KEY (`id`),
  INDEX `ix_actionid` (`actionid` ASC),
  INDEX `ix_columnname` (`columnname` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;