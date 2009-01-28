<?php
/*
Plugin:      SpamHash
Version:     0.2
Author:      Samuel Abels, Elliott Back
Description: Client-side Javascript computes an md5 code, server double
             checks. Blocks spam bots and makes DoS a little more difficult.
Constructor: spamhash_init
*/
include_once 'spamhash.class.php';

define('CHECK_REGISTERED_ACCOUNTS', FALSE);
$spamhash = ''; // The spamhash instance.


function spamhash_init($forum) {
  $eventbus = $forum->eventbus();
  $eventbus->signal_connect('on_run_before', 'spamhash_on_run');
}


function spamhash_on_run($forum) {
  global $spamhash;

  // Check if the plugin is enabled for the given user.
  if (!CHECK_REGISTERED_ACCOUNTS && !$forum->user()->is_anonymous())
    return;

  // Create a new instance of the spamhash generator/checker.
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

  // If this is an attempt to submit a message, check the hash.
  // Note that this may lead to rendering a form if an error such as
  // an unfilled username field happens, so we may still need to inject
  // the Javascript into the resulting page.
  if ($action == 'message_submit' && $_POST[send])
    spamhash_check_hash();
  if ($action == 'account_create')
    spamhash_check_hash();

  $forum->add_js('head',     $spamhash->get_header_code());
  $forum->add_js('onload',   $spamhash->get_body_code());
  $forum->add_js('onsubmit', $spamhash->get_onsubmit_code());

  $eventbus = $forum->eventbus();
  $eventbus->signal_connect('on_content_print_before',
                            'spamhash_on_content_print');
}


function spamhash_on_content_print($forum) {
  global $spamhash;
  if (!$spamhash)
    return;
  $content = $spamhash->insert_form_code($forum->get_content());
  $forum->set_content($content);
}


function spamhash_check_hash() {
  global $spamhash;
  $err = $spamhash->check_hash();
  switch ($err) {
  case 0:
    //echo "Successfully checked hash.";
    break;

  case SPAMHASH_ERROR_REFERRER:
    die('Error: Invalid referrer - blocked due to spam protection.');
    return FALSE;

  case SPAMHASH_ERROR_REMOTE_ADDRESS:
    die('Error: Invalid remote address - blocked due to spam protection.');
    return FALSE;

  case SPAMHASH_ERROR_SESSION:
    die('Error: Invalid session ID - blocked due to spam protection.');
    return FALSE;

  case SPAMHASH_ERROR_HASH_MISSING:
    die('Error: Missing hash - blocked due to spam protection.');
    return FALSE;

  case SPAMHASH_ERROR_UNKNOWN:
    die('Error: Invalid hash - blocked due to spam protection.');
    return FALSE;

  default:
    die("Hash check returned an unknown error code ($err)");
    return FALSE;
  }
  return TRUE;
}
?>
