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
if (!is_readable('data/config.inc.php'))
  die('Error: Config file not found. Please follow the'
    . ' installation instructions delivered with the software.');

include_once 'main_controller.class.php';

/**
 * This class provides the public API to access the forum. It also
 * serves as the API for plugins.
 */
class Freech {
  function Freech() {
    $this->controller = new MainController($this);
    $this->controller->init();
  }


  /*************************************************************
   * Access to internal state.
   *************************************************************/
  /**
   * Returns the current user.
   */
  function &user() {
    return $this->controller->get_current_user();
  }


  /**
   * Returns the group of the current user.
   */
  function &group() {
    return $this->controller->get_current_group();
  }


  /**
   * Returns the current action.
   */
  function &action() {
    return $this->controller->get_current_action();
  }


  /**
   * Returns the current forum, if the user is viewing one.
   * Returns NULL if a forum is not viewed (for example because the request
   * points to a special page such as the homepage or the statistics).
   */
  function &forum() {
    return $this->controller->get_current_forum();
  }


  /**
   * Returns a state object that represents the folding (folded/unfolded) of
   * all threads.
   */
  function &thread_state($_section) {
    return $this->controller->get_thread_state($_section);
  }


  /**
   * Defines a title for the current site. Note the the title is
   * overwritten by the forum at the time the run() method is called.
   */
  function set_title($_title) {
    return $this->controller->set_title($_title);
  }


  /**
   * Returns a title for the current site. Note the the title is
   * only defined after the run() method was called.
   */
  function get_title() {
    return $this->controller->get_title();
  }


  /*************************************************************
   * Access to the database adapters.
   *************************************************************/
  /**
   * Provides access to the adodb database connection.
   */
  function &db() {
    return $this->controller->_get_db();
  }


  /**
   * Provides access to the forum database that contains all postings.
   */
  function &forumdb() {
    return $this->controller->get_forumdb();
  }


  /**
   * Provides access to the user database.
   */
  function &userdb() {
    return $this->controller->get_userdb();
  }


  /**
   * Provides access to the user group database.
   */
  function &groupdb() {
    return $this->controller->_get_groupdb();
  }


  /**
   * Provides access to the visitor database.
   */
  function &visitordb() {
    return $this->controller->visitordb;
  }


  /**
   * Provides access to the moderation log.
   */
  function &modlogdb() {
    return $this->controller->get_modlogdb();
  }


  /*************************************************************
   * Access to the the eventbus and smarty.
   *************************************************************/
  /**
   * An eventbus over which plugins and the forum may communicate.
   */
  function &eventbus() {
    return $this->controller->get_eventbus();
  }


  /**
   * The smarty template processor used by this forum.
   */
  function &smarty() {
    return $this->controller->_get_smarty();
  }


  /*************************************************************
   * Actions.
   *************************************************************/
  /**
   * Calls the forum to do the actual work and prepare the output.
   */
  function run() {
    return $this->controller->run();
  }


  /**
   * Renders the HTML header. The run() method MUST be called before
   * using this function.
   */
  function print_head($_header = NULL) {
    return $this->controller->print_head($_header);
  }


  /**
   * Renders the HTML. The run() method MUST be called before
   * using this function.
   */
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


  /*************************************************************
   * Hooks for plugins.
   *************************************************************/
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
    return $this->forumdb()->register_renderer($_name, $_decorator_name);
  }


  /**
   * Plugins may use this to make an URL available to the API user.
   */
  function register_url($_name, &$_url) {
    return $this->controller->register_url($_name, $_url);
  }


  function get_js($_where) {
    return $this->controller->get_js($_where);
  }


  function add_js($_where, $_javascript) {
    return $this->controller->add_js($_where, $_javascript);
  }


  function get_style() {
    return $this->controller->get_style();
  }


  function add_style($_css) {
    return $this->controller->add_style($_css);
  }


  function get_html($_where) {
    return $this->controller->get_html($_where);
  }


  function add_html($_where, $_css) {
    return $this->controller->add_html($_where, $_css);
  }


  /**
   * Provides access to link sections that are rendered in the HTML.
   */
  function &links($_where) {
    return $this->controller->links($_where);
  }


  /**
   * Convenience wrapper around links().
   */
  function &breadcrumbs() {
    return $this->controller->links('breadcrumbs');
  }


  /*************************************************************
   * Utilities.
   *************************************************************/
  function set_cookie($_name, $_value) {
    return $this->controller->set_cookie($_name, $_value);
  }


  function refer_to($_url_string) {
    return $this->controller->_refer_to($_url_string);
  }


  function refer_to_posting_id($_id) {
    return $this->controller->_refer_to_posting_id($_id);
  }


  function refer_to_posting(&$_posting) {
    return $this->controller->_refer_to_posting_id($_posting->get_id());
  }


  /**
   * Sends an email to the given user. $_vars is an associative
   * array that contains the values for fields that are replaced
   * in the subject and body. For example, if the body contains
   * a field in brackets such as "[NAME]", the array('name' => 'Joe')
   * causes that field to be replaced.
   */
  function send_mail(&$_user, $_subject, $_body, $_vars = NULL) {
    return $this->controller->_send_mail($_user,
                                         $_subject,
                                         $_body,
                                         $_vars);
  }


  function &get_url($_which) {
    if (!$url = $this->controller->get_url($_which))
      die('Freech->get_url(): No such URL.');
    return $url;
  }
}
?>
