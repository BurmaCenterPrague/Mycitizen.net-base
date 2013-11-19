-- Adminer 3.3.3 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `access_level`;
CREATE TABLE `access_level` (
  `access_level_id` int(11) NOT NULL AUTO_INCREMENT,
  `access_level_name` varchar(255) NOT NULL,
  `access_level_description` text NOT NULL,
  PRIMARY KEY (`access_level_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

TRUNCATE `access_level`;
INSERT INTO `access_level` (`access_level_id`, `access_level_name`, `access_level_description`) VALUES
(1,	'normal',	'Basic user with privileges to his own account'),
(2,	'moderator',	'Basic user with privileges to group'),
(3,	'administrator',	'Administrator with privileges to other users accouts');

DROP TABLE IF EXISTS `group`;
CREATE TABLE `group` (
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
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `group_tag`;
CREATE TABLE `group_tag` (
  `group_tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`group_tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `group_user`;
CREATE TABLE `group_user` (
  `group_user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `group_user_status` int(1) NOT NULL DEFAULT '1',
  `group_user_access_level` int(2) NOT NULL DEFAULT '1',
  PRIMARY KEY (`group_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `language`;
CREATE TABLE `language` (
  `language_id` int(11) NOT NULL AUTO_INCREMENT,
  `language_flag` varchar(255) NOT NULL,
  `language_name` varchar(255) NOT NULL,
  PRIMARY KEY (`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

TRUNCATE `language`;
INSERT INTO `language` (`language_id`, `language_flag`, `language_name`) VALUES
(1,	'en',	'English'),
(2,	'my',	'Burmese');

DROP TABLE IF EXISTS `phpsessions`;
CREATE TABLE `phpsessions` (
  `id` varchar(32) NOT NULL,
  `session_expires` int(10) NOT NULL,
  `session_data` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `resource`;
CREATE TABLE `resource` (
  `resource_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_parent_id` int(11) DEFAULT NULL,
  `resource_author` int(11) NOT NULL,
  `resource_type` int(2) DEFAULT NULL,
  `resource_name` varchar(255) NOT NULL,
  `resource_description` text NOT NULL,
  `resource_data` text,
  `resource_owner` int(11) DEFAULT NULL,
  `resource_visibility_level` int(2) NOT NULL DEFAULT '1',
  `resource_language` int(2) NOT NULL DEFAULT '1',
  `resource_icon` blob,
  `resource_status` int(2) NOT NULL DEFAULT '1',
  `resource_viewed` int(11) NOT NULL DEFAULT '0',
  `resource_position_x` double DEFAULT NULL,
  `resource_position_y` double DEFAULT NULL,
  `resource_creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resource_trash` tinyint(1) NOT NULL DEFAULT '0',
  `resource_last_activity` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `resource_tag`;
CREATE TABLE `resource_tag` (
  `resource_tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  PRIMARY KEY (`resource_tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `resource_type`;
CREATE TABLE `resource_type` (
  `resource_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_type_group` int(11) NOT NULL,
  `resource_type_name` varchar(255) NOT NULL,
  PRIMARY KEY (`resource_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `resource_user_group`;
CREATE TABLE `resource_user_group` (
  `resource_user_group_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `member_type` int(2) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `resource_user_group_status` int(1) NOT NULL DEFAULT '1',
  `resource_opened_by_user` int(1) NOT NULL DEFAULT '0',
  `resource_user_group_access_level` int(2) NOT NULL DEFAULT '1',
  `resource_trash` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`resource_user_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `variable_name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `variable_value` varchar(255) COLLATE utf8_czech_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;



DROP TABLE IF EXISTS `status`;
CREATE TABLE `status` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `status_name` varchar(255) NOT NULL,
  `status_description` text NOT NULL,
  PRIMARY KEY (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `tag`;
CREATE TABLE `tag` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(255) NOT NULL,
  `tag_desription` text NOT NULL,
  `tag_parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
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
  `user_status` int(2) NOT NULL DEFAULT '0',
  `user_viewed` int(11) NOT NULL DEFAULT '0',
  `user_hash` varchar(8) NOT NULL,
  `user_position_x` double DEFAULT NULL,
  `user_position_y` double DEFAULT NULL,
  `user_registration_confirmed` int(1) NOT NULL DEFAULT '0',
  `user_first_login` int(1) NOT NULL DEFAULT '0',
  `user_email_new` varchar(255) NOT NULL,
  `user_last_activity` timestamp NULL DEFAULT NULL,
  `user_registration` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `user_friend`;
CREATE TABLE `user_friend` (
  `user_friend_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL,
  `user_friend_status` int(1) NOT NULL DEFAULT '0',
  `user_friend_access_level` int(2) NOT NULL DEFAULT '1',
  PRIMARY KEY (`user_friend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `user_tag`;
CREATE TABLE `user_tag` (
  `user_tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`user_tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `visibility_level`;
CREATE TABLE `visibility_level` (
  `visibility_level_id` int(11) NOT NULL AUTO_INCREMENT,
  `visibility_level_name` varchar(255) NOT NULL,
  `visibility_level_description` text NOT NULL,
  PRIMARY KEY (`visibility_level_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

TRUNCATE `visibility_level`;
INSERT INTO `visibility_level` (`visibility_level_id`, `visibility_level_name`, `visibility_level_description`) VALUES
(1,	'world',	'All users (even unregistered) see posts of this user or group'),
(2,	'registered',	'Only registered users see posts of this user or group'),
(3,	'friends/members',	'Only friends of this user or members of this group see posts');

DROP TABLE IF EXISTS `visits`;
CREATE TABLE `visits` (
  `visit_id` int(11) NOT NULL AUTO_INCREMENT,
  `type_id` int(11) NOT NULL,
  `object_id` int(11) NOT NULL,
  `ip_address` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`visit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


TRUNCATE `status`;
INSERT INTO `status` (`status_id`, `status_name`, `status_description`) VALUES
(1,	'active',	'User/Group/Resource is active'),
(2,	'deactivated',	'User/Group/Resource is not active'),
(3,	'banned',	'User/Group/Resource was banned by administrator');

TRUNCATE `settings`;
INSERT INTO `settings` (`variable_name`, `variable_value`) VALUES
('from_email',	'support@replace-deployment.com'),
('gps_default_latitude',	'50.089133'),
('gps_default_longitude',	'14.419327'),
('object_creation_min_time',	'18000'),
('alert_offset', '3600');

DROP TABLE IF EXISTS `cron`;
CREATE TABLE `cron` (
  `cron_id` int(11) NOT NULL AUTO_INCREMENT,
  `time` timestamp NOT NULL,
  `executed_time` timestamp NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `text` text DEFAULT NULL,
  `object_type` int(2) NOT NULL,
  `object_id` int(11) NOT NULL,
  PRIMARY KEY (`cron_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;