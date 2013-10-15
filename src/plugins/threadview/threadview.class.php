<?php
  /*
  Freech.
  Copyright (C) 2008 Samuel Abels

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
  function _format_posting(&$_posting) {
    $_posting->set_selected($_posting->get_id() == (int)$_GET['msg_id']);
  }


  function show($_forum_id, $_offset) {
    trace('enter');
    // Load the threads from the database.
    $thread_state = $this->api->thread_state('');
    $func         = array(&$this, '_format_posting');
    trace('fetching threads');
    $threads      = $this->forumdb->get_threads_from_forum_id($_forum_id,
                                                              FALSE,
                                                              $_offset,
                                                              cfg('tpp'));
    trace('threads fetched');

    // Format the threads.
    foreach ($threads as $thread) {
      if (!$thread_state->is_folded($thread->get_thread_id()))
        continue;
      $thread->fold();
      $parent      = $thread->get_parent();
      $was_updated = $parent->is_updated();
      $updated     = $parent->get_thread_updated_unixtime();
      $parent->set_created_unixtime($updated);
      $parent->set_updated_unixtime($updated);
    }
    trace('threads formatted');

    // Create the index bar.
    $group     = $this->api->group();
    $n_threads = $this->forumdb->get_n_threads($_forum_id);
    $args      = array('forum_id'           => (int)$_forum_id,
                       'n_threads'          => $n_threads,
                       'n_threads_per_page' => cfg('tpp'),
                       'n_offset'           => $_offset,
                       'n_pages_per_index'  => cfg('ppi'),
                       'thread_state'       => $thread_state);

    include dirname(__FILE__).'/indexbar.class.php';
    $indexbar = new IndexBarByThread($args);
    trace('indexbar built');

    // Render the template.
    $this->clear_all_assign();
    $this->assign_by_ref('indexbar',           $indexbar);
    $this->assign       ('n_rows',                    count($threads));
    $this->assign_by_ref('threads',            $threads);
    $this->assign       ('max_usernamelength', cfg('max_usernamelength'));
    $this->assign       ('max_subjectlength',  cfg('max_subjectlength'));
    $this->render_php('thread_with_indexbar.php.tmpl');
    trace('leave');
  }


  function show_thread(&$_parent_posting) {
    $this->show_posting($_parent_posting);
  }


  function show_posting(&$_posting) {
    trace('enter');
    $user            = $this->api->user();
    $group           = $this->api->group();
    $db              = $this->forumdb;
    $prev_posting_id = $db->get_prev_posting_id_in_thread($_posting);
    $next_posting_id = $db->get_next_posting_id_in_thread($_posting);
    trace('previous/next postings found');
    $prev_thread_id  = $db->get_prev_thread_id($_posting);
    $next_thread_id  = $db->get_next_thread_id($_posting);
    trace('previous/next threads found');
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

    include dirname(__FILE__).'/indexbar_read_posting.class.php';
    $indexbar = new ThreadViewIndexBarReadPosting($_posting,
                                                  $prev_posting_id,
                                                  $next_posting_id,
                                                  $prev_thread_id,
                                                  $next_thread_id);
    trace('indexbar built');

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
    trace('links updated');

    // Load the thread.
    $this->clear_all_assign();
    $this->assign_by_ref('showthread', $showthread);
    if ($showthread) {
      trace('loading thread');
      $state      = new ThreadState(THREAD_STATE_UNFOLDED, '');
      $func       = array(&$this, '_format_posting');
      $thread_ids = array($_posting->get_thread_id());
      $threads    = $this->forumdb->get_threads_from_id($thread_ids, TRUE);
      trace('thread loaded');
      $threads[0]->foreach_posting($func);
      trace('thread formatted');
      $this->assign       ('n_rows',  1);
      $this->assign_by_ref('threads', $threads);
    }

    // Render.
    $this->assign_by_ref('indexbar', $indexbar);
    $this->assign_by_ref('posting',  $_posting);
    $this->assign       ('max_usernamelength', cfg('max_usernamelength'));
    $this->assign       ('max_subjectlength',  cfg('max_subjectlength'));
    $this->render_php(dirname(__FILE__).'/threadview_read_posting.php.tmpl');
    $this->api->set_title($_posting->get_subject());
    trace('leave');
  }
}
?>
