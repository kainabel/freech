<?php
  /*
  Tefinch.
  Copyright (C) 2003 Samuel Abels, <spam debain org>

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
  define("MESSAGE_RELATION_PARENT_STUB",     1);
  define("MESSAGE_RELATION_PARENT_UNFOLDED", 2);
  define("MESSAGE_RELATION_PARENT_FOLDED",   3);
  define("MESSAGE_RELATION_BRANCHEND_STUB",  4);
  define("MESSAGE_RELATION_BRANCHEND",       5);
  define("MESSAGE_RELATION_CHILD_STUB",      6);
  define("MESSAGE_RELATION_CHILD",           7);
  
  /**
   * Represents a message in the forum and all associated data.
   */
  class Message {
    var $_fields;
    
    // Constructor. May be passed a row from the database for initialisation.
    function Message() {
      $this->clear();
    }
    
    
    // Resets all values.
    function clear() {
      $this->_fields = array();
      $this->_fields[relation]     = MESSAGE_RELATION_CHILD;
      $this->_fields[active]       = TRUE;
      $this->_fields[allow_answer] = TRUE;
    }
    
    
    // Sets all values from a given database row.
    function set_from_db(&$_db_row) {
      if (!is_object($_db_row))
        die("Message:set_from_db(): Non-object.");
      $this->clear();
      $this->_fields[id]              = $_db_row->id;
      $this->_fields[forum_id]        = $_db_row->forum_id;
      $this->_fields[username]        = $_db_row->username;
      $this->_fields[subject]         = $_db_row->subject;
      $this->_fields[body]            = $_db_row->body;
      $this->_fields[updated]         = $_db_row->updated;
      $this->_fields[created]         = $_db_row->created;
      $this->_fields[n_children]      = $_db_row->n_children;
      if (isset($_db_row->relation))
        $this->_fields[relation]      = $_db_row->relation;
      $this->_fields[active]          = $_db_row->active;
      if (isset($_db_row->allow_answer))
        $this->_fields[allow_answer]  = $_db_row->allow_answer;
      $this->_fields[next_message_id] = $_db_row->next_message_id;
      $this->_fields[prev_message_id] = $_db_row->prev_message_id;
      $this->_fields[next_thread_id]  = $_db_row->next_thread_id;
      $this->_fields[prev_thread_id]  = $_db_row->prev_thread_id;
    }
    
    
    // Set a unique id for the message.
    function set_id($_id) {
      $this->_fields[id] = $_id * 1;
    }
    
    
    function get_id() {
      return $this->_fields[id];
    }
    
    
    function set_forum_id($_forum_id) {
      $this->_fields[forum_id] = $_forum_id * 1;
    }
    
    
    function get_forum_id() {
      return $this->_fields[forum_id];
    }
    
    
    function set_username($_username) {
      $this->_fields[username] = $_username;
    }
    
    
    function get_username() {
      return $this->_fields[username];
    }
    
    
    function set_subject($_subject) {
      $this->_fields[subject] = $_subject;
    }
    
    
    function get_subject() {
      return $this->_fields[subject];
    }
    
    
    function set_body($_body) {
      $this->_fields[body] = $_body;
    }
    
    
    function get_body() {
      return $this->_fields[body];
    }
    
    
    function get_created_unixtime() {
      return $this->_fields[created];
    }
    
    
    // Returns the formatted time.
    function get_created_time($_format = '') {
      global $lang; //FIXME
      if (!$_format)
        $_format = $lang[dateformat];
      return date($_format, $this->_fields[created]);
    }
    
    
    // Returns whether the row was newly created in the last X minutes.
    function is_new() {
      return (time() - $this->_fields[created] < 60 * 60 * 24);
    }
    
    
    function set_updated_time($_updated) {
      $this->_fields[updated] = $_updated * 1;
    }
    
    
    function get_updated_unixtime() {
      return $this->_fields[updated];
    }
    
    
    // Returns the formatted time.
    function get_updated_time($_format = '') {
      global $lang; //FIXME
      if (!$_format)
        $_format = $lang[dateformat];
      return date($_format, $this->_fields[updated]);
    }
    
    
    // The number of children.
    function set_n_children($_n_children) {
      $this->_fields[n_children] = $_n_children;
    }
    
    
    function get_n_children() {
      if ($this->_fields[relation] != MESSAGE_RELATION_PARENT_STUB
        && $this->_fields[relation] != MESSAGE_RELATION_PARENT_UNFOLDED
        && $this->_fields[relation] != MESSAGE_RELATION_PARENT_FOLDED)
        die("Message:get_n_children(): This function must not be called on"
          . " non-parent rows.");
      return $this->_fields[n_children] * 1;
    }
    
    
    // The relation is the relation in the tree, see the define()s above.
    function set_relation($_relation) {
      $this->_fields[relation] = $_relation;
    }
    
    
    function get_relation() {
      return $this->_fields[relation];
    }
    
    
    function set_active($_active = TRUE) {
      $this->_fields[active] = $_active;
    }
    
    
    function is_active() {
      return $this->_fields[active];
    }
    
    
    function set_allow_answer($_allow = TRUE) {
      $this->_fields[allow_answer] = $_allow;
    }
    
    
    function get_allow_answer() {
      return $this->_fields[allow_answer];
    }
    
    
    function has_thread() {
      if ($this->_fields[relation] != MESSAGE_RELATION_PARENT_STUB
        && $this->_fields[relation] != MESSAGE_RELATION_PARENT_UNFOLDED
        && $this->_fields[relation] != MESSAGE_RELATION_PARENT_FOLDED)
        return TRUE;
      return $this->_fields[n_children] != 0;
    }
    
    
    function set_next_message_id($_next_message_id) {
      $this->_fields[next_message_id] = $_next_message_id * 1;
    }
    
    
    function get_next_message_id() {
      return $this->_fields[next_message_id];
    }
    
    
    function set_prev_message_id($_prev_message_id) {
      $this->_fields[prev_message_id] = $_prev_message_id * 1;
    }
    
    
    function get_prev_message_id() {
      return $this->_fields[prev_message_id];
    }
    
    
    function set_next_thread_id($_next_thread_id) {
      $this->_fields[next_thread_id] = $_next_thread_id * 1;
    }
    
    
    function get_next_thread_id() {
      return $this->_fields[next_thread_id];
    }
    
    
    function set_prev_thread_id($_prev_thread_id) {
      $this->_fields[prev_thread_id] = $_prev_thread_id * 1;
    }
    
    
    function get_prev_thread_id() {
      return $this->_fields[prev_thread_id];
    }
    
    
    function set_selected($_selected = TRUE) {
      $this->_fields[selected] = $_selected;
    }
    
    
    function is_selected() {
      return $this->_fields[selected];
    }
  }
?>
