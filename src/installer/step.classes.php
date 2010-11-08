<?php
/*
   Freech.
   Copyright (C) 2003-2008 Samuel Abels

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

class Step {

  function Step(&$_id, &$_state) {
    $this->id = $_id;
    $this->state = $_state;
  }

  function render($_filename, $_args = array()) {
    $nextstep = $this->id + 1;
    foreach ($_args as $key => $value) {
      $$key = $value;
    }
    require $_filename;
  }

  function check() {
    return TRUE;
  }

  function submit() {
    return TRUE;
  }
}

/* formerly welcome.class.php */
class Welcome extends Step {

  function show() {
    $this->render('0_welcome.tmpl');
  }

  function check() {
    return TRUE;
  }
}

/* formerly check_requirements.class.php */
class CheckRequirements extends Step {

  function __construct($_id, $_state) {
    $this->Step($_id, $_state);
    $this->results = array($this->_is_not_installed(),
                           util_check_php_function_exists('gettext'));
    $this->failed = FALSE;
    foreach ($this->results as $result) {
      if (!$result->result) {
        $this->failed = TRUE;
      }
    }
  }

  function _is_not_installed() {
    $name = 'Checking whether the installation is already complete.';

    // Check whether a config file exists.
    $cfg_file = '../data/config.inc.php';
    if (!file_exists($cfg_file)) {
      return new Result($name, TRUE);
    }
    if (!is_readable($cfg_file)) {
      return new Result($name, FALSE, 'An unreadable config file was found.');
    }

    // Check whether that file contains any database config.
    $dbn = cfg('db_dbn', FALSE);
    if (!$dbn) {
      return new Result($name, FALSE, 'Config contains no database config.');
    }

    // Check the version of the database schema.
    $installed = util_get_attribute($dbn, 'version');
    if (is_object($installed)) return $installed;
    if ($installed != FREECH_VERSION) {
      return new Result($name, FALSE, "Installed DB schema is $installed. "
      . "Please use the files from folder /update to modify your installation.");
    }
    $msg = sprintf('Version %s is already installed.', $installed);
    return new Result($name, FALSE, $msg);
  }

  function show() {
    $args = array('results' => $this->results, 'success' => !$this->failed,);
    $this->render('1_check_requirements.tmpl', $args);
  }

  function check() {
    if ($this->failed) {
      $this->show();
      return FALSE;
    }
    return TRUE;
  }
}

/* formerly database_setup.class.php */
class DatabaseSetup extends Step {

  function show($_args = array()) {
    $vars = array('db_host'   => $this->state->get('db_host', 'localhost'),
                  'db_user'   => $this->state->get('db_user', 'user'),
                  'db_pass'   => $this->state->get('db_pass'),
                  'db_name'   => $this->state->get('db_name', 'freech'),
                  'db_create' => $this->state->get('db_create'),
                  'db_base'   => $this->state->get('db_base', cfg('db_tablebase')),
                  'errors'    => $_args['errors'],
                  'success'   => !$_args['errors'],
                  'fatal'     => $this->state->get('fatal', null));
    $this->render('2_database_setup.tmpl', $vars);
  }

  function _get_dbn($_args) {
    return sprintf('%s://%s:%s@%s/%s',$_args['db_type'],
                                      $_args['db_user'],
                                      $_args['db_pass'],
                                      $_args['db_host'],
                                      $_args['db_name']);
  }

