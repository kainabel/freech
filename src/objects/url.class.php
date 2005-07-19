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
  /**
   * Represents a URL, including the query variables.
   */
  class URL {
    var $base;
    var $vars;
    
    
    // Constructor.
    function URL($_base = '?', $_vars = array()) {
      $this->base = $_base;
      $this->vars = $_vars;
    }
    
    
    // Uses the given string as the URL to which the variables are appended.
    function set_base($_base = '') {
      $this->base = $_base;
    }
    
    
    // Appends the given variable to the URL.
    function set_var($_name, $_value) {
      $this->vars[$_name] = $_value;
    }
    
    
    // Deletes the given variable from the URL.
    function delete_var($_name) {
      unset($this->vars[$_name]);
    }
    
    
    // Removes all variables from the URL that are not listed in the mask.
    function mask($_keep = array()) {
      foreach ($_keep as $var)
        $vars[$var] = $this->vars[$var];
      $this->vars = &$vars;
    }
    
    
    // Returns the URL as a string.
    function get_string($_escape = FALSE) {
      if ($_escape)
        return htmlentities($this->base . http_build_query($this->vars));
      else
        return $this->base . http_build_query($this->vars);
    }
  }
?>
