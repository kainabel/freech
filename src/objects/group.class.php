<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels

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
define('GROUP_STATUS_DELETED', 0);
define('GROUP_STATUS_ACTIVE',  1);

  /**
   * Represents a group of users.
   */
  class Group {
    /// Constructor.
    function Group(&$_row = '') {
      if ($_row) {
        $this->fields      = $_row;
        $this->permissions = array();
      }
      else
        $this->clear();
    }


    /// Resets all values.
    function clear() {
      $this->fields          = array();
      $this->fields['created'] = time();
      $this->permissions     = array();
    }


    /// Set a unique id for the user.
    function set_id($_id) {
      $this->fields['id'] = (int)$_id;
    }


    function get_id() {
      return $this->fields['id'];
    }


    function is_anonymous() {
      return $this->get_id() == cfg('anonymous_group_id');
    }


    function set_name($_name) {
      $this->fields['name'] = trim($_name);
    }


    function get_name() {
      return $this->fields['name'];
    }


    function get_icon() {
      return 'data/group_icons/'.$this->fields['id'].'.png';
    }


    function set_special($_special = TRUE) {
      $this->fields['is_special'] = (bool)$_special;
    }


    function is_special() {
      return $this->fields['is_special'];
    }


    function set_status($_status) {
      $this->fields['status'] = (int)$_status;
    }


    function get_status() {
      return $this->fields['status'];
    }


    function is_active() {
      return $this->fields['status'] == GROUP_STATUS_ACTIVE;
    }


    function get_status_name() {
      if ($this->is_active())
        return _('Active');
      else
        return _('Inactive');
    }


    function get_created_unixtime() {
      return $this->fields['created'];
    }


    /// Returns the formatted time.
    function get_created_time($_format = '') {
      if (!$_format)
        $_format = cfg('dateformat');
      return strftime($_format, $this->fields['created']);
    }


    function get_updated_unixtime() {
      return $this->fields['updated'];
    }


    /// Returns the formatted time.
    function get_updated_time($_format = '') {
      if (!$_format)
        $_format = cfg('dateformat');
      return strftime($_format, $this->fields['updated']);
    }


    function get_profile_url() {
      $url = new FreechURL('', $this->get_name());
      $url->set_var('action',    'group_profile');
      $url->set_var('groupname', $this->get_name());
      return $url;
    }


    function get_profile_url_string() {
      return $this->get_profile_url()->get_string();
    }


    function get_profile_url_html() {
      return $this->get_profile_url()->get_html();
    }


    function get_editor_url() {
      $url = new FreechURL('', '[' . _('Edit') . ']');
      $url->set_var('action',    'group_editor');
      $url->set_var('groupname', $this->get_name());
      return $url;
    }


    function get_editor_url_html() {
      return $this->get_editor_url()->get_html();
    }


    function grant($_permission) {
      $this->permissions[$_permission] = TRUE;
    }


    function deny($_permission) {
      $this->permissions[$_permission] = FALSE;
    }


    // the call of invalid or missing $actions returns per default FALSE
    function may($_permission) {
      return $this->permissions[$_permission] == TRUE;
    }


    function assert_may($_permission) {
      if (!$this->may($_permission))
        die('Permission denied.');
    }


    function get_permission_list() {
      $actions = array('write',      // write permissions
                       'administer', // member of group admin
                       'moderate',   // member of group moderators
                       'delete',     // lock posts
                       'bypass',     // show locked posts
                       'unlock',     // unlock locked posts
                       'write_ro',   // write on read only forums
                       );
      $permissions = array();
      foreach ($actions as $action)
        $permissions[$action] = $this->may($action);
      return $permissions;
    }


    /// Returns an error code if any of the required fields is not filled.
    function check_complete() {
      if (ctype_space($this->fields['name']))
        return _('Error: Please enter a group name.');
      return 0;
    }
  }
?>
