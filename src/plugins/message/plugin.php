<?php
/*
Plugin:      Message
Version:     0.1
Author:      Samuel Abels
Description: Shows normal messages in the forum.
Constructor: message_init
Active:      1
*/
include_once dirname(__FILE__).'/message.class.php';
include_once dirname(__FILE__).'/message_printer.class.php';

function message_init($forum) {
  $forum->get_eventbus()->signal_connect('on_run_before', 'message_on_run');

  // Register a class that is responsible for formatting the posting object
  // that holds the message.
  $forum->register_renderer('message', 'Message');

  // Register our extra actions.
  $forum->register_action('write',          'message_on_write');
  $forum->register_action('respond',        'message_on_respond');
  $forum->register_action('edit',           'message_on_edit_saved');
  $forum->register_action('message_submit', 'message_on_submit');
}


function message_on_run($forum) {
  if (!$forum->get_current_group()->may('write'))
    return;

  // Add 'new message' button to the index bar.
  $url = new URL('?', cfg('urlvars'), lang('writemessage'));
  $url->set_var('forum_id', $forum->get_current_forum_id());
  $url->set_var('action',   'write');
  $forum->forum_links()->add_link($url, 200);
}


function message_on_write($forum) {
  $forum->breadcrumbs()->add_separator();
  $forum->breadcrumbs()->add_text(lang('writemessage'));
  $parent_id = (int)$_POST['parent_id'];
  $posting   = new Posting;
  $printer   = new MessagePrinter($forum);
  $printer->show_compose($posting, '', $parent_id, FALSE);
}


function message_on_respond($forum) {
  $parent_id = (int)$_GET['parent_id'];
  $posting   = $forum->get_forumdb()->get_posting_from_id($parent_id);
  $printer   = new MessagePrinter($forum);

  $forum->breadcrumbs()->add_separator();
  $forum->breadcrumbs()->add_link($posting->get_url());
  $forum->breadcrumbs()->add_separator();
  $forum->breadcrumbs()->add_text(lang('writeanswer'));

  $printer->show_compose_reply($posting, '');
}


function message_on_edit_saved($forum) {
  $user    = $forum->get_current_user();
  $posting = $forum->get_forumdb()->get_posting_from_id($_GET['msg_id']);
  $printer = new MessagePrinter($forum);

  if (!cfg('postings_editable'))
    die('Postings may not be changed as per configuration.');
  if ($posting->get_user_is_anonymous())
    die('Anonymous postings may not be changed.');
  elseif ($user->is_anonymous())
    die('You are not logged in.');
  elseif ($user->get_id() != $posting->get_user_id())
    die('You are not the owner.');

  $forum->breadcrumbs()->add_separator();
  $forum->breadcrumbs()->add_link($posting->get_url());
  $forum->breadcrumbs()->add_separator();
  $forum->breadcrumbs()->add_text(lang('editposting'));

  $printer->show_compose($posting, '', 0, FALSE);
}


function message_on_submit($forum) {
  if ($_POST['quote'])
    message_on_quote($forum);
  elseif ($_POST['preview'])
    message_on_preview($forum);
  elseif ($_POST['send'])
    message_on_send($forum);
  elseif ($_POST['edit'])
    message_on_edit_unsaved($forum);
  else
    die('message_on_submit(): no matching POST field found.');
}


// Edit an unsaved message.
function message_on_edit_unsaved($forum) {
  $parent_id = (int)$_POST['parent_id'];
  $may_quote = (int)$_POST['may_quote'];
  $posting   = message_init_posting_from_post_data();
  $printer   = &new MessagePrinter($forum);

  $printer->show_compose($posting, '', $parent_id, $may_quote);
}


// Insert a quote from the parent message.
function message_on_quote($forum) {
  $parent_id  = (int)$_POST['parent_id'];
  $quoted_msg = $forum->get_forumdb()->get_posting_from_id($parent_id);
  $posting    = message_init_posting_from_post_data();
  $printer    = &new MessagePrinter($forum);
  $printer->show_compose_quoted($posting, $quoted_msg, '');
}


