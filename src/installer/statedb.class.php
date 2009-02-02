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
class StateDB {
  function StateDB($_data_dir) {
    $this->data_dir = $_data_dir;
    if (!is_dir($this->data_dir))
      mkdir($this->data_dir);
  }


  function _filename_of($_id) {
    return sprintf('%s/state%d.cfg', $this->data_dir, (int)$_id);
  }


  /**
   * Returns the state with the given id. If no such state exists, a new 
   * state is returned.
   */
  function get($_id) {
    $state     = new State;
    $state->id = $_id;
    if (!is_readable($this->_filename_of($_id)))
      return $state;

    $pairs = parse_ini_file($this->_filename_of($_id));
    foreach ($pairs as $key => $value)
      $state->set($key, $value);
    return $state;
  }


  /**
   * Persistently saves the given state under the given id.
   */
  function save($_id, $_state) {
    $_state->id = $_id;
    $output     = fopen($this->_filename_of($_id), 'w');
    foreach ($_state->get_attributes() as $key => $value)
      fwrite($output, sprintf("%s=%s\n", $key, $value));
    fclose($output);
  }
}
?>