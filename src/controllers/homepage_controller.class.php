<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels

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
class HomepageController extends Controller {
  function HomepageController(&$_api) {
    $this->Controller($_api);
    $this->postings = array();
  }


  function _append_posting(&$_posting, $_data) {
    // Required to enable correct formatting of the posting.
    $current_id = (int)$_GET['msg_id'];
    $_posting->set_selected($_posting->get_id() == $current_id);
    $_posting->apply_block();

    // Append everything to a list.
    array_push($this->postings, $_posting);
  }


  function show() {
    // Get a list of forums.
    $group         = $this->api->group();
    $may_edit      = $group->may('administer');
    $add_forum_url = new FreechURL('', _('Add a New Forum'));
    $add_forum_url->set_var('action', 'forum_add');

    // Collect status information regarding each forum.
    if ($may_edit)
      $forums = $this->forumdb->get_forums();
    else
      // do not show inactive forums
      $forums = $this->forumdb->get_not_forums(FORUM_STATUS_INACTIVE);
    if (!cfg('disable_posting_counter')) {
      foreach ($forums as $forum) {
        $search     = array('forum_id' => $forum->get_id());
        $n_postings = $this->forumdb->get_n_postings($search);
        $start      = time() - cfg('new_post_time');
        $n_new      = $this->forumdb->get_n_postings($search, $start);
        $text       = sprintf(_('%d postings, %d new'), $n_postings, $n_new);
        $forum->set_status_text($text);
      }
    }

    // Get a list of the most recent postings.
    $db = $this->forumdb;
    $n  = $db->foreach_latest_posting(NULL,
                                      0,
                                      cfg('homepage_n_entries'),
                                      FALSE,
                                      FALSE,
                                      array($this, '_append_posting'),
                                      '');

    // Get other page info.
    $new_users = $this->userdb->get_newest_users(cfg('homepage_n_entries'));

    // Render the template.
    $this->clear_all_assign();
    $this->assign('title',         cfg('site_title'));
    $this->assign('may_edit',      $may_edit);
    $this->assign('add_forum_url', $add_forum_url);
    $this->assign('forums',        $forums);
    $this->assign('forum_links',   $this->api->links('forum'));
    $this->assign('postings',      $this->postings);
    $this->assign('new_users',     $new_users);
    $this->render_php('home.php.tmpl');
  }
}
?>
