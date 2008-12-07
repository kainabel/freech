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
    function ThreadPrinter(&$_parent) {
      $this->PrinterBase(&$_parent);
      $this->messages = array();
    }


    function _append_message(&$_message, $_data) {
      // Required to enable correct formatting of the message.
      $msg_id = $this->parent->get_current_message_id();
      $_message->set_selected($_message->get_id() == $msg_id);
      $_message->apply_block();

      // Append everything to a list.
      array_push($this->messages, $_message);
    }


    function show($_forum_id, $_msg_id, $_offset, $_thread_state) {
      // Load messages from the database.
      if ($_msg_id == 0)
        $this->db->foreach_child($_forum_id,
                                 0,
                                 $_offset,
                                 cfg("tpp"),
                                 cfg("updated_threads_first"),
                                 $_thread_state,
                                 array(&$this, '_append_message'),
                                 '');
      else
        $this->db->foreach_child_in_thread($_msg_id,
                                           $_offset,
                                           cfg("tpp"),
                                           $_thread_state,
                                           array(&$this, '_append_message'),
                                           '');

      // Create the index bar.
      $n_threads = $this->db->get_n_threads($_forum_id);
      $args      = array(forum_id           => (int)$_forum_id,
                         n_threads          => $n_threads,
                         n_threads_per_page => cfg("tpp"),
                         n_offset           => $_offset,
                         n_pages_per_index  => cfg("ppi"),
                         thread_state       => $_thread_state);
      $n_rows   = count($this->messages);
      $indexbar = &new IndexBarByThread($args);

      // Render the template.
      $this->clear_all_assign();
      $this->assign_by_ref('indexbar',        $indexbar);
      $this->assign_by_ref('n_rows',          $n_rows);
      $this->assign_by_ref('messages',        $this->messages);
      $this->assign_by_ref('max_namelength',  cfg("max_namelength"));
      $this->assign_by_ref('max_titlelength', cfg("max_titlelength"));
      $this->render('list_by_thread.tmpl');
    }
  }
?>
