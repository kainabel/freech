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
class HomepagePrinter extends PrinterBase {
  function HomepagePrinter($_forum) {
    $this->PrinterBase($_forum);
    $this->postings = array();
  }


  function _append_posting(&$_posting, $_data) {
    // Required to enable correct formatting of the posting.
    $posting    = $this->parent->_decorate_posting($_posting);
    $current_id = $this->parent->get_current_posting_id();
    $posting->set_selected($posting->get_id() == $current_id);
    $posting->apply_block();

    // Append everything to a list.
    array_push($this->postings, $_posting);
  }


  function show() {
    // Get a list of forums.
    $forums        = $this->forumdb->get_forums();
    $group         = $this->parent->get_current_group();
    $may_edit      = $group->may('administer');
    $add_forum_url = new URL('?', cfg('urlvars'), lang('forum_add'));
    $add_forum_url->set_var('action', 'forum_add');

    // Collect status information regarding each posting.
    if (!cfg('disable_posting_counter')) {
      foreach ($forums as $forum) {
        $search     = array('forum_id' => $forum->get_id());
        $n_postings = $this->forumdb->get_n_postings($search);
        $start      = time() - cfg('new_post_time');
        $n_new      = $this->forumdb->get_n_postings($search, $start);
        $vars       = array('postings'    => $n_postings,
                            'newpostings' => $n_new);
        $forum->set_status_text(lang('forum_status', $vars));
      }
    }

    // Get a list of the most recent postings.
    $db = $this->forumdb;
    $n  = $db->foreach_latest_posting(NULL,
                                      0,
                                      cfg('homepage_n_entries'),
                                      FALSE,
                                      array($this, '_append_posting'),
                                      '');

    // Get other page info.
    $forum_links = $this->parent->forum_links();
    $new_users   = $this->parent->get_newest_users(cfg('homepage_n_entries'));

    // Render the template.
    $this->clear_all_assign();
    $this->assign('title',         cfg('site_title'));
    $this->assign('may_edit',      $may_edit);
    $this->assign('add_forum_url', $add_forum_url);
    $this->assign('forums',        $forums);
    $this->assign('forum_links',   $forum_links);
    $this->assign('postings',      $this->postings);
    $this->assign('new_users',     $new_users);
    $this->render('home.tmpl');
  }
}
?>
