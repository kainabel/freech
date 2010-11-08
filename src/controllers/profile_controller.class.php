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
  class ProfileController extends Controller {
    function ProfileController(&$_api) {
      $this->Controller($_api);
      $this->users = array();
    }


    function _format_posting(&$_posting) {
      // Required to enable correct formatting of the posting.
      $msg_id  = (int)$_GET['msg_id'];
      $_posting->set_selected($_posting->get_id() == $msg_id);
      $_posting->apply_block();
    }


    function _append_user(&$_user, $_data) {
      array_push($this->users, $_user);
    }


    function _assign_user_postings(&$_user, $_offset = 0) {
      // Load the postings.
      $thread_state = $this->api->thread_state('user_postings_');
      $func         = array($this, '_format_posting');
      $threads      = $this->forumdb->get_postings_from_user($_user->get_id(),
                                                             $_offset,
                                                             cfg('epp'));

      // Format the threads.
      foreach ($threads as $thread) {
        $thread->foreach_posting($func);
        if (!$thread_state->is_folded($thread->get_thread_id()))
          continue;
        $thread->fold();
        $parent  = $thread->get_parent();
        $updated = $parent->get_thread_updated_unixtime();
        $parent->set_created_unixtime($updated);
      }

      // Create the index bar.
      $search    = array('userid' => $_user->get_id());
      $n_entries = $this->forumdb->get_n_postings($search);
      $action    = $this->api->action();
      $args      = array(action              => $action,
                         user                => $_user,
                         n_postings          => $n_entries,
                         n_postings_per_page => cfg('epp'),
                         n_offset            => $_offset,
                         n_pages_per_index   => cfg('ppi'),
                         thread_state        => $thread_state);

      include 'objects/indexbar_user_postings.class.php';
      $indexbar = new IndexBarUserPostings($args);

      $this->assign_by_ref('n_rows',     count($threads));
      $this->assign_by_ref('n_postings', $n_entries);
      $this->assign_by_ref('threads',    $threads);
      $this->assign_by_ref('indexbar',   $indexbar);
      $this->assign_by_ref('max_usernamelength', cfg('max_usernamelength'));
      $this->assign_by_ref('max_subjectlength',  cfg('max_subjectlength'));
    }


    function show_user_profile(&$_user, $_offset = 0) {
      // Load the group info.
      $groupdb = $this->api->groupdb();
      $search  = array('id' => $_user->get_group_id());
      $group   = $groupdb->get_group_from_query($search);

      // Check permissions.
      $current   = $this->api->user();
      $curgroup  = $this->api->group();
      $is_self   = $current->get_id() == $_user->get_id();
      $may_admin = $curgroup->may('administer');
      $may_mod   = $curgroup->may('moderate') && !$group->may('administer');
      $edit_sane = !$_user->is_anonymous();
      $mod_sane  = $edit_sane && ($_user->is_active() || $_user->is_locked());
      $may_edit  = $may_admin || ($mod_sane && ($is_self || $may_mod));
      $is_user   = !$current->is_anonymous();
      $may_write = $is_user && $_user->is_active() && !$is_self && $edit_sane;
      $id_to     = $_user->get_id();

      // Load the threads (if they are to be displayed).
      $this->clear_all_assign();
      $search    = array('userid' => $_user->get_id());
      $n_entries = $this->forumdb->get_n_postings($search);
      $this->assign_by_ref('n_postings', $n_entries);

      // Render the template.
      $this->assign_by_ref('user',     $_user);
      $this->assign_by_ref('group',    $group);
      $this->assign_by_ref('may_edit', $may_edit);
      $this->assign_by_ref('is_self',  $is_self);
      $this->assign_by_ref('may_write',$may_write);
      $this->assign_by_ref('id_to'    ,$id_to);
      $this->render_php('user_profile.php.tmpl');
      $this->api->set_title($_user->get_name());
    }


    function show_user_postings(&$_user, $_offset = 0) {
      $current  = $this->api->user();
      $group    = $this->api->group();
      $showlist = $_user->get_name() == $current->get_name();
      $showlist = $showlist || $group->may('moderate');
      $this->api->set_title($_user->get_name());
      if (!$showlist)
        return;

      // Load the threads.
      $this->clear_all_assign();
      $this->_assign_user_postings($_user, $_offset);

      // Render the template.
      $this->assign_by_ref('user', $_user);
      $this->render_php('user_postings.php.tmpl');
    }


    function show_user_editor(&$_user) {
      $url = new FreechURL;
      $url->set_var('action', 'user_submit');

      // Fetch the corresponding group.
      $groupdb = $this->api->groupdb();
      $query   = array('id' => $_user->get_group_id());
      $group   = $groupdb->get_group_from_query($query);

      // Find permissions.
      $current      = $this->api->user();
      $curgroup     = $this->api->group();
      $is_self      = $current->get_id() == $_user->get_id();
      $edit_sane    = !$_user->is_anonymous();
      $lock_sane    = $edit_sane && $_user->is_active();
      $unlock_sane  = $edit_sane && $_user->is_locked();
      $may_admin    = $curgroup->may('administer');
      $may_mod      = $curgroup->may('moderate') && !$group->may('administer');
      $may_lock     = $may_mod && ($lock_sane || $unlock_sane);
      $may_delete   = $may_admin || $is_self;

      // Permissions passed to the template.
      $may_edit_group    = $may_admin;
      $may_edit_name     = $may_admin;
      $may_edit_data     = $may_admin || ($edit_sane && $is_self);
      $may_change_status = $may_admin || $may_lock || $may_delete;

      if (!$may_edit_group
        && !$may_edit_name
        && !$may_edit_data
        && !$may_change_status)
        die('Nothing for you to do here.');

      // Load a list of group names.
      $query  = array();
      $list   = $groupdb->get_groups_from_query($query);
      $groups = array();
      foreach ($list as $current_group)
        $groups[$current_group->get_id()] = $current_group->get_name();

      // Get a list of user status names.
      if ($may_admin)
        $status = $_user->get_status_names();
      elseif ($is_self) {
        $status = array(
          USER_STATUS_ACTIVE  => $_user->get_status_names(USER_STATUS_ACTIVE),
          USER_STATUS_DELETED => $_user->get_status_names(USER_STATUS_DELETED)
        );
      }
      elseif ($may_lock || $may_unlock) {
        $status = array(
          USER_STATUS_ACTIVE  => $_user->get_status_names(USER_STATUS_ACTIVE),
          USER_STATUS_BLOCKED => $_user->get_status_names(USER_STATUS_BLOCKED)
        );
      }

      // Render the template.
      $this->clear_all_assign();
      $this->assign_by_ref('may_edit_group',    $may_edit_group);
      $this->assign_by_ref('may_edit_name',     $may_edit_name);
      $this->assign_by_ref('may_edit_data',     $may_edit_data);
      $this->assign_by_ref('may_change_status', $may_change_status);
      $this->assign_by_ref('user',              $_user);
      $this->assign_by_ref('group',             $group);
      $this->assign_by_ref('groups',            $groups);
      $this->assign_by_ref('status',            $status);
      $this->assign_by_ref('action',            $url->get_string());
      $this->render_php('user_editor.php.tmpl');
      $this->api->set_title($_user->get_name());
    }


    function show_user_options(&$_user) {
      $url = new FreechURL;
      $url->set_var('action', 'user_options_submit');

      // Render the template.
      $this->clear_all_assign();
      $this->assign_by_ref('user',   $_user);
      $this->assign_by_ref('action', $url->get_string());
      $this->render_php('user_options.php.tmpl');
      $this->api->set_title($_user->get_name());
    }


    function show_group_profile(&$_group, $_offset = 0) {
      // Load a list of users.
      $search = array('group_id' => $_group->get_id());
      $userdb = $this->api->userdb();
      $n_rows = $userdb->foreach_user_from_query($search,
                                                 cfg("epp"),
                                                 $_offset,
                                                 array($this, '_append_user'),
                                                 '');

      // Create the index bar.
      $n_entries = $userdb->get_n_users_from_query($search);
      $args      = array(group             => $_group,
                         n_users           => $n_entries,
                         n_users_per_page  => cfg("epp"),
                         n_offset          => $_offset,
                         n_pages_per_index => cfg("ppi"));

      include 'objects/indexbar_group_profile.class.php';
      $indexbar = new IndexBarGroupProfile($args);

      // Render the template.
      $this->clear_all_assign();
      $this->assign_by_ref('indexbar', $indexbar);
      $this->assign_by_ref('group',    $_group);
      $this->assign_by_ref('n_rows',   $n_rows);
      $this->assign_by_ref('users',    $this->users);
      $this->render_php('group_profile.php.tmpl');
      $this->api->set_title($_group->get_name());
    }


    function show_group_editor(&$_group) {
      $url = new FreechURL;
      $url->set_var('action', 'group_submit');

      // Render the template.
      $this->clear_all_assign();
      $this->assign_by_ref('group',  $_group);
      $this->assign_by_ref('action', $url->get_string());
      $this->render_php('group_editor.php.tmpl');
      $this->api->set_title($_group->get_name());
    }
  }
?>
