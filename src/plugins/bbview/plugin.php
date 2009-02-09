<?php
/*
Plugin:      BBView
Version:     0.1
Author:      Samuel Abels
Description: This plugin adds a view that looks similar to phpBB.
Active:      1
*/

function bbview_init(&$api) {
  $api->register_view('bbview', 'BBView', _('Order by Topic'), 600);
  $api->eventbus()->signal_connect('on_run_before', 'bbview_on_run');
}

function bbview_on_run(&$api) {
  if ($api->view_class() != 'BBView')
    return;
  include dirname(__FILE__).'/bbview.class.php';
}
?>
