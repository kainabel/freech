--
-- Database: `freech`
--

-- --------------------------------------------------------

--
-- Table structure for table `freech_forum`
--

CREATE TABLE IF NOT EXISTS `freech_forum` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(50) collate latin1_general_ci NOT NULL,
  `description` varchar(255) collate latin1_general_ci NOT NULL default '',
  `is_active` tinyint(1) unsigned default '1',
  `owner_id` int(11) unsigned default NULL,
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `owner_id` (`owner_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_group`
--

CREATE TABLE IF NOT EXISTS `freech_group` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(50) collate latin1_general_ci NOT NULL,
  `is_special` tinyint(1) unsigned default '0',
  `is_active` tinyint(1) unsigned default '1',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_posting`
--

CREATE TABLE IF NOT EXISTS `freech_posting` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `forum_id` int(11) unsigned NOT NULL default '0',
  `thread_id` int(11) unsigned default NULL,
  `priority` int(11) unsigned NOT NULL default '0',
  `is_parent` tinyint(1) unsigned default '0',
  `n_children` int(11) unsigned default '0',
  `n_descendants` int(11) unsigned default '0',
  `path` varbinary(255) default NULL,
  `user_id` int(11) unsigned default '0',
  `username` varchar(50) collate latin1_general_ci NOT NULL,
  `user_is_special` tinyint(1) unsigned default '0',
  `user_icon` varchar(50) collate latin1_general_ci default NULL,
  `user_icon_name` varchar(50) collate latin1_general_ci default NULL,
  `renderer` varchar(10) collate latin1_general_ci NOT NULL,
  `subject` varchar(100) collate latin1_general_ci NOT NULL,
  `body` text collate latin1_general_ci NOT NULL,
  `hash` varchar(40) collate latin1_general_ci NOT NULL,
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `is_active` tinyint(1) unsigned default '1',
  `force_stub` tinyint(1) unsigned default '0',
  `ip_hash` varchar(40) collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `is_parent` (`is_parent`),
  KEY `hash` (`hash`),
  KEY `created` (`created`),
  KEY `forum_id` (`forum_id`),
  KEY `thread_id` (`thread_id`),
  KEY `user_id` (`user_id`),
  KEY `forum_id_2` (`forum_id`,`priority`,`id`),
  KEY `path` (`thread_id`,`path`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_permission`
--

CREATE TABLE IF NOT EXISTS `freech_permission` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `group_id` int(11) unsigned NOT NULL,
  `name` varchar(50) collate latin1_general_ci NOT NULL,
  `allow` tinyint(1) unsigned default '0',
  `created` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `group_id` (`group_id`, `allow`),
  KEY `allow` (`allow`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_user`
--

CREATE TABLE IF NOT EXISTS `freech_user` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `group_id` int(11) unsigned NOT NULL,
  `name` varchar(50) collate latin1_general_ci NOT NULL,
  `soundexname` varchar(5) collate latin1_general_ci default NULL,
  `password` varchar(40) collate latin1_general_ci NOT NULL,
  `firstname` varchar(50) collate latin1_general_ci NOT NULL,
  `lastname` varchar(50) collate latin1_general_ci NOT NULL,
  `mail` varchar(100) collate latin1_general_ci default NULL,
  `public_mail` tinyint(1) NOT NULL default '0',
  `homepage` varchar(100) collate latin1_general_ci default NULL,
  `im` varchar(100) collate latin1_general_ci default NULL,
  `signature` varchar(255) collate latin1_general_ci default NULL,
  `status` int(11) unsigned default '2',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `lastlogin` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `mail` (`mail`),
  UNIQUE KEY `name` (`name`),
  KEY `soundexname` (`soundexname`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_visitor`
--

CREATE TABLE IF NOT EXISTS `freech_visitor` (
  `id` int(11) NOT NULL auto_increment,
  `ip_hash` varchar(40) collate latin1_general_ci NOT NULL,
  `counter` bigint(20) default NULL,
  `visit` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `counter` (`counter`),
  KEY `visit` (`visit`),
  KEY `ip_hash` (`ip_hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_modlog`
--

CREATE TABLE IF NOT EXISTS `freech_modlog` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `moderator_id` int(11) unsigned,
  `moderator_name` varchar(50) collate latin1_general_ci NOT NULL,
  `moderator_group_name` varchar(50) collate latin1_general_ci default NULL,
  `moderator_icon` varchar(50) collate latin1_general_ci default NULL,
  `action` varchar(30) collate latin1_general_ci NOT NULL,
  `reason` text collate latin1_general_ci NOT NULL,
  `created` timestamp NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `moderator_id` (`moderator_id`),
  KEY `created` (`created`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_modlog_attribute`
--

CREATE TABLE IF NOT EXISTS `freech_modlog_attribute` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `modlog_id` int(11) unsigned NOT NULL,
  `attribute_name` varchar(30) collate latin1_general_ci NOT NULL,
  `attribute_type` varchar(10) collate latin1_general_ci NOT NULL,
  `attribute_value` text collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `modlog_id` (`modlog_id`),
  UNIQUE KEY `attribute_name` (`modlog_id`, `attribute_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `freech_poll_option`
--

CREATE TABLE IF NOT EXISTS `freech_poll_option` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `poll_id` int(11) unsigned NOT NULL,
  `name` varchar(50) collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `poll_id` (`poll_id`),
  UNIQUE KEY `poll_id_2` (`poll_id`,`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_poll_vote`
--

CREATE TABLE IF NOT EXISTS `freech_poll_vote` (
  `option_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`option_id`, `user_id`),
  KEY `option_id` (`option_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `freech_forum`
--
ALTER TABLE `freech_forum`
  ADD CONSTRAINT `0_776` FOREIGN KEY (`owner_id`) REFERENCES `freech_user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `freech_posting`
--
ALTER TABLE `freech_posting`
  ADD CONSTRAINT `0_778` FOREIGN KEY (`forum_id`) REFERENCES `freech_forum` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `0_779` FOREIGN KEY (`thread_id`) REFERENCES `freech_posting` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `0_780` FOREIGN KEY (`user_id`) REFERENCES `freech_user` (`id`) ON DELETE SET NULL;

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

--
-- Constraints for table `freech_modlog`
--
ALTER TABLE `freech_modlog`
  ADD CONSTRAINT `freech_modlog_ibfk_1` FOREIGN KEY (`moderator_id`) REFERENCES `freech_user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `freech_modlog_attribute`
--
ALTER TABLE `freech_modlog_attribute`
  ADD CONSTRAINT `freech_modlog_attribute_ibfk_1` FOREIGN KEY (`modlog_id`) REFERENCES `freech_modlog` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freech_poll_option`
--
ALTER TABLE `freech_poll_option`
  ADD CONSTRAINT `freech_poll_option_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `freech_posting` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freech_poll_vote`
--
ALTER TABLE `freech_poll_vote`
  ADD CONSTRAINT `freech_poll_vote_ibfk_1` FOREIGN KEY (`option_id`) REFERENCES `freech_poll_option` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `freech_poll_vote_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `freech_user` (`id`) ON DELETE CASCADE;

-- Create admin group.
INSERT INTO freech_group (id, name, is_special, is_active, created)
                  VALUES (1, 'admin', 1, 1, NULL);
INSERT INTO freech_permission (group_id, name, allow) VALUES (1, 'read',       1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (1, 'write',      1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (1, 'administer', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (1, 'moderate',   1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (1, 'delete',     1);

-- Create anonymous group.
INSERT INTO freech_group (id, name, is_special, is_active, created)
                  VALUES (2, 'anonymous',  1, 1, NULL);
INSERT INTO freech_permission (group_id, name, allow) VALUES (2, 'read', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (2, 'write', 1);

-- Create group for normal users.
INSERT INTO freech_group (id, name, is_special, is_active, created)
                  VALUES (3, 'users', 0, 1, NULL);
INSERT INTO freech_permission (group_id, name, allow) VALUES (3, 'read',  1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (3, 'write', 1);

-- Create group for moderators.
INSERT INTO freech_group (id, name, is_special, is_active, created)
                  VALUES (4, 'moderators', 1, 1, NULL);
INSERT INTO freech_permission (group_id, name, allow) VALUES (4, 'read',     1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (4, 'write',    1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (4, 'moderate', 1);
INSERT INTO freech_permission (group_id, name, allow) VALUES (4, 'delete',   1);

-- Create default users.
INSERT INTO freech_user (id, group_id, status, name, password, firstname, lastname, created)
                  VALUES (1, 2, 1, 'anonymous', '', 'Anonymous', 'George', NULL);

-- Create a default forum.
INSERT INTO freech_forum (name, description, owner_id, created)
                   VALUES ('Forum', 'Default forum', 1, NULL);
