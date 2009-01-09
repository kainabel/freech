<?php
/*
Plugin:      Search
Version:     0.1
Author:      Samuel Abels
Description: Adds a search function to the forum.
Constructor: search_init
Active:      1
*/
include_once dirname(__FILE__).'/search_printer.class.php';
include_once dirname(__FILE__).'/indexbar_search_result.class.php';
include_once dirname(__FILE__).'/indexbar_search_users.class.php';
include_once dirname(__FILE__).'/search_query.class.php';

function search_init($forum) {
  $forum->register_action('search', 'search_on_search');

  $url = new URL('?', cfg('urlvars'), lang('search_forum'));
  $url->set_var('action',   'search');
  $url->set_var('forum_id', $forum->get_current_forum_id());
  $forum->get_forum_links()->add_link($url);
}


function search_on_search($forum) {
  if ($_GET['q'])
    search_on_search_result($forum);
  else
    search_on_search_form($forum);
}


function search_on_search_form($forum) {
  $printer = &new SearchPrinter($forum);
  $printer->show((int)$_GET['forum_id'], $_GET['q']);
}


function search_on_search_result($forum) {
  if (!$_GET['q'] || trim($_GET['q']) == '')
    return search_on_search_form($forum);

  // Search for postings or users.
  $printer  = new SearchPrinter($forum);
  $forum_id = (int)$_GET['forum_id'];
  if ($_GET['user_search'])
    $printer->show_users($_GET['q'], $_GET['hs']);
  else
    $printer->show_postings($forum_id, $_GET['q'], $_GET['hs']);
}


/***********************************************
 * Utilities.
 ***********************************************/
?>
