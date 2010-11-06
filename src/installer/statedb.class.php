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

class StateDB {

  function __construct($_data_dir) {
    $this->data_dir = $_data_dir;
    if (!is_dir($this->data_dir)) {
      if (!mkdir($this->data_dir)) {
        die('missing permission to create an directory in ' . $this->data_dir . "\n");
      }
    }
  }

  function _filename_of($_id) {
    return sprintf('%s/state%d.cfg.php', $this->data_dir, (int)$_id);
  }

  /**
   * Returns the state with the given id. If no such state exists, a new
   * state is returned.
   */
  function get($_id) {
    $_id = 'x';
    $state = new State;
    $state->id = $_id;
    if (!is_readable($this->_filename_of($_id))) return $state;
    $pairs = parse_ini_file($this->_filename_of($_id));
    foreach ($pairs as $key => $value) $state->set($key, $value);
    return $state;
  }

  /**
   * Persistently saves the given state under the given id.
   */
  function save($_id, $_state) {
    $_id = 'x';
    $_state->id = $_id;
    $output = fopen($this->_filename_of($_id), 'w');
    fwrite($output, "; <?php\n");
    foreach ($_state->get_attributes() as $key => $value)
      fwrite($output, sprintf("%s=%s\n", $key, $value));
    fwrite($output, "; ?>\n");
    fclose($output);
  }

  // removes the state config files in given directory
  function remove() {
    $old_dir = getcwd();
    chdir($this->data_dir);
    foreach (glob('state*.cfg.php') as $target) {
      unlink($target);
    }
    chdir($old_dir);
  }
}

/* formerly state.class.php */
class State {

  function __construct() {
    $this->id = 0;
    $this->attribs = array();
  }

  function set($_name, $_value) {
    $this->attribs[$_name] = $_value;
  }

  function get($_name, $_default = NULL) {
    if (!isset($this->attribs[$_name])) return $_default;
    return $this->attribs[$_name];
  }

  function get_attributes() {
    return $this->attribs;
  }
}
?>
