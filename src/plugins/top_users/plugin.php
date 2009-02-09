<?php
/*
Plugin:      Top Users
Version:     0.1
Author:      Samuel Abels
Description: This plugin adds a page that shows the top users.
*/

function top_users_init(&$api) {
  $api->register_action('top_posters', 'top_users_on_show');

  $url = new FreechURL('', _('Top Users'));
  $url->set_var('action', 'top_posters');
  $api->links('forum')->add_link($url);
}


function top_users_on_show(&$api) {
  include dirname(__FILE__).'/top_users_controller.class.php';
  $api->breadcrumbs()->add_separator();
  $api->breadcrumbs()->add_text(_('Top Users'));

  $controller = new TopUsersController($api);
  $controller->show();
}


/***********************************************
 * Utilities.
 ***********************************************/
?>
