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
   * Concrete strategy, prints the index bar for "by time" ordered list.
   */
  class IndexBarStrategy_list_by_time extends IndexBarStrategy {
    var $n_messages;
    var $n_messages_per_page;
    var $n_offset;
    var $n_pages_per_index;
    
    /// Constructor.
    function IndexBarStrategy_list_by_time(&$_args) {
      $this->n_messages          = $_args[n_messages];
      $this->n_messages_per_page = $_args[n_messages_per_page];
      $this->n_offset            = $_args[n_offset];
      $this->n_pages_per_index   = $_args[n_pages_per_index];
    }
    
    
    function foreach_link($_func) {
      global $cfg; //FIXME
      
      // Print the "Index" keyword, followed by a separator.
      call_user_func($_func, lang("index"));
      
      // Calculate the total number of pages.
      $n_pages = ceil($this->n_messages / $this->n_messages_per_page);
      if ($n_pages <= 0)
        $n_pages = 1;
      
      // Find the selected page's number from the parent with the given offset.
      $activepage = ceil($this->n_offset / $this->n_messages_per_page) + 1;
      
      // Find the first number to show in the index.
      $n_indexoffset = 1;
      if ($activepage > $this->n_pages_per_index / 2)
        $n_indexoffset = $activepage - ceil($this->n_pages_per_index / 2);
      if ($n_indexoffset + $this->n_pages_per_index > $n_pages)
        $n_indexoffset = $n_pages - $this->n_pages_per_index;
      if ($n_indexoffset < 1)
        $n_indexoffset = 1;
      
      // Always show a link to the first page.
      $url = new URL('?', $cfg[urlvars]);  //FIXME: cfg
      $url->set_var('list',  1);
      $url->set_var('hs',    0);
      $url->set_var('forum', $_GET[forum_id]);
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
          $url->set_var('hs', ($i - 1) * $this->n_messages_per_page);
          call_user_func($_func, $i, $url);
        }
      }
      
      // Always show a link to the last page.
      if ($n_indexoffset + $this->n_pages_per_index < $n_pages - 1)
        call_user_func($_func, '...');
      if ($n_indexoffset + $this->n_pages_per_index < $n_pages) {
        $url->set_var('hs', ($n_pages - 1) * $this->n_messages_per_page);
        call_user_func($_func, $n_pages, $url);
      }
      
      // "Newer threads" link.
      if ($activepage > 1) {
        $url->set_var('hs', ($activepage - 2) * $this->n_messages_per_page);
        call_user_func($_func);
        call_user_func($_func, lang("next"), $url);
      }
      
      // "Older threads" link.
      $older_threads[text] = lang("prev");
      if ($activepage < $pages) {
        $url->set_var('hs', $activepage * $this->n_messages_per_page);
        call_user_func($_func);
        call_user_func($_func, lang("prev"), $url);
      }
      
      // "New message" link.
      $url->delete_var('hs');
      $url->delete_var('list');
      $url->set_var('write', 1);
      call_user_func($_func);
      call_user_func($_func, lang("writemessage"), $url);
    }
  }
?>
