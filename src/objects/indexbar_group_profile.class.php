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
  class IndexBarGroupProfile extends Menu {
    var $items;


    // Constructor.
    function IndexBarGroupProfile($_args) {
      $this->Menu();

      // Prints the index (pagination).
      $url = new URL('', cfg('urlvars'));
      $url->set_var('action',    'group_profile');
      $url->set_var('groupname', $_args[group]->get_name());
      $this->add_index($url,
                       $_args[n_users],
                       $_args[n_users_per_page],
                       $_args[n_pages_per_index],
                       $_args[n_offset]);
    }
  }
?>
