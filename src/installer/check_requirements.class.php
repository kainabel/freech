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
class CheckRequirements extends Step {
  function CheckRequirements($_id, $_smarty, $_state) {
    $this->Step($_id, $_smarty, $_state);
    $this->results = array(
      $this->_is_not_installed(),
      util_check_php_function_exists('gettext')
      //util.check_locale_support(),
    );
    $this->failed = FALSE;
    foreach ($this->results as $result)
      if (!$result->result)
        $this->failed = TRUE;
  }


  function _is_not_installed() {
    $name = 'Checking whether the installation is already complete.';

    // Check whether a config file exists.
    $cfg_file = '../data/config.inc.php';
    if (!file_exists($cfg_file))
      return new Result($name, TRUE);
    if (!is_readable($cfg_file))
      return new Result($name, FALSE, 'An unreadable config file was found.');

    // Check whether that file contains any database config.
    $dbn = cfg('db_dbn', FALSE);
    if (!$dbn)
      return new Result($name, FALSE, 'Config contains no database config.');

    // Check the version of the database schema.
    $installed = util_get_attribute($dbn, 'version');
    if ($installed != FREECH_VERSION)
      return new Result($name, TRUE, "Installed DB schema is $installed.");

    $msg = sprintf('Version %s is already installed.', $installed);
    return new Result($name, FALSE, $msg);
  }


  function show() {
    $args = array('results' => $this->results,
                  'success' => !$this->failed);
    $this->render('check_requirements.tmpl', $args);
  }


  function check() {
    if ($this->failed) {
      $this->show();
      return FALSE;
    }
    return TRUE;
  }
}
?>
