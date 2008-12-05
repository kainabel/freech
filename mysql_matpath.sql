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
  `active` tinyint(1) unsigned default '1',
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
  `active` tinyint(1) unsigned default '1',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_message`
--

CREATE TABLE IF NOT EXISTS `freech_message` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `forum_id` int(11) unsigned NOT NULL default '0',
  `thread_id` int(11) unsigned NOT NULL default '0',
  `priority` int(11) NOT NULL default '0',
  `is_parent` tinyint(1) unsigned default '0',
  `n_children` int(11) unsigned default '0',
  `n_descendants` int(11) unsigned default '0',
  `path` varchar(255) character set latin1 collate latin1_bin default NULL,
  `user_id` int(11) unsigned default '0',
  `group_id` int(11) unsigned default NULL,
  `username` varchar(50) collate latin1_general_ci NOT NULL,
  `subject` varchar(100) collate latin1_general_ci NOT NULL,
  `body` text collate latin1_general_ci NOT NULL,
  `hash` varchar(40) collate latin1_general_ci NOT NULL,
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active` tinyint(3) unsigned default '1',
  `ip_hash` varchar(40) collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `is_parent` (`is_parent`),
  KEY `hash` (`hash`),
  KEY `created` (`created`),
  KEY `forum_id` (`forum_id`),
  KEY `thread_id` (`thread_id`),
  KEY `user_id` (`user_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_permission`
--

CREATE TABLE IF NOT EXISTS `freech_permission` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `group_id` int(11) unsigned NOT NULL,
  `name` varchar(50) collate latin1_general_ci NOT NULL,
  `description` varchar(100) collate latin1_general_ci NOT NULL default '',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_user`
--

CREATE TABLE IF NOT EXISTS `freech_user` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `group_id` int(11) unsigned NOT NULL,
  `username` varchar(50) collate latin1_general_ci NOT NULL,
  `soundexusername` varchar(5) collate latin1_general_ci default NULL,
  `password` varchar(40) collate latin1_general_ci NOT NULL,
  `firstname` varchar(50) collate latin1_general_ci NOT NULL,
  `lastname` varchar(50) collate latin1_general_ci NOT NULL,
  `mail` varchar(100) collate latin1_general_ci NOT NULL,
  `public_mail` tinyint(1) NOT NULL default '0',
  `homepage` varchar(100) collate latin1_general_ci default NULL,
  `im` varchar(100) collate latin1_general_ci default NULL,
  `signature` varchar(255) collate latin1_general_ci default NULL,
  `status` int(11) unsigned default '1',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `lastlogin` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `mail` (`mail`),
  UNIQUE KEY `username` (`username`),
  KEY `soundexusername` (`soundexusername`),
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `freech_forum`
--
ALTER TABLE `freech_forum`
  ADD CONSTRAINT `0_776` FOREIGN KEY (`owner_id`) REFERENCES `freech_user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `freech_message`
--
ALTER TABLE `freech_message`
  ADD CONSTRAINT `freech_message_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `freech_group` (`id`) ON DELETE SET NULL,
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

-- Create default groups.
INSERT INTO freech_group (id, name, active, created)
                  VALUES (1, 'admin',      1, NULL);
INSERT INTO freech_group (id, name, active, created)
                  VALUES (2, 'anonymous',  1, NULL);
INSERT INTO freech_group (id, name, active, created)
                  VALUES (3, 'users',      1, NULL);
INSERT INTO freech_group (id, name, active, created)
                  VALUES (4, 'moderators', 1, NULL);

-- Create default users.
INSERT INTO freech_user (id, group_id, username, password, firstname, lastname, mail, created)
                  VALUES (1, 1, 'admin', '', 'admin', 'admin', 'admin', NULL);
INSERT INTO freech_user (id, group_id, username, password, firstname, lastname, mail, created)
                  VALUES (2, 2, 'anonymous', '', 'Anonymous', 'George', '', NULL);

-- Create a default forum.
INSERT INTO freech_forum (name, description, owner_id, created)
                   VALUES ('Forum', 'Default forum', 1, NULL);
