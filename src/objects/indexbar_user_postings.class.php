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
  class IndexBarUserPostings extends IndexBar {
    var $items;


    // Constructor.
    function IndexBarUserPostings($_args) {
      $this->IndexBar();
      $action              = $_args[action];
      $user                = $_args[user];
      $n_postings          = $_args[n_postings];
      $n_postings_per_page = $_args[n_postings_per_page];
      $n_offset            = $_args[n_offset];
      $n_pages_per_index   = $_args[n_pages_per_index];
      $thread_state        = $_args[thread_state];

      // Print the "Index" keyword, followed by a separator.
      $additem = array(&$this, 'add_item');
      call_user_func($additem, lang("index"));

      // Calculate the total number of pages.
      $n_pages = ceil($n_postings / $n_postings_per_page);
      if ($n_pages <= 0)
        $n_pages = 1;

      // Find the selected page's number from the parent with the given offset.
      $activepage = ceil($n_offset / $n_postings_per_page) + 1;

      // Find the first number to show in the index.
      $n_indexoffset = 1;
      if ($activepage > $n_pages_per_index / 2)
        $n_indexoffset = $activepage - ceil($n_pages_per_index / 2);
      if ($n_indexoffset + $n_pages_per_index > $n_pages)
        $n_indexoffset = $n_pages - $n_pages_per_index;
      if ($n_indexoffset < 1)
        $n_indexoffset = 1;

      // Always show a link to the first page.
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('action',   $action);
      $url->set_var('username', $user->get_name());
      $url->set_var('hs',     0);
      if ($n_indexoffset > 1) {
        $url->set_var('hs', 0);
        call_user_func($additem, 1, $url);
      }
      if ($n_indexoffset > 2)
        call_user_func($additem, '...');

      // Print the numbers. Print the active number using another color.
      for ($i = $n_indexoffset;
           $i <= $n_indexoffset + $n_pages_per_index && $i <= $n_pages;
           $i++) {
        if ($i == $activepage)
          call_user_func($additem, $i);
        else {
          $url = clone($url);
          $url->set_var('hs', ($i - 1) * $n_postings_per_page);
          call_user_func($additem, $i, $url);
        }
      }

      // Always show a link to the last page.
      $url = clone($url);
      if ($n_indexoffset + $n_pages_per_index < $n_pages - 1)
        call_user_func($additem, '...');
      if ($n_indexoffset + $n_pages_per_index < $n_pages) {
        $url->set_var('hs', ($n_pages - 1) * $n_postings_per_page);
        call_user_func($additem, $n_pages, $url);
      }

      // "Newer threads" link.
      $url = clone($url);
      call_user_func($additem);
      if ($activepage > 1) {
        $url->set_var('hs', ($activepage - 2) * $n_postings_per_page);
        call_user_func($additem, lang("next"), $url);
      }
      else
        call_user_func($additem, lang("next"));

      // "Older threads" link.
      $url = clone($url);
      call_user_func($additem);
      if ($activepage < $n_pages) {
        $url->set_var('hs', $activepage * $n_postings_per_page);
        call_user_func($additem, lang("prev"), $url);
      }
      else
        call_user_func($additem, lang("prev"), $url);
      
      if (!$thread_state)
        die("IndexBarUserPostings:foreach_page(): Thread state.");
      
      $fold = $thread_state->get_default();
      $swap = $thread_state->get_string_swap();
      
      // "Unfold all" link.
      $url = clone($url);
      call_user_func($additem);
      $url->set_var('hs',       $n_offset);
      $url->set_var('refer_to', $_SERVER['REQUEST_URI']);
      if ($fold != THREAD_STATE_UNFOLDED || $swap != '') {
        $url->set_var('user_postings_fold', THREAD_STATE_UNFOLDED);
        call_user_func($additem, lang("unfoldall"), $url);
      }
      else
        call_user_func($additem, lang("unfoldall"));
      
      // "Fold all" link.
      $url = clone($url);
      call_user_func($additem);
      if ($fold != THREAD_STATE_FOLDED || $swap != '') {
        $url->set_var('user_postings_fold', THREAD_STATE_FOLDED);
        call_user_func($additem, lang("foldall"), $url);
      }
      else
        call_user_func($additem, lang("foldall"));
    }
  }
?>