// Print a preview of a message.
function message_on_preview($forum) {
  global $err;
  $parent_id = (int)$_POST['parent_id'];
  $may_quote = (int)$_POST['may_quote'];
  $printer   = new MessagePrinter($forum);
  $user      = $forum->get_current_user();
  $message   = new Message(message_get_new_posting($forum), $forum);
  message_init_posting_from_post_data($message);

  // Check the posting for completeness.
  $ret = $message->check_complete();
  if ($ret < 0)
    return $printer->show_compose($message,
                                  $err[$ret],
                                  $parent_id,
                                  $may_quote);

  // Make sure that the username is not in use.
  if ($user->is_anonymous()
    && !$forum->_username_available($message->get_username()))
     return $printer->show_compose($message,
                                   lang('usernamenotavailable'),
                                   $parent_id,
                                   $may_quote);

  // Success.
  /* Plugin hook: on_message_preview_print
   *   Called before the HTML for the posting preview is produced.
   *   Args: posting: The posting that is about to be previewed.
   */
  $forum->eventbus->emit('on_message_preview_print', $forum, $message);

  $forum->breadcrumbs()->add_separator();
  $forum->breadcrumbs()->add_text(lang('preview'));

  $printer->show_preview($message, $parent_id, $may_quote);
}


// Saves the posted message.
function message_on_send($forum) {
  global $err;
  $parent_id = (int)$_POST['parent_id'];
  $may_quote = (int)$_POST['may_quote'];
  $printer   = new MessagePrinter($forum);
  $user      = $forum->get_current_user();
  $forum_id  = $forum->get_current_forum_id();
  $forumdb   = $forum->get_forumdb();
  $forum->_assert_may('write');

  // Check whether editing is allowed per configuration.
  if ($_POST['msg_id'] && !cfg('postings_editable'))
    die('Postings may not be changed as per configuration.');

  // Fetch the posting from the database (when editing an existing one) or
  // create a new one from the POST data.
  if ($_POST['msg_id']) {
    $posting = $forumdb->get_posting_from_id($_POST['msg_id']);
    $posting->set_subject($_POST['subject']);
    $posting->set_body($_POST['body']);
    $posting->set_updated_unixtime(time());
  }
  else {
    $posting = message_get_new_posting($forum);
    message_init_posting_from_post_data($posting);
  }

  // Make sure that the user is not trying to spoof a name.
  if (!$user->is_anonymous()
    && $user->get_name() !== $posting->get_username())
    die('Username does not match currently logged in user');

  // Check the posting for completeness.
  $ret = $posting->check_complete();
  if ($ret < 0)
    return $printer->show_compose($posting,
                                  $err[$ret],
                                  $parent_id,
                                  $may_quote);

  // Make sure that the username is not in use.
  if ($user->is_anonymous()
    && !$forum->_username_available($posting->get_username()))
    return $printer->show_compose($posting,
                                  lang('usernamenotavailable'),
                                  $parent_id,
                                  $may_quote);

  if ($posting->get_id() <= 0) {
    // If the posting a new one (not an edited one), check for duplicates.
    $duplicate_id = $forumdb->get_duplicate_id_from_posting($posting);
    if ($duplicate_id)
      $forum->_refer_to_posting_id($duplicate_id);

    $blocked_until = $forum->_flood_blocked_until($posting);
    if ($blocked_until) {
      $args = array('seconds' => $blocked_until - time());
      return $printer->show_compose($posting,
                                    lang('too_many_postings', $args),
                                    $parent_id,
                                    $may_quote);
    }
  }

  // Save the posting.
  if ($posting->get_id())
    $forumdb->save($forum_id, $parent_id, $posting);
  else
    $forumdb->insert($forum_id, $parent_id, $posting);
  if (!$posting->get_id())
    return $printer->show_compose($posting,
                                  lang('posting_save_failed'),
                                  $parent_id,
                                  $may_quote);

  // Success! Refer to the new item.
  $forum->_refer_to_posting_id($posting->get_id());
}


/***********************************************
 * Utilities.
 ***********************************************/
// Returns a new Posting object that is initialized for the current
// user/group.
function message_get_new_posting($forum) {
  $posting = new Posting;
  $posting->set_from_group($forum->get_current_group());
  $posting->set_from_user($forum->get_current_user());
  return $posting;
}


function message_init_posting_from_post_data($_posting = NULL) {
  if (!$_posting)
    $_posting = new Posting;
  $_posting->set_id($_POST['msg_id']);
  $_posting->set_username($_POST['username']);
  $_posting->set_subject($_POST['subject']);
  $_posting->set_body($_POST['body']);
  return $_posting;
}
?>
