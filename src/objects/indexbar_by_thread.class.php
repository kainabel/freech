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
   * Represents the index bar that is shown when listing all threads.
   */
  class IndexBarByThread extends IndexBar {
    var $items;
    
    
    // Constructor.
    function IndexBarByThread($_args) {
      $this->IndexBar();
      $this->n_threads          = $_args[n_threads];
      $this->n_threads_per_page = $_args[n_threads_per_page];
      $this->n_offset           = $_args[n_offset];
      $this->n_pages_per_index  = $_args[n_pages_per_index];
      $this->folding            = $_args[folding];

      // Print the "Index" keyword, followed by a separator.
      $additem = array(&$this, 'add_item');
      call_user_func($additem, lang("index"));
      
      // Calculate the total number of pages.
      $n_pages = ceil($this->n_threads / $this->n_threads_per_page);
      if ($n_pages <= 0)
        $n_pages = 1;
      
      // Find the selected page's number from the parent with the given offset.
      $activepage = ceil($this->n_offset / $this->n_threads_per_page) + 1;
      
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
      $url->set_var('list',  1);
      $url->set_var('hs',    0);
      $url->set_var('forum', (int)$_GET[forum_id]);
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
          $url->set_var('hs', ($i - 1) * $this->n_threads_per_page);
          call_user_func($additem, $i, $url);
        }
      }
      
      // Always show a link to the last page.
      $url = clone($url);
      if ($n_indexoffset + $this->n_pages_per_index < $n_pages - 1)
        call_user_func($additem, '...');
      if ($n_indexoffset + $this->n_pages_per_index < $n_pages) {
        $url->set_var('hs', ($n_pages - 1) * $this->n_threads_per_page);
        call_user_func($additem, $n_pages, $url);
      }
      
      // "Newer threads" link.
      $url = clone($url);
      call_user_func($additem);
      if ($activepage > 1) {
        $url->set_var('hs', ($activepage - 2) * $this->n_threads_per_page);
        call_user_func($additem, lang("next"), $url);
      }
      else
        call_user_func($additem, lang("next"));
      
      // "Older threads" link.
      $url = clone($url);
      call_user_func($additem);
      if ($activepage < $n_pages) {
        $url->set_var('hs', $activepage * $this->n_threads_per_page);
        call_user_func($additem, lang("prev"), $url);
      }
      else
        call_user_func($additem, lang("prev"));
      
      if (!$this->folding)
        die("IndexBarStrategy_list_by_thread:foreach_page(): Folding.");
      
      $fold  = $this->folding->get_default();
      $swap  = $this->folding->get_string_swap();
      
      // "Unfold all" link.
      $url = clone($url);
      call_user_func($additem);
      $url->set_var('hs', (int)$_GET[hs]);
      if ($fold != UNFOLDED || $swap != '') {
        $url->set_var('fold', UNFOLDED);
        call_user_func($additem, lang("unfoldall"), $url);
      }
      else
        call_user_func($additem, lang("unfoldall"));
      
      // "Fold all" link.
      $url = clone($url);
      call_user_func($additem);
      if ($fold != FOLDED || $swap != '') {
        $url->set_var('fold', FOLDED);
        call_user_func($additem, lang("foldall"), $url);
      }
      else
        call_user_func($additem, lang("foldall"));
      
      // "New message" link.
      $url = clone($url);
      $url->delete_var('fold');
      $url->delete_var('hs');
      $url->delete_var('list');
      $url->set_var('write', 1);
      call_user_func($additem);
      call_user_func($additem, lang("writemessage"), $url);
    }
  }
?>
