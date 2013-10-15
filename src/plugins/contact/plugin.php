<?php
/*
Plugin:      Contact
Version:     0.2
Author:      Kain Abel
Description: Drop-in for user profile: Send a private message to profile owner.
*/


function contact_init(&$api) {
  $api->register_action('contact_user',  'contact_on_send');
}


function contact_on_read(&$api) {
  if ($_GET['action'] != 'user_profile') { return; }
}

function contact_on_send(&$api) {

  $message_width = 75;

  $error = 0;
  $vars = array();
  if (isset($_POST['id_to']) && !is_array($_POST['id_to'])) {
    $id_to = intval($_POST['id_to']);
  } else {
    $error++;
  }
  if (isset($_POST['action']) && ($_POST['action'] != 'contact_user')) {
    $error++;
  }
  if (isset($_POST['body']) && !is_array($_POST['body'])) {
    $body  = unesc(rtrim($_POST['body']));
    $raw_body  = wordwrap($body, $message_width, "\n");
  } else {
    $error++;
  }
  if (isset($_POST['subject']) && !is_array($_POST['subject'])) {
    $subject = unesc(trim($_POST['subject']));
  } else {
    $error++;
  }
  if (( trim($raw_body) == '' ) || ( trim($subject) == '')) {
    $error++;
  }

  $subject = cfg('contact_subject_prefix') . $subject;
  $use_realname = (bool)($_POST['realname'] == 'yes');

  $from = $api->user();
  if ($from->is_anonymous()) {
    $error++;
  } else {
    $vars['username']  =  $from->get_name();
    $vars['writer'] =  $from->get_nice_mail($use_realname);
  }

  $to = $api->userdb()->get_user_from_id( $id_to);
  if ((!$to) || ($to->is_deleted()) ) {
    $error++;
  } else {
    $vars['to_user'] = $to->get_nice_mail($use_realname);
  }

  $status_head    = esc(_("Status: private message"));
  $status_reject  = esc(_("The message was rejected, because not all fields"
                        . " were filled."));
  $status_success = esc(_("The email has been sent. A blind carbon copy was"
                        . " delivered to you."));

  if ($error != 0 ) {
    echo "<h3>" . $status_head . "</h3><p>" . $status_reject . "</p>\n";
    echo html_get_homebutton();
    return;
  }

  $vars['noreply']  =  cfg('mail_from');
  //FIXME: $vars['username'] =  $vars['username'];

  // Construction of disclaimer in the top of email.
  $head_seperator = str_pad('', $message_width , "=");
  $head_content = array (
    $head_seperator,
    _("This email was dispatched with the address [NOREPLY] to make sure"
    . " under all circumstances that the sender receives your email address"
    . " not without your explicit consent."),
    _("Your answer to user [USERNAME] should be forwarded to following"
    . " address:"),
    "#\n[WRITER]\n", // special case
    _("If your mail client does not show this address, you have to put it"
    . " in manually."),
    _("All answers to [NOREPLY] will be deleted automatically."),
    _("The forum operator is not responsible for the contents of the"
    . " message."),
    $head_seperator,
    _("[USERNAME] wrote:")
  );

  foreach ($head_content as $i => $str) {
    $str = replace_vars($str, $vars);
    if ($str[0] != '#') {
      $str = wordwrap($str, $message_width, "\n", FALSE);
    } else { // special case: don't wrap '#' beginning lines
      $str = substr($str, 1);
    }
    $head_content[$i] = $str;
  }
  $mail_head = implode("\n", $head_content) . "\n";

  $head  = 'MIME-Version: 1.0'."\n"
         . "From: [NOREPLY]\n"
         . "Reply-To: [WRITER]\n"
         . 'Content-Type: text/plain; charset=UTF-8'."\n"
         . 'Content-Transfer-Encoding: 8bit';
  $head  = replace_vars($head, $vars);
  $body  = "\n" . $mail_head . "\n" . $raw_body;

  // ready to send the message to user and writer
  // encode to UTF-8
  $subject  = '=?UTF-8?B?'.base64_encode($subject).'?=';
  mail($vars['to_user'], $subject, $body, $head);
  mail($vars['writer'], $subject, $body, $head);

  echo "<h3>" . $status_head . "</h3><p>" . $status_success . "</p>\n";
  echo html_get_homebutton();

}

?>
