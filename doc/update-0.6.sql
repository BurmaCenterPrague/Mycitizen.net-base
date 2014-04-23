-- mycitizen.net version 0.6 to 0.7

ALTER TABLE `user_friend` DROP `user_friend_access_level`;
ALTER TABLE `resource`
  DROP `resource_owner`,
  DROP `resource_icon`;
ALTER TABLE  `activity` ADD INDEX (  `timestamp` ,  `object_type` ,  `object_id` );

UPDATE `system` SET `value` = '0.7' WHERE `name` = 'database_version';