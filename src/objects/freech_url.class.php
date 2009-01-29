<?php
  /*
  Freech.
  Copyright (C) 2009 Samuel Abels, <http://debain.org>

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
   * Represents a URL, including the query variables.
   */
  class FreechURL extends URL {
    function FreechURL($_path = '', $_label = '') {
      $this->URL($_path, cfg('urlvars'), $_label);
    }


    function _get_mod_rewrite_url($_escape = FALSE) {
      $path     = $this->get_path();
      $action   = $this->get_var('action');
      $forum_id = $this->get_var('forum_id');
      $msg_id   = $this->get_var('msg_id');
      $username = $this->get_var('username');
      $this->delete_var('action');

      if ($forum_id) {
        $this->set_path($path . "forum-$forum_id/");
        $this->delete_var('forum_id');
      }

      switch ($action) {
      case '':
        break;

      case 'user_profile':
        $this->set_path($path . "user/$username/");
        $this->delete_var('username');
        break;

      case 'user_editor':
        $this->set_path($path . "user/$username/edit");
        $this->delete_var('username');
        break;

      case 'user_postings':
        $this->set_path($path . "user/$username/postings");
        $this->delete_var('username');
        break;

      default:
        $this->set_var('action', $action);
      }

      return parent::get_string($_escape);
    }


    function get_string($_escape = FALSE) {
      if (!$_GET['rewrite'])
        return parent::get_string($_escape);
      return $this->_get_mod_rewrite_url($_escape);
    }
  }
?>
