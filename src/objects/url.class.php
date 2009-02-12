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
   * Represents a URL, including the query variables.
   */
  class FreechURL {
    function FreechURL($_path = '', $_label = '') {
      $this->label = $_label;
      $this->path  = $_path;
      $this->vars  = cfg('urlvars');
    }


    function get_path() {
      return $this->path;
    }


    function set_path($_path) {
      $this->path = $_path;
    }


    // Appends the given variable to the URL.
    function set_var($_name, $_value) {
      if ($_value)
        $this->vars[$_name] = $_value;
      else
        $this->delete_var($_name);
    }


    function get_var($_name, $_default = NULL) {
      if (!$this->vars[$_name])
        return $default;
      return $this->vars[$_name];
    }


    // Deletes the given variable from the URL.
    function delete_var($_name) {
      unset($this->vars[$_name]);
    }


    // Returns the URL as a string.
    function get_string($_escape = FALSE) {
      if ($_GET['rewrite'])
        $this->_update_mod_rewrite_url();

      $query = http_build_query($this->vars);

      if (!$query)
        $url = $this->path;
      elseif (!$this->path)
        $url = '?' . $query;
      elseif (!strstr($this->path, '?'))
        $url = $this->path . '?' . $query;
      elseif (substr($this->path, -1) != '&')
        $url = $this->path . '&' . $query;
      else
        $url = $this->path . $query;

      if (!$url)
        $url = '.';
      if ($_escape)
        return htmlentities($url);
      return $url;
    }


    function set_label($_label = '') {
      $this->label = $_label;
    }


    function get_label($_escape = FALSE) {
      if ($_escape)
        return htmlentities($this->label, ENT_QUOTES, 'UTF-8');
      else
        return $this->label;
    }


    // Returns <a href="...">...</a>
    function get_html($_label = '') {
      $url = $this->get_string(TRUE);
      if ($_label)
        $label = htmlentities($_label, ENT_QUOTES, 'UTF-8');
      elseif ($this->label)
        $label = htmlentities($this->label, ENT_QUOTES, 'UTF-8');
      else
        $label = $url;
      return "<a href=\"$url\">$label</a>";
    }


    function _update_mod_rewrite_url() {
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
    }
  }
?>
