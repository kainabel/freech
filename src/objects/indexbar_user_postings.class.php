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
  class IndexBarUserPostings extends Menu {
    var $items;


    // Constructor.
    function IndexBarUserPostings($_args) {
      $this->Menu();

      // Prints the index (pagination).
      $url = new FreechURL;
      $url->set_var('action',   $_args[action]);
      $url->set_var('username', $_args[user]->get_name());
      $url->set_var('hs',     0);
      $this->add_index($url,
                       $_args[n_postings],
                       $_args[n_postings_per_page],
                       $_args[n_pages_per_index],
                       $_args[n_offset]);

      // Prepare thread state links.
      $fold = $_args[thread_state]->get_default();
      $swap = $_args[thread_state]->get_string_swap();
      $url->set_var('hs',       $_args[n_offset]);
      $url->set_var('refer_to', $_SERVER['REQUEST_URI']);

      // "Unfold all" link.
      $this->add_separator();
      if ($fold != THREAD_STATE_UNFOLDED || $swap != '') {
        $url = clone($url);
        $url->set_var('user_postings_fold', THREAD_STATE_UNFOLDED);
        $url->set_label(_('Unfold All'));
        $this->add_link($url);
      }
      else
        $this->add_text(_('Unfold All'));

      // "Fold all" link.
      $this->add_separator();
      if ($fold != THREAD_STATE_FOLDED || $swap != '') {
        $url = clone($url);
        $url->set_var('user_postings_fold', THREAD_STATE_FOLDED);
        $url->set_label(_('Fold All'));
        $this->add_link($url);
      }
      else
        $this->add_text(_('Fold All'));
    }
  }
?>