  function check() {
    if (isset($_POST['db_setup']) && ($_POST['db_setup'] == 'passed')) {
      return TRUE;
    }
    $this->state->set('db_host', trim($_POST['db_host']));
    $this->state->set('db_user', trim($_POST['db_user']));
    $this->state->set('db_pass', trim($_POST['db_pass']));
    $this->state->set('db_name', trim($_POST['db_name']));
    $this->state->set('db_create', (string)trim($_POST['db_create']));

    // A few improvements to the table prefix.
    $db_base = preg_replace('~[^a-z0-9_]~', '', trim($_POST['db_base']));
    if ((substr($db_base, -1)) != '_') $db_base.= '_';
    $this->state->set('db_base', $db_base);

    if ($db_base != trim($_POST['db_base'])) {
      $vars['errors'][] = new Result('Table prefix changed!', FALSE,
        'Only small letters, digits and the underscore are permitted.');
    }

    $vars = $this->state->get_attributes();
    $vars['errors'] = array();
    $name = 'Verify form input.';

    // Check the form input.
    if ($this->state->get('db_host') == '') {
      $vars['errors'][] = new Result($name, FALSE,
        'Database hostname was not specified.');
    }
    if ($this->state->get('db_user') == '') {
      $vars['errors'][] = new Result($name, FALSE,
        'Database user was not specified.');
    }
    if ($this->state->get('db_pass') == '') {
      $vars['errors'][] = new Result($name, FALSE,
        'Password was not specified.');
    }
    if ($this->state->get('db_name') == '') {
      $vars['errors'][] = new Result($name, FALSE,
        'Database name was not specified.');
    }
    if ($this->state->get('db_base') == '') {
      $vars['errors'][] = new Result($name, FALSE,
        'Table prefix was not specified.');
    }
    if (!empty($vars['errors'])) {
      $this->show($vars);
      return FALSE;
    }

    // Check connection and create database, if requested
    $res = util_check_db_connection($vars['db_host'],
                                    $vars['db_user'],
                                    $vars['db_pass'],
                                    $vars['db_name'],
                                    (bool)($vars['db_create'] == 'on'));
    if (is_object($res)) {
      $vars['errors'][] = $res;
      $this->show($vars);
      return FALSE;
    }

    // Check the database.
    $vars['db_type'] = ADODB_DRIVER;
    $this->state->set('dbn', $this->_get_dbn($vars));
    $dbn = $this->state->get('dbn');
    $res = util_check_db_supports_constraints($dbn);
    if (is_object($res)) {
      $vars['errors'][] = $res;
      $this->state->set('fatal', TRUE);
      $this->show($vars);
      return FALSE;
    }

    // Success!
    return TRUE;
  }
}

/* formerly database_install.class.php */
class DatabaseInstall extends Step {

  function __construct($_id, $_state) {

    // don't run again
    if (isset($_POST['db_install']))
      return TRUE;

    $this->Step($_id, $_state);
    $this->results = array();
    $this->failed = FALSE;
    if (($this->state->get('db_install', 'FALSE')) == 'passed') {
      return TRUE;
    }

    // get the table prefix from default_config.inc.php
    $preorg = cfg('db_tablebase', 'freech_');
    $prefix = $this->state->get('db_base');
    if ($prefix == '') {
      die('The table prefix in $db_tablebase was lost. :-/');
    }
    $replace = (bool)($preorg != $prefix);
    $pattern = '~' . $preorg . '~';
    $fp = fopen('mysql_matpath.sql', 'r') or die("SQL file not found!\n");
    while ($sql = util_get_next_sql_command($fp)) {
      if ($replace) {
        $sql = preg_replace($pattern, $prefix, $sql);
      }
      if ($_POST['do_not_act']) {
        // just dump out SQL
        var_dump($sql);
      } else {
        $result = util_execute_sql($this->state->get('dbn'), $sql);
        array_push($this->results, $result);
        if (!$result->result) {
          $this->failed = TRUE;
        }
      }
    }
    fclose($fp);
  }

  function show() {
    if (!$this->failed)
      $this->state->set('db_install', 'passed');
    $args = array('results' => $this->results,
                  'success' => !$this->failed);
    $this->render('3_database_install.tmpl', $args);
  }

  function check() {
    if ($this->failed) {
      $this->show();
      return FALSE;
    }
    return TRUE;
  }
}

/* formerly default_setup.class.php */
class DefaultSetup extends Step {

  function show($_args = array()) {
    if (!isset($_args['salt'])) {
      $_args['salt'] = util_get_random_string(10);
    }
    if (!isset($_args['domain'])) {
      $_args['domain'] = $_SERVER['SERVER_NAME'];
    }
    if (!isset($_args['lang'])) {
      $_args['lang'] = cfg('default_language');
    }
    if (!isset($_args['site'])) {
      $_args['site'] = cfg('site_title');
    }
    if (!isset($_args['rss'])) {
      $_args['rss'] = cfg('rss_enabled');
    }
    if (!isset($_args['desc'])) {
      $_args['desc'] = cfg('rss_description');
    }
    $this->render('4_default_setup.tmpl', $_args);
  }

