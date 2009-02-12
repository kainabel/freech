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
  function _format_posting(&$_posting) {
    $_posting->set_selected($_posting->get_id() == (int)$_GET['msg_id']);
  }


  function show($_forum_id, $_offset) {
    $func    = array(&$this, '_format_posting');
    $threads = $this->forumdb->get_threads_from_forum_id($_forum_id,
                                                         FALSE,
                                                         $_offset,
                                                         cfg('tpp'));

    $group     = $this->api->group();
    $n_threads = $this->forumdb->get_n_threads((int)$_forum_id);
    $args      = array(forum_id           => (int)$_forum_id,
                       n_threads          => (int)$n_threads,
                       n_threads_per_page => cfg('epp'),
                       n_offset           => (int)$_offset,
                       n_pages_per_index  => cfg('ppi'));

    include dirname(__FILE__).'/indexbar.class.php';
    $indexbar = new IndexBarBBView($args);

    $this->clear_all_assign();
    $this->assign_by_ref('indexbar', $indexbar);
    $this->assign_by_ref('threads',  $threads);
    $this->render_php(dirname(__FILE__).'/bbview.php.tmpl');
  }


  function show_thread(&$_posting) {
    $func      = array(&$this, '_format_posting');
    $user      = $this->api->user();
    $group     = $this->api->group();
    $may_write = $group->may('write');
    $may_edit  = $may_write
              && cfg('postings_editable')
              && !$user->is_anonymous();

    // Format the thread.
    $db       = $this->forumdb;
    $args     = array('thread_id' => (int)$_posting->get_thread_id());
    $postings = $db->get_postings_from_fields($args,
                                              FALSE,
                                              (int)$_GET['hs'],
                                              cfg('epp'));

    // Create the indexbar.
    $n_postings = $this->forumdb->get_n_postings($args);
    $args       = array(n_postings          => (int)$n_postings,
                        n_postings_per_page => cfg('epp'),
                        n_offset            => (int)$_GET['hs'],
                        n_pages_per_index   => cfg('ppi'));

    include dirname(__FILE__).'/indexbar_read_posting.class.php';
    $indexbar = new BBViewIndexBarReadPosting($_posting, $args);

    foreach ($postings as $posting) {
      $posting->apply_block();
      /* Plugin hook: on_message_read_print
       *   Called before the HTML for the posting is produced.
       *   Args: posting: The posting that is about to be shown.
       */
      $this->eventbus->emit('on_message_read_print', $this->api, $posting);
    }

    $this->clear_all_assign();
    $this->assign_by_ref('plugin_dir', dirname(__FILE__));
    $this->assign_by_ref('indexbar',   $indexbar);
    $this->assign_by_ref('postings',   $postings);
    $this->assign_by_ref('may_write',  $may_write);
    $this->assign_by_ref('may_edit',   $may_edit);
    $this->assign_by_ref('max_usernamelength', cfg('max_usernamelength'));
    $this->assign_by_ref('max_subjectlength',  cfg('max_subjectlength'));
    $this->render_php(dirname(__FILE__).'/bbview_read_thread.php.tmpl');
    $this->api->set_title($_posting->get_subject());
  }


  function show_posting(&$_posting) {
    $this->show_thread($_posting);
  }
}
?>
