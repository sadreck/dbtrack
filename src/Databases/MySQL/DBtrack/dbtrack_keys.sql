DROP TABLE IF EXISTS `dbtrack_keys`;
CREATE TABLE `dbtrack_keys` (
  `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
  `actionid` BIGINT(10) NOT NULL DEFAULT 0,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `value` LONGTEXT,
  PRIMARY KEY (`id`),
  INDEX `ix_keyactionid` (`actionid` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;