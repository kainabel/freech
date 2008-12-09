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
  class ProfilePrinter extends PrinterBase {
    function ProfilePrinter(&$_forum) {
      $this->PrinterBase(&$_forum);
      $this->messages = array();
      $this->users    = array();
    }


    function _append_message(&$_message, $_data) {
      // Required to enable correct formatting of the message.
      $msg_id = $this->parent->get_current_message_id();
      $_message->set_selected($_message->get_id() == $msg_id);
      $_message->apply_block();

      // Append everything to a list.
      array_push($this->messages, $_message);
    }


    function _append_user(&$_user, $_data) {
      array_push($this->users, $_user);
    }


    function show_user_profile($_user, $_hint = '') {
      $search    = array('userid' => $_user->get_id());
      $n_entries = $this->forumdb->get_n_messages($search);
      $groupdb   = $this->parent->_get_groupdb();
      $search    = array('id' => $_user->get_group_id());
      $group     = $groupdb->get_group_from_query($search);

      // Render the template.
      $this->assign_by_ref('user',       $_user);
      $this->assign_by_ref('group',      $group);
      $this->assign_by_ref('hint',       $_hint);
      $this->assign_by_ref('n_messages', $n_entries);
      $this->render('user_profile.tmpl');
      $this->parent->_set_title($_user->get_name());
    }


    function show_user_postings($_user, $_thread_state, $_offset = 0) {
      $current  = $this->parent->get_current_user();
      $showlist = $_user->get_name() == $current->get_name();

      // Load the threads (if they are to be displayed).
      $this->clear_all_assign();
      if ($showlist) {
        $func = array(&$this, '_append_message');
        $this->forumdb->foreach_message_from_user($_user->get_id(),
                                                  $_offset,
                                                  cfg("epp"),
                                                  cfg("updated_threads_first"),
                                                  $_thread_state,
                                                  $func,
                                                  '');
        $this->assign_by_ref('n_rows',   count($this->messages));
        $this->assign_by_ref('messages', $this->messages);
      }

      // Create the index bar.
      $search    = array('userid' => $_user->get_id());
      $n_entries = $this->forumdb->get_n_messages($search);
      $args      = array(n_messages          => $n_entries,
                         n_messages_per_page => cfg("epp"),
                         n_offset            => $_offset,
                         n_pages_per_index   => cfg("ppi"),
                         thread_state        => $_thread_state);
      $indexbar = &new IndexBarUserPostings($args);

      // Render the template.
      $this->assign_by_ref('user',     $_user);
      $this->assign_by_ref('showlist', $showlist);
      $this->assign_by_ref('indexbar', $indexbar);
      $this->assign_by_ref('max_usernamelength', cfg("max_usernamelength"));
      $this->assign_by_ref('max_subjectlength',  cfg("max_subjectlength"));
      $this->render('list_by_thread.tmpl');
      $this->parent->_set_title($_user->get_name());
    }


    function show_user_data($_user, $_hint = '') {
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('action', 'user_data_submit');

      // Load a list of group names.
      $groupdb = $this->parent->_get_groupdb();
      $list    = $groupdb->get_groups_from_query(array());
      $groups  = array();
      foreach ($list as $group)
        $groups[$group->get_id()] = $group->get_name();

      // Fetch some variables.
      $query  = array('id' => $_user->get_group_id());
      $group  = $groupdb->get_group_from_query($query);
      $status = $_user->get_status_names();

      // Render the template.
      $this->clear_all_assign();
      $this->assign_by_ref('user',   $_user);
      $this->assign_by_ref('group',  $group);
      $this->assign_by_ref('groups', $groups);
      $this->assign_by_ref('status', $status);
      $this->assign_by_ref('hint',   $_hint);
      $this->assign_by_ref('action', $url->get_string());
      $this->render('user_data.tmpl');
      $this->parent->_set_title($_user->get_name());
    }


    function show_user_options($_user, $_hint = '') {
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('action', 'user_options_submit');

      // Render the template.
      $this->clear_all_assign();
      $this->assign_by_ref('user',   $_user);
      $this->assign_by_ref('hint',   $_hint);
      $this->assign_by_ref('action', $url->get_string());
      $this->render('user_options.tmpl');
      $this->parent->_set_title($_user->get_name());
    }


    function show_group_profile($_group, $_offset = 0) {
      // Load a list of users.
      $search = array('group_id' => $_group->get_id());
      $userdb = $this->parent->_get_userdb();
      $n_rows = $userdb->foreach_user_from_query($search,
                                                 cfg("epp"),
                                                 $_offset,
                                                 array(&$this, '_append_user'),
                                                 '');

      // Create the index bar.
      $n_entries = $userdb->get_n_users_from_query($search);
      $args      = array(group             => $_group,
                         n_users           => $n_entries,
                         n_users_per_page  => cfg("epp"),
                         n_offset          => $_offset,
                         n_pages_per_index => cfg("ppi"));
      $indexbar = &new IndexBarGroupProfile($args);

      // Render the template.
      $this->clear_all_assign();
      $this->assign_by_ref('indexbar', $indexbar);
      $this->assign_by_ref('group',    $_group);
      $this->assign_by_ref('n_rows',   $n_rows);
      $this->assign_by_ref('users',    $this->users);
      $this->render('group_profile.tmpl');
      $this->parent->_set_title($_group->get_name());
    }
  }
?>
