<?php
/*
Plugin:      SpamHash
Version:     0.2
Author:      Samuel Abels, Elliott Back
Description: Client-side Javascript computes an md5 code, server double
             checks. Blocks spam bots and makes DoS a little more difficult.
*/
define('CHECK_REGISTERED_ACCOUNTS', FALSE);
unset($spamhash); // The spamhash instance.

function spamhash_init(&$api) {
  $eventbus = $api->eventbus();
  $eventbus->signal_connect('on_run_before', 'spamhash_on_run');
}


function spamhash_on_run(&$api) {
  global $spamhash;

  // Check if the plugin is enabled for the given user.
  if (!CHECK_REGISTERED_ACCOUNTS && !$api->user()->is_anonymous())
    return;
  include 'spamhash.class.php';

  // Create a new instance of the spamhash generator/checker.
  $action = $api->action();
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

  $api->add_js('head',     $spamhash->get_header_code());
  $api->add_js('onload',   $spamhash->get_onload_code());
  $api->add_js('onsubmit', $spamhash->get_onsubmit_code());
  $api->add_style($spamhash->get_style());
  $api->add_html('form', $spamhash->get_form_html());
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
    // return FALSE;

  case SPAMHASH_ERROR_REMOTE_ADDRESS:
    die('Error: Invalid remote address - blocked due to spam protection.');
    // return FALSE;

  case SPAMHASH_ERROR_SESSION:
    die('Error: Invalid session ID - blocked due to spam protection.');
    // return FALSE;

  case SPAMHASH_ERROR_HASH_MISSING:
    die('Error: Missing hash - blocked due to spam protection.');
    // return FALSE;

  case SPAMHASH_ERROR_UNKNOWN:
    die('Error: Invalid hash - blocked due to spam protection.');
    // return FALSE;

  default:
    die("Hash check returned an unknown error code ($err)");
    // return FALSE;
  }
  return true;
}
?>
