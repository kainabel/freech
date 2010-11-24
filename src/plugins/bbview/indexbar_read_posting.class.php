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
   * Represents the Menu that is shown when reading a posting.
   */
  class BBViewIndexBarReadPosting extends Menu {
    var $items;


    // Constructor.
    function BBViewIndexBarReadPosting(&$_posting, &$_args) {
      $this->Menu();

      // Prints the index (pagination).
      $this->add_index($_posting->get_thread_url(),
                       $_args['n_postings'],
                       $_args['n_postings_per_page'],
                       $_args['n_pages_per_index'],
                       $_args['n_offset']);
    }
  }
?>
