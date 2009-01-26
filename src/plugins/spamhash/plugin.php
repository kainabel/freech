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


function spamhash_init($forum) {
  $eventbus = $forum->eventbus();
  $eventbus->signal_connect('on_run_before', 'spamhash_on_run');
}


function spamhash_on_run($forum) {
  global $spamhash;
  if (!CHECK_REGISTERED_ACCOUNTS
    && !$forum->user()->is_anonymous())
    return;
  $action = $forum->action();
  if ($action == 'write'
    || $action == 'respond'
    || $action == 'edit'
    || $action == 'message_submit')
    $spamhash = new SpamHash('commentform');
  elseif ($action == 'account_register' || $action == 'account_create')
    $spamhash = new SpamHash('registration');
  else
    return;
  $eventbus = $forum->eventbus();
  $eventbus->signal_connect('on_header_print_before',
                            'spamhash_on_header_print');
}


function spamhash_on_header_print($forum) {
  global $spamhash;
  if (!$spamhash)
    return;
  if ($forum->action() == 'message_submit'
    && $_POST[send]
    && !spamhash_check_hash())
    return;
  if ($forum->action() == 'account_create'
    && !spamhash_check_hash())
    return;
  $content = $spamhash->insert_header_code($forum->get_content());
  $content = $spamhash->insert_body_code($content);
  $forum->set_content($content);
  $eventbus = $forum->eventbus();
  $eventbus->signal_connect('on_content_print_before',
                            'spamhash_on_content_print');
}


function spamhash_on_content_print($forum) {
  global $spamhash;
  if (!$spamhash)
    return;
  if ($forum->action() == 'message_submit'
    && $_POST[send]
    && !spamhash_html_contains_form($forum->content)) {
    unset($spamhash);
    return;
  }
  $content = $spamhash->insert_form_code($forum->get_content());
  $content = $spamhash->insert_body_code($content);
  $forum->set_content($content);
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
