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
 * A base class for all views. Views render the forum overview as well
 * as the page on which a posting is shown.
 */
class ListView extends View {
  function ListView($_forum) {
    $this->View($_forum);
    $this->postings = array();
  }


  function _append_posting(&$_posting, $_data) {
    // Required to enable correct formatting of the posting.
    $posting    = $this->parent->_decorate_posting($_posting);
    $current_id = $this->parent->get_current_posting_id();
    $posting->set_selected($posting->get_id() == $current_id);
    $posting->apply_block();

    // Append everything to a list.
    $this->postings[$_posting->get_id()] = $posting;
  }


  function show($_forum_id, $_offset) {
    $db = $this->forumdb;
    $n  = $db->foreach_latest_posting((int)$_forum_id,
                                      (int)$_offset,
                                      cfg('epp'),
                                      FALSE,
                                      array(&$this, '_append_posting'),
                                      '');

    $group     = $this->parent->get_current_group();
    $search    = array('forum_id' => (int)$_forum_id);
    $n_entries = $this->forumdb->get_n_postings($search);
    $args      = array(forum_id            => (int)$_forum_id,
                       n_postings          => (int)$n_entries,
                       n_postings_per_page => cfg('epp'),
                       n_offset            => (int)$_offset,
                       n_pages_per_index   => cfg('ppi'));
    $indexbar = new IndexBarByTime($args);

    krsort($this->postings);
    $this->clear_all_assign();
    $this->assign_by_ref('indexbar', $indexbar);
    $this->assign_by_ref('n_rows',   $n);
    $this->assign_by_ref('postings', array_values($this->postings));
    $this->render(dirname(__FILE__).'/listview.tmpl');
  }


  function show_posting($_posting) {
    $user      = $this->parent->get_current_user();
    $group     = $this->parent->get_current_group();
    $db        = $this->forumdb;
    $msg_uid   = $_posting ? $_posting->get_user_id() : -1;
    $showlist  = $_posting && $_COOKIE[thread] != 'hide'; //FIXME: rename "thread" cookie
    $may_write = $group->may('write');
    $may_edit  = $may_write
              && cfg('postings_editable')
              && !$user->is_anonymous()
              && $user->get_id() === $msg_uid
              && $_posting->is_editable();
    if (cfg('posting_arrow_reverse')) {
      $prev_posting_id = $db->get_next_posting_id_in_forum($_posting);
      $next_posting_id = $db->get_prev_posting_id_in_forum($_posting);
    }
    else {
      $prev_posting_id = $db->get_prev_posting_id_in_forum($_posting);
      $next_posting_id = $db->get_next_posting_id_in_forum($_posting);
    }
    $indexbar = new ListViewIndexBarReadPosting($_posting,
                                                $prev_posting_id,
                                                $next_posting_id,
                                                $may_write,
                                                $may_edit);

    $this->clear_all_assign();
    $this->assign_by_ref('showlist', $showlist);
    if ($showlist) {
      $posting    = $this->parent->_decorate_posting($_posting);
      $current_id = $this->parent->get_current_posting_id();
      $func       = array(&$this, '_append_posting');
      $posting->set_selected($posting->get_id() == $current_id);
      $this->postings[$_posting->get_id()] = $posting;
      $db->foreach_prev_posting($_posting, cfg('epp') / 2, $func);
      $db->foreach_next_posting($_posting, cfg('epp') / 2, $func);
      krsort($this->postings);
      $this->assign_by_ref('n_rows',   count($this->postings));
      $this->assign_by_ref('postings', array_values($this->postings));
    }

    $this->assign_by_ref('indexbar', $indexbar);
    $this->assign_by_ref('posting',  $_posting);
    $this->assign_by_ref('max_usernamelength', cfg('max_usernamelength'));
    $this->assign_by_ref('max_subjectlength',  cfg('max_subjectlength'));
    $this->render(dirname(__FILE__).'/listview_read_posting.tmpl');
    $this->parent->_set_title($_posting->get_subject());
  }
}
?>
