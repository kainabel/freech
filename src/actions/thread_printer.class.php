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
      $this->postings = array();
    }


    function _append_posting(&$_posting, $_data) {
      // Required to enable correct formatting of the posting.
      $posting    = $this->parent->_decorate_posting($_posting);
      $current_id = $this->parent->get_current_posting_id();
      $posting->set_selected($posting->get_id() == $current_id);
      $posting->apply_block();

      // Append everything to a list.
      array_push($this->postings, $posting);
    }


    function show($_forum_id, $_msg_id, $_offset, $_thread_state) {
      // Load postings from the database.
      $func = array(&$this, '_append_posting');
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
      $extra_urls = $this->parent->get_extra_indexbar_links();
      $n_threads  = $this->forumdb->get_n_threads($_forum_id, $may_write);
      $args       = array(forum_id           => (int)$_forum_id,
                          n_threads          => $n_threads,
                          n_threads_per_page => cfg("tpp"),
                          n_offset           => $_offset,
                          n_pages_per_index  => cfg("ppi"),
                          thread_state       => $_thread_state);
      $n_rows    = count($this->postings);
      $indexbar  = &new IndexBarByThread($args);
      $indexbar->add_links($extra_urls);

      // Render the template.
      $this->clear_all_assign();
      $this->assign_by_ref('indexbar',           $indexbar);
      $this->assign_by_ref('n_rows',             $n_rows);
      $this->assign_by_ref('postings',           $this->postings);
      $this->assign_by_ref('max_usernamelength', cfg("max_usernamelength"));
      $this->assign_by_ref('max_subjectlength',  cfg("max_subjectlength"));
      $this->render('list_by_thread.tmpl');
    }
  }
?>
