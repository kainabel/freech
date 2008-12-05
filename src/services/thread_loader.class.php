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
  class ThreadLoader {
    var $thread_state;
    var $messages;

    function ThreadLoader($_db, $_thread_state) {
      $this->db           = $_db;
      $this->thread_state = $_thread_state;
      $this->messages     = array();
    }


    function load_threads_from_forum($_forum_id, $_offset) {
      $this->db->foreach_child($_forum_id,
                               0,
                               $_offset,
                               cfg("tpp"),
                               cfg("updated_threads_first"),
                               $this->thread_state,
                               array(&$this, '_append_row'),
                               '');
    }


    function load_threads_from_user($_user_id, $_offset) {
      $this->db->foreach_message_from_user($_user_id,
                                           $_offset,
                                           cfg("epp"),
                                           cfg("updated_threads_first"),
                                           $this->thread_state,
                                           array(&$this, '_append_row'),
                                           '');
    }


    function load_thread_from_message($_forum_id, $_msg_id, $_offset) {
      $this->db->foreach_child_in_thread($_forum_id,
                                         $_msg_id,
                                         $_offset,
                                         cfg("tpp"),
                                         $this->thread_state,
                                         array(&$this, '_append_row'),
                                         '');
    }


    function _append_row(&$_message, $_data) {
      // Required to enable correct formatting of the message.
      $_message->apply_block();
      if ($_message->get_id() == $_GET[msg_id])
        $_message->set_selected();

      // Append everything to a list.
      array_push($this->messages, $_message);
    }
  }
?>
