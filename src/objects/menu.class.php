<?php
  /*
  Freech.
  Copyright (C) 2008 Samuel Abels

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
  class Menu extends MenuItem {
    var $items;

    // Constructor.
    function Menu() {
      $this->MenuItem();
      $this->items        = array();
      $this->max_priority = 0;
    }


    function is_submenu() {
      return TRUE;
    }


    function length() {
      return count($this->items);
    }


    function add_index($_url,
                       $_total    = 1,
                       $_per_page = 10,
                       $_size     = 10,
                       $_offset   = 0) {
      // Calculate the total number of pages.
      $n_pages = ceil($_total / $_per_page);
      if ($n_pages <= 0)
        $n_pages = 1;

      // Find the selected page's number from the parent with the given offset.
      $activepage = ceil($_offset / $_per_page) + 1;

      // Find the first number to show in the index.
      $n_indexoffset = 1;
      if ($activepage > $_size / 2)
        $n_indexoffset = $activepage - ceil($_size / 2);
      if ($n_indexoffset + $_size > $n_pages)
        $n_indexoffset = $n_pages - $_size;
      if ($n_indexoffset < 1)
        $n_indexoffset = 1;

      // Show the << link to the previous page.
      if ($activepage == 1)
        $this->add_text('<<');
      else {
        $url = clone($_url);
        $url->set_var('hs', $_per_page * ($activepage - 2));
        $url->set_label('<<');
        $this->add_link($url);
      }

      // Always show a link to the first page.
      $url = clone($_url);
      $url->set_var('hs', 0);
      if ($n_indexoffset > 1) {
        $url->set_label('1');
        $this->add_link($url);
      }
      if ($n_indexoffset > 2)
        $this->add_text('...');

      // Print the numbers. Print the active number using another color.
      for ($i = $n_indexoffset;
           $i <= $n_indexoffset + $_size && $i <= $n_pages;
           $i++) {
        if ($i == $activepage)
          $this->add_text($i);
        else {
          $url = clone($url);
          $url->set_var('hs', ($i - 1) * $_per_page);
          $url->set_label($i);
          $this->add_link($url);
        }
      }

      // Always show a link to the last page.
      $url = clone($url);
      if ($n_indexoffset + $_size < $n_pages - 1)
        $this->add_text('...');
      if ($n_indexoffset + $_size < $n_pages) {
        $url->set_var('hs', ($n_pages - 1) * $_per_page);
        $url->set_label($n_pages);
        $this->add_link($url);
      }

      // Show the >> link to the next page.
      if ($activepage >= $n_pages)
        $this->add_text('>>');
      else {
        $url = clone($_url);
        $url->set_var('hs', $_per_page * $activepage);
        $url->set_label('>>');
        $this->add_link($url);
      }
    }


    function add_item($_item, $_priority = NULL) {
      if ($_priority === NULL)
        $_priority = $this->max_priority + 1000;
      while ($this->items[$_priority])
        $_priority += 1;
      $this->items[$_priority] = $_item;
      $this->max_priority = max($this->max_priority, $_priority);
    }


    function add_link($_url, $_priority = NULL) {
      $this->add_item(new MenuItem($_url), $_priority);
    }


    function add_html($_html, $_priority = NULL) {
      $item = new MenuItem;
      $item->set_html($_html);
      $this->add_item($item, $_priority);
    }


    function add_text($_text = '', $_priority = NULL) {
      $this->add_item(new MenuItem(NULL, $_text), $_priority);
    }


    function add_separator($_priority = NULL) {
      $this->add_item(new MenuItem(), $_priority);
    }


    function get_items() {
      ksort($this->items);
      return array_values($this->items);
    }
  }
?>
