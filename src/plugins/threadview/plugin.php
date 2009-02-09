<?php
/*
Plugin:      Thread View
Version:     0.1
Author:      Samuel Abels
Description: This plugin adds a view that shows postings in thread order.
*/

function threadview_init($api) {
  $api->register_view('thread', 'ThreadView', _('Order by Thread'), 100);
  $api->eventbus()->signal_connect('on_run_before', 'threadview_on_run');
}

function threadview_on_run(&$api) {
  if ($api->view_class() != 'ThreadView')
    return;
  include dirname(__FILE__).'/threadview.class.php';
}
?>
