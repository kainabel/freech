<?php
  /*
  Freech.
  Copyright (C) 2003-2008 Samuel Abels, <http://debain.org>

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
class DatabaseSetup extends Step {
  function show($_args = array()) {
    $vars = array('db_host'     => $this->state->get('db_host'),
                  'db_user'     => $this->state->get('db_user'),
                  'db_password' => $this->state->get('db_password'),
                  'db_name'     => $this->state->get('db_name'),
                  'errors'      => $_args[errors],
                  'success'     => !$_args[errors]);
    $this->render('database_setup.tmpl', $vars);
  }


  function _get_dbn($_args) {
    return sprintf('%s://%s:%s@%s/%s',
                   $_args[db_type],
                   $_args[db_user],
                   $_args[db_password],
                   $_args[db_host],
                   $_args[db_name]);
  }


  function check() {
    $this->state->set('db_host',     trim($_POST['db_host']));
    $this->state->set('db_user',     trim($_POST['db_user']));
    $this->state->set('db_password', trim($_POST['db_password']));
    $this->state->set('db_name',     trim($_POST['db_name']));

    $vars = $this->state->get_attributes();
    $vars['db_type'] = 'mysqlt';
    $this->state->set('dbn', $this->_get_dbn($vars));

    # Check the syntax.
    if ($this->state->get('db_host') == '') {
      $this->show();
      return FALSE;
    }
    if ($this->state->get('db_user') == '') {
      $this->show();
      return FALSE;
    }
    if ($this->state->get('db_password') == '') {
      $this->show();
      return FALSE;
    }
    if ($this->state->get('db_name') == '') {
      $this->show();
      return FALSE;
    }

    # Check the database.
    $dbn    = $this->state->get('dbn');
    $errors = array();
    $checks = array(util_check_db_connection($dbn),
                    util_check_db_supports_constraints($dbn));
    foreach ($checks as $check)
      if (!$check->result)
        array_push($errors, $check);
    if (count($errors) > 0) {
      $args = array('errors' => $errors);
      $this->show($args);
      return FALSE;
    }

    # Success!
    return TRUE;
  }
}
?>
