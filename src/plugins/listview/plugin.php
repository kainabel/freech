<?php
/*
Plugin:      List View
Version:     0.1
Author:      Samuel Abels
Description: This plugin adds a view that shows postings in time order.
Constructor: listview_init
Active:      1
*/
include_once dirname(__FILE__).'/listview.class.php';
include_once dirname(__FILE__).'/indexbar.class.php';
include_once dirname(__FILE__).'/indexbar_read_posting.class.php';

function listview_init($forum) {
  $forum->register_view('list', 'ListView', lang('listview'), 500);
}
?>
