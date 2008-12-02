<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels, <http://debain.org>

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
  class ThreadPrinter extends PrinterBase {
    function show($_forum_id, $_msg_id, $_offset, &$_thread_state) {
      // Load messages from the database.
      $loader = new ThreadLoader($this->db, $_thread_state);
      if ($_msg_id == 0)
        $loader->load_threads_from_forum($_forum_id, $_offset);
      else
        $loader->load_thread_from_message($_forum_id, $_msg_id, $_offset);

      // Create the index bar.
      $n_threads = $this->db->get_n_threads($_forum_id);
      $args      = array(forum_id           => (int)$_forum_id,
                         n_threads          => $n_threads,
                         n_threads_per_page => cfg("tpp"),
                         n_offset           => $_offset,
                         n_pages_per_index  => cfg("ppi"),
                         thread_state       => $_thread_state);
      $n_rows   = count($loader->messages);
      $indexbar = &new IndexBarByThread($args);

      // Render the template.
      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('indexbar',        $indexbar);
      $this->smarty->assign_by_ref('n_rows',          $n_rows);
      $this->smarty->assign_by_ref('messages',        $loader->messages);
      $this->smarty->assign_by_ref('max_namelength',  cfg("max_namelength"));
      $this->smarty->assign_by_ref('max_titlelength', cfg("max_titlelength"));
      $this->parent->append_content($this->smarty->fetch('list_by_thread.tmpl'));
    }
  }
?>
