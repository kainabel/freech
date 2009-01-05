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
   * Represents a IndexBar, including the query variables.
   */
  class IndexBarByTime extends IndexBar {
    var $items;


    // Constructor.
    function IndexBarByTime($_args,
                            $_may_write  = FALSE,
                            $_extra_urls = array()) {
      $this->IndexBar();
      $this->forum_id            = $_args[forum_id];
      $this->n_postings          = $_args[n_postings];
      $this->n_postings_per_page = $_args[n_postings_per_page];
      $this->n_offset            = $_args[n_offset];
      $this->n_pages_per_index   = $_args[n_pages_per_index];

      // Print the "Index" keyword, followed by a separator.
      $additem = array(&$this, 'add_item');
      call_user_func($additem, lang('index'));

      // Calculate the total number of pages.
      $n_pages = ceil($this->n_postings / $this->n_postings_per_page);
      if ($n_pages <= 0)
        $n_pages = 1;

      // Find the selected page's number from the parent with the given offset.
      $activepage = ceil($this->n_offset / $this->n_postings_per_page) + 1;

      // Find the first number to show in the index.
      $n_indexoffset = 1;
      if ($activepage > $this->n_pages_per_index / 2)
        $n_indexoffset = $activepage - ceil($this->n_pages_per_index / 2);
      if ($n_indexoffset + $this->n_pages_per_index > $n_pages)
        $n_indexoffset = $n_pages - $this->n_pages_per_index;
      if ($n_indexoffset < 1)
        $n_indexoffset = 1;

      // Always show a link to the first page.
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('hs',       0);
      $url->set_var('forum_id', $this->forum_id);
      if ($n_indexoffset > 1) {
        $url->set_var('hs', 0);
        call_user_func($additem, 1, $url);
      }
      if ($n_indexoffset > 2)
        call_user_func($additem, '...');

      // Print the numbers. Print the active number using another color.
      for ($i = $n_indexoffset;
           $i <= $n_indexoffset + $this->n_pages_per_index && $i <= $n_pages;
           $i++) {
        if ($i == $activepage)
          call_user_func($additem, $i);
        else {
          $url = clone($url);
          $url->set_var('hs', ($i - 1) * $this->n_postings_per_page);
          call_user_func($additem, $i, $url);
        }
      }

      // Always show a link to the last page.
      $url = clone($url);
      if ($n_indexoffset + $this->n_pages_per_index < $n_pages - 1)
        call_user_func($additem, '...');
      if ($n_indexoffset + $this->n_pages_per_index < $n_pages) {
        $url->set_var('hs', ($n_pages - 1) * $this->n_postings_per_page);
        call_user_func($additem, $n_pages, $url);
      }

      // "Newer threads" link.
      $url = clone($url);
      call_user_func($additem);
      if ($activepage > 1) {
        $url->set_var('hs', ($activepage - 2) * $this->n_postings_per_page);
        call_user_func($additem, lang('next'), $url);
      }
      else
        call_user_func($additem, lang('next'));

      // "Older threads" link.
      $url = clone($url);
      call_user_func($additem);
      if ($activepage < $n_pages) {
        $url->set_var('hs', $activepage * $this->n_postings_per_page);
        call_user_func($additem, lang('prev'), $url);
      }
      else
        call_user_func($additem, lang('prev'), $url);

      // "New posting" link.
      if ($_may_write) {
        $url = clone($url);
        $url->delete_var('hs');
        $url->set_var('action', 'write');
        call_user_func($additem);
        call_user_func($additem, lang('writemessage'), $url);
      }

      foreach ($_extra_urls as $url) {
        call_user_func($additem);
        call_user_func($additem, $url->get_label(), $url);
      }
    }
  }
?>
