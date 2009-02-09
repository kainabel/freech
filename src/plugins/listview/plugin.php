<?php
/*
Plugin:      List View
Version:     0.1
Author:      Samuel Abels
Description: This plugin adds a view that shows postings in time order.
Active:      1
*/
function listview_init(&$api) {
  $api->register_view('list', 'ListView', _('Order by Date'), 500);
  $api->eventbus()->signal_connect('on_run_before', 'listview_on_run');
}

function listview_on_run(&$api) {
  if ($api->view_class() != 'ListView')
    return;
  include dirname(__FILE__).'/listview.class.php';
}
?>
