<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels, <http://debain.org>

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
  /**
   * Represents a group of users.
   */
  class Group {
    /// Constructor.
    function Group() {
      $this->clear();
    }


    /// Resets all values.
    function clear() {
      $this->fields          = array();
      $this->fields[created] = time();
      $this->permissions     = array();
    }


    /// Sets all values from a given database row.
    function set_from_db(&$_db_row) {
      if (!is_object($_db_row))
        die("Group:set_from_db(): Non-object.");
      $this->clear();
      $this->fields[id]         = $_db_row->id;
      $this->fields[name]       = $_db_row->name;
      $this->fields[is_special] = $_db_row->is_special;
      $this->fields[active]     = $_db_row->active;
      $this->fields[created]    = $_db_row->created;
      $this->fields[updated]    = $_db_row->updated;
    }


    /// Set a unique id for the user.
    function set_id($_id) {
      $this->fields[id] = (int)$_id;
    }


    function get_id() {
      return $this->fields[id];
    }


    function set_name($_name) {
      if (strlen($_name) < cfg("min_groupnamelength"))
        return ERR_GROUP_NAME_TOO_SHORT;
      if (strlen($_name) > cfg("max_groupnamelength"))
        return ERR_GROUP_NAME_TOO_LONG;
      $this->fields[name] = (int)$_name;
    }


    function get_name() {
      return $this->fields[name];
    }


    function set_special($_special = TRUE) {
      $this->fields[is_special] = (bool)$_special;
    }


    function is_special() {
      return $this->fields[is_special];
    }


    function set_active($_active = TRUE) {
      $this->fields[active] = (bool)$_active;
    }


    function is_active() {
      return $this->fields[active];
    }


    function get_created_unixtime() {
      return $this->fields[created];
    }


    /// Returns the formatted time.
    function get_created_time($_format = '') {
      if (!$_format)
        $_format = lang("dateformat");
      return date($_format, $this->fields[created]);
    }


    function get_updated_unixtime() {
      return $this->fields[updated];
    }


    /// Returns the formatted time.
    function get_updated_time($_format = '') {
      if (!$_format)
        $_format = lang("dateformat");
      return date($_format, $this->fields[updated]);
    }


    function grant($_permission) {
      $this->permissions[$_permission] = TRUE;
    }


    function deny($_permission) {
      $this->permissions[$_permission] = FALSE;
    }


    function may($_permission) {
      return $this->permissions[$_permission] == TRUE;
    }


    /// Returns an error code if any of the required fields is not filled.
    function check_complete() {
      if (ctype_space($this->fields[name]))
        return ERR_GROUP_NAME_INCOMPLETE;
      return 0;
    }
  }
?>
