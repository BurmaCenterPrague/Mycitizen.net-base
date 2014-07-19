-- mycitizen.net version 0.11

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `access_level` (
  `access_level_id` int(11) NOT NULL AUTO_INCREMENT,
  `access_level_name` varchar(255) NOT NULL,
  `access_level_description` text NOT NULL,
  PRIMARY KEY (`access_level_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `access_level` (`access_level_id`, `access_level_name`, `access_level_description`) VALUES
(1, 'normal', 'Basic user'),
(2, 'moderator', 'Basic user with privileges to moderate'),
(3, 'administrator', 'Administrator with privileges to moderate and change settings');

CREATE TABLE IF NOT EXISTS `activity` (
  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(16) NOT NULL,
  `activity` int(2) NOT NULL,
  `object_type` int(2) NOT NULL,
  `object_id` int(11) NOT NULL,
  `affected_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`activity_id`),
  KEY `timestamp` (`timestamp`,`object_type`,`object_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `cron` (
  `cron_id` int(11) NOT NULL AUTO_INCREMENT,
  `time` int(16) NOT NULL DEFAULT '0',
  `executed_time` int(16) NOT NULL DEFAULT '0',
  `recipient_type` int(2) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `text` text,
  `object_type` int(2) NOT NULL,
  `object_id` int(11) NOT NULL,
  PRIMARY KEY (`cron_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `failed_logins` (
  `failed_logins_id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` bigint(20) NOT NULL,
  `event` int(2) NOT NULL,
  `time` int(16) NOT NULL,
  PRIMARY KEY (`failed_logins_id`),
  KEY `event` (`event`),
  KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `group` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_author` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `group_description` text NOT NULL,
  `group_language` int(2) NOT NULL DEFAULT '1',
  `group_visibility_level` int(2) NOT NULL DEFAULT '1',
  `group_access_level` int(2) NOT NULL DEFAULT '1',
  `group_logo` blob,
  `group_status` int(2) NOT NULL DEFAULT '1',
  `group_viewed` int(11) NOT NULL DEFAULT '0',
  `group_position_x` double DEFAULT NULL,
  `group_position_y` double DEFAULT NULL,
  `group_last_activity` timestamp NULL DEFAULT NULL,
  `group_portrait` longblob,
  `group_largeicon` longblob,
  `group_icon` longblob,
  `group_hash` varchar(16) NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `status` (`group_status`),
  KEY `visibility` (`group_visibility_level`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `group_tag` (
  `group_tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`group_tag_id`),
  KEY `ids` (`tag_id`,`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `group_user` (
  `group_user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `group_user_status` int(1) NOT NULL DEFAULT '1',
  `group_user_access_level` int(2) NOT NULL DEFAULT '1',
  PRIMARY KEY (`group_user_id`),
  KEY `ids-type` (`user_id`,`group_id`,`group_user_status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `language` (
  `language_id` int(11) NOT NULL AUTO_INCREMENT,
  `language_flag` varchar(255) NOT NULL,
  `language_code` varchar(255) NOT NULL,
  `language_name` varchar(255) NOT NULL,
  PRIMARY KEY (`language_id`),
  KEY `flag` (`language_flag`),
  KEY `code` (`language_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

INSERT INTO `language` (`language_id`, `language_flag`, `language_code`, `language_name`) VALUES
(1, 'en_US', 'en', 'English');

CREATE TABLE IF NOT EXISTS `phpsessions` (
  `id` varchar(32) NOT NULL,
  `session_expires` int(10) NOT NULL,
  `session_data` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `resource` (
  `resource_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_parent_id` int(11) DEFAULT NULL,
  `resource_author` int(11) NOT NULL,
  `resource_type` int(2) DEFAULT NULL,
  `resource_name` varchar(255) NOT NULL,
  `resource_description` text NOT NULL,
  `resource_data` text,
  `resource_visibility_level` int(2) NOT NULL DEFAULT '1',
  `resource_language` int(2) NOT NULL DEFAULT '1',
  `resource_status` int(2) NOT NULL DEFAULT '1',
  `resource_viewed` int(11) NOT NULL DEFAULT '0',
  `resource_position_x` double DEFAULT NULL,
  `resource_position_y` double DEFAULT NULL,
  `resource_creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resource_trash` tinyint(1) NOT NULL DEFAULT '0',
  `resource_last_activity` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`resource_id`),
  KEY `status` (`resource_status`),
  KEY `visibility` (`resource_visibility_level`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `resource_tag` (
  `resource_tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  PRIMARY KEY (`resource_tag_id`),
  KEY `ids` (`tag_id`,`resource_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `resource_type` (
  `resource_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_type_group` int(11) NOT NULL,
  `resource_type_name` varchar(255) NOT NULL,
  PRIMARY KEY (`resource_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

INSERT INTO `resource_type` (`resource_type_id`, `resource_type_group`, `resource_type_name`) VALUES
(1, 0, 'message'),
(2, 1, 'event'),
(3, 1, 'organization'),
(4, 1, 'text information'),
(5, 1, 'video/audio'),
(6, 1, 'other'),
(7, 0, 'report'),
(8, 0, 'comment'),
(9, 0, 'system_message');

CREATE TABLE IF NOT EXISTS `resource_user_group` (
  `resource_user_group_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `member_type` int(2) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `resource_user_group_status` int(1) NOT NULL DEFAULT '1',
  `resource_opened_by_user` int(1) NOT NULL DEFAULT '0',
  `resource_user_group_access_level` int(2) NOT NULL DEFAULT '1',
  `resource_trash` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`resource_user_group_id`),
  KEY `ids-type` (`member_id`,`resource_id`,`member_type`, `resource_user_group_status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `settings` (
  `variable_name` varchar(255) NOT NULL,
  `variable_value` varchar(255) NOT NULL,
  `variable_display_label` varchar(255) NOT NULL,
  KEY `variable_name` (`variable_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `settings` (`variable_name`, `variable_value`, `variable_display_label`) VALUES
('from_email', '', 'Address where system emails come from'),
('gps_default_latitude', '', 'GPS default latitude'),
('gps_default_longitude', '', 'GPS default longitude'),
('maintenance_mode', '0', 'Time when maintenance mode begins (0 or timestamp or "in x minutes")'),
('object_creation_min_time', '7', 'Time (in days) before users are allowed to create groups and resources'),
('signup_answer', '', 'Correct answer for signup'),
('signup_question', '', 'Question that needs to be answered for signup'),
('sign_in_disabled', '0', 'Sign in disabled (0 or 1)'),
('sign_up_disabled', '0', 'Sign up disabled (0 or 1)'),
('ip_max_failed_logins', '5', 'Tolerated number of failed logins from the same IP address within one hour.'),
('ip_blocking_time_hours', '1', 'How long to block an IP after too many failed logins'),
('ip_failure_time_minutes', '10', 'Time in minutes during which failed logins are counted.');

CREATE TABLE IF NOT EXISTS `status` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `status_name` varchar(255) NOT NULL,
  `status_description` text NOT NULL,
  PRIMARY KEY (`status_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

INSERT INTO `status` (`status_id`, `status_name`, `status_description`) VALUES
(1, 'active', 'User/Group/Resource is active'),
(2, 'deactivated', 'User/Group/Resource is not active'),
(3, 'banned', 'User/Group/Resource was deactivated forcefully by administrator');

CREATE TABLE IF NOT EXISTS `system` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL,
  `value` varchar(256) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

INSERT INTO `system` (`id`, `name`, `value`) VALUES
(1, 'cron_last_run', '0'),
(2, 'database_version', '0.11');

CREATE TABLE IF NOT EXISTS `tag` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(255) NOT NULL,
  `tag_position` int(11) NOT NULL,
  `tag_parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`tag_id`),
  KEY `parent` (`tag_parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_login` varchar(255) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_surname` varchar(255) NOT NULL,
  `user_password` varchar(255) NOT NULL,
  `user_description` text NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_phone` varchar(255) NOT NULL,
  `user_phone_imei` varchar(255) DEFAULT NULL,
  `user_language` int(2) NOT NULL DEFAULT '1',
  `user_visibility_level` int(2) NOT NULL DEFAULT '1',
  `user_access_level` int(2) NOT NULL DEFAULT '1',
  `user_creation_rights` int(2) DEFAULT '1',
  `user_portrait` longblob,
  `user_largeicon` longblob,
  `user_icon` longblob,
  `user_status` int(2) NOT NULL DEFAULT '0',
  `user_viewed` int(11) NOT NULL DEFAULT '0',
  `user_hash` varchar(8) NOT NULL,
  `user_position_x` double DEFAULT NULL,
  `user_position_y` double DEFAULT NULL,
  `user_registration_confirmed` int(1) NOT NULL DEFAULT '0',
  `user_captcha_ok` INT(1) NOT NULL,
  `user_first_login` int(1) NOT NULL DEFAULT '0',
  `user_email_new` varchar(255) NOT NULL,
  `user_last_activity` timestamp NULL DEFAULT NULL,
  `user_registration` timestamp NULL DEFAULT NULL,
  `user_url` varchar(512) NOT NULL,
  `user_send_notifications` int(2) NOT NULL DEFAULT '24',
  `user_last_notification` int(16) NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `status` (`user_status`),
  KEY `access` (`user_access_level`),
  KEY `visibility` (`user_visibility_level`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `user_friend` (
  `user_friend_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL,
  `user_friend_status` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_friend_id`),
  KEY `id-status` (`friend_id`, `user_friend_status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `user_tag` (
  `user_tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`user_tag_id`),
  KEY `ids` (`tag_id`,`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `visits` (
  `visit_id` int(11) NOT NULL AUTO_INCREMENT,
  `type_id` int(11) NOT NULL,
  `object_id` int(11) NOT NULL,
  `ip_address` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`visit_id`),
  KEY `type_id` (`type_id`,`object_id`,`ip_address`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

