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

      $renderer_name = $_message->get_renderer_name();
      $renderer      = $this->parent->get_renderer($renderer_name);
      $_message->set_renderer($renderer);

      // Append everything to a list.
      array_push($this->messages, $_message);
    }


    function show($_forum_id, $_msg_id, $_offset, $_thread_state) {
      // Load messages from the database.
      $func = array(&$this, '_append_message');
      if ($_msg_id == 0)
        $this->forumdb->foreach_child($_forum_id,
                                      0,
                                      $_offset,
                                      cfg("tpp"),
                                      cfg("updated_threads_first"),
                                      $_thread_state,
                                      $func,
                                      '');
      else
        $this->forumdb->foreach_child_in_thread($_msg_id,
                                                $_offset,
                                                cfg("tpp"),
                                                $_thread_state,
                                                $func,
                                                '');

      // Create the index bar.
      $group      = $this->parent->get_current_group();
      $may_write  = $group->may('write');
      $extra_urls = $this->parent->get_extra_indexbar_links();
      $n_threads  = $this->forumdb->get_n_threads($_forum_id, $may_write);
      $args       = array(forum_id           => (int)$_forum_id,
                          n_threads          => $n_threads,
                          n_threads_per_page => cfg("tpp"),
                          n_offset           => $_offset,
                          n_pages_per_index  => cfg("ppi"),
                          thread_state       => $_thread_state);
      $n_rows    = count($this->messages);
      $indexbar  = &new IndexBarByThread($args, $may_write, $extra_urls);

      // Render the template.
      $this->clear_all_assign();
      $this->assign_by_ref('indexbar',           $indexbar);
      $this->assign_by_ref('n_rows',             $n_rows);
      $this->assign_by_ref('messages',           $this->messages);
      $this->assign_by_ref('max_usernamelength', cfg("max_usernamelength"));
      $this->assign_by_ref('max_subjectlength',  cfg("max_subjectlength"));
      $this->render('list_by_thread.tmpl');
    }
  }
?>
