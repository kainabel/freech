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
  `active` tinyint(3) unsigned default '1',
  `owner_id` int(11) unsigned default NULL,
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
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
  `active` tinyint(3) unsigned default '1',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_group_permission`
--

CREATE TABLE IF NOT EXISTS `freech_group_permission` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `group_id` int(11) unsigned default '0',
  `permission_id` int(11) unsigned default '0',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `group_id` (`group_id`),
  KEY `permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_group_user`
--

CREATE TABLE IF NOT EXISTS `freech_group_user` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `group_id` int(11) unsigned default '0',
  `user_id` int(11) unsigned default '0',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `group_id` (`group_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_message`
--

CREATE TABLE IF NOT EXISTS `freech_message` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `forum_id` int(11) unsigned NOT NULL default '0',
  `thread_id` int(11) unsigned NOT NULL default '0',
  `priority` int(11) NOT NULL default '0',
  `is_parent` tinyint(1) default '0',
  `n_children` int(11) unsigned default '0',
  `n_descendants` int(11) unsigned default '0',
  `path` varchar(255) character set latin1 collate latin1_bin default NULL,
  `user_id` int(11) unsigned default '0',
  `username` varchar(50) collate latin1_general_ci NOT NULL,
  `subject` varchar(255) collate latin1_general_ci NOT NULL default '',
  `body` text collate latin1_general_ci NOT NULL,
  `hash` varchar(40) collate latin1_general_ci NOT NULL,
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active` tinyint(3) unsigned default '1',
  `ip_hash` varchar(40) collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `forum_id` (`forum_id`),
  KEY `thread_id` (`thread_id`),
  KEY `user_id` (`user_id`),
  KEY `is_parent` (`is_parent`),
  KEY `hash` (`hash`),
  KEY `created` (`created`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_permission`
--

CREATE TABLE IF NOT EXISTS `freech_permission` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(50) collate latin1_general_ci NOT NULL,
  `description` varchar(100) collate latin1_general_ci NOT NULL default '',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_user`
--

CREATE TABLE IF NOT EXISTS `freech_user` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `login` varchar(50) collate latin1_general_ci NOT NULL,
  `soundexlogin` varchar(5) collate latin1_general_ci default NULL,
  `password` varchar(40) collate latin1_general_ci NOT NULL,
  `firstname` varchar(50) collate latin1_general_ci NOT NULL,
  `lastname` varchar(50) collate latin1_general_ci NOT NULL,
  `mail` varchar(100) collate latin1_general_ci NOT NULL,
  `public_mail` tinyint(1) NOT NULL default '0',
  `homepage` varchar(255) collate latin1_general_ci default NULL,
  `im` varchar(100) collate latin1_general_ci default NULL,
  `signature` varchar(255) collate latin1_general_ci default NULL,
  `status` int(11) unsigned default '1',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  `lastlogin` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `mail` (`mail`),
  KEY `soundexlogin` (`soundexlogin`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freech_visitor`
--

CREATE TABLE IF NOT EXISTS `freech_visitor` (
  `id` int(11) NOT NULL auto_increment,
  `ip_hash` varchar(40) collate latin1_general_ci default NULL,
  `counter` bigint(20) default NULL,
  `visit` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `counter` (`counter`),
  KEY `ip_hash` (`ip_hash`),
  KEY `visit` (`visit`)
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
-- Constraints for table `freech_group_permission`
--
ALTER TABLE `freech_group_permission`
  ADD CONSTRAINT `0_770` FOREIGN KEY (`group_id`) REFERENCES `freech_group` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `0_771` FOREIGN KEY (`permission_id`) REFERENCES `freech_permission` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freech_group_user`
--
ALTER TABLE `freech_group_user`
  ADD CONSTRAINT `0_773` FOREIGN KEY (`group_id`) REFERENCES `freech_group` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `0_774` FOREIGN KEY (`user_id`) REFERENCES `freech_user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freech_message`
--
ALTER TABLE `freech_message`
  ADD CONSTRAINT `0_778` FOREIGN KEY (`forum_id`) REFERENCES `freech_forum` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `0_779` FOREIGN KEY (`user_id`) REFERENCES `freech_user` (`id`) ON DELETE SET NULL;

INSERT INTO freech_user (id, login, password, firstname, lastname, mail, created)
                  VALUES (1, 'root', '', 'root', 'root', 'root', NULL);
INSERT INTO freech_user (id, login, password, firstname, lastname, mail, created)
                  VALUES (2, 'anonymous', '', 'Anonymous', 'George', '', NULL);
INSERT INTO freech_forum (name, description, owner_id, created)
                   VALUES ('Forum', 'Default forum', 1, NULL);
