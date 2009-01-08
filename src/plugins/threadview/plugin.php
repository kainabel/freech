<?php
/*
Plugin:      Thread View
Version:     0.1
Author:      Samuel Abels
Description: This plugin adds a view that shows postings in thread order.
Constructor: threadview_init
Active:      1
*/
include_once dirname(__FILE__).'/threadview.class.php';
include_once dirname(__FILE__).'/indexbar.class.php';
include_once dirname(__FILE__).'/indexbar_read_posting.class.php';

function threadview_init($forum) {
  $forum->register_view('thread', 'ThreadView', lang('threadview'), 100);
}
?>
