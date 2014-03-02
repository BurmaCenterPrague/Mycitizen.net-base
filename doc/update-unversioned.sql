-- mycitizen.net version unversioned to 0.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `visibility_level` ;


CREATE TABLE IF NOT EXISTS `system` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL,
  `value` varchar(256) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `system` (`id`, `name`, `value`) VALUES
(1, 'cron_last_run', '0'),
(2, 'database_version', '0.3.1');


INSERT INTO `settings` (`variable_name`, `variable_value`, `variable_display_label`) VALUES
('maintenance_mode', '0', 'Time when maintenance mode begins (0 or timestamp or "in x minutes")'),
('object_creation_min_time', '7', 'Time (in days) before users are allowed to create groups and resources'),
('signup_answer', '', 'Correct answer for signup'),
('signup_question', '', 'Question that needs to be answered for signup'),
('sign_in_disabled', '0', 'Sign in disabled (0 or 1)'),
('sign_up_disabled', '0', 'Sign up disabled (0 or 1)');


CREATE TABLE IF NOT EXISTS `activity` (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(16) NOT NULL,
  `activity` int(2) NOT NULL,
  `object_type` int(2) NOT NULL,
  `object_id` int(11) NOT NULL,
  `affected_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`activity_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
