--
-- Updating table structure for plugin rating v0.1 to v0.2
-- and transfering votes from table `freech_user_rating`
-- to table `freech_rating_vote`
--

SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

--
-- Please replace the table prefix manually "freech_" when changed in config
--

DROP TABLE IF EXISTS `freech_rating_vote`;
CREATE TABLE `freech_rating_vote` (
  `posting_id` int(11) unsigned NOT NULL,
  `thread_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `rating` tinyint(4) NOT NULL,
  PRIMARY KEY (`posting_id`,`user_id`),
  KEY `thread_id` (`thread_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

TRUNCATE `freech_rating_vote`;
INSERT INTO `freech_rating_vote`
  (`posting_id`, `thread_id`, `user_id`, `rating`)
  (SELECT r.posting_id, p.thread_id, r.user_id, r.rating
  FROM `freech_user_rating` r
  INNER JOIN `freech_posting` p ON r.posting_id = p.id);

SET foreign_key_checks = 1;
