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
class ThreadView extends View {
  function ThreadView($_forum) {
    $this->View($_forum);
  }


  function _format_posting(&$_posting, $_data) {
    $current_id = (int)$_GET['msg_id'];
    $_posting->set_selected($_posting->get_id() == $current_id);
    $_posting->apply_block();
  }


  function show($_forum_id, $_offset) {
    // Load the threads from the database.
    $thread_state = $this->api->thread_state('');
    $func         = array(&$this, '_format_posting');
    $threads      = $this->forumdb->get_threads_from_forum_id($_forum_id,
                                                              $_offset,
                                                              cfg('tpp'));

    // Format the threads.
    foreach ($threads as $thread) {
      $thread->remove_locked_postings();
      $thread->foreach_posting($func);
      if (!$thread_state->is_folded($thread->get_parent_id()))
        continue;
      $thread->fold();
      $parent  = $thread->get_parent();
      $updated = $parent->get_thread_updated_unixtime();
      $parent->set_created_unixtime($updated);
    }

    // Create the index bar.
    $group     = $this->api->group();
    $n_threads = $this->forumdb->get_n_threads($_forum_id, $may_write);
    $args      = array(forum_id           => (int)$_forum_id,
                       n_threads          => $n_threads,
                       n_threads_per_page => cfg('tpp'),
                       n_offset           => $_offset,
                       n_pages_per_index  => cfg('ppi'),
                       thread_state       => $thread_state);
    $indexbar = new IndexBarByThread($args);

    // Render the template.
    $this->clear_all_assign();
    $this->assign_by_ref('indexbar',           $indexbar);
    $this->assign_by_ref('n_rows',             count($threads));
    $this->assign_by_ref('threads',            $threads);
    $this->assign_by_ref('max_usernamelength', cfg('max_usernamelength'));
    $this->assign_by_ref('max_subjectlength',  cfg('max_subjectlength'));
    $this->render('thread_with_indexbar.tmpl');
  }


  function show_posting($_posting) {
    $user            = $this->api->user();
    $group           = $this->api->group();
    $db              = $this->forumdb;
    $prev_posting_id = $db->get_prev_posting_id_in_thread($_posting);
    $next_posting_id = $db->get_next_posting_id_in_thread($_posting);
    $prev_thread_id  = $db->get_prev_thread_id($_posting);
    $next_thread_id  = $db->get_next_thread_id($_posting);
    $posting_uid     = $_posting ? $_posting->get_user_id() : -1;
    $may_write       = $group->may('write');
    $may_edit        = $may_write
                    && cfg('postings_editable')
                    && !$user->is_anonymous()
                    && $user->get_id() === $posting_uid
                    && $_posting->is_editable();
    $showthread      = $_posting
                    && $_posting->has_thread()
                    && $_COOKIE[thread] != 'hide';
    $indexbar = new ThreadViewIndexBarReadPosting($_posting,
                                                  $prev_posting_id,
                                                  $next_posting_id,
                                                  $prev_thread_id,
                                                  $next_thread_id);

    // Add the 'respond' button.
    if ($may_write) {
      $url = $_posting->get_respond_url();
      if ($_posting->is_active() && $_posting->get_allow_answer())
        $this->api->links('page')->add_link($url, 250);
      else
        $this->api->links('page')->add_text($url->get_label(), 200);
    }

    // Add the 'edit' button.
    if ($may_edit) {
      $url = $_posting->get_edit_url();
      $this->api->links('page')->add_link($url, 300);
    }

    // Add 'show/hide thread' buttons.
    $url = new FreechURL;
    $url->set_var('action',   'read');
    $url->set_var('forum_id', $_posting->get_forum_id());
    $url->set_var('msg_id',   $_posting->get_id());
    $url->set_var('refer_to', $_SERVER['REQUEST_URI']);
    if ($_posting->has_thread()) {
      if ($_COOKIE[thread] === 'hide') {
        $url->set_var('showthread', 1);
        $url->set_label(_('Show Thread'));
      }
      else {
        $url->set_var('showthread', -1);
        $url->set_label(_('Hide Thread'));
      }
      $this->api->links('view')->add_link($url);
    }

    // Load the thread.
    $this->clear_all_assign();
    $this->assign_by_ref('showthread', $showthread);
    if ($showthread) {
      $state      = new ThreadState(THREAD_STATE_UNFOLDED, '');
      $func       = array(&$this, '_format_posting');
      $thread_ids = array($_posting->get_thread_id());
      $threads    = $this->forumdb->get_threads_from_id($thread_ids);
      $threads[0]->remove_locked_postings();
      $threads[0]->foreach_posting($func);
      $this->assign_by_ref('n_rows',  1);
      $this->assign_by_ref('threads', $threads);
    }

    // Render.
    $this->assign_by_ref('indexbar', $indexbar);
    $this->assign_by_ref('posting',  $_posting);
    $this->assign_by_ref('max_usernamelength', cfg('max_usernamelength'));
    $this->assign_by_ref('max_subjectlength',  cfg('max_subjectlength'));
    $this->render(dirname(__FILE__).'/threadview_read_posting.tmpl');
    $this->api->set_title($_posting->get_subject());
  }
}
?>
