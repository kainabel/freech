<?php
/*
Plugin:      Registration
Version:     0.1
Author:      Samuel Abels
Description: Adds pages for user registration to the forum.
Constructor: registration_init
Active:      1
*/
include_once dirname(__FILE__).'/registration_printer.class.php';

function registration_init($forum) {
  $forum->register_action('account_register',  'registration_on_register');
  $forum->register_action('account_create',    'registration_on_create');
  $forum->register_action('account_confirm',   'registration_on_confirm');
  $forum->register_action('account_reconfirm', 'registration_on_reconfirm');
}


function registration_on_register($forum) {
  $registration = &new RegistrationPrinter($forum);
  $registration->show(new User);
}


function registration_on_create($forum) {
  $registration = &new RegistrationPrinter($forum);
  $user         = $forum->_init_user_from_post_data();

  // Check the data for completeness.
  $err = $user->check_complete();
  if ($err)
    return $registration->show($user, $err);
  if ($_POST['password'] !== $_POST['password2'])
    return $registration->show($user, _('Error: Passwords do not match.'));

  // Make sure that the name is available.
  if (!$forum->_username_available($user->get_name())) {
    $msg = _('The entered username is not available.');
    return $registration->show($user, $msg);
  }

  // Make sure that the email address is available.
  if ($forum->get_userdb()->get_user_from_mail($user->get_mail())) {
    $msg = _('The given email address already exists in our database.');
    return $registration->show($user, $msg);
  }

  // Create the user.
  $user->set_group_id(cfg('default_group_id'));
  $userdb = $forum->get_userdb();
  if (!$userdb->save_user($user))
    return $registration->show($user, _('Failed to save the user.'));

  // Done.
  registration_mail_send($forum, $user);
}


// Called when the user opens the link in the initial account confirmation
// mail.
function registration_on_confirm($forum) {
  $userdb = $forum->get_userdb();
  $user   = $userdb->get_user_from_name($_GET['username']);
  $forum->_assert_confirmation_hash_is_valid($user);

  // See if the user still needs to set a password.
  if (!$user->get_password_hash()) {
    $url = new URL(cfg('site_url').'?', cfg('urlvars'));
    $url->set_var('action',   'password_change');
    $url->set_var('username', $user->get_name());
    $url->set_var('hash',     $_GET['hash']);
    $forum->_refer_to($url->get_string());
  }

  // Make the user active.
  $user->set_status(USER_STATUS_ACTIVE);
  $ret = $userdb->save_user($user);
  if ($ret < 0)
    die('User activation failed');

  // Done.
  $registration = &new RegistrationPrinter($forum);
  $registration->show_done($user);
}


function registration_on_reconfirm($forum) {
  $userdb  = $forum->get_userdb();
  $user    = $userdb->get_user_from_name($_GET['username']);
  if ($user->get_status() != USER_STATUS_UNCONFIRMED)
    die('User is already confirmed.');
  registration_mail_send($forum, $user);
}


/***********************************************
 * Utilities.
 ***********************************************/
function registration_mail_send($forum, $user) {
  $subject  = _('Your registration at [SITE_TITLE]');
  $body     = _("Hello [FIRSTNAME] [LASTNAME],\n"
              . "\n"
              . "Thank you for registering at"
              . " [SITE_TITLE]. Your account name"
              . " is \"[LOGIN]\".\n"
              . "\n"
              . "Please confirm your email address by"
              . " clicking the registration link below."
              . "\n"
              . "[URL]\n");
  $username = urlencode($user->get_name());
  $hash     = urlencode($user->get_confirmation_hash());
  $url      = cfg('site_url') . '?action=account_confirm'
            . "&username=$username&hash=$hash";
  $forum->_send_account_mail($user, $subject, $body, array('url' => $url));
  $printer = new RegistrationPrinter($forum);
  $printer->show_mail_sent($user);
}
?>
