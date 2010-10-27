-- Add table for plugin rating

CREATE TABLE IF NOT EXISTS freech_user_rating (
 forum_id int(11) unsigned NOT NULL,
 posting_id int(11) unsigned NOT NULL,
 user_id int(11) unsigned NOT NULL,
 rating tinyint NOT NULL,
 PRIMARY KEY (forum_id, posting_id, user_id)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Insert 2 cols in freech_posting

ALTER TABLE `freech_posting` ADD `rating` tinyint(4);
ALTER TABLE `freech_posting` ADD `rating_count` int(11) unsigned NOT
NULL DEFAULT 0;
