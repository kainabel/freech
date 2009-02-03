<?php
/*
Plugin:      BBView
Version:     0.1
Author:      Samuel Abels
Description: This plugin adds a view that looks similar to phpBB.
Active:      1
*/
include_once dirname(__FILE__).'/bbview.class.php';
include_once dirname(__FILE__).'/indexbar.class.php';
include_once dirname(__FILE__).'/indexbar_read_posting.class.php';

function bbview_init($api) {
  $api->register_view('bbview', 'BBView', _('Order by Topic'), 600);
}
?>
