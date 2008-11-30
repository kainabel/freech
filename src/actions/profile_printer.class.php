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
  class ProfilePrinter extends ThreadPrinter {
    function show($_user) {
      $current  = $this->parent->get_current_user();
      $showlist = $current && $_user->get_login() == $current->get_login();

      if ($showlist)
        $n = $this->db->foreach_message_from_user($_user->get_id(),
                                                  $_GET['hs'],
                                                  cfg("epp"),
                                                  cfg("updated_threads_first"),
                                                  $this->thread_state,
                                                  array(&$this, '_append_row'),
                                                  '');

      $search    = array('userid' => $_user->get_id());
      $n_entries = $this->db->get_n_messages($search);
      $args      = array(forum_id            => $this->parent->get_forum_id(),
                         n_messages          => $n_entries,
                         n_messages_per_page => cfg("epp"),
                         n_offset            => $_GET['hs'],
                         n_pages_per_index   => cfg("ppi"),
                         thread_state        => $this->thread_state);
      $indexbar = &new IndexBarUserPostings($args);

      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('user',     $_user);
      $this->smarty->assign_by_ref('showlist', $showlist);
      $this->smarty->assign_by_ref('indexbar', $indexbar);
      $this->smarty->assign_by_ref('n_rows',   $n);
      $this->smarty->assign_by_ref('messages', $this->messages);
      $this->smarty->assign_by_ref('max_namelength',  cfg("max_namelength"));
      $this->smarty->assign_by_ref('max_titlelength', cfg("max_titlelength"));
      $this->parent->append_content($this->smarty->fetch('profile.tmpl'));
    }
  }
?>
