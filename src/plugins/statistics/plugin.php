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
  $forum->add_extra_footer_link($url);
}


function statistics_on_show($forum) {
  $printer = new StatisticsPrinter($forum);
  $printer->show();
}


/***********************************************
 * Utilities.
 ***********************************************/
?>