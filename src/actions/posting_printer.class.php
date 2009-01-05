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
  class PostingPrinter extends ThreadPrinter {
    function show(&$_msg) {
      $user       = $this->parent->get_current_user();
      $group      = $this->parent->get_current_group();
      $msg_uid    = $_msg ? $_msg->get_user_id() : -1;
      $may_write  = $group->may('write');
      $may_edit   = $may_write
                 && cfg('postings_editable')
                 && !$user->is_anonymous()
                 && $user->get_id() === $msg_uid
                 && $_msg->is_editable();
      $indexbar   = &new IndexBarReadPosting($_msg, $may_write, $may_edit);
      $showthread = $_msg && $_msg->has_thread() && $_COOKIE[thread] != 'hide';

      if ($_msg)
        $_msg->apply_block();
      else {
        $_msg = new Posting;
        $_msg->set_subject(lang("noentrytitle"));
        $_msg->set_body(lang("noentrybody"));
      }

      $this->clear_all_assign();
      $this->assign_by_ref('showthread', $showthread);
      if ($showthread) {
        $state = new ThreadState(THREAD_STATE_UNFOLDED, '');
        $func  = array(&$this, '_append_posting');
        $this->forumdb->foreach_child_in_thread($_msg->get_id(),
                                                0,
                                                cfg("tpp"),
                                                $state,
                                                $func,
                                                '');
        $this->assign_by_ref('n_rows',   count($this->postings));
        $this->assign_by_ref('postings', $this->postings);
      }

      $this->assign_by_ref('indexbar', $indexbar);
      $this->assign_by_ref('message',  $_msg);
      $this->assign_by_ref('max_usernamelength', cfg("max_usernamelength"));
      $this->assign_by_ref('max_subjectlength',  cfg("max_subjectlength"));
      $this->render('read.tmpl');
      $this->parent->_set_title($_msg->get_subject());
    }
  }
?>
