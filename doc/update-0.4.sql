-- mycitizen.net version 0.4 to 0.5

ALTER TABLE  `user` ADD  `user_captcha_ok` INT( 1 ) NOT NULL AFTER  `user_registration_confirmed` ;

UPDATE `system` SET `value` = '0.5' WHERE `name` = 'database_version' ;