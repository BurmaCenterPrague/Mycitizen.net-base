-- mycitizen.net version 0.12 to 0.13

ALTER TABLE  `settings` ADD UNIQUE (
`variable_name`
);

UPDATE `system` SET `value` = '0.13' WHERE `name` = 'database_version';