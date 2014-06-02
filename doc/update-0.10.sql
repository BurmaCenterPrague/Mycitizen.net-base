-- mycitizen.net version 0.10 to 0.11

CREATE TABLE IF NOT EXISTS `failed_logins` (
  `failed_logins_id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` bigint(20) NOT NULL,
  `time` int(16) NOT NULL,
  PRIMARY KEY (`ip`),
  UNIQUE KEY `failed_logins_id` (`failed_logins_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `settings` (`variable_name`, `variable_value`, `variable_display_label`) VALUES
('ip_max_failed_logins', '5', 'Tolerated number of failed logins from the same IP address without one hour.'),
('ip_blocking_time_hours', '1', 'How long to block an IP after too many failed logins'),
('ip_failure_time_minutes', '10', 'Time in minutes during which failed logins are counted.');

UPDATE `system` SET `value` = '0.11' WHERE `name` = 'database_version';