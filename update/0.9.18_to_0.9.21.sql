------------------------------------------------------------------------
------------------------------------------------------------------------
-- Obligatory part: Some structural changes.
------------------------------------------------------------------------
------------------------------------------------------------------------

-- Add table for plugin rating

CREATE TABLE IF NOT EXISTS `freech_user_rating` (
 `forum_id` int(11) unsigned NOT NULL,
 `posting_id` int(11) unsigned NOT NULL,
 `user_id` int(11) unsigned NOT NULL,
 `rating` tinyint NOT NULL,
 PRIMARY KEY (`forum_id`, `posting_id`, `user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Insert two cols in freech_posting

ALTER TABLE `freech_posting` ADD `rating` tinyint(4);
ALTER TABLE `freech_posting` ADD `rating_count` int(11) unsigned NOT
NULL DEFAULT 0;

-- Update the version number in freech_info

UPDATE `freech_info` SET value = '0.9.21' WHERE name = 'version';


------------------------------------------------------------------------
------------------------------------------------------------------------
-- Optional part: The rights of groups can be also adjusted by hand.
----------------- SQL is currently commented out.
------------------------------------------------------------------------
------------------------------------------------------------------------

-- Reset the table freech_permission to defaults and add some new persissons.

/*

DROP TABLE IF EXISTS `freech_permission`;
CREATE TABLE IF NOT EXISTS `freech_permission` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `group_id` int(11) unsigned NOT NULL,
  `name` varchar(50) collate latin1_general_ci NOT NULL,
  `allow` tinyint(1) unsigned default '0',
  `created` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `group_id_2` (`group_id`, `name`),
  KEY `group_id` (`group_id`, `allow`),
  KEY `allow` (`allow`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

ALTER TABLE `freech_permission`
  ADD CONSTRAINT `freech_permission_group_id` FOREIGN KEY (`group_id`)
    REFERENCES `freech_group` (`id`) ON DELETE CASCADE;

INSERT INTO freech_permission (group_id, name, allow) VALUES (1, 'read', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (1, 'write', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (1, 'administer', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (1, 'moderate', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (1, 'delete', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (1, 'bypass', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (1, 'unlock', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (1, 'write_ro', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (2, 'read', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (2, 'write', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (3, 'read', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (3, 'write', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (4, 'read', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (4, 'write', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (4, 'moderate', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (4, 'delete', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (4, 'bypass', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (4, 'unlock', 1);

*/
