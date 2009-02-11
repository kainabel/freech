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
  define('FORUM_STATUS_INACTIVE', 0);
  define('FORUM_STATUS_ACTIVE',   1);

  class Forum {
    function Forum($_title = '', $_description = '') {
      $this->fields['id']          = NULL;
      $this->fields['owner_id']    = NULL;
      $this->fields['name']        = $_name;
      $this->fields['description'] = $_description;
      $this->fields['status']      = FORUM_STATUS_ACTIVE;
      $this->fields['status_text'] = '';
    }


    function set_from_assoc(&$_row) {
      $this->fields = array_merge($this->fields, $_row);
    }


    function set_id($_id) {
      $this->fields['id'] = (int)$_id;
    }


    function get_id() {
      return $this->fields['id'];
    }


    function set_owner_id($_id) {
      $this->fields['owner_id'] = (int)$_id;
    }


    function get_owner_id() {
      return $this->fields['owner_id'];
    }


    function set_name($_name) {
      $this->fields['name'] = $_name;
    }


    function get_name() {
      return $this->fields['name'];
    }


    function set_description($_description) {
      $this->fields['description'] = $_description;
    }


    function get_description() {
      return $this->fields['description'];
    }


    function &get_url() {
      $url = new FreechURL('', $this->get_name());
      $url->set_var('forum_id', $this->get_id());
      return $url;
    }


    function get_url_html() {
      return $this->get_url()->get_html();
    }


    function &get_editor_url() {
      $url = new FreechURL('', '[' . _('Edit') . ']');
      $url->set_var('action',   'forum_edit');
      $url->set_var('forum_id', $this->get_id());
      return $url;
    }


    function get_editor_url_html() {
      return $this->get_editor_url()->get_html();
    }


    function set_status($_status) {
      $this->fields['status'] = (int)$_status;
    }


    function get_status() {
      return $this->fields['status'];
    }


    function is_active() {
      return $this->fields['status'] == FORUM_STATUS_ACTIVE;
    }


    function get_status_names($_status = -1) {
      $list = array(
        FORUM_STATUS_INACTIVE => _('Inactive'),
        FORUM_STATUS_ACTIVE   => _('Active')
      );
      if ($_status >= 0)
        return $list[$_status];
      return $list;
    }


    function get_status_name() {
      return $this->get_status_names($this->fields['status']);
    }


    function set_status_text($_status_text) {
      $this->fields['status_text'] = $_status_text;
    }


    function get_status_text() {
      return $this->fields['status_text'];
    }


    // Returns an error if any of the required fields is not filled.
    // Returns NULL otherwise.
    function check() {
      if (!$this->fields['name'] || ctype_space($this->fields['name']))
        return _('Please enter a valid name.');
      if (!$this->fields['description'] || ctype_space($this->fields['description']))
        return _('Please enter a summary.');

      return NULL;
    }
  }
?>
