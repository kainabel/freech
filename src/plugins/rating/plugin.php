<?php
/*
Plugin:      Rating
Version:     0.2
Author:      Nico Gau
Description: adds Rating functions
*/

define('RATING_TYPE_MINUS_MINUS', 0);
define('RATING_TYPE_MINUS',       25);
define('RATING_TYPE_PLUS',        75);
define('RATING_TYPE_PLUS_PLUS',   100);

function rating_init(&$api) {
  $eventbus = $api->eventbus();
  $eventbus->signal_connect('on_message_read_print', 'rating_on_read');

  // Register our extra actions.
  $api->register_action('set_rating', 'rating_on_set_rating');
}

function rating_on_read(&$api,&$message) {
  $posting = $message->posting;

  // activate rating actions only on ordinary messages
  if($posting->get_renderer() != "message")
    return;

  // don't apply on locked messages or anonymous users
  if (($posting->is_active() != TRUE) || $api->user()->is_anonymous())
    return;

  // 'panic' switch in config file was set
  if (cfg('set_read_only')) {
    $api->group()->permissions['write'] = FALSE;
    $api->unregister_action('set_rating');
    return;
  }

  if (!isset($api->rating_proto)) {
    list($api->rating_proto, $api->rating_array) = _init_rating_body($message);
  }

  $_user_id = $api->user()->get_id();
  if($_user_id != $posting->get_user_id()) {

    // caching rating votes by current user an thread
    $rating = _get_rating_vote($api, $_user_id, $message->posting);

      if($rating == null) {
        $msg_id = $message->posting->get_id();
        $rating_insert = str_replace('ID', (int) $msg_id, $api->rating_proto);
       } else {
        $text = $api->rating_array[$rating]['text'];
        $css  = $api->rating_array[$rating]['css'];
        $rating_insert = "<span class='{$css}'>{$text}</span>";
      }
      $rating_body = "<div class='rating_bar'>{$rating_insert}</div>";
  }

  $body = $message->get_body_html() . $rating_body;
  $message->set_body_html($body);
}

function rating_on_set_rating(&$api) {

  $rating = $_GET["rating"];
  $posting = $api->forumdb()->get_posting_from_id($_GET['msg_id']);

  if($posting && !$api->user()->is_anonymous()
     && $api->user()->get_id() != $posting->get_user_id()
     && is_rating_valid($rating)) {
    set_user_rating($api, $posting, $rating);
  }

  $url = new FreechURL();
  $url->set_var('action',   'read');
  $url->set_var('msg_id',   $posting->get_id());
  $url->set_var('forum_id', $posting->get_forum_id());
  $api->refer_to($url->get_string());
}

function is_rating_valid($rating) {

  return $rating == RATING_TYPE_MINUS_MINUS ||
         $rating == RATING_TYPE_MINUS       ||
         $rating == RATING_TYPE_PLUS        ||
         $rating == RATING_TYPE_PLUS_PLUS;
}

/***********************************************
 * Utilities.
 ***********************************************/

function _init_rating_body($_msg) {

  $types = array ( /* reversed order for css float */
    RATING_TYPE_PLUS_PLUS   => array('css' => 'positive_rating', 'text' => '++'),
    RATING_TYPE_PLUS        => array('css' => 'positive_rating', 'text' => '+'),
    RATING_TYPE_MINUS       => array('css' => 'negative_rating', 'text' => '-'),
    RATING_TYPE_MINUS_MINUS => array('css' => 'negative_rating', 'text' => '--'),
  );

  $url = new FreechURL('', 'set rating');
  $url->set_var('action', 'set_rating');
  $url->set_var('thread_id', $_msg->get_thread_id());
  $url->set_var('msg_id', 'ID');
  $url->set_var('rating', 'TYPE');
  $url_proto = $url->get_string();

  $rating_body = array();
  foreach ($types as $key => $type) {
    $vote = str_replace('TYPE', (int) $key, $url_proto);
    $text = $type['text'];
    $css  = $type['css'];
    $rating_body[] = "<a class='{$css}' href='" . esc($vote) . "'>{$text}</a>";
  }
  return array(implode("&nbsp;",$rating_body), $types);
}

function _get_rating_vote(&$api, $_user_id, $_msg) {

  $_msg_id = $_msg->get_id();
  if (!isset($api->votings)) {
    $api->votings = array();

    $sql  = 'SELECT * FROM {t_rating_vote}';
    $sql .= ' WHERE user_id={user_id}';
    $sql .= " AND thread_id={thread_id}";

    $query = new FreechSqlQuery($sql);
    $query->set_int('user_id', $_user_id);
    $query->set_int('thread_id', $_msg->get_thread_id());

    $db  = $api->db();
    $res = $api->db()->_Execute($query->sql()) or die('Rating::get_user_rating()');
    $arr = $res->GetArray();
    foreach ($arr as $result) {
      $posting_id = $result['posting_id'];
      $api->votings[$posting_id] = $result['rating'];
    }
  }

  return isset($api->votings[$_msg_id]) ? $api->votings[$_msg_id] : NULL;
}

function set_user_rating(&$api, $_msg, $rating) {

  $_forum_id = $_msg->get_forum_id();
  $_posting_id = $_msg->get_id();
  $_thread_id = $_msg->get_thread_id();
  $_user_id = $api->user()->get_id();
  $_rating = $rating;

  $db = $api->db();
  $db->StartTrans();

  // first insert new user rating
  $sql  = "INSERT {t_rating_vote}";
  $sql .= " (posting_id, thread_id, user_id, rating)";
  $sql .= " VALUES ({posting_id}, {thread_id}, {user_id}, {rating})";

  $query = new FreechSqlQuery($sql);
  $query->set_int('posting_id', $_posting_id);
  $query->set_int('thread_id',  $_thread_id);
  $query->set_int('user_id',    $_user_id);
  $query->set_int('rating',     $_rating);
  $db->_Execute($query->sql()) or die('ForumDB::save_rating(): user_rating');

  // then select all existing ratings to compute new average rating
  $sql  = "SELECT posting_id as id, count(rating) as count,";
  $sql .= " AVG(rating) as avg_rating FROM {t_rating_vote}";
  $sql .= " WHERE posting_id={posting_id} GROUP BY thread_id";

  $query = new FreechSqlQuery($sql);
  $query->set_int('posting_id', $_posting_id);
  $res = $db->_Execute($query->sql()) or die('ForumDB::save_rating(): query');

  // save average rating into table
   $sql  = "UPDATE {t_posting}";
   $sql .= " SET rating={rating}, rating_count={count}";
   $sql .= " WHERE id={posting_id}";

   $query = new FreechSqlQuery($sql);
   $query->set_int('posting_id', $res->fields['id']);
   $query->set_int('rating',     $res->fields['avg_rating']);
   $query->set_int('count',      $res->fields['count']);

   $db->_Execute($query->sql()) or die('ForumDB::save_rating(): rating');
   $db->CompleteTrans();
}

?>
