<?php
/*
Plugin:      Registration
Version:     0.1
Author:      Samuel Abels
Description: Adds pages for user registration to the forum.
*/
include_once dirname(__FILE__).'/registration_controller.class.php';

function registration_init($api) {
  $api->register_action('account_register',  'registration_on_register');
  $api->register_action('account_create',    'registration_on_create');
  $api->register_action('account_confirm',   'registration_on_confirm');
  $api->register_action('account_reconfirm', 'registration_on_reconfirm');

  $url = new FreechURL('', _('Register Account'));
  $url->set_var('action', 'account_register');
  $api->register_url('registration', $url);

  $api->eventbus()->signal_connect('on_run_before', 'registration_on_run');
}


function registration_on_run($api) {
  if ($api->user()->is_anonymous())
    $api->links('account')->add_link($api->get_url('registration'));
}


function registration_on_register($api) {
  $registration = new RegistrationController($api);
  $registration->show(new User);
}


function registration_on_create($api) {
  $registration = new RegistrationController($api);
  $user         = init_user_from_post_data();

  if ($_POST['cancel'])
    $api->refer_to(cfg('site_url'));

  // Check the data for completeness.
  $err = $user->check_complete();
  if ($err)
    $registration->add_hint(new Error($err));
  if ($_POST['password'] !== $_POST['password2'])
    $registration->add_hint(new Error(_('Error: Passwords do not match.')));

  if ($registration->has_errors())
    return $registration->show($user);

  // Make sure that the name is available.
  if (!$api->userdb()->username_is_available($user->get_name())) {
    $err = _('The entered username is not available.');
    $registration->add_hint(new Error($err));
  }

  // Make sure that the email address is available.
  if ($api->userdb()->get_user_from_mail($user->get_mail())) {
    $err = _('The given email address already exists in our database.');
    $registration->add_hint(new Error($err));
  }

  if ($registration->has_errors())
    return $registration->show($user);

  // Create the user.
  $user->set_group_id(cfg('default_group_id'));
  if (!$api->userdb()->save_user($user)) {
    $registration->add_hint(new Error(_('Failed to save the user.')));
    return $registration->show($user);
  }

  // Done.
  registration_mail_send($api, $user);
}


// Called when the user opens the link in the initial account confirmation
// mail.
function registration_on_confirm($api) {
  $userdb = $api->userdb();
  $user   = $userdb->get_user_from_name($_GET['username']);
  assert_user_confirmation_hash_is_valid($user);

  // See if the user still needs to set a password.
  if (!$user->get_password_hash()) {
    $url = new FreechURL(cfg('site_url'));
    $url->set_var('action',   'password_change');
    $url->set_var('username', $user->get_name());
    $url->set_var('hash',     $_GET['hash']);
    $api->refer_to($url->get_string());
  }

  // Make the user active.
  $user->set_status(USER_STATUS_ACTIVE);
  $ret = $userdb->save_user($user);
  if ($ret < 0)
    die('User activation failed');

  // Done.
  $registration = new RegistrationController($api);
  $registration->show_done($user);
}


function registration_on_reconfirm($api) {
  $userdb  = $api->userdb();
  $user    = $userdb->get_user_from_name($_GET['username']);
  if ($user->get_status() != USER_STATUS_UNCONFIRMED)
    die('User is already confirmed.');
  registration_mail_send($api, $user);
}


/***********************************************
 * Utilities.
 ***********************************************/
function registration_mail_send($api, $user) {
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
  $api->send_mail($user, $subject, $body, array('url' => $url));
  $controller = new RegistrationController($api);
  $controller->show_mail_sent($user);
}
?>
