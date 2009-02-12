<?php
  /*
  Freech.
  Copyright (C) 2003-2009 Samuel Abels, <http://debain.org>

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
  */
?>
<?php
class DefaultSetup extends Step {
  function show($_args = array()) {
    $this->render('default_setup.tmpl', $_args);
  }


  function check() {
    $username  = trim($_POST['username']);
    $password1 = trim($_POST['password1']);
    $password2 = trim($_POST['password2']);

    // Check the syntax.
    $errors = array();
    if ($username == '')
      array_push($errors, new Result('Checking the username.',
                                     FALSE,
                                     'Please enter a username.'));
    if ($password1 == '')
      array_push($errors, new Result('Checking the password.',
                                     FALSE,
                                     'Please enter a password.'));
    if ($password1 != $password2)
      array_push($errors, new Result('Checking the password.',
                                     FALSE,
                                     'The passwords do not match.'));
    if (count($errors) > 0) {
      $this->show($errors);
      return FALSE;
    }

    // Connect to the database.
    $db = ADONewConnection($this->state->get('dbn'));
    if (!$db) {
      array_push($errors, new Result('Connecting to the database.',
                                     FALSE,
                                     'Database connection failed.'));
      $this->show($errors);
      return FALSE;
    }

    // Create the user.
    $salt   = util_get_random_string(10);
    $userdb = new UserDB($db);
    $user   = new User;
    $user->set_name($username);
    $user->set_password($password1, $salt);
    $user->set_group_id(1); //FIXME: hardcoded
    $user->set_status(USER_STATUS_ACTIVE);
    if (!$userdb->save_user($user)) {
      $result = new Result('Creating the user.', FALSE, 'Failed.');
      array_push($errors, $result);
      $this->show($errors);
      return FALSE;
    }

    // Find the public address of the forum.
    $hostname  = $_SERVER['SERVER_NAME'];
    $mail_from = 'noreply@' . $hostname;
    $full_path = $_SERVER['PHP_SELF'];
    $home_path = substr($full_path, 0, strrpos($full_path, 'installer'));
    $site_url  = 'http://' . $hostname . $home_path;
    $this->state->set('site_url', $site_url);

    // Write the configuration file.
    $config = array('db_dbn'    => $this->state->get('dbn'),
                    'salt'      => $salt,
                    'site_url'  => $site_url,
                    'mail_from' => $mail_from);
    $res = util_write_config('../data/config.inc.php', $config);
    if (!$res->result) {
      array_push($errors, $result);
      $this->show($errors);
      return FALSE;
    }

    // Store the new version number in the database.
    $dbn = $this->state->get('dbn');
    $res = util_store_attribute($dbn, 'version', FREECH_VERSION);
    if (!$res->result) {
      array_push($errors, $result);
      $this->show($errors);
      return FALSE;
    }

    // Success!
    return TRUE;
  }
}
?>
