DROP TABLE IF EXISTS `dbtrack_config`;
CREATE TABLE `dbtrack_config` (
  `name` VARCHAR(255) NOT NULL,
  `value` TEXT NULL,
  PRIMARY KEY (`name`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `dbtrack_config` (`name`, `value`) VALUES('version', '{%VERSION%}');