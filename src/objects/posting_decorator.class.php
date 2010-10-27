<?php
  /*
  Freech.
  Copyright (C) 2003-2008 Samuel Abels, <http://debain.org>

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
 * This decorator ony provides access to methods that need to be
 * made available to the templates.
 */
class PostingDecorator extends Trackable {
  function PostingDecorator(&$_posting, &$_api) {
    $this->Trackable();
    $this->posting = $_posting;
    $this->api     = $_api;
  }


  function get_thread_id() {
    return $this->posting->get_thread_id();
  }

  function get_rating() {
  	return $this->posting->get_rating();
  }

  function get_rating_count() {
  	return $this->posting->get_rating_count();
  }

  function _get_path() {
    return $this->posting->_get_path();
  }


  function set_id($_id) {
    return $this->posting->set_id($_id);
  }


  function get_id() {
    return $this->posting->get_id();
  }


  function set_forum_id($_id) {
    return $this->posting->set_forum_id($_id);
  }


  function get_forum_id() {
    return $this->posting->get_forum_id();
  }


  function get_origin_forum_id() {
    return $this->posting->get_origin_forum_id();
  }


  function is_parent() {
    return $this->posting->is_parent();
  }


  function set_priority($_priority) {
    return $this->posting->set_priority($_priority);
  }


  function get_priority() {
    return $this->posting->get_priority();
  }


  function get_user_id() {
    return $this->posting->get_user_id();
  }


  function set_from_group(&$_group) {
    $this->posting->set_from_group($_group);
  }


  function set_from_user(&$_user) {
    $this->posting->set_from_user($_user);
  }


  function get_user_is_special() {
    return $this->posting->get_user_is_special();
  }


  function get_user_is_anonymous() {
    return $this->posting->get_user_is_anonymous();
  }


  function get_user_icon() {
    return $this->posting->get_user_icon();
  }


  function get_user_icon_name() {
    return $this->posting->get_user_icon_name();
  }


  function set_username($_name) {
    return $this->posting->set_username($_name);
  }


  function get_username() {
    return $this->posting->get_username();
  }


  function set_subject($_subject) {
    return $this->posting->set_subject($_subject);
  }


  function get_subject() {
    return $this->posting->get_subject();
  }


  function set_body($_body) {
    return $this->posting->set_body($_body);
  }


  function get_body() {
    return $this->posting->get_body();
  }


  function get_quoted_body($_depth = 1) {
    return $this->posting->get_quoted_body($_depth);
  }


  function get_renderer() {
    return $this->posting->get_renderer();
  }


  function get_url() {
    return $this->posting->get_url();
  }


  function get_url_html() {
    return $this->posting->get_url_html();
  }


  function get_url_string() {
    return $this->posting->get_url()->get_string();
  }


  function get_thread_url() {
    return $this->posting->get_thread_url();
  }


  function get_url_thread_html() {
    return $this->posting->get_thread_url()->get_html();
  }


  function get_edit_url() {
    return $this->posting->get_edit_url();
  }


  function get_edit_url_html() {
    return $this->posting->get_edit_url()->get_html();
  }


  function get_respond_url() {
    return $this->posting->get_respond_url();
  }


  function get_respond_url_html() {
    return $this->posting->get_respond_url()->get_html();
  }


  function get_fold_url() {
    return $this->posting->get_fold_url();
  }


  function get_fold_url_string() {
    return $this->posting->get_fold_url()->get_string();
  }


  function get_lock_url() {
    return $this->posting->get_lock_url();
  }


  function get_lock_url_string() {
    return $this->posting->get_lock_url()->get_string();
  }


  function get_unlock_url() {
    return $this->posting->get_unlock_url();
  }


  function get_unlock_url_string() {
    return $this->posting->get_unlock_url()->get_string();
  }


  function get_stub_url() {
    return $this->posting->get_stub_url();
  }


