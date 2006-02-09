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


$spamhash = ''; // The spamhash instance.


function spamhash_init(&$forum) {
  $eventbus = &$forum->get_eventbus();
  $eventbus->signal_connect("on_construct",            "spamhash_on_construct");
  $eventbus->signal_connect("on_header_print_before",  "spamhash_on_header_print");
  $eventbus->signal_connect("on_content_print_before", "spamhash_on_content_print");
}


function spamhash_on_construct() {
  global $spamhash;
  if (!$_POST[send]
    && !$_GET[write]
    && !$_POST[quote]
    && !$_POST[preview]
    && !$_POST[edit])
    return;
  $spamhash = new SpamHash("commentform");
}


function spamhash_on_header_print(&$html) {
  global $spamhash;
  if (!$spamhash)
    return;
  if ($_POST[send] && !spamhash_check_hash())
    return;
  $html = $spamhash->insert_header_code($html);
  $html = $spamhash->insert_body_code($html);
}


function spamhash_on_content_print(&$html) {
  global $spamhash;
  if (!$spamhash)
    return;
  if ($_POST[send] && !spamhash_html_contains_form($html)) {
    unset($spamhash);
    return;
  }
  $html = $spamhash->insert_form_code($html);
  $html = $spamhash->insert_body_code($html);
}


function spamhash_html_contains_form(&$html) {
  return preg_match("/<form /i", $html) > 0;
}


function spamhash_check_hash() {
  global $spamhash;
  switch ($spamhash->check_hash()) {
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
    //echo "Successfully checked hash.";
    break;
  }
  return TRUE;
}
?>
