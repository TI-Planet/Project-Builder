CREATE TABLE IF NOT EXISTS `pb_projects` (
`id` UNSIGNED INTEGER PRIMARY KEY NOT NULL,
  `randkey` varchar(10) NOT NULL,
  `author` UNSIGNED INTEGER NOT NULL,
  `type` varchar(20) NOT NULL, -- enum('basic_z80','native_z80','native_eZ80','lua_nspire','sprite','var_z80','numworks_os')
  `name` varchar(30) NOT NULL,
  `internal_name` varchar(30) NOT NULL,
  `multiuser` tinyint(1) NOT NULL DEFAULT '0',
  `multi_readwrite` tinyint(1) NOT NULL DEFAULT '0',
  `multi_rw_custom` tinyint(1) NOT NULL DEFAULT '0',
  `chat_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `created` UNSIGNED INTEGER NOT NULL,
  `updated` UNSIGNED INTEGER NOT NULL,
  `fork_of` UNSIGNED INTEGER DEFAULT NULL REFERENCES `pb_projects`,
  `deleted` UNSIGNED INTEGER DEFAULT NULL
);
CREATE UNIQUE INDEX pb_projects_randkey_idx ON `pb_projects`(`author`,`created`,`randkey`);
CREATE INDEX pb_projects_author_idx ON `pb_projects`(`author`);
CREATE INDEX pb_projects_type_idx ON `pb_projects`(`type`);
CREATE INDEX pb_projects_fork_of_idx ON `pb_projects`(`fork_of`);
CREATE INDEX pb_projects_deleted_idx ON `pb_projects`(`deleted`);

CREATE TABLE IF NOT EXISTS `pb_project_rw_acl` (
`proj_id` UNSIGNED INTEGER NOT NULL REFERENCES `pb_projects`(`id`) ON DELETE CASCADE,
  `user_id` UNSIGNED INTEGER NOT NULL,
  PRIMARY KEY (`proj_id`, `user_id`)
);
CREATE INDEX pb_project_rw_acl_uid_idx ON `pb_project_rw_acl`(`user_id`);

CREATE TABLE IF NOT EXISTS `pb_tokens` (
`id` UNSIGNED INTEGER PRIMARY KEY NOT NULL,
  `user_id` int(11) UNIQUE NOT NULL,
  `token` varchar(1030) NOT NULL,
  `created` varchar(10) NOT NULL,
  `expires` varchar(10) NOT NULL
);
CREATE INDEX pb_tokens_uid_idx ON `pb_tokens`(`user_id`);

CREATE TABLE IF NOT EXISTS `pb_logs` (
`id` UNSIGNED INTEGER PRIMARY KEY NOT NULL,
  `user_id` UNSIGNED INTEGER NOT NULL,
  `proj_id` UNSIGNED INTEGER NOT NULL REFERENCES `pb_projects`,
  `action` varchar(25) NOT NULL,
  `params` varchar(50) DEFAULT NULL,
  `ok` tinyint(1) NOT NULL,
  `tstamp` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX pb_logs_uid_idx ON `pb_logs`(`user_id`);
CREATE INDEX pb_logs_pid_idx ON `pb_logs`(`proj_id`);
CREATE INDEX pb_logs_tstamp_idx ON `pb_logs`(`tstamp`);
