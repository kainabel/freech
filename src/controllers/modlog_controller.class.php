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
  class ModLogController extends Controller {
    function show($_offset = 0) {
      $modlogdb = $this->api->modlogdb();
      $group    = $this->api->group();
      $query    = array();
      $items    = $modlogdb->get_items_from_query($query,
                                                  cfg('modlog_epp'),
                                                  (int)$_offset);
      $this->clear_all_assign();
      $this->assign       ('may_bypass', $group->may('bypass'));
      $this->assign       ('n_rows', count($items));
      $this->assign_by_ref('items', $items);
      $this->render_php('modlog.php.tmpl');
    }


    function show_lock_posting(&$_posting) {
      $url = new FreechURL;
      $url->set_var('action', 'posting_lock_submit');
      $url->set_var('msg_id', $_posting->get_id());

      $this->clear_all_assign();
      $this->assign       ('action',   $url->get_string());
      $this->assign       ('refer_to', $_posting->get_url_string());
      $this->assign_by_ref('posting',  $_posting);
      $this->render_php('posting_lock.php.tmpl');
    }


    function show_thread_move(&$_posting) {
      $url = new FreechURL;
      $url->set_var('action', 'thread_move_submit');
      $url->set_var('msg_id', $_posting->get_id());

      $forums    = $this->forumdb->get_forums(FORUM_STATUS_ACTIVE);
      $forum_map = array();
      foreach ($forums as $forum)
        if ($forum->get_id() != $_posting->get_forum_id())
          $forum_map[$forum->get_id()] = $forum->get_name();
      ksort($forum_map);

      $this->clear_all_assign();
      $this->assign       ('action',  $url->get_string());
      $this->assign_by_ref('posting', $_posting);
      $this->assign_by_ref('forums',  $forum_map);
      $this->render_php('thread_move.php.tmpl');
    }
  }
?>
