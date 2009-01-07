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
   * Represents a log entry in the moderation log.
   */
  class ModLogItem {
    /// Constructor.
    function ModLogItem($_action = '') {
      $this->clear();
      $this->set_action($_action);
    }


    /// Resets all values.
    function clear() {
      $this->fields          = array();
      $this->fields[created] = time();
      $this->attributes      = array();
    }


    /// Sets all values from a given database row.
    function set_from_db($_db_row) {
      if (!is_object($_db_row))
        die('ModLogItem:set_from_db(): Non-object.');
      $this->clear();
      $this->fields[id]                   = $_db_row->id;
      $this->fields[moderator_id]         = $_db_row->moderator_id;
      $this->fields[moderator_name]       = $_db_row->moderator_name;
      $this->fields[moderator_group_name] = $_db_row->moderator_group_name;
      $this->fields[moderator_icon]       = $_db_row->moderator_icon;
      $this->fields[action]               = $_db_row->action;
      $this->fields[reason]               = $_db_row->reason;
      $this->fields[created]              = $_db_row->created;
    }


    function set_attribute_from_db($_db_row) {
      if (!is_object($_db_row))
        die('ModLogItem:set_attribute_from_db(): Non-object.');
      $name  = $_db_row->attribute_name;
      $type  = $_db_row->attribute_type;
      $value = $_db_row->attribute_value;
      switch ($type) {
      case 'int':
        $this->set_attribute($name, (int)$value);
        break;

      case 'string':
        $this->set_attribute($name, (string)$value);
        break;

      case 'bool':
        $this->set_attribute($name, (bool)$value);
        break;

      default:
        die('Error: set_attribute_from_db(): Invalid attribute type.');
      }
    }


    function set_from_user($_user) {
      $this->fields[moderator_id]   = $_user->get_id();
      $this->fields[moderator_name] = $_user->get_name();
    }


    function set_from_moderator_group($_group) {
      $this->fields[moderator_group_name] = $_group->get_name();
      $this->fields[moderator_icon]       = $_group->get_icon();
    }


    /// Set a unique id for the item.
    function set_id($_id) {
      $this->fields[id] = (int)$_id;
    }


    function get_id() {
      return $this->fields[id];
    }


    function get_moderator_id() {
      return $this->fields[moderator_id];
    }


    function get_moderator_name() {
      return $this->fields[moderator_name];
    }


    function get_moderator_group_name() {
      return $this->fields[moderator_group_name];
    }


    function get_moderator_icon() {
      return $this->fields[moderator_icon];
    }


    function set_action($_action) {
      $this->fields[action] = $_action;
    }


    function get_action() {
      return $this->fields[action];
    }


    function set_reason($_reason) {
      $this->fields[reason] = $_reason;
    }


    function get_reason() {
      return $this->fields[reason];
    }


    function get_created_unixtime() {
      return $this->fields[created];
    }


    /// Returns the formatted time.
    function get_created_time($_format = '') {
      if (!$_format)
        $_format = lang('dateformat');
      return date($_format, $this->fields[created]);
    }


    function set_attribute($_name, $_value) {
      $this->attributes[$_name] = $_value;
    }


    function get_attribute($_name) {
      return $this->attributes[$_name];
    }


    function get_attribute_list() {
      return $this->attributes;
    }


    function get_html() {
      $args = array('moderator'       => $this->get_moderator_name(),
                    'moderator_id'    => $this->get_moderator_id(),
                    'moderator_icon'  => $this->get_moderator_icon(),
                    'moderator_group' => $this->get_moderator_group());
      $args = array_merge($args, $this->attributes);
      return lang('modlog_'.$this->get_action(), $args);
    }
  }
?>
