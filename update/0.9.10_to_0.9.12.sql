-- First, drop all constraints! (Manually)

-- Now, rename the tables.
RENAME TABLE `forum`  TO `freech_forum`;
RENAME TABLE `group`  TO `freech_group`;
RENAME TABLE `group_permission`  TO `freech_permission`;
RENAME TABLE `message`  TO `freech_message`;
RENAME TABLE `user`  TO `freech_user`;

-- Drop useless tables.
DROP TABLE `permission`;
DROP TABLE `group_user`;

-- Modify the forum table.
ALTER TABLE `freech_forum` CHANGE `active` `is_active` TINYINT( 1 ) UNSIGNED NULL DEFAULT '1';
ALTER TABLE `freech_forum` CHANGE `name` `name` VARCHAR( 50 ) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `freech_forum` CHANGE `ownerid` `owner_id` INT( 11 ) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `freech_forum` DROP INDEX `ownerid`,
                            ADD INDEX `owner_id` ( `owner_id` );

-- Group table.
ALTER TABLE `freech_group` CHANGE `name` `name` VARCHAR( 50 ) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `freech_group` CHANGE `active` `is_active` TINYINT( 1 ) UNSIGNED NULL DEFAULT '1';
ALTER TABLE `freech_group` ADD `is_special` TINYINT( 1 ) UNSIGNED NULL DEFAULT '0' AFTER `name`;
ALTER TABLE `freech_group` ADD UNIQUE (`name`);

-- Permission table.
ALTER TABLE `freech_permission` CHANGE `g_id` `group_id` INT( 11 ) UNSIGNED NOT NULL;
ALTER TABLE `freech_permission` DROP `p_id`;
ALTER TABLE `freech_permission` ADD `name` VARCHAR(50) NOT NULL AFTER `group_id`;
ALTER TABLE `freech_permission` DROP `updated`;
ALTER TABLE `freech_permission` ADD `allow` TINYINT( 1 ) UNSIGNED NULL DEFAULT '0' AFTER `name`;
ALTER TABLE `freech_permission` DROP INDEX `g_id`, ADD UNIQUE `group_id` ( `group_id` , `allow` );
ALTER TABLE `freech_permission` ADD INDEX ( `allow` );

-- Message table.
ALTER TABLE `freech_message` DROP FOREIGN KEY `message_ibfk_1` ;
ALTER TABLE `freech_message` DROP FOREIGN KEY `message_ibfk_2` ;
ALTER TABLE `freech_message` CHANGE `forumid` `forum_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `freech_message` CHANGE `threadid` `thread_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `freech_message` ADD `priority` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `thread_id`;
ALTER TABLE `freech_message` CHANGE `is_parent` `is_parent` INT( 1 ) UNSIGNED NULL DEFAULT '0';
ALTER TABLE `freech_message` CHANGE `path` `path` VARCHAR( 255 ) CHARACTER SET binary NULL DEFAULT NULL;
ALTER TABLE `freech_message` CHANGE `u_id` `user_id` INT( 11 ) UNSIGNED NULL DEFAULT '0';
ALTER TABLE `freech_message` CHANGE `name` `username` VARCHAR( 50 ) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `freech_message` ADD `user_is_special` TINYINT( 1 ) NULL DEFAULT '0' AFTER `username`,
                      ADD `user_icon` VARCHAR( 50 ) NULL AFTER `user_is_special`,
                      ADD `user_icon_name` VARCHAR( 50 ) NULL AFTER `username`;
ALTER TABLE `freech_message` CHANGE `title` `subject` VARCHAR( 100 ) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `freech_message` CHANGE `text` `body` TEXT CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `freech_message` ADD `hash` VARCHAR( 40 ) NOT NULL AFTER `body`;
ALTER TABLE `freech_message` CHANGE `active` `is_active` TINYINT( 1 ) UNSIGNED NULL DEFAULT '1';
ALTER TABLE `freech_message` ADD `ip_hash` VARCHAR( 40 ) NOT NULL AFTER `is_active`;
ALTER TABLE `freech_message` DROP INDEX `forumid`;
ALTER TABLE `freech_message` DROP INDEX `threadid`;
ALTER TABLE `freech_message` DROP INDEX `is_parent`;
ALTER TABLE `freech_message` DROP INDEX `u_id`;
ALTER TABLE `freech_message` ADD INDEX ( `is_parent` );
ALTER TABLE `freech_message` ADD INDEX ( `hash` );
ALTER TABLE `freech_message` ADD INDEX ( `created` );
ALTER TABLE `freech_message` ADD INDEX ( `forum_id` );
ALTER TABLE `freech_message` ADD INDEX ( `thread_id` );
ALTER TABLE `freech_message` ADD INDEX ( `user_id` );
ALTER TABLE `freech_message` ADD INDEX ( `forum_id`, `priority`, `id` );
ALTER TABLE `freech_message` ADD INDEX ( `thread_id`, `path` );

-- User table.
ALTER TABLE `freech_user` ADD `group_id` INT( 11 ) UNSIGNED NOT NULL AFTER `id`;
ALTER TABLE `freech_user` CHANGE `login` `name` VARCHAR( 50 ) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `freech_user` ADD `soundexname` VARCHAR( 5 ) NULL AFTER `name`;
ALTER TABLE `freech_user` CHANGE `password` `password` VARCHAR( 40 ) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `freech_user` CHANGE `firstname` `firstname` VARCHAR( 50 ) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `freech_user` CHANGE `lastname` `lastname` VARCHAR( 50 ) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `freech_user` CHANGE `mail` `mail` VARCHAR( 100 ) CHARACTER SET latin1 COLLATE latin1_general_ci NULL;
ALTER TABLE `freech_user` ADD `public_mail` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `mail`;
ALTER TABLE `freech_user` CHANGE `homepage` `homepage` VARCHAR( 100 ) CHARACTER SET latin1 COLLATE latin1_general_ci NULL;
ALTER TABLE `freech_user` ADD INDEX ( `mail` );
ALTER TABLE `freech_user` ADD INDEX ( `name` );
ALTER TABLE `freech_user` ADD INDEX ( `soundexname` );
ALTER TABLE `freech_user` ADD INDEX ( `group_id` );

-- Visitor table.
CREATE TABLE IF NOT EXISTS `freech_visitor` (
  `id` int(11) NOT NULL auto_increment,
  `ip_hash` varchar(40) collate latin1_general_ci NOT NULL,
  `counter` bigint(20) default NULL,
  `visit` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `counter` (`counter`),
  KEY `visit` (`visit`),
  KEY `ip_hash` (`ip_hash`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Constraints for table `freech_forum`
--
ALTER TABLE `freech_forum`
  ADD CONSTRAINT `0_776` FOREIGN KEY (`owner_id`) REFERENCES `freech_user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `freech_message`
--
ALTER TABLE `freech_message`
  ADD CONSTRAINT `0_778` FOREIGN KEY (`forum_id`) REFERENCES `freech_forum` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `0_779` FOREIGN KEY (`user_id`) REFERENCES `freech_user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `freech_permission`
--
ALTER TABLE `freech_permission`
  ADD CONSTRAINT `freech_permission_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `freech_group` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freech_user`
--
ALTER TABLE `freech_user`
  ADD CONSTRAINT `freech_user_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `freech_group` (`id`) ON DELETE CASCADE;