  function get_stub_url_string() {
    return $this->posting->get_stub_url()->get_string();
  }


  function get_unstub_url() {
    return $this->posting->get_unstub_url();
  }


  function get_unstub_url_string() {
    return $this->posting->get_unstub_url()->get_string();
  }


  function get_move_url() {
    return $this->posting->get_move_url();
  }


  function get_move_url_string() {
    return $this->posting->get_move_url()->get_string();
  }


  function get_prioritize_url($_priority) {
    return $this->posting->get_prioritize_url($_priority);
  }


  function get_prioritize_url_string($_priority) {
    return $this->posting->get_prioritize_url($_priority)->get_string();
  }


  function get_user_profile_url() {
    return $this->posting->get_user_profile_url();
  }


  function get_user_profile_url_html() {
    return $this->posting->get_user_profile_url()->get_html();
  }


  function get_hash() {
    return $this->posting->get_hash();
  }


  function set_created_unixtime($_time) {
    return $this->posting->set_created_unixtime($_time);
  }


  function get_created_time() {
    return $this->posting->get_created_time();
  }


  function get_created_unixtime() {
    return $this->posting->get_created_unixtime();
  }


  function is_new() {
    return $this->posting->is_new();
  }


  function get_newness_hex() {
    return $this->posting->get_newness_hex();
  }


  function get_updated_newness_hex() {
    return $this->posting->get_updated_newness_hex();
  }


  function set_updated_unixtime($_time) {
    return $this->posting->set_updated_unixtime($_time);
  }


  function get_updated_unixtime() {
    return $this->posting->get_updated_unixtime();
  }


  function get_updated_time($_format = '') {
    return $this->posting->get_updated_time($_format);
  }


  function is_updated() {
    return $this->posting->is_updated();
  }


  function get_thread_updated_unixtime() {
    return $this->posting->get_thread_updated_unixtime();
  }


  function get_thread_updated_time($_format = '') {
    return $this->posting->get_thread_updated_time($_format);
  }


  function get_n_children() {
    return $this->posting->get_n_children();
  }


  function get_ip_address_hash($_maxlen = NULL) {
    return $this->posting->get_ip_address_hash($_maxlen);
  }


  function set_relation($_relation) {
    return $this->posting->set_relation($_relation);
  }


  function get_relation() {
    return $this->posting->get_relation();
  }


  function is_folded() {
    return $this->posting->is_folded();
  }


  function set_status($_status) {
    return $this->posting->set_status($_status);
  }


  function get_status() {
    return $this->posting->get_status();
  }


  function is_active() {
    return $this->posting->is_active();
  }


  function is_locked() {
    return $this->posting->is_locked();
  }


  function is_spam() {
    return $this->posting->is_spam();
  }


  function is_editable() {
    return $this->posting->is_editable();
  }


  function apply_block() {
    return $this->posting->apply_block();
  }


  function set_force_stub($_force_stub = TRUE) {
    return $this->posting->set_force_stub($_force_stub);
  }


  function get_force_stub() {
    return $this->posting->get_force_stub();
  }


  function get_allow_answer() {
    return $this->posting->get_allow_answer();
  }


  function set_notify_author($_notify) {
    return $this->posting->set_notify_author($_notify);
  }


  function get_notify_author() {
    return $this->posting->get_notify_author();
  }


  function has_thread() {
    return $this->posting->has_thread();
  }


  function has_descendants() {
    return $this->posting->has_descendants();
  }


  function set_indent(&$_indent) {
    return $this->posting->set_indent($_indent);
  }


  function get_indent() {
    return $this->posting->get_indent();
  }


  function set_selected($_selected = TRUE) {
    return $this->posting->set_selected($_selected);
  }


  function is_selected() {
    return $this->posting->is_selected();
  }


  function was_moved() {
    return $this->posting->was_moved();
  }


  function check_complete() {
    return $this->posting->check_complete();
  }
}
?>
