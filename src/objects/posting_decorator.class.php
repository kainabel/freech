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
  protected $posting;

  function PostingDecorator($_posting, $_forum) {
    $this->Trackable();
    $this->posting = $_posting;
    $this->forum   = $_forum;
  }


  function _get_thread_id() {
    return $this->posting->_get_thread_id();
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


  function is_parent() {
    return $this->posting->is_parent();
  }


  function get_priority() {
    return $this->posting->get_priority();
  }


  function get_user_id() {
    return $this->posting->get_user_id();
  }


  function set_from_group($_group) {
    $this->posting->set_from_group($_group);
  }


  function set_from_user($_user) {
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


  function get_signature() {
    return $this->posting->get_signature();
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
    return $this->posting->get_url_string();
  }


  function get_fold_url() {
    return $this->posting->get_fold_url();
  }


  function get_fold_url_string() {
    return $this->posting->get_fold_url_string();
  }


  function get_lock_url() {
    return $this->posting->get_lock_url();
  }


  function get_lock_url_string() {
    return $this->posting->get_lock_url_string();
  }


  function get_unlock_url() {
    return $this->posting->get_unlock_url();
  }


  function get_unlock_url_string() {
    return $this->posting->get_unlock_url_string();
  }


  function get_prioritize_url($_priority) {
    return $this->posting->get_prioritize_url($_priority);
  }


  function get_prioritize_url_string($_priority) {
    return $this->posting->get_prioritize_url_string($_priority);
  }


  function get_user_profile_url() {
    return $this->posting->get_user_profile_url();
  }


  function get_user_profile_url_string() {
    return $this->posting->get_user_profile_url_string();
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


  function is_new() {
    return $this->posting->is_new();
  }


  function get_newness() {
    return $this->posting->get_newness();
  }


  function get_newness_hex() {
    return $this->posting->get_newness_hex();
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


  function get_relation() {
    return $this->posting->get_relation();
  }


  function is_folded() {
    return $this->posting->is_folded();
  }


  function is_active() {
    return $this->posting->is_active();
  }


  function is_editable() {
    return $this->posting->is_editable();
  }


  function apply_block() {
    return $this->posting->apply_block();
  }


  function get_allow_answer() {
    return $this->posting->get_allow_answer();
  }


  function has_thread() {
    return $this->posting->has_thread();
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


  function check_complete() {
    return $this->posting->check_complete();
  }
}
?>
