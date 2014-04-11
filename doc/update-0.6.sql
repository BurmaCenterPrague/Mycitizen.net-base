-- mycitizen.net version 0.6 to 0.7

-- drop `user_friend`.`user_friend_access_level`
-- drop `resource`.`resource_trash`

UPDATE `system` SET `value` = '0.7' WHERE `name` = 'database_version' ;