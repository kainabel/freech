<?php
/*
Plugin:      Search
Version:     0.1
Author:      Samuel Abels
Description: Adds a search function to the forum.
*/
include_once dirname(__FILE__).'/search_controller.class.php';
include_once dirname(__FILE__).'/indexbar_search_result.class.php';
include_once dirname(__FILE__).'/indexbar_search_users.class.php';
include_once dirname(__FILE__).'/search_query.class.php';

function search_init(&$api) {
  $api->register_action('search', 'search_on_search');

  $forum_id = $api->forum() ? $api->forum()->get_id() : NULL;
  $url      = new FreechURL('', _('Find'));
  $url->set_var('action',   'search');
  $url->set_var('forum_id', $forum_id);
  $api->links('forum')->add_link($url);

  // Add a small search field to the forum.
  $html = "<form id='quicksearch'\n"
        . "      action='.'\n"
        . "      method='get'\n"
        . "      accept-charset='utf-8'>\n"
        . "<div>\n"
        . "<input type='hidden' name='action' value='search' />";
  if ($forum_id)
    $html .= "<input type='hidden' name='forum_id' value='$forum_id' />\n";
  $html .= htmlentities(_('Find in this forum:'), ENT_QUOTES, 'UTF-8');
  $html .= "&nbsp;<input type='text' name='q' value='' />\n";
  $html .= "</div>\n";
  $html .= "</form>\n";
  $api->links('search')->add_html($html);
}


function search_on_search(&$api) {
  if ($_GET['q'])
    search_on_search_result($api);
  else
    search_on_search_form($api);
}


function search_on_search_form(&$api) {
  $api->breadcrumbs()->add_separator();
  $api->breadcrumbs()->add_text(_('Find'));
  $controller = new SearchController($api);
  $controller->show((int)$_GET['forum_id'], $_GET['q']);
}


function search_on_search_result(&$api) {
  if (!$_GET['q'] || trim($_GET['q']) == '')
    return search_on_search_form($api);

  $forum_id = $api->forum() ? $api->forum()->get_id() : NULL;
  $url      = new FreechURL('', _('Find'));
  $url->set_var('action',   'search');
  $url->set_var('forum_id', $forum_id);
  $api->breadcrumbs()->add_separator();
  $api->breadcrumbs()->add_link($url);
  $api->breadcrumbs()->add_separator();
  $api->breadcrumbs()->add_text($_GET['q']);

  // Search for postings or users.
  $controller = new SearchController($api);
  if ($_GET['user_search'])
    $controller->show_users($_GET['q'], $_GET['hs']);
  else
    $controller->show_postings($forum_id, $_GET['q'], $_GET['hs']);
}


/***********************************************
 * Utilities.
 ***********************************************/
?>
