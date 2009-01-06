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
  class LatestPrinter extends PrinterBase {
    var $postings;

    function LatestPrinter(&$_forum) {
      $this->PrinterBase(&$_forum);
      $this->postings = array();
    }


    function _append_row(&$_posting, $_data) {
      // Required to enable correct formatting of the posting.
      $posting    = $this->parent->_decorate_posting($_posting);
      $current_id = $this->parent->get_current_posting_id();
      $posting->set_selected($posting->get_id() == $current_id);
      $posting->apply_block();

      // Append everything to a list.
      array_push($this->postings, $posting);
    }


    function show($_forum_id, $_offset) {
      $n = $this->forumdb->foreach_latest_posting((int)$_forum_id,
                                                  (int)$_offset,
                                                  cfg("epp"),
                                                  FALSE,
                                                  array(&$this, '_append_row'),
                                                  '');

      $group      = $this->parent->get_current_group();
      $extra_urls = $this->parent->get_extra_indexbar_links();
      $search     = array('forum_id' => (int)$_forum_id);
      $n_entries  = $this->forumdb->get_n_postings($search);
      $args       = array(forum_id            => (int)$_forum_id,
                          n_postings          => (int)$n_entries,
                          n_postings_per_page => cfg("epp"),
                          n_offset            => (int)$_offset,
                          n_pages_per_index   => cfg("ppi"));
      $indexbar = &new IndexBarByTime($args);
      $indexbar->add_links($extra_urls);

      $this->clear_all_assign();
      $this->assign_by_ref('indexbar', $indexbar);
      $this->assign_by_ref('n_rows',   $n);
      $this->assign_by_ref('postings', $this->postings);
      $this->render('list_by_time.tmpl');
    }
  }
?>
