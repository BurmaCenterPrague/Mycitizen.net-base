-- mycitizen.net version 0.3.1 to 0.4

CREATE TABLE IF NOT EXISTS `activity` (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(16) NOT NULL,
  `activity` int(2) NOT NULL,
  `object_type` int(2) NOT NULL,
  `object_id` int(11) NOT NULL,
  `affected_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`activity_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

UPDATE `system` SET `value` = '0.4' WHERE `name` = 'database_version' ;