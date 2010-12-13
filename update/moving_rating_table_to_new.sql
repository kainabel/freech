--
-- Please, only apply if you have installed a snapshot version of freech
-- between SVN r812 and SVN r841!
--
-- http://code.google.com/p/freech/updates/list

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


-- Starting Transaction
BEGIN;

-- Fetching data from old table freech_user_rating
INSERT INTO `freech_rating_vote`
  (`posting_id`, `thread_id`, `user_id`, `rating`)
  (SELECT r.posting_id, p.thread_id, r.user_id, r.rating
  FROM `freech_user_rating` r
  INNER JOIN `freech_posting` p ON
  (r.posting_id = p.id) AND (r.forum_id = p.forum_id));

-- Updating counter and voting results in freech_posting
UPDATE
	freech_posting post, freech_rating_vote vote
SET
	post.rating = (
	  SELECT AVG(vote.rating)
		FROM freech_rating_vote vote
		WHERE post.id = vote.posting_id
		GROUP BY vote.posting_id ),
  post.rating_count = (
		SELECT Count(vote.rating)
		FROM freech_rating_vote vote
		WHERE post.id = vote.posting_id
		GROUP BY vote.posting_id )
WHERE
	post.id = vote.posting_id;

-- All fine? Do it.
COMMIT;

SET foreign_key_checks = 1;

--
-- If there was no error and the new table `freech_rating_vote`
-- contains data, then the old table `freech_user_rating` can be deleted.
--

-- DROP TABLE IF EXISTS `freech_user_rating`;
