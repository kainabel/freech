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
class BBView extends View {
  function BBView($_forum) {
    $this->View($_forum);
    $this->postings    = array();
    $this->posting_map = array();
  }


  function _append_posting(&$_posting, $_data) {
    // Required to enable correct formatting of the posting.
    $current_id = (int)$_GET['msg_id'];
    $_posting->set_selected($_posting->get_id() == $current_id);
    $_posting->apply_block();

    // Append everything to a list.
    array_push($this->postings, $_posting);
    $this->posting_map[$_posting->get_id()] = $_posting;
  }


  function show($_forum_id, $_offset) {
    $db   = $this->forumdb;
    $args = array('forum_id' => (int)$forum_id, 'is_parent' => 1);
    $n    = $db->foreach_posting_from_fields($args,
                                             (int)$_offset,
                                             cfg('epp'),
                                             array(&$this, '_append_posting'),
                                             '');

    $group     = $this->api->group();
    $n_threads = $this->forumdb->get_n_threads((int)$_forum_id);
    $args      = array(forum_id           => (int)$_forum_id,
                       n_threads          => (int)$n_threads,
                       n_threads_per_page => cfg('epp'),
                       n_offset           => (int)$_offset,
                       n_pages_per_index  => cfg('ppi'));
    $indexbar = new IndexBarBBView($args);

    $this->clear_all_assign();
    $this->assign_by_ref('indexbar', $indexbar);
    $this->assign_by_ref('n_rows',   $n);
    $this->assign_by_ref('postings', $this->postings);
    $this->render(dirname(__FILE__).'/bbview.tmpl');
  }


  function show_thread($_thread) {
    $user      = $this->api->user();
    $group     = $this->api->group();
    $may_write = $group->may('write');
    $may_edit  = $may_write
              && cfg('postings_editable')
              && !$user->is_anonymous();

    // Get all postings from the current thread.
    $db   = $this->forumdb;
    $args = array('thread_id' => (int)$_thread->get_thread_id());
    $n    = $db->foreach_posting_from_fields($args,
                                             (int)$_GET['hs'],
                                             cfg('epp'),
                                             array(&$this, '_append_posting'),
                                             '');

    // Create the indexbar.
    $n_postings = $this->forumdb->get_n_postings($args);
    $args       = array(n_postings          => (int)$n_postings,
                        n_postings_per_page => cfg('epp'),
                        n_offset            => (int)$_GET['hs'],
                        n_pages_per_index   => cfg('ppi'));
    $indexbar = new BBViewIndexBarReadPosting($_thread, $args);

    foreach ($this->postings as $posting) {
      /* Plugin hook: on_message_read_print
       *   Called before the HTML for the posting is produced.
       *   Args: posting: The posting that is about to be shown.
       */
      $this->eventbus->emit('on_message_read_print', $this->api, $posting);
    }

    $this->clear_all_assign();
    $this->assign_by_ref('indexbar',  $indexbar);
    $this->assign_by_ref('postings',  $this->postings);
    $this->assign_by_ref('may_write', $may_write);
    $this->assign_by_ref('may_edit',  $may_edit);
    $this->assign_by_ref('max_usernamelength', cfg('max_usernamelength'));
    $this->assign_by_ref('max_subjectlength',  cfg('max_subjectlength'));
    $this->render(dirname(__FILE__).'/bbview_read_thread.tmpl');
    $this->api->set_title($_thread->get_subject());
  }


  function show_posting($_posting) {
    $this->show_thread($_posting);
  }
}
?>