  function check() {
    $arr['user']      = trim($_POST['user']);
    $arr['pass1']     = trim($_POST['pass1']);
    $arr['pass2']     = trim($_POST['pass2']);
    $arr['salt']      = trim($_POST['salt']);
    $arr['email']     = trim($_POST['email']);
    $arr['firstname'] = trim($_POST['firstname']);
    $arr['lastname']  = trim($_POST['lastname']);
    $arr['domain']    = trim($_POST['domain']);
    $arr['site']      = trim($_POST['site']);
    $arr['lang']      = trim($_POST['lang']);
    $arr['rss']       = trim($_POST['rss']);
    $arr['desc']      = trim($_POST['desc']);

    // Check the syntax.
    $arr['errors'] = array();
    $min = cfg('min_usernamelength');
    $max = cfg('max_usernamelength');
    if ($arr['salt'] == '') {
      $arr['errors'][] = new Result('Checking the salt.', FALSE,
        'Salt string is empty.');
    }
    if ($arr['pass1'] != $arr['pass2']) {
      $arr['errors'][] = new Result('Checking the password.', FALSE,
        'The passwords do not match.');
    }
    if ($arr['pass1']    == $arr['user']
        || $arr['pass1'] == $arr['firstname']
        || $arr['pass1'] == $arr['lastname']
        || $arr['pass1'] == $arr['salt']
        || $arr['pass1'] == $arr['domain']
        || $arr['pass1'] == $arr['email']
        ) {
      $arr['errors'][] = new Result('Checking the password.', FALSE,
        'Be carefull! The password must be different from the other fields!');
    }

    // Create a new user an storing the account data after passing all checks.
    $user = new User;
    $user->set_name($arr['user']);
    if ($err = $user->set_password($arr['pass1'], $arr['salt'])) {
      $arr['errors'][] = new Result('Checking password security.', FALSE, $err);
    }
    $user->set_firstname($arr['firstname']);
    $user->set_lastname($arr['lastname']);
    $user->set_mail($arr['email']);
    if ($err = $user->check_complete()) {
      $arr['errors'][] = new Result('Checking account data.', FALSE, $err);
    }
    $user->set_status(USER_STATUS_ACTIVE);
    $user->set_group_id(1);
    if (count($arr['errors']) > 0) {
      $this->show($arr);
      return FALSE;
    }
    if (($this->state->get('account', 'FALSE')) != 'stored') {
      $res = util_store_user(&$user, &$this);
      if (!$res->result) {
        $arr['errors'][] = $res;
        $this->show($arr);
        return FALSE;
      }
      $this->state->set('account', 'stored');
    }

    // Updating or storing the version number of Freech.
    if (($this->state->get('version', 'FALSE')) != 'stored') {
      $res = util_store_version(&$this);
      if (!$res->result) {
        $arr['errors'][] = $res;
        $this->show($arr);
        return FALSE;
      }
      $this->state->set('version', 'stored');
    }

    // Find the public address of the forum.
    $hostname = $arr['domain'];
    $mail_from = 'noreply@' . $hostname;
    $full_path = $_SERVER['PHP_SELF'];
    $home_path = substr($full_path, 0, strrpos($full_path, 'installer'));
    $site_url = 'http://' . $hostname . $home_path;
    $this->state->set('site_url', $site_url);

    // Write the configuration file.
    $config = array(
      'db_dbn' => $this->state->get('dbn'),
      'db_tablebase' => $this->state->get('db_base'),
      'salt' => $arr['salt'],
      'site_url' => $site_url,
      'mail_from' => $mail_from,
      'content_language' => $arr['lang'],
      'default_language' => $arr['lang'] . '_' . strtoupper($arr['lang']),
      'site_title' => $arr['site'],
      'rss_enabled' => (bool)($arr['rss'] == 'on'),
      'rss_description' => $arr['desc']
      );
    $res = util_write_config('../data/config.inc.php', $config);

    // Success!
    return TRUE;
  }
}

/* formerly done.class.php */
class Done extends Step {

  function show() {
    $arr['site_url'] = $this->state->get('site_url');

    // removing the temporary data file(s)
    global $data_dir;
    $statedb = new StateDB($data_dir);
    $statedb->remove();
    $this->render('5_done.tmpl', $arr);
  }

  function check() {
    return FALSE;
  }
}
?>
