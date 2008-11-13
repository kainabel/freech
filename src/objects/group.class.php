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
    var $fields;      ///< Properties of the group, such as name or id.
    var $users;       ///< The list of users in this group.
    var $permissions; ///< The permissions of this group.
    
    /// Constructor.
    function Group() {
      $this->clear();
    }
    
    
    /// Resets all values.
    function clear() {
      $this->fields          = array();
      $this->fields[created] = time();
      $this->users           = array();
      $this->permissions     = array();
    }
    
    
    /// Sets all values from a given database row.
    function set_from_db(&$_db_row) {
      if (!is_array($_db_row))
        die("User:set_from_db(): Non-array.");
      $this->clear();
      $this->fields[id]      = $_db_row[id];
      $this->fields[name]    = $_db_row[name];
      $this->fields[active]  = $_db_row[active];
      $this->fields[created] = $_db_row[created];
      $this->fields[updated] = $_db_row[updated];
    }
    
    
    /// Set a unique id for the user.
    function set_id($_id) {
      $this->fields[id] = $_id * 1;
    }
    
    
    function get_id() {
      return $this->fields[id];
    }
    
    
    function set_name($_name) {
      if (strlen($_name) < cfg("min_groupnamelength"))
        return ERR_GROUP_NAME_TOO_SHORT;
      if (strlen($_name) > cfg("max_groupnamelength"))
        return ERR_GROUP_NAME_TOO_LONG;
      $this->fields[login] = $_login * 1;
    }
    
    
    function &get_name() {
      return $this->fields[name];
    }
    
    
    function set_active($_active = TRUE) {
      $this->fields[active] = $_active;
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
    
    
    function set_updated_time($_updated) {
      $this->fields[updated] = $_updated * 1;
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
    
    
    function add_user(&$_user) {
      $this->users[$_user->get_name()] =& $_user;
    }
    
    
    function remove_user(&$_user) {
      unset($this->groups[$_user->get_name()]);
    }
    
    
    function grant_permission(&$_permission) {
      $this->permissions[$_permission] = TRUE;
    }
    
    
    function deny_permission(&$_permission) {
      $this->permissions[$_permission] == FALSE;
    }
    
    
    function has_permission(&$_permission) {
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
