<?php
  require_once 'adodb/adodb.inc.php';
  include_once 'functions/config.inc.php';
  $db = ADONewConnection(cfg('db_dbn'))
        or die('FreechForum::FreechForum(): Error: Can\'t connect.'
             . ' Please check username, password and hostname.');

  $db->debug = TRUE;
  $db->StartTrans();
  $db->Execute("
CREATE TABLE IF NOT EXISTS `freech_thread` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `forum_id` int(11) unsigned NOT NULL,
  `thread_id` int(11) unsigned NOT NULL,
  `n_children` int(11) unsigned default '0',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `forum_id` (`forum_id`),
  KEY `updated` (`updated`),
  KEY `created` (`created`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci") or die();

  // Initialize the thread table using posting table data.
  $db->Execute("
INSERT INTO `freech_thread` SELECT NULL, forum_id, thread_id, n_children, updated, created FROM `freech_posting` WHERE is_parent") or die();

  // Drop constraints.
  $db->Execute("ALTER IGNORE TABLE `freech_forum` DROP FOREIGN KEY `0_776`");
  $db->Execute("ALTER IGNORE TABLE `freech_posting` DROP FOREIGN KEY `0_778`");
  $db->Execute("ALTER IGNORE TABLE `freech_posting` DROP FOREIGN KEY `0_779`");
  $db->Execute("ALTER IGNORE TABLE `freech_posting` DROP FOREIGN KEY `0_780`");

  // Map old thread ids to new ones.
  $res = $db->Execute('SELECT id, thread_id FROM freech_thread');
  $map = array();
  while (!$res->EOF) {
    $obj = $res->FetchObj();
    $map[$obj->thread_id] = $obj->id;
    $res->MoveNext();
  }

  // Update the thread IDs in the posting table.
  ksort($map);
  foreach ($map as $thread_id => $id)
    $res = $db->Execute("UPDATE freech_posting SET thread_id=$id WHERE thread_id=$thread_id") or die();

  // Create constraints.
  $db->execute("
ALTER TABLE `freech_group` CHANGE `is_active` `status` INT( 11 ) UNSIGNED NOT NULL,
  ADD `priority` int(11) unsigned NOT NULL default '0'") or die();

  $db->execute("
ALTER TABLE `freech_posting` CHANGE `thread_id` `thread_id` INT( 11 ) UNSIGNED NOT NULL,
  ADD `origin_forum_id` int(11) unsigned NOT NULL default '0'") or die();

  $db->Execute("
ALTER TABLE `freech_forum`
  CHANGE `is_active` `status` INT( 11 ) UNSIGNED NOT NULL,
  ADD CONSTRAINT `freech_forum_owner_id` FOREIGN KEY (`owner_id`) REFERENCES `freech_user` (`id`) ON DELETE SET NULL") or die();

  $db->Execute("
ALTER TABLE `freech_thread`
  ADD CONSTRAINT `freech_thread_forum_id` FOREIGN KEY (`forum_id`) REFERENCES `freech_forum` (`id`) ON DELETE CASCADE") or die();
  $db->CompleteTrans();

  $db->Execute("
ALTER TABLE `freech_posting`
  ADD CONSTRAINT `freech_posting_forum_id` FOREIGN KEY (`forum_id`) REFERENCES `freech_forum` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `freech_posting_thread_id` FOREIGN KEY (`thread_id`) REFERENCES `freech_thread` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `freech_posting_user_id` FOREIGN KEY (`user_id`) REFERENCES `freech_user` (`id`) ON DELETE SET NULL") or die();

  // Update poll table definition.
  $db->Execute("
ALTER TABLE `freech_poll_option` CHANGE `name` `name` VARCHAR( 100 ) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL");

  // Drop useless columns.
  $db->Execute("
ALTER TABLE `freech_thread` DROP `thread_id`");

  $db->Execute("
ALTER TABLE `freech_posting` DROP `n_children`");
?>
