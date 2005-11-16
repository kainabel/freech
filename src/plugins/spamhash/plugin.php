<?php
/*
Plugin:      SpamHash
Version:     0.1
Author:      Samuel Abels, Elliott Back
Description: Client-side Javascript computes an md5 code, server double
             checks. Blocks spam bots and makes DoS a little more difficult.
Constructor: spamhash_init
*/
include_once "spamhash.class.php";


function spamhash_init(&$registry) {
  $registry->add_callback("on_construct", "spamhash_on_construct");
  $registry->add_callback("on_destroy",   "spamhash_on_destroy");
}


function spamhash_insert_hash($page) {
  $spamhash = new SpamHash("commentform");
  return $spamhash->insert_hash($page);
}


function spamhash_on_construct() {
  // Insert the hashes only if we are editing a comment.
  if ($_GET[write]
    || $_POST[quote]
    || $_POST[preview]
    || $_POST[edit]) {
    session_start();
    ob_start(spamhash_insert_hash);
    return;
  }

  // If this is an attempt to submit a comment, check the hash.
  if (!$_POST[send])
    return TRUE;
  $spamhash = new SpamHash("commentform");
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

function spamhash_on_destroy() {
  ob_end_flush();
}
?>
