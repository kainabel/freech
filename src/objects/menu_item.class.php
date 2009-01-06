<?php
  /*
  Freech.
  Copyright (C) 2008 Samuel Abels, <http://debain.org>

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
   * Represents a Menu, including the query variables.
   */
  class MenuItem {
    var $text;
    var $url;


    // Constructor.
    function MenuItem($_url = '', $_text = '') {
      if ($_url)
        $this->set_url($_url);
      if ($_text) {
        $this->url  = $_url;
        $this->text = $_text;
      }
    }


    function is_separator() {
      return $this->text == '';
    }


    function is_link() {
      return $this->url ? TRUE : FALSE;
    }


    function set_text($_text) {
      $this->text = $_text;
    }


    function get_text() {
      return $this->text;
    }


    function set_url($_url) {
      $this->url  = $_url;
      $this->text = $_url->get_label();
    }


    function get_url() {
      return $this->url;
    }


    function get_url_string() {
      if (!$this->url)
        return '';
      return $this->url->get_string();
    }
  }
?>
