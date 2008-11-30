<?php
/*
Plugin:      SpamHash
Version:     0.2
Author:      Samuel Abels, Elliott Back
Description: Client-side Javascript computes an md5 code, server double
             checks. Blocks spam bots and makes DoS a little more difficult.
Constructor: spamhash_init
*/
include_once "spamhash.class.php";

define('CHECK_REGISTERED_ACCOUNTS', FALSE);
$spamhash = ''; // The spamhash instance.


function spamhash_init(&$forum) {
  $eventbus = &$forum->get_eventbus();
  $eventbus->signal_connect("on_construct",
                            "spamhash_on_construct");
  $eventbus->signal_connect("on_header_print_before",
                            "spamhash_on_header_print");
}


function spamhash_on_construct(&$forum) {
  global $spamhash;
  $action = $forum->get_action();
  if ($action != 'write'
    && $action != 'respond'
    && $action != 'edit'
    && $action != 'message_submit')
    return;
  $spamhash = new SpamHash("commentform");
}


function spamhash_on_header_print(&$forum) {
  global $spamhash;
  if (!$spamhash)
    return;
  if (!CHECK_REGISTERED_ACCOUNTS && $forum->get_current_user())
    return;
  if ($forum->get_action() == 'message_submit'
    && $_POST[send]
    && !spamhash_check_hash())
    return;
  $forum->content = $spamhash->insert_header_code($forum->content);
  $forum->content = $spamhash->insert_body_code($forum->content);
  $eventbus       = &$forum->get_eventbus();
  $eventbus->signal_connect("on_content_print_before",
                            "spamhash_on_content_print");
}


function spamhash_on_content_print(&$forum) {
  global $spamhash;
  if (!$spamhash)
    return;
  if ($forum->get_action() == 'message_submit'
    && $_POST[send]
    && !spamhash_html_contains_form($forum->content)) {
    unset($spamhash);
    return;
  }
  $forum->content = $spamhash->insert_form_code($forum->content);
  $forum->content = $spamhash->insert_body_code($forum->content);
}


function spamhash_html_contains_form(&$html) {
  return preg_match("/<form /i", $html) > 0;
}


function spamhash_check_hash() {
  global $spamhash;
  $err = $spamhash->check_hash();
  switch ($err) {
  case 0:
    //echo "Successfully checked hash.";
    break;

  case SPAMHASH_ERROR_REFERRER:
    echo "Error: Invalid referrer - sorry, blocked due to spam protection.";
    die();
    return FALSE;

  case SPAMHASH_ERROR_REMOTE_ADDRESS:
    echo "Error: Invalid remote address - sorry, blocked due to spam protection.";
    die();
    return FALSE;

  case SPAMHASH_ERROR_SESSION:
    echo "Error: Invalid session ID - sorry, blocked due to spam protection.";
    die();
    return FALSE;

  case SPAMHASH_ERROR_HASH_MISSING:
    echo "Error: Missing hash  - sorry, blocked due to spam protection.";
    die();
    return FALSE;

  case SPAMHASH_ERROR_UNKNOWN:
    echo "Error: Invalid hash - sorry, blocked due to spam protection.";
    die();
    return FALSE;

  default:
    die("Hash check returned an unknown error code ($err)");
    return FALSE;
  }
  return TRUE;
}
?>
