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
  class ProfilePrinter extends PrinterBase {
    function ProfilePrinter(&$_forum) {
      $this->PrinterBase(&$_forum);
      $this->messages = array();
    }


    function _append_message(&$_message, $_data) {
      // Required to enable correct formatting of the message.
      $msg_id = $this->parent->get_message_id();
      $_message->set_selected($_message->get_id() == $msg_id);
      $_message->apply_block();

      // Append everything to a list.
      array_push($this->messages, $_message);
    }


    function show($_user, $_hint = '') {
      $search    = array('userid' => $_user->get_id());
      $n_entries = $this->db->get_n_messages($search);

      // Render the template.
      $this->smarty->assign_by_ref('user',       $_user);
      $this->smarty->assign_by_ref('hint',       $_hint);
      $this->smarty->assign_by_ref('n_messages', $n_entries);
      $this->parent->append_content($this->smarty->fetch('profile.tmpl'));
    }


    function show_user_postings($_user, $_thread_state, $_offset = 0) {
      $current  = $this->parent->get_current_user();
      $showlist = $current && $_user->get_username()
                           == $current->get_username();

      // Load the threads (if they are to be displayed).
      $this->smarty->clear_all_assign();
      if ($showlist) {
        $this->db->foreach_message_from_user($_user->get_id(),
                                             $_offset,
                                             cfg("epp"),
                                             cfg("updated_threads_first"),
                                             $_thread_state,
                                             array(&$this, '_append_message'),
                                             '');
        $this->smarty->assign_by_ref('n_rows',   count($this->messages));
        $this->smarty->assign_by_ref('messages', $this->messages);
      }

      // Create the index bar.
      $search    = array('userid' => $_user->get_id());
      $n_entries = $this->db->get_n_messages($search);
      $args      = array(forum_id            => $this->parent->get_forum_id(),
                         n_messages          => $n_entries,
                         n_messages_per_page => cfg("epp"),
                         n_offset            => $_offset,
                         n_pages_per_index   => cfg("ppi"),
                         thread_state        => $_thread_state);
      $indexbar = &new IndexBarUserPostings($args);

      // Render the template.
      $this->smarty->assign_by_ref('user',     $_user);
      $this->smarty->assign_by_ref('showlist', $showlist);
      $this->smarty->assign_by_ref('indexbar', $indexbar);
      $this->smarty->assign_by_ref('max_namelength',  cfg("max_namelength"));
      $this->smarty->assign_by_ref('max_titlelength', cfg("max_titlelength"));
      $this->parent->append_content($this->smarty->fetch('list_by_thread.tmpl'));
    }


    function show_user_data($_user, $_hint = '') {
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('action', 'user_data_submit');

      // Render the template.
      $this->smarty->assign_by_ref('user',   $_user);
      $this->smarty->assign_by_ref('hint',   $_hint);
      $this->smarty->assign_by_ref('action', $url->get_string());
      $this->parent->append_content($this->smarty->fetch('user_data.tmpl'));
    }


    function show_user_options($_user, $_hint = '') {
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('action', 'user_options_submit');

      // Render the template.
      $this->smarty->assign_by_ref('user',   $_user);
      $this->smarty->assign_by_ref('hint',   $_hint);
      $this->smarty->assign_by_ref('action', $url->get_string());
      $this->parent->append_content($this->smarty->fetch('user_options.tmpl'));
    }
  }
?>
