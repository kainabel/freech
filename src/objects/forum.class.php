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
      $this->id          = NULL;
      $this->owner_id    = NULL;
      $this->name        = $_name;
      $this->description = $_description;
      $this->status      = FORUM_STATUS_ACTIVE;
      $this->status_text = '';
    }


    function set_from_db($_obj) {
      $this->id          = $_obj->id;
      $this->owner_id    = $_obj->owner_id;
      $this->name        = $_obj->name;
      $this->description = $_obj->description;
      $this->status      = $_obj->status;
    }


    function set_id($_id) {
      $this->id = (int)$_id;
    }


    function get_id() {
      return $this->id;
    }


    function set_owner_id($_id) {
      $this->owner_id = (int)$_id;
    }


    function get_owner_id() {
      return $this->owner_id;
    }


    function set_name($_name) {
      $this->name = $_name;
    }


    function get_name() {
      return $this->name;
    }


    function set_description($_description) {
      $this->description = $_description;
    }


    function get_description() {
      return $this->description;
    }


    function get_url() {
      $url = new URL('?', cfg('urlvars'), $this->get_name());
      $url->set_var('forum_id', $this->get_id());
      return $url;
    }


    function get_url_html() {
      return $this->get_url()->get_html();
    }


    function get_editor_url() {
      $url = new URL('?', cfg('urlvars'), lang('forum_edit'));
      $url->set_var('action',   'forum_edit');
      $url->set_var('forum_id', $this->get_id());
      return $url;
    }


    function get_editor_url_html() {
      return $this->get_editor_url()->get_html();
    }


    function set_status($_status) {
      $this->status = (int)$_status;
    }


    function get_status() {
      return $this->status;
    }


    function is_active() {
      return $this->status == FORUM_STATUS_ACTIVE;
    }


    function get_status_names($_status = -1) {
      $list = array(
        FORUM_STATUS_INACTIVE => lang('FORUM_STATUS_INACTIVE'),
        FORUM_STATUS_ACTIVE   => lang('FORUM_STATUS_ACTIVE')
      );
      if ($_status >= 0)
        return $list[$_status];
      return $list;
    }


    function get_status_name() {
      return $this->get_status_names($this->fields[status]);
    }


    function set_status_text($_status_text) {
      $this->status_text = $_status_text;
    }


    function get_status_text() {
      return $this->status_text;
    }


    // Returns an error if any of the required fields is not filled.
    // Returns NULL otherwise.
    function check() {
      if (!$this->name || ctype_space($this->name))
        return lang('forum_invalid_name');
      if (!$this->description || ctype_space($this->description))
        return lang('forum_invalid_description');

      return NULL;
    }
  }
?>
