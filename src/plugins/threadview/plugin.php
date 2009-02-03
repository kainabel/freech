<?php
/*
Plugin:      Thread View
Version:     0.1
Author:      Samuel Abels
Description: This plugin adds a view that shows postings in thread order.
*/
include_once dirname(__FILE__).'/threadview.class.php';
include_once dirname(__FILE__).'/indexbar.class.php';
include_once dirname(__FILE__).'/indexbar_read_posting.class.php';

function threadview_init($api) {
  $api->register_view('thread', 'ThreadView', _('Order by Thread'), 100);
}
?>
