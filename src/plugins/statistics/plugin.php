<?php
/*
Plugin:      Statistics
Version:     0.1
Author:      Samuel Abels
Description: This plugin adds a page with statistics to the forum.
*/
include_once dirname(__FILE__).'/statistics_controller.class.php';

function statistics_init($api) {
  $api->register_action('statistics', 'statistics_on_show');

  $url = new FreechURL('', _('Statistics'));
  $url->set_var('action', 'statistics');
  $api->links('forum')->add_link($url);
}


function statistics_on_show($api) {
  $api->breadcrumbs()->add_separator();
  $api->breadcrumbs()->add_text(_('Statistics'));
  $controller = new StatisticsController($api);
  $controller->show();
}


/***********************************************
 * Utilities.
 ***********************************************/
?>
