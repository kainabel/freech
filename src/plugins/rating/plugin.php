<?php
/*
Plugin:      Rating
Version:     0.1
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

  // HACK: activate ratings only on ordinary messages
  if($posting->get_renderer() != "message")
    return;

    // don't apply on locked messages
  if ($posting->is_active() != TRUE)
    return;

  $rating_body = "";

  if(!$api->user()->is_anonymous() &&
       $api->user()->get_id() != $posting->get_user_id()) {
    $rating = get_user_rating($api, $message->posting);
    $rating_body .= '<div class="rating-bar">';

      if($rating == null) {
        $minus_minus = get_rating_url($api, $posting, RATING_TYPE_MINUS_MINUS);
        $minus = 			 get_rating_url($api, $posting, RATING_TYPE_MINUS);
        $plus = 			 get_rating_url($api, $posting, RATING_TYPE_PLUS);
        $plus_plus = 	 get_rating_url($api, $posting, RATING_TYPE_PLUS_PLUS);

        $rating_body .= rating_type_to_action_link($api, $posting, RATING_TYPE_PLUS_PLUS);
        $rating_body .= rating_type_to_action_link($api, $posting, RATING_TYPE_PLUS);
        $rating_body .= rating_type_to_action_link($api, $posting, RATING_TYPE_MINUS);
        $rating_body .= rating_type_to_action_link($api, $posting, RATING_TYPE_MINUS_MINUS);

       } else {
        $css = rating_type_to_css_class($rating);
        $text = rating_type_to_text($rating);

        $rating_body .= '<span class="' . $css . '">' . $text . '</span>';
      }
      $rating_body .= '</div>';
  }

  $body = $message->get_body_html();
  $body .= $rating_body;
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

  $api->refer_to_posting($posting);
}

function is_rating_valid($rating) {

  return $rating == RATING_TYPE_MINUS_MINUS ||
         $rating == RATING_TYPE_MINUS       ||
         $rating == RATING_TYPE_PLUS        ||
         $rating == RATING_TYPE_PLUS_PLUS;
}

function rating_type_to_css_class($type) {

  switch($type) {
    case RATING_TYPE_MINUS_MINUS:
    case RATING_TYPE_MINUS:
      return "negative_rating";

    case RATING_TYPE_PLUS:
    case RATING_TYPE_PLUS_PLUS:
      return "positive_rating";

    default:
      return "??";
  }
}

function rating_type_to_text($type) {

  switch($type) {
    case RATING_TYPE_MINUS_MINUS:
      return "--";

    case RATING_TYPE_MINUS:
      return "-";

    case RATING_TYPE_PLUS:
      return "+";

    case RATING_TYPE_PLUS_PLUS:
      return "++";

    default:
      return "??";
  }
}

function rating_type_to_action_link(&$api, $posting, $type) {

  $url  = get_rating_url($api, $posting, $type);
  $text = rating_type_to_text($type);
  $css  = rating_type_to_css_class($type);

  return '<a href="' . esc($url) . '" class="' . $css . '">' . $text . '</a>';
}

function get_user_rating(&$api, $posting) {

  $sql  = 'SELECT * FROM {t_user_rating} ';
  $sql .= ' WHERE user_id={user_id}';
  $sql .= " AND forum_id={forum_id}";
  $sql .= " AND posting_id={posting_id}";

  $query = new FreechSqlQuery($sql);
  $query->set_int('user_id',    $api->user()->get_id());
  $query->set_int('forum_id',   $api->forum()->get_id());
  $query->set_int('posting_id', $posting->get_id());
  $res = $api->db()->_Execute($query->sql()) or die('Rating::get_user_rating()');

  return $res->EOF ? null : $res->fields["rating"];
}

function set_user_rating(&$api, $posting, $rating) {

  $user_id    = $api->user()->get_id();
  $forum_id   = $api->forum()->get_id();
  $posting_id = $posting->get_id();

  $api->forumdb()->save_rating($forum_id, $posting_id, $user_id, $rating);
}

function get_rating_url(&$api, $posting, $type) {

  $url      = new FreechURL('', _('set rating'));
  $url->set_var('forum_id', $api->forum()->get_id());
  $url->set_var('msg_id'  , $posting->get_id());
  $url->set_var('action'  , 'set_rating');
  $url->set_var('rating'  , $type);

  return $url->get_string();
}

?>
