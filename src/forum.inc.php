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
  define('FREECH_VERSION', '0.9.19');
  include_once 'forum_controller.class.php';

  /**
   * This class provides the public API to access the forum. It also
   * serves as the API for plugins.
   */
  class FreechForum {
    function FreechForum() {
      $this->controller = new ForumController($this);
      $this->controller->init();
    }


    /**
     * Returns the current user.
     */
    function user() {
      return $this->controller->get_current_user();
    }


    /**
     * Returns the current group.
     */
    function group() {
      return $this->controller->get_current_group();
    }


    /**
     * Returns the current action.
     */
    function action() {
      return $this->controller->get_current_group();
    }


    /**
     * Returns the current forum, if the user is viewing it.
     * Returns NULL if a forum is not viewed (for example because the request
     * points to a special page such as the homepage or the statistics).
     */
    function forum() {
      return $this->controller->get_current_forum();
    }


    /**
     * Provides access to the adodb database connection.
     */
    function db() {
      return $this->controller->_get_db();
    }


    /**
     * Provides access to the forum database that contains all postings.
     */
    function forumdb() {
      return $this->controller->get_forumdb();
    }


    /**
     * Provides access to the user database.
     */
    function userdb() {
      return $this->controller->get_userdb();
    }


    /**
     * Provides access to the user group database.
     */
    function groupdb() {
      return $this->controller->_get_groupdb();
    }


    /**
     * Provides access to the visitor database.
     */
    function visitordb() {
      return $this->controller->visitordb;
    }


    /**
     * Provides access to the moderation log.
     */
    function modlogdb() {
      return $this->controller->get_modlogdb();
    }


    /**
     * An eventbus over which plugins and the forum may communicate.
     */
    function eventbus() {
      return $this->controller->get_eventbus();
    }


    /**
     * The smarty template processor used by this forum.
     */
    function smarty() {
      return $this->controller->_get_smarty();
    }


    /**
     * Calls the forum to do the actual work and prepare the output.
     */
    function run() {
      return $this->controller->run();
    }


    function set_cookie($_name, $_value) {
      return $this->controller->set_cookie($_name, $_value);
    }


    function register_action($_action, $_func) {
      return $this->controller->register_action($_action, $_func);
    }


    function register_view($_name, $_view, $_caption, $_priority) {
      return $this->controller->register_view($_name,
                                              $_view,
                                              $_caption,
                                              $_priority);
    }


    function register_renderer($_name, $_decorator_name) {
      return $this->controller->register_renderer($_name, $_decorator_name);
    }


    function forum_links() {
      return $this->controller->forum_links();
    }


    function page_links() {
      return $this->controller->page_links();
    }


    function search_links() {
      return $this->controller->search_links();
    }


    function account_links() {
      return $this->controller->account_links();
    }


    function footer_links() {
      return $this->controller->footer_links();
    }


    function breadcrumbs() {
      return $this->controller->breadcrumbs();
    }


    function print_head($_header = NULL) {
      return $this->controller->print_head($_header);
    }


    function show() {
      return $this->controller->show();
    }


    function print_rss($_forum_id,
                       $_title,
                       $_descr,
                       $_off,
                       $_n_entries) {
      return $this->controller->print_rss($_forum_id,
                                          $_title,
                                          $_descr,
                                          $_off,
                                          $_n_entries);
    }


    function destroy() {
      return $this->controller->destroy();
    }


    /************
     * Methods below should possibly be merged.
     ************/
    function get_login_url() {
      return $this->controller->get_login_url();
    }


    function get_logout_url() {
      return $this->controller->get_logout_url();
    }


    /************
     * FIXME: Methods below should possibly be removed.
     ************/
    function send_account_mail($_user, $_subject, $_body, $_vars) {
      return $this->controller->_send_account_mail($_user,
                                                   $_subject,
                                                   $_body,
                                                   $_vars);
    }


    function append_content($_content) {
      return $this->controller->_append_content($_content);
    }


    function refer_to($_url_string) {
      return $this->controller->_refer_to($_url_string);
    }


    function refer_to_posting($_posting) {
      return $this->controller->_refer_to_posting_id($_posting->get_id());
    }


    /**
     * Returns a state object that represents the folding (folded/unfolded) of
     * all threads.
     */
    function thread_state($_section) {
      return $this->controller->get_thread_state($_section);
    }


    /************
     * FIXME: Methods below should definitely be removed.
     ************/
    function _assert_confirmation_hash_is_valid($_user) {
      return $this->controller->_assert_confirmation_hash_is_valid($_user);
    }


    function _init_user_from_post_data($_user = NULL) {
      return $this->controller->_init_user_from_post_data($_user);
    }


    function _username_available($_name) {
      return $this->controller->_username_available($_name);
    }


    function _flood_blocked_until($_posting) {
      return $this->controller->_flood_blocked_until($_posting);
    }


    function _posting_is_spam($_posting) {
      return $this->controller->_posting_is_spam($_posting);
    }


    function _set_title($_title) {
      return $this->controller->_set_title($_title);
    }


    function get_content() {
      return $this->controller->content;
    }


    function set_content($_content) {
      $this->controller->content = $_content;
    }


    function decorate_posting($_posting) {
      return $this->controller->_decorate_posting($_posting);
    }


    /**
     * Returns a title for the current site.
     */
    function get_current_title() {
      return $this->controller->get_current_title();
    }


    function get_current_forum_id() {
      return $this->controller->get_current_forum_id();
    }


    function get_current_posting_id() {
      return $this->controller->get_current_posting_id();
    }


    function get_registration_url() {
      //FIXME: should not be here.
      return $this->controller->get_registration_url();
    }


    function get_online_users() {
      return $this->controller->get_online_users();
    }


    function get_newest_users($_limit) {
      return $this->controller->get_newest_users($_limit);
    }


    function refer_to_posting_id($_id) {
      return $this->controller->_refer_to_posting_id($_id);
    }


    function get_posting_from_id_or_die($_id) {
      return $this->controller->_get_posting_from_id_or_die($_id);
    }
  }
?>
