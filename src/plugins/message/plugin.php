<?php
/*
Plugin:      Message
Version:     0.1
Author:      Samuel Abels
Description: Shows normal messages in the forum.
*/
include dirname(__FILE__).'/message.class.php';

function message_init(&$api) {
  $api->eventbus()->signal_connect('on_run_before', 'message_on_run');

  // Register a class that is responsible for formatting the posting object
  // that holds the message.
  $api->register_renderer('message', 'Message');

  // Register our extra actions.
  $api->register_action('write',          'message_on_write');
  $api->register_action('respond',        'message_on_respond');
  $api->register_action('edit',           'message_on_edit_saved');
  $api->register_action('message_submit', 'message_on_submit');
}


function message_on_run(&$api) {

  $forum_id = $api->forum() ? $api->forum()->get_id() : NULL;

  // go away in the absence of write permission
  if (!$api->group()->may('write'))
    return;

  // 'panic' switch in config file was set
  if (cfg('set_read_only')) {
    $api->group()->permissions['write'] = FALSE;
    $api->unregister_action('write');
    $api->unregister_action('respond');
    $api->unregister_action('edit');
    return;
  }

  // permission to write on read only forums?
  // moderator actions are still permitted
  if ( isset($forum_id) && $api->forum()->is_readonly()
       && !$api->group()->may('write_ro') ) {
    $api->group()->permissions['write'] = FALSE;
    $api->unregister_action('write');
    $api->unregister_action('respond');
    $api->unregister_action('edit');
    return;
  }

  // Add 'new message' button to the index bar.
  $url = new FreechURL('', _('Start a New Topic'));
  $url->set_var('forum_id', $forum_id);
  $url->set_var('action',   'write');
  $api->links('page')->add_link($url, 200);
}


function message_on_write(&$api) {
  include dirname(__FILE__).'/message_controller.class.php';
  $api->breadcrumbs()->add_text(_('Start a New Topic'));
  $parent_id  = (int)$_POST['parent_id'];
  $posting    = new Posting;
  $controller = new MessageController($api);
  $controller->show_compose($posting, $parent_id, FALSE);
}


function message_on_respond(&$api) {
  include dirname(__FILE__).'/message_controller.class.php';
  $parent_id  = (int)$_GET['parent_id'];
  $posting    = $api->forumdb()->get_posting_from_id($parent_id);
  $controller = new MessageController($api);
  if (!$posting)
    die('Invalid parent ID');

  $api->breadcrumbs()->add_link($posting->get_url());
  $api->breadcrumbs()->add_text(_('Reply'));

  $controller->show_compose_reply($posting);
}


function message_on_edit_saved(&$api) {
  include dirname(__FILE__).'/message_controller.class.php';
  $user       = $api->user();
  $posting    = $api->forumdb()->get_posting_from_id($_GET['msg_id']);
  $controller = new MessageController($api);

  if (!cfg('postings_editable'))
    die('Postings may not be changed as per configuration.');
  if ($posting->get_user_is_anonymous())
    die('Anonymous postings may not be changed.');
  elseif ($user->is_anonymous())
    die('You are not logged in.');
  elseif ($user->get_id() != $posting->get_user_id())
    die('You are not the owner.');

  $api->breadcrumbs()->add_link($posting->get_url());
  $api->breadcrumbs()->add_text(_('Edit'));

  $controller->show_compose($posting, 0, FALSE);
}


function message_on_submit(&$api) {
  if ($_POST['quote'])
    message_on_quote($api);
  elseif ($_POST['preview'])
    message_on_preview($api);
  elseif ($_POST['send'])
    message_on_send($api);
  elseif ($_POST['edit'])
    message_on_edit_unsaved($api);
  else
    die('message_on_submit(): no matching POST field found.');
}


// Edit an unsaved message.
function message_on_edit_unsaved(&$api) {
  include dirname(__FILE__).'/message_controller.class.php';
  $parent_id  = (int)$_POST['parent_id'];
  $may_quote  = (int)$_POST['may_quote'];
  $posting    = message_init_posting_from_post_data();
  $controller = new MessageController($api);

  $controller->show_compose($posting, $parent_id, $may_quote);
}


// Insert a quote from the parent message.
function message_on_quote(&$api) {
  include dirname(__FILE__).'/message_controller.class.php';
  $parent_id  = (int)$_POST['parent_id'];
  $quoted_msg = $api->forumdb()->get_posting_from_id($parent_id);
  $posting    = message_init_posting_from_post_data();
  $controller = new MessageController($api);
  $controller->show_compose_quoted($posting, $quoted_msg);
}


// Print a preview of a message.
function message_on_preview(&$api) {
  include dirname(__FILE__).'/message_controller.class.php';
  $parent_id  = (int)$_POST['parent_id'];
  $may_quote  = (int)$_POST['may_quote'];
  $controller = new MessageController($api);
  $user       = $api->user();
  $message    = new Message(message_get_new_posting($api), $api);
  message_init_posting_from_post_data($message);

  // Check the posting for completeness.
  $err = $message->check_complete();
  if ($err) {
    $controller->add_hint(new \hint\Error($err));
    return $controller->show_compose($message, $parent_id, $may_quote);
  }

  // Make sure that the username is not in use.
  if ($user->is_anonymous()
   && !$api->userdb()->username_is_available($message->get_username())) {
    $err = _('The entered username is not available.');
    $controller->add_hint(new \hint\Error($err));
    return $controller->show_compose($message, $parent_id, $may_quote);
  }

  // Success.
  /* Plugin hook: on_message_preview_print
   *   Called before the HTML for the posting preview is produced.
   *   Args: posting: The posting that is about to be previewed.
   */
  $api->eventbus()->emit('on_message_preview_print', $api, $message);

  $api->breadcrumbs()->add_text(_('Preview'));

  $controller->show_preview($message, $parent_id, $may_quote);
}


