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
      $this->html = '';
      if ($_url)
        $this->set_url($_url);
      else
        $this->url = new URL('', cfg('urlvars'));
      if ($_text)
        $this->url->set_label($_text);
    }


    function is_separator() {
      return !$this->get_text() && !$this->is_link() && !$this->is_html();
    }


    function is_html() {
      return $this->html != '';
    }


    function is_link() {
      return $this->url->get_base() ? TRUE : FALSE;
    }


    function is_submenu() {
      return FALSE;
    }


    function get_text() {
      return $this->url->get_label();
    }


    function set_html($_html) {
      $this->html = $_html;
    }


    function get_html() {
      return $this->html;
    }


    function set_url($_url) {
      $this->url = clone($_url);
    }


    function get_url() {
      return $this->url;
    }


    function get_url_html() {
      if (!$this->is_link())
        return '';
      return $this->url->get_html();
    }


    function get_url_string() {
      if (!$this->is_link())
        return '';
      return $this->url->get_string();
    }
  }
?>
