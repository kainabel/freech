<?php
/*
Plugin:      Top Users
Version:     0.1
Author:      Samuel Abels
Description: This plugin adds a page that shows the top users.
Constructor: top_users_init
Active:      1
*/
include_once dirname(__FILE__).'/top_users_printer.class.php';

function top_users_init($forum) {
  $forum->register_action('top_posters', 'top_users_on_show');

  $url = new URL('?', cfg('urlvars'), lang('top_users'));
  $url->set_var('action', 'top_posters');
  $forum->get_forum_links()->add_link($url);
}


function top_users_on_show($forum) {
  $forum->breadcrumbs()->add_separator();
  $forum->breadcrumbs()->add_text(lang('top_users'));

  $printer = new TopUsersPrinter($forum);
  $printer->show();
}


/***********************************************
 * Utilities.
 ***********************************************/
?>
