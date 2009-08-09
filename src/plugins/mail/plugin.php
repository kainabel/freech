<?php
/*
Plugin:      Mail
Version:     0.1
Author:      Samuel Abels
Description: Sends email notifications.
*/


function mail_init(&$api) {
  $eventbus = $api->eventbus();
  $eventbus->signal_connect('on_message_insert_after', 'mail_on_submit');
}

function mail_on_submit(&$api, &$parent_id, &$message) {
  if (!$parent_id)
    return;

  // Check whether the author requested a notification.
  $parent = $api->forumdb()->get_posting_from_id($parent_id);
  if (!$parent || !$parent->get_notify_author())
    return;

  // Find the user that requested the notification.
  $user_id = $parent->get_user_id();
  $user    = $api->userdb()->get_user_from_id($user_id);
  if (!$user)
    return;

  // Send the email notification.
  $subject = '[SITE_TITLE]: '.$message->get_subject();
  $vars    = array('subject' => $parent->get_subject(),
                   'sender'  => $message->get_username(),
                   'body'    => $message->get_body());
  $body    = _("Hello [FIRSTNAME] [LASTNAME],\n"
             . "\n"
             . "Your posting [SUBJECT] has received the following"
             . " response from [SENDER].\n"
             . "\n"
             . "*******************************************************\n"
             . "[BODY]"
             . "\n");
  $api->send_mail($user, $subject2, $body);
}
?>
