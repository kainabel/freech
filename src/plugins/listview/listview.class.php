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
  function ListView(&$_forum) {
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
    $db = $this->forumdb;
    $n  = $db->foreach_latest_posting((int)$_forum_id,
                                      (int)$_offset,
                                      cfg('epp'),
                                      FALSE,
                                      TRUE,
                                      array(&$this, '_append_posting'),
                                      '');

    $group     = $this->api->group();
    $search    = array('forum_id' => (int)$_forum_id);
    $n_entries = $this->forumdb->get_n_postings($search);
    $args      = array(forum_id            => (int)$_forum_id,
                       n_postings          => (int)$n_entries,
                       n_postings_per_page => cfg('epp'),
                       n_offset            => (int)$_offset,
                       n_pages_per_index   => cfg('ppi'));

    include dirname(__FILE__).'/indexbar.class.php';
    $indexbar = new IndexBarByTime($args);

    $this->clear_all_assign();
    $this->assign_by_ref('indexbar', $indexbar);
    $this->assign_by_ref('n_rows',   $n);
    $this->assign_by_ref('postings', $this->postings);
    $this->render_php(dirname(__FILE__).'/listview.php.tmpl');
  }


  function show_thread(&$_parent_posting) {
    $this->show_posting($_parent_posting);
  }


  function show_posting(&$_posting) {
    $user      = $this->api->user();
    $group     = $this->api->group();
    $db        = $this->forumdb;
    $msg_uid   = $_posting ? $_posting->get_user_id() : -1;
    $showlist  = $_posting && $_COOKIE[thread] != 'hide'; //FIXME: rename "thread" cookie
    $may_write = $group->may('write');
    $may_edit  = $may_write
              && cfg('postings_editable')
              && !$user->is_anonymous()
              && $user->get_id() === $msg_uid
              && $_posting->is_editable();

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
        $url->set_label(_('Show Posting List'));
      }
      else {
        $url->set_var('showthread', -1);
        $url->set_label(_('Hide Posting List'));
      }
      $this->api->links('view')->add_link($url);
    }

    // Create the indexbar.
    if (cfg('posting_arrow_reverse')) {
      $prev_posting_id = $db->get_next_posting_id_in_forum($_posting);
      $next_posting_id = $db->get_prev_posting_id_in_forum($_posting);
    }
    else {
      $prev_posting_id = $db->get_prev_posting_id_in_forum($_posting);
      $next_posting_id = $db->get_next_posting_id_in_forum($_posting);
    }

    include dirname(__FILE__).'/indexbar_read_posting.class.php';
    $indexbar = new ListViewIndexBarReadPosting($_posting,
                                                $prev_posting_id,
                                                $next_posting_id);

    $this->clear_all_assign();
    $this->assign_by_ref('showlist', $showlist);
    if ($showlist) {
      $current_id = (int)$_GET['msg_id'];
      $func       = array(&$this, '_append_posting');
      $_posting->set_selected($_posting->get_id() == $current_id);
      $this->posting_map[$_posting->get_id()] = $_posting;
      $db->foreach_prev_posting($_posting, cfg('epp') / 2, $func);
      $db->foreach_next_posting($_posting, cfg('epp') / 2, $func);
      krsort($this->posting_map);
      $this->assign_by_ref('n_rows',   count($this->posting_map));
      $this->assign_by_ref('postings', array_values($this->posting_map));
    }

    $this->assign_by_ref('indexbar', $indexbar);
    $this->assign_by_ref('posting',  $_posting);
    $this->assign       ('max_usernamelength', cfg('max_usernamelength'));
    $this->assign       ('max_subjectlength',  cfg('max_subjectlength'));
    $this->render_php(dirname(__FILE__).'/listview_read_posting.php.tmpl');
    $this->api->set_title($_posting->get_subject());
  }
}
?>
