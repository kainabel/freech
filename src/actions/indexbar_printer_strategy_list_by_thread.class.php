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
   * Concrete strategy, prints the index bar for the "threaded" list.
   */
  class IndexBarPrinterStrategy_list_by_thread extends IndexBarPrinterStrategy {
    var $n_threads;
    var $n_threads_per_page;
    var $n_offset;
    var $n_pages_per_index;
    var $folding;
    
    /// Constructor.
    function IndexBarPrinterStrategy_list_by_thread(&$_args) {
      $this->n_threads          = $_args[n_threads];
      $this->n_threads_per_page = $_args[n_threads_per_page];
      $this->n_offset           = $_args[n_offset];
      $this->n_pages_per_index  = $_args[n_pages_per_index];
      $this->folding            = $_args[folding];
    }
    
    
    function foreach_page($_func) {
      global $lang; //FIXME
      global $cfg; //FIXME
      
      // Print the "Index" keyword, followed by a separator.
      call_user_func($_func, $lang[index]);
      call_user_func($_func);
      
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
      $url = new URL('?', $_GET);
      $url->mask(array_merge($cfg[urlvars], 'forum_id', 'list'));  //FIXME: cfg
      if ($n_indexoffset > 1) {
        $url->set_var('hs', 0);
        call_user_func($_func, 1, $url);
      }
      if ($n_indexoffset > 2)
        call_user_func($_func, '...');
      
      // Print the numbers. Print the active number using another color.
      for ($i = $n_indexoffset;
           $i <= $n_indexoffset + $this->n_pages_per_index && $i <= $n_pages;
           $i++) {
        if ($i == $activepage)
          call_user_func($_func, $i);
        else {
          $url->set_var('hs', ($i - 1) * $this->n_threads_per_page);
          call_user_func($_func, $i, $url);
        }
      }
      
      // Always show a link to the last page.
      if ($n_indexoffset + $this->n_pages_per_index < $n_pages - 1)
        call_user_func($_func, '...');
      if ($n_indexoffset + $this->n_pages_per_index < $n_pages) {
        $url->set_var('hs', ($n_pages - 1) * $this->n_threads_per_page);
        call_user_func($_func, $n_pages, $url);
      }
      
      // "Newer threads" link.
      call_user_func($_func);
      if ($activepage > 1) {
        $url->set_var('hs', ($activepage - 2) * $this->n_threads_per_page);
        call_user_func($_func, $lang[next], $url); //FIXME: lang
      }
      else
        call_user_func($_func, $lang[next]); //FIXME: lang
      
      // "Older threads" link.
      call_user_func($_func);
      if ($activepage < $n_pages) {
        $url->set_var('hs', $activepage * $this->n_threads_per_page);
        call_user_func($_func, $lang[prev], $url); //FIXME: lang
      }
      else
        call_user_func($_func, $lang[prev]); //FIXME: lang
      
      $url->delete_var('hs');
      if (!$this->folding)
        die("IndexBarPrinterStrategy_list_by_thread:foreach_page(): Folding.");
      
      $fold  = $this->folding->get_default();
      $swap  = $this->folding->get_string_swap();
      
      // "Unfold all" link.
      call_user_func($_func);
      if ($fold != UNFOLDED || $swap != '') {
        $url->set_var('fold', UNFOLDED);
        call_user_func($_func, $lang[unfoldall], $url); //FIXME: lang
      }
      else
        call_user_func($_func, $lang[unfoldall]); //FIXME: lang
      
      // "Fold all" link.
      call_user_func($_func);
      if ($fold != FOLDED || $swap != '') {
        $url->set_var('fold', FOLDED);
        call_user_func($_func, $lang[foldall], $url); //FIXME: lang
      }
      else
        call_user_func($_func, $lang[foldall]); //FIXME: lang
      $url->delete_var('fold');
      
      // "New message" link.
      $url->set_var('write', 1);
      call_user_func($_func);
      call_user_func($_func, $lang[writemessage], $url); //FIXME: lang
    }
  }
?>