// Saves the posted message.
function message_on_send(&$api) {
  include dirname(__FILE__).'/message_controller.class.php';
  $parent_id  = (int)$_POST['parent_id'];
  $may_quote  = (int)$_POST['may_quote'];
  $controller = new MessageController($api);
  $user       = $api->user();
  $forum_id   = $api->forum()->get_id();
  $forumdb    = $api->forumdb();
  $api->group()->assert_may('write');

  // Check whether editing is allowed per configuration.
  if ($_POST['msg_id'] && !cfg('postings_editable'))
    die('Postings may not be changed as per configuration.');

  // Fetch the posting from the database (when editing an existing one) or
  // create a new one from the POST data.
  if ($_POST['msg_id']) {
    $posting  = $forumdb->get_posting_from_id($_POST['msg_id']);
    $old_hash = $posting->get_hash();
    $posting->set_subject($_POST['subject']);
    $posting->set_body($_POST['body']);
    $new_hash = $posting->get_hash();
    // Was the content changed?
    if ($old_hash === $new_hash) {
      $api->refer_to_posting($posting);
    } else {
      // Processing without labeling as modified after creation for xx seconds.
      $marker_delay = (int) cfg('posting_marker_delay', 10);
      $created_on   = (int) $posting->get_created_unixtime();
      $updated_on = time();
      if (($created_on + $marker_delay) < $updated_on)
        $posting->set_updated_unixtime($updated_on);
    }
  }
  else {
    $posting = message_get_new_posting($api);
    message_init_posting_from_post_data($posting);
  }

  // Make sure that the user is not trying to spoof a name.
  if (!$user->is_anonymous()
    && $user->get_name() !== $posting->get_username())
    die('Username does not match currently logged in user');

  // Check the posting for completeness.
  $err = $posting->check_complete();
  if ($err) {
    $controller->add_hint(new \hint\Error($err));
    return $controller->show_compose($posting, $parent_id, $may_quote);
  }

  // Make sure that the username is not in use.
  if ($user->is_anonymous()
   && !$api->userdb()->username_is_available($posting->get_username())) {
    $err = _('The entered username is not available.');
    $controller->add_hint(new \hint\Error($err));
    return $controller->show_compose($posting, $parent_id, $may_quote);
  }

  if ($posting->get_id() <= 0) {
    // If the posting a new one (not an edited one), check for duplicates.
    $duplicate_id = $forumdb->get_duplicate_id_from_posting($posting);
    if ($duplicate_id)
      $api->refer_to_posting_id($duplicate_id);

    // Check whether too many messages were sent.
    $blocked_until = $api->forumdb()->get_flood_blocked_until($posting);
    if ($blocked_until) {
      $err  = sprintf(_('You have sent too many messages.'
                      . ' %d seconds until your message may be sent.'),
                      $blocked_until - time());
      $controller->add_hint(new \hint\Error($err));
      return $controller->show_compose($posting, $parent_id, $may_quote);
    }

    // Check whether the user or IP is spam-locked.
    if ($api->forumdb()->is_spam($posting)) {
      $controller->add_hint(new \hint\Error(_('Message rejected by spamblocker.')));
      return $controller->show_compose($posting, $parent_id, $may_quote);
    }
  }

  // Save the posting.
  $eventbus = $api->eventbus();
  if ($posting->get_id()) {
    $forumdb->save($forum_id, $parent_id, $posting);

    /* Plugin hook: on_message_edit_after
     *   Called after a message was edited.
     *   Args: parent: The parent message id or NULL.
     *         posting: The posting that was saved.
     */
    $eventbus->emit('on_message_edit_after', $api, $parent_id, $posting);
  }
  else {
    $forumdb->insert($forum_id, $parent_id, $posting);

    /* Plugin hook: on_message_insert_after
     *   Called after a new message was posted.
     *   Args: parent: The parent message id or NULL.
     *         posting: The posting that was sent.
     */
    $eventbus->emit('on_message_insert_after', $api, $parent_id, $posting);
  }

  if (!$posting->get_id()) {
    $controller->add_hint(new \hint\Error(_('Failed to save the posting.')));
    return $controller->show_compose($posting, $parent_id, $may_quote);
  }

  // Success! Refer to the new item.
  $api->refer_to_posting($posting);
}


/***********************************************
 * Utilities.
 ***********************************************/
// Returns a new Posting object that is initialized for the current
// user/group.
function &message_get_new_posting(&$api) {
  $posting = new Posting;
  $posting->set_from_group($api->group());
  $posting->set_from_user($api->user());
  return $posting;
}


function &message_init_posting_from_post_data(&$_posting = NULL) {
  if (!$_posting)
    $_posting = new Posting;
  $_posting->set_id($_POST['msg_id']);
  $_posting->set_username($_POST['username']);
  $_posting->set_subject($_POST['subject']);
  $_posting->set_body($_POST['body']);
  return $_posting;
}
?>
