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


  function show($_forum_id, $_offset) {
    // Load postings from the database.
    $thread_state = new ThreadState($_COOKIE['fold'], $_COOKIE['c']);
    $func         = array(&$this, '_append_posting');
    $this->forumdb->foreach_child($_forum_id,
                                  0,
                                  $_offset,
                                  cfg('tpp'),
                                  cfg('updated_threads_first'),
                                  $thread_state,
                                  $func,
                                  '');

    // Create the index bar.
    $group     = $this->parent->get_current_group();
    $n_threads = $this->forumdb->get_n_threads($_forum_id, $may_write);
    $args      = array(forum_id           => (int)$_forum_id,
                       n_threads          => $n_threads,
                       n_threads_per_page => cfg('tpp'),
                       n_offset           => $_offset,
                       n_pages_per_index  => cfg('ppi'),
                       thread_state       => $thread_state);
    $n_rows   = count($this->postings);
    $indexbar = new IndexBarByThread($args);

    // Render the template.
    $this->clear_all_assign();
    $this->assign_by_ref('indexbar',           $indexbar);
    $this->assign_by_ref('n_rows',             $n_rows);
    $this->assign_by_ref('postings',           $this->postings);
    $this->assign_by_ref('max_usernamelength', cfg("max_usernamelength"));
    $this->assign_by_ref('max_subjectlength',  cfg("max_subjectlength"));
    $this->render('thread_with_indexbar.tmpl');
  }


  function show_posting($_posting) {
    $user            = $this->parent->get_current_user();
    $group           = $this->parent->get_current_group();
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
      if ($_posting->is_active() && $_posting->get_allow_answer()) {
        $url = new URL('?', cfg('urlvars'), lang('writeanswer'));
        $url->set_var('action',    'respond');
        $url->set_var('forum_id',  $_posting->get_forum_id());
        $url->set_var('parent_id', $_posting->get_id());
        $this->parent->forum_links()->add_link($url, 250);
      }
      else
        $this->parent->forum_links()->add_text(lang('writeanswer'), 200);
    }

    // Add the 'edit' button.
    if ($may_edit) {
      $url = new URL('?', cfg('urlvars'), lang('editposting'));
      $url->set_var('action', 'edit');
      $url->set_var('forum_id',  $_posting->get_forum_id());
      $url->set_var('msg_id', $_posting->get_id());
      $this->parent->forum_links()->add_link($url, 300);
    }

    // Add 'show/hide thread' buttons.
    $url = new URL('?', cfg('urlvars'));
    $url->set_var('action',   'read');
    $url->set_var('forum_id', $_posting->get_forum_id());
    $url->set_var('msg_id',   $_posting->get_id());
    $url->set_var('refer_to', $_SERVER['REQUEST_URI']);
    if ($_posting->has_thread()) {
      if ($_COOKIE[thread] === 'hide') {
        $url->set_var('showthread', 1);
        $url->set_label(lang('showthread'));
      }
      else {
        $url->set_var('showthread', -1);
        $url->set_label(lang('hidethread'));
      }
      $this->parent->footer_links()->add_link($url);
    }

    // Load the thread.
    $this->clear_all_assign();
    $this->assign_by_ref('showthread', $showthread);
    if ($showthread) {
      $state = new ThreadState(THREAD_STATE_UNFOLDED, '');
      $func  = array(&$this, '_append_posting');
      $this->forumdb->foreach_child_in_thread($_posting->get_id(),
                                              0,
                                              cfg('tpp'),
                                              $state,
                                              $func,
                                              '');
      $this->assign_by_ref('n_rows',   count($this->postings));
      $this->assign_by_ref('postings', $this->postings);
    }

    // Render.
    $this->assign_by_ref('indexbar', $indexbar);
    $this->assign_by_ref('posting',  $_posting);
    $this->assign_by_ref('max_usernamelength', cfg('max_usernamelength'));
    $this->assign_by_ref('max_subjectlength',  cfg('max_subjectlength'));
    $this->render(dirname(__FILE__).'/threadview_read_posting.tmpl');
    $this->parent->_set_title($_posting->get_subject());
  }
}
?>
