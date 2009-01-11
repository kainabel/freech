<?php
/*
Plugin:      Statistics
Version:     0.1
Author:      Samuel Abels
Description: This plugin adds a page with statistics to the forum.
Constructor: statistics_init
Active:      1
*/
include_once dirname(__FILE__).'/statistics_printer.class.php';

function statistics_init($forum) {
  $forum->register_action('statistics', 'statistics_on_show');

  $url = new URL('?', cfg('urlvars'), lang('statistics'));
  $url->set_var('action', 'statistics');
  $forum->page_links()->add_link($url);
}


function statistics_on_show($forum) {
  $forum->breadcrumbs()->add_separator();
  $forum->breadcrumbs()->add_text(lang('statistics'));
  $printer = new StatisticsPrinter($forum);
  $printer->show();
}


/***********************************************
 * Utilities.
 ***********************************************/
?>
