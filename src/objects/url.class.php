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
  class URL {
    var $label;
    var $path;
    var $vars;


    // Constructor.
    function URL($_path = '', $_vars = array(), $_label = '') {
      $this->label = $_label;
      $this->path  = $_path;
      $this->vars  = array();
      $this->set_var_from_array($_vars);
    }


    function get_path() {
      return $this->path;
    }


    // Appends the given variable to the URL.
    function set_var($_name, $_value) {
      if ($_value)
        $this->vars[$_name] = $_value;
      else
        $this->delete_var($_name);
    }


    function set_var_from_array($_array) {
      foreach ($_array as $key => $value)
        $this->set_var($key, $value);
    }


    // Deletes the given variable from the URL.
    function delete_var($_name) {
      unset($this->vars[$_name]);
    }


    // Returns the URL as a string.
    function get_string($_escape = FALSE) {
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
      $url   = $this->get_string(TRUE);
      $label = htmlentities($_label, ENT_QUOTES, 'UTF-8');
      if (!$label)
        $label = $this->get_label(TRUE);
      if (!$label)
        $label = $url;
      return "<a href=\"$url\">$label</a>";
    }
  }
?>
