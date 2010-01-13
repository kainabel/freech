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
define('FREECH_VERSION', '0.9.20');
ini_set('arg_separator.output', '&');
error_reporting(E_ALL & ~E_NOTICE);

include 'functions/config.inc.php';
include 'functions/trace.inc.php';
trace('Start');

$ADODB_INCLUDED_CSV = TRUE;
require 'adodb/adodb.inc.php';
trace('Adodb imported');

include 'libuseful/string.inc.php';
include 'services/trackable.class.php';
include 'objects/thread_state.class.php';

include 'functions/forum.inc.php';
trace('Function imports done');

include 'objects/hint.class.php';
include 'objects/error.class.php';
include 'objects/ack.class.php';
include 'objects/url.class.php';
include 'objects/posting.class.php';
include 'objects/forum.class.php';
include 'objects/user.class.php';
include 'objects/group.class.php';
include 'objects/posting_decorator.class.php';
include 'objects/thread.class.php';
include 'objects/menu_item.class.php';
include 'objects/menu.class.php';
trace('Object imports done');

include 'controllers/controller.class.php';
include 'controllers/breadcrumbs_controller.class.php';
include 'controllers/footer_controller.class.php';
include 'controllers/view.class.php';
trace('Controller imports done');

include 'services/groupdb.class.php';
include 'services/sql_query.class.php';
include 'services/forumdb.class.php';
include 'services/userdb.class.php';
include 'services/plugin_registry.class.php';
trace('Service imports done');

class MainController {
  function MainController(&$_plugin_api) {
    if (is_dir('installer') && !cfg('ignore_installer_dir', FALSE))
      die('Error: Please delete the installer/ directory before using this forum.');
    if (cfg_is('salt', ''))
      die('Error: Please define the salt variable in config.inc.php!');
    $this->api = $_plugin_api;
    trace();
  }

  function init() {
    trace('Enter');
    // Select a supported language.
    if ($_SERVER[HTTP_ACCEPT_LANGUAGE]) {
      $langs = explode(',', $_SERVER[HTTP_ACCEPT_LANGUAGE]);
      foreach ($langs as $current) {
        if (strstr($current, '-')) {
          $both    = explode('-', $current);
          $current = $both[0] . '_' . strtoupper($both[1]);
        }
        $best = $this->_get_language($current);
        if ($best) {
          $lang = $best;
          break;
        }
      }
    }

    // Fallback to the default language.
    if (!$lang || !preg_match('/^[a-z_]*$/i', $lang))
      $lang = cfg('default_language');

    // Init gettext.
    if (!function_exists('gettext'))
      die('This webserver does not have gettext installed.<br/>'
        . 'Please contact your webspace provider.');

    $locale_dir = './language/';
    $domain     = 'freech';
    putenv("LANG=$lang.UTF-8");
    @setlocale(LC_ALL, '');
    bindtextdomain($domain, $locale_dir);
    textdomain($domain);
    bind_textdomain_codeset($domain, 'UTF-8');

    trace('Gettext initialized');

    // Start the PHP session.
    session_set_cookie_params(time() + cfg('login_time'));
    if ($_COOKIE['permanent_session']) {
      session_id($_COOKIE['permanent_session']);
      session_start();
    }
    else {
      session_start();
      if (strtoupper($_POST['permanent']) === 'ON')
        setcookie('permanent_session', session_id(), time() + cfg('login_time'));
    }

    trace('Session started');

    // Only now start the timer, as cookie handling may scew the result.
    $this->start_time = microtime(TRUE);

    // (Ab)use a Trackable as an eventbus.
    $this->eventbus      = new Trackable;
    $this->actions       = array();
    $this->views         = array();
    $this->current_user  = NULL;
    $this->current_forum = NULL;
    $this->links = array('forum'       => new Menu,
                         'page'        => new Menu,
                         'search'      => new Menu,
                         'view'        => new Menu,
                         'footer'      => new Menu,
                         'account'     => new Menu,
                         'breadcrumbs' => new Menu);

    trace('Eventbus created');

    // Connect to the DB.
    $dbn = cfg('db_dbn');
    if (cfg('persistent_db_connection'))
      $dbn .= '?persist';
    $this->db = ADONewConnection($dbn)
      or die('FreechForum::FreechForum(): Error: Can\'t connect.'
           . ' Please check username, password and hostname.');
    global $ADODB_FETCH_MODE;
    $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
    trace('DB connection opened');
    $this->forumdb = new ForumDB($this->api);
    trace('ForumDB set up');

    $registry = new PluginRegistry;
    trace('Plugin registry initialized');
    foreach (cfg('plugins') as $plugin => $active)
      if ($active)
        $registry->activate_plugin_from_dirname('plugins/'.$plugin,
                                                $this->api);

    trace('Plugins activated');

    $this->_handle_cookies();

    trace('Cookies initialized');

    // Initialize the visitordb after cookie handling to prevent useless
    // updates.
    if (!cfg('disable_visitor_counter')) {
      include 'services/visitordb.class.php';
      $this->visitordb = new VisitorDB($this->db);
      trace('VisitorDB initialized');
      $this->visitordb->count();
    }
    trace('Visitor counted');

    /* Plugin hook: on_construct
     *   Called from within the FreechForum() constructor before any
     *   other output is produced.
     *   The return value of the callback is ignored.
     *   Args: None.
     */
    $this->eventbus->emit('on_construct', $this->api);

    trace('on_construct calls completed.');

    // Attempt to login, if requested.
    $this->login_error = 0;
    if ($this->get_current_action() == 'login' && $_POST['username'])
      $this->login_error = $this->_try_login();
    if ($this->get_current_action() == 'logout') {
      session_unset();
      $url = new FreechURL;
      $this->_refer_to($url->get_string());
    }

    trace('Login processed');

    // Add the modlog URL to the forum links.
    $url = new FreechURL('', _('Moderation Log'));
    $url->set_var('action', 'moderation_log');
    $this->links['forum']->add_link($url);

    // Add login/logout links.
    $refer_to = $this->_get_login_refer_url();
    $url      = new FreechURL('', _('Log in'));
    $url->set_var('action',   'login');
    $url->set_var('refer_to', $refer_to);
    $this->register_url('login',  $url);

    $url = new FreechURL('', _('Log out'));
    $url->set_var('action', 'logout');
    $this->register_url('logout', $url);

    // Add user-specific links.
    //FIXME: this probably should not be here.
    $user = $this->get_current_user();
    if (!$user->is_active())
      return $this->_refer_to($this->get_url('logout')->get_string());
    elseif ($user->is_anonymous())
      $this->links['account']->add_link($this->get_url('login'));
    else {
      $url = $user->get_postings_url();
      $url->set_label(_('My Postings'));
      $this->links['account']->add_link($url);

      $url = $user->get_profile_url();
      $url->set_label(_('My Profile'));
      $this->links['account']->add_link($url);

      $this->links['account']->add_link($this->get_url('logout'));
    }

    trace('Leave');
  }


  /*************************************************************
   * Initialization and login/cookie handling.
   *************************************************************/
  function _language_supported($_lang) {
    if (is_readable("./language/$_lang/LC_MESSAGES/freech.mo"))
      return TRUE;
  }


  function _get_language($_lang) {
    if ($this->_language_supported($_lang))
      return $_lang;

    // Cut de_CH -> de.
    if (preg_match('/^([a-z]+)_[A-Z]+$/', $_lang, $matches)) {
      $_lang = $matches[1];
      if ($this->_language_supported($_lang))
        return $_lang;
    }

    // Change de -> de_DE
    $lang = $_lang . '_' . strtoupper($_lang);
    if ($this->_language_supported($lang))
      return $lang;

    return NULL;
  }


  function _try_login() {
    $userdb = $this->get_userdb();
    $user   = $userdb->get_user_from_name($_POST['username']);
    if (!$user)
      return _('Login failed.');
    if (!$user->is_confirmed())
      return _('Your account is not yet confirmed.');
    if (!$user->is_active())
      return _('Login failed.');
    if (!$user->is_valid_password($_POST['password']))
      return _('Login failed.');

    // Save user to update his timestamp.
    $user->set_last_login_time(time());
    $ret = $userdb->save_user($user);
    if ($ret < 0)
      die("Failed to log in user, return code $ret");
    $_SESSION['user_id'] = $user->get_id();
    $this->_refer_to(urldecode($_POST['refer_to']));
  }


  // Changes a cookie only if necessary.
  function set_cookie($_name, $_value) {
    if ($_COOKIE[$_name] != $_value) {
      setcookie($_name, $_value, cfg('cookie_expire_time'));
      $_COOKIE[$_name] = $_value;
    }
  }


  function get_thread_state($_section) {
    $default = cfg('default_thread_state', THREAD_STATE_UNFOLDED);
    $fold    = (int)$_COOKIE[$_section.'fold'];
    return new ThreadState($fold ? $fold : $default,
                           $_COOKIE[$_section.'c']);
  }


  function _handle_cookies() {
    if (get_magic_quotes_gpc()) {
      $_GET    = array_map('stripslashes_deep', $_GET);
      $_POST   = array_map('stripslashes_deep', $_POST);
      $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
    }

    $thread_state        = $this->get_thread_state('');
    $user_postings_state = $this->get_thread_state('user_postings_');

    if ($_GET['c']) {
      $thread_state->swap($_GET['c']);
      $this->set_cookie('c', $thread_state->get_string());
      $this->_refer_to($_GET['refer_to']);
    }

    if ($_GET['user_postings_c']) {
      $user_postings_state->swap($_GET['user_postings_c']);
      $this->set_cookie('user_postings_c',
                         $user_postings_state->get_string());
      $this->_refer_to($_GET['refer_to']);
    }

    if ($_GET['changeview']) {
      if ($this->views[$_GET['changeview']])
        $this->set_cookie('view', $_GET['changeview']);
      $this->_refer_to($_GET['refer_to']);
    }

    if ($_GET['showthread'] === '-1') {
      $this->set_cookie('thread', 'hide');
      $this->_refer_to($_GET['refer_to']);
    }
    elseif ($_GET['showthread'] === '1') {
      $this->set_cookie('thread', 'show');
      $this->_refer_to($_GET['refer_to']);
    }

    if ($_GET['fold'] === '1') {
      $this->set_cookie('fold', '1');
      $this->set_cookie('c', '');
      $this->_refer_to($_GET['refer_to']);
    } elseif ($_GET['fold'] === '2') {
      $this->set_cookie('fold', '2');
      $this->set_cookie('c', '');
      $this->_refer_to($_GET['refer_to']);
    }

    if ($_GET['user_postings_fold'] === '1') {
      $this->set_cookie('user_postings_fold', '1');
      $this->set_cookie('user_postings_c', '');
      $this->_refer_to($_GET['refer_to']);
    } elseif ($_GET['user_postings_fold'] === '2') {
      $this->set_cookie('user_postings_fold', '2');
      $this->set_cookie('user_postings_c', '');
      $this->_refer_to($_GET['refer_to']);
    }
  }


  function _init_breadcrumbs() {
    $url = $this->_get_homepage_url();
    $this->breadcrumbs()->add_link($url);

    if ($this->get_current_forum_id() == NULL)
      return;
    $url = $this->get_current_forum()->get_url();
    $this->breadcrumbs()->add_link($url);
  }


  /*************************************************************
   * Private utilities.
   *************************************************************/
  function &_get_user_from_id($_id) {
    return $this->get_userdb()->get_user_from_id((int)$_id);
  }


  function &_get_user_from_id_or_die($_id) {
    $user = $this->_get_user_from_id($_id);
    if (!$user)
      die('No such user');
    return $user;
  }


  function &_get_user_from_name($_name) {
    return $this->get_userdb()->get_user_from_name($_name);
  }


  function &_get_user_from_name_or_die($_name) {
    $user = $this->_get_user_from_name($_name);
    if (!$user)
      die('No such user');
    return $user;
  }


  function &_get_groupdb() {
    if (!$this->groupdb)
      $this->groupdb = new GroupDB($this->db);
    return $this->groupdb;
  }


  function &_get_group_from_id($_id) {
    $query = array('id' => (int)$_id);
    return $this->_get_groupdb()->get_group_from_query($query);
  }


  function &_get_group_from_id_or_die($_id) {
    $group = $this->_get_group_from_id($_id);
    if (!$group)
      die('No such group');
    return $group;
  }


  function &_get_group_from_name($_name) {
    $query = array('name' => $_name);
    return $this->_get_groupdb()->get_group_from_query($query);
  }


  function &_get_group_from_name_or_die($_name) {
    $group = $this->_get_group_from_name($_name);
    if (!$group)
      die('No such group');
    return $group;
  }


  function &_get_posting_from_id_or_die($_id) {
    $posting = $this->forumdb->get_posting_from_id((int)$_id);
    if (!$posting)
      die('No such posting.');
    return $posting;
  }


  function _assert_may($_action) {
    if (!$this->get_current_group()->may($_action))
      die('Permission denied.');
  }


  function &_init_group_from_post_data(&$_group = NULL) {
    if (!$_group)
      $_group = new Group;
    $_group->set_name($_POST['groupname']);

    // Read permissions.
    foreach ($_group->get_permission_list() as $action => $allow)
      if ($_POST["may_$action"] == 'on')
        $_group->grant($action);
      else
        $_group->deny($action);
    return $_group;
  }


  // Returns an URL that points to the homepage.
  function &_get_homepage_url() {
    if (!cfg('default_forum_id', FALSE))
      return new FreechURL('', _('Home'));

    $url = new FreechURL('', _('Home'));
    $url->set_var('action', 'homepage');
    return $url;
  }


  // Wrapper around get_current_user() that also works if a matching
  // user/hash combination was passed in through the GET request.
  function &_get_current_or_confirming_user() {
    $userdb   = $this->get_userdb();
    $username = $_GET['username'] ? $_GET['username'] : $_POST['username'];
    if (!$username)
      return $this->get_current_user();

    $user = $userdb->get_user_from_name($username);
    if (!$user|| $user->is_anonymous())
      return $this->get_current_user();

    assert_user_confirmation_hash_is_valid($user);
    return $user;
  }


  // Sends an email to the given user.
  function _send_mail(&$user, $subject, $body, $vars = NULL) {
    if (!$vars)
      $vars = array();
    $vars['site_title'] = cfg('site_title');
    $vars['login']      = $user->get_name();
    $vars['firstname']  = $user->get_firstname();
    $vars['lastname']   = $user->get_lastname();
    $head  = 'MIME-Version: 1.0'."\r\n"
           . 'From: '.cfg('mail_from')."\r\n"
           . 'Content-Type: text/plain; charset=UTF-8'."\r\n"
           . 'Content-Transfer-Encoding: 8bit';
    $subject            = replace_vars($subject, $vars);
    $body               = replace_vars($body,    $vars);
    // encode to UTF-8
    $subject  = '=?UTF-8?B?'.base64_encode($subject).'?=';
    mail($user->get_mail(), $subject, $body, $head);
  }


  // Convenience wrapper around _send_mail().
  function _send_password_reset_mail(&$user) {
    $subject  = _('Your password at [SITE_TITLE]');
    $body     = _("Hello [FIRSTNAME] [LASTNAME],\n"
                . "\n"
                . "We have received a password reset request"
                . " for your account \"[LOGIN]\".\n"
                . "\n"
                . "To change your password please click the link"
                . " below. If you did not request that your"
                . " password be changed you may ignore"
                . " this message.\n"
                . "\n"
                . "[URL]\n");
    $username = urlencode($user->get_name());
    $hash     = urlencode($user->get_confirmation_hash());
    $url      = cfg('site_url') . '?action=password_mail_confirm'
              . "&username=$username&hash=$hash";
    $vars     = array('url' => $url);
    $this->_send_mail($user, $subject, $body, $vars);
  }


  // Returns a (relative) url that points to the current page,
  // except in cases where the login page is shown or where
  // a referrer was already specified in the GET or POST variables.
  // In other words, this function returns a URL to which the
  // login form may refer after performing the login.
  function _get_login_refer_url() {
    $do_refer = array('read',
                      'write',
                      'search',
                      'respond',
                      'user_editor',
                      'user_postings',
                      'user_profile',
                      'group_profile',
                      'list');
    if ($_GET['refer_to'])
      return urldecode($_GET['refer_to']);
    elseif ($_POST['refer_to'])
      return urldecode($_POST['refer_to']);
    elseif (!in_array($this->get_current_action(), $do_refer)) {
      $forum = $this->get_current_forum();
      if ($forum)
        return $forum->get_url()->get_string();
      $url = new FreechURL;
      return $url->get_string();
    }
    elseif ($_SERVER['REQUEST_URI'] == '/')
      return '';
    else
      return $_SERVER['REQUEST_URI'];
  }


  function _refer_to($_url) {
    header('Location: '.$_url, TRUE, 301);
    die();
  }


  function _refer_to_posting_id($_posting_id) {
    $url = new FreechURL(cfg('site_url'));
    $url->set_var('action',   'read');
    $url->set_var('msg_id',   $_posting_id);
    $url->set_var('forum_id', $this->get_current_forum_id());
    $this->_refer_to($url->get_string());
  }


  function _get_db() {
    return $this->db;
  }


  function set_title($_title) {
    $this->title = $_title;
  }


  function _append_content($_content) {
    $this->content .= $_content . "\n";
  }


  function _get_current_view_name() {
    $name = $_COOKIE['view'];
    if ($name)
      return $name;
    return cfg('default_view');
  }


  function _get_current_view_class() {
    $view_name  = $this->_get_current_view_name();
    $view_class = $this->views[$view_name];
    if (!$view_class) {
      $view_name  = cfg('default_view');
      $view_class = $this->views[$view_name];
    }
    if (!$view_class)
      die('Plugin for default view is not installed (or active).');
    return $view_class;
  }


  function _get_current_view() {
    $view_class = $this->_get_current_view_class();
    return new $view_class($this->api);
  }


  /*************************************************************
   * Action controllers for the forum overview.
   *************************************************************/
  // Shows the homepage.
  function _show_homepage() {
    include 'controllers/homepage_controller.class.php';
    $controller = new HomepageController($this->api);
    $controller->show();
  }


  // Shows the forum.
  function _show_list() {
    $forum_id = $this->get_current_forum_id();
    $view     = $this->_get_current_view();
    $view->show($forum_id, (int)$_GET['hs']);
  }


  /*************************************************************
   * Action controllers for reading and editing postings.
   *************************************************************/
  // Prints the breadcrumbs pointing to the given posting.
  function _add_posting_breadcrumbs(&$_posting) {
    if (!$_posting)
      $this->breadcrumbs()->add_text(_('No Such Message'));
    elseif (!$_posting->is_active())
      $this->breadcrumbs()->add_text(_('Locked Message'));
    else
      $this->breadcrumbs()->add_text($_posting->get_subject());
  }


  // Logs a change to the moderation log.
  function _log_posting_moderation($_action, &$_posting, $_reason) {
    $this->_prepare_modlog();
    $change = new ModLogItem($_action);
    $change->set_reason($_reason);
    $change->set_from_user($this->get_current_user());
    $change->set_from_moderator_group($this->get_current_group());
    $change->set_attribute('forum_id',       $_posting->get_forum_id());
    $change->set_attribute('id',             $_posting->get_id());
    $change->set_attribute('subject',        $_posting->get_subject());
    $change->set_attribute('username',       $_posting->get_username());
    $change->set_attribute('user_icon',      $_posting->get_user_icon());
    $change->set_attribute('user_groupname', $_posting->get_user_icon_name());
    $this->get_modlogdb()->log($change);
  }


  // Read a thread.
  function _thread_read() {
    $args     = array('thread_id' => (int)$_GET['thread_id'],
                      'is_parent' => 1);
    $postings = $this->forumdb->get_postings_from_fields($args);
    $thread   = $postings[0];
    $this->_add_posting_breadcrumbs($thread);
    $view = $this->_get_current_view();
    $view->show_thread($thread);
  }


  // Read a posting.
  function _posting_read() {
    $posting = $this->forumdb->get_posting_from_id($_GET['msg_id']);
    $this->_add_posting_breadcrumbs($posting);

    /* Plugin hook: on_message_read_print
     *   Called before the HTML for the posting is produced.
     *   Args: posting: The posting that is about to be shown.
     */
    $this->eventbus->emit('on_message_read_print', $this->api, $posting);

    // Hide subject and body if the message is locked.
    if ($posting)
      $posting->apply_block();
    else {
      $posting = new Posting;
      $posting->set_subject(_('No Such Message'));
      $posting->set_body(_('A message with the given ID does not exist.'));
    }

    $view = $this->_get_current_view();
    $view->show_posting($posting);
  }


  // Form for moving an entire thread into a different forum.
  function _thread_move() {
    $this->_prepare_modlog();
    $this->_assert_may('moderate');
    $posting    = $this->_get_posting_from_id_or_die((int)$_GET['msg_id']);
    $controller = new ModLogController($this->api);
    $controller->show_thread_move($posting);
    $this->breadcrumbs()->add_link($posting->get_url());
    $this->breadcrumbs()->add_text(_('Move'));
  }


  // Moves an entire thread into a different forum.
  function _thread_move_submit() {
    $this->_assert_may('moderate');
    $posting_id = (int)$_POST['msg_id'];
    $forum_id   = (int)$_POST['forum_id'];
    $posting    = $this->_get_posting_from_id_or_die($posting_id);
    if (!$forum_id || $posting->get_forum_id() == $forum_id)
      $this->_refer_to_posting_id($posting_id);

    $posting->set_forum_id($forum_id);
    $this->_log_posting_moderation('move_thread', $posting, '');
    $this->forumdb->move_thread($posting->get_thread_id(), $forum_id);
    $this->_refer_to($posting->get_url()->get_string());
  }


  // Changes the priority of an existing posting.
  function _posting_prioritize() {
    $this->_assert_may('moderate');
    $posting = $this->_get_posting_from_id_or_die((int)$_GET['msg_id']);
    $posting->set_priority((int)$_GET['priority']);
    $this->forumdb->save($posting->get_forum_id(), -1, $posting);
    if ($posting->get_priority() == 0)
      $this->_log_posting_moderation('remove_sticky', $posting, '');
    else
      $this->_log_posting_moderation('set_sticky', $posting, '');
    $this->_refer_to(urldecode($_GET['refer_to']));
  }


  // Locks an existing posting.
  function _posting_lock() {
    $this->_prepare_modlog();
    $this->_assert_may('moderate');
    $posting    = $this->_get_posting_from_id_or_die((int)$_GET['msg_id']);
    $controller = new ModLogController($this->api);
    $controller->show_lock_posting($posting);
    $this->breadcrumbs()->add_link($posting->get_url());
    $this->breadcrumbs()->add_text(_('Lock'));
  }


  // Locks an existing posting.
  function _posting_lock_submit() {
    $this->_prepare_modlog();
    $this->_assert_may('moderate');
    $posting = $this->_get_posting_from_id_or_die((int)$_POST['msg_id']);

    // Check for completeness.
    $reason = $_POST['reason'];
    if ($_POST['spam'] == 'on') {
      $reason = '[IS_SPAM]';
      $posting->set_status(POSTING_STATUS_SPAM);
    }
    else
      $posting->set_status(POSTING_STATUS_LOCKED);

    if (!$reason) {
      $controller = new ModLogController($this->api);
      $controller->add_hint(new Error(_('Please enter a reason.')));
      return $controller->show_lock_posting($posting);
    }

    // Lock the posting and log the action.
    $this->forumdb->save($posting->get_forum_id(), -1, $posting);
    $this->_log_posting_moderation('lock_posting', $posting, $reason);
    $this->_refer_to(urldecode($_POST['refer_to']));
  }


  // Unlocks an existing posting.
  function _posting_unlock() {
    $this->_assert_may('moderate');
    $posting = $this->_get_posting_from_id_or_die((int)$_GET['msg_id']);
    $posting->set_status(POSTING_STATUS_ACTIVE);
    $this->forumdb->save($posting->get_forum_id(), -1, $posting);
    $this->_log_posting_moderation('unlock_posting', $posting, '');
    $this->_refer_to(urldecode($_GET['refer_to']));
  }


  // Disallow responses to a posting.
  function _posting_stub() {
    $this->_assert_may('moderate');
    $posting = $this->_get_posting_from_id_or_die((int)$_GET['msg_id']);
    $posting->set_force_stub(TRUE);
    $this->forumdb->save($posting->get_forum_id(), -1, $posting);
    $this->_log_posting_moderation('stub_posting', $posting, '');
    $this->_refer_to(urldecode($_GET['refer_to']));
  }


  // Allow responses to a posting.
  function _posting_unstub() {
    $this->_assert_may('moderate');
    $posting = $this->_get_posting_from_id_or_die((int)$_GET['msg_id']);
    $posting->set_force_stub(FALSE);
    $this->forumdb->save($posting->get_forum_id(), -1, $posting);
    $this->_log_posting_moderation('unstub_posting', $posting, '');
    $this->_refer_to(urldecode($_GET['refer_to']));
  }


  /*************************************************************
   * Action controllers for the user profile.
   *************************************************************/
  function _import_profile_controller() {
    include_once 'controllers/profile_controller.class.php';
  }


  // Logs a change to the moderation log.
  function _log_user_moderation($_action, &$_user, $_reason) {
    $this->_prepare_modlog();
    $change = new ModLogItem($_action);
    $change->set_reason($_reason);
    $change->set_from_user($this->get_current_user());
    $change->set_from_moderator_group($this->get_current_group());
    $change->set_attribute('username', $_user->get_name());
    $this->get_modlogdb()->log($change);
  }


  function _add_profile_breadcrumbs(&$_named_item) {
    $this->breadcrumbs()->add_text($_named_item->get_name());
  }


  // Lists all postings of one user.
  function _show_user_postings() {
    $this->_import_profile_controller();
    $user    = $this->_get_user_from_name_or_die($_GET['username']);
    $profile = new ProfileController($this->api);
    $this->_add_profile_breadcrumbs($user);
    $profile->show_user_postings($user, (int)$_GET['hs']);
  }


  // Display information of one user.
  function _show_user_profile() {
    $this->_import_profile_controller();
    $user    = $this->_get_user_from_name_or_die($_GET['username']);
    $profile = new ProfileController($this->api);
    $this->_add_profile_breadcrumbs($user);
    $profile->show_user_profile($user, (int)$_GET['hs']);
  }


  // Edit personal data.
  function _show_user_editor() {
    // Check permissions.
    $current = $this->get_current_user();
    $user    = $this->_get_user_from_name_or_die($_GET['username']);
    if ($current->is_anonymous())
      die('Not logged in');
    elseif ($_GET['username'] == $current->get_name()) {
      //accept
    }
    elseif ($this->get_current_group()->may('administer')) {
      //accept
    }
    elseif ($this->get_current_group()->may('moderate')) {
      //accept
    }
    else
      die('Permission denied.');

    // Accepted.
    $this->_add_profile_breadcrumbs($user);
    $this->_import_profile_controller();
    $profile = new ProfileController($this->api);
    $profile->show_user_editor($user);
  }


  // Submit personal data.
  function _submit_user() {
    $this->_import_profile_controller();
    $profile = new ProfileController($this->api);
    $user    = $this->get_current_user();
    $group   = $this->get_current_group();
    $is_self = $_POST['user_id'] == $user->get_id();

    // Check permissions.
    if ($user->is_anonymous())
      die('Not logged in');
    elseif ($group->may('administer')) {
      $user = $this->_get_user_from_id_or_die($_POST['user_id']);
      $user->set_name($_POST['username']);
      $user->set_group_id($_POST['group_id']);
      $user->set_status($_POST['status']);
      init_user_from_post_data($user);
    }
    elseif ($is_self) {
      if ($_POST['status'] != USER_STATUS_DELETED
       && $_POST['status'] != USER_STATUS_ACTIVE)
        die('Invalid status');
      init_user_from_post_data($user);
      $user->set_status($_POST['status']);
    }
    elseif ($group->may('moderate')) {
      if ($_POST['status'] != USER_STATUS_ACTIVE
       && $_POST['status'] != USER_STATUS_BLOCKED)
        die('Invalid status');
      $user = $this->_get_user_from_id_or_die($_POST['user_id']);
      if (!$user->is_locked() && !$user->is_active())
        die('No permission to change the user status.');
      $group2 = $this->_get_group_from_id_or_die($user->get_group_id());
      if ($user->is_anonymous() || $group2->may('administer'))
        die('No permission to change that user.');
      $user->set_status($_POST['status']);
      if ($user->is_active())
        $this->_log_user_moderation('unlock_user', $user, '');
      else
        $this->_log_user_moderation('lock_user', $user, '');
    }
    else
      die('Permission to edit user denied.');

    $this->_add_profile_breadcrumbs($user);

    // If the user status is now DELETED, remove any related attributes.
    if ($user->get_status() == USER_STATUS_DELETED)
      $user->set_deleted();
    else {
      // Else make sure that the data is complete and valid.
      $err = $user->check_complete();
      if ($err) {
        $profile->add_hint(new Error($err));
        return $profile->show_user_editor($user);
      }

      // Make sure that the passwords match.
      if ($_POST['password'] !== $_POST['password2']) {
        $profile->add_hint(new Hint(_('Error: Passwords do not match.')));
        return $profile->show_user_editor($user);
      }

      if ($_POST['password'] != '')
        $user->set_password($_POST['password']);
    }

    // Save the user.
    if (!$this->get_userdb()->save_user($user)) {
      $profile->add_hint(new Error(_('Failed to save the user.')));
      return $profile->show_user_editor($user);
    }

    // Done.
    if ($user->is_deleted() && $is_self)
      return $this->_refer_to($this->get_url('logout')->get_string());
    $profile->add_hint(new Ack(_('Your data has been saved.')));
    $profile->show_user_editor($user);
  }


  function _show_user_options() {
    $this->_import_profile_controller();
    $user = $this->get_current_user();
    $this->_add_profile_breadcrumbs($user);
    $profile = new ProfileController($this->api);
    $profile->show_user_options($user);
  }


  /*************************************************************
   * Action controllers for the group profile.
   *************************************************************/
  // Shows group info and lists all users of one group.
  function _show_group_profile() {
    $this->_import_profile_controller();
    $group = $this->_get_group_from_name_or_die($_GET['groupname']);
    $this->_add_profile_breadcrumbs($group);
    $profile = new ProfileController($this->api);
    $profile->show_group_profile($group, (int)$_GET['hs']);
  }


  // Edits the group profile.
  function _show_group_editor() {
    $this->_assert_may('administer');
    $this->_import_profile_controller();
    $group = $this->_get_group_from_name_or_die($_GET['groupname']);
    $this->_add_profile_breadcrumbs($group);
    $profile = new ProfileController($this->api);
    $profile->show_group_editor($group);
  }


  // Saves the group.
  function _group_submit() {
    $this->_assert_may('administer');
    $this->_import_profile_controller();
    $group = $this->_get_group_from_id_or_die($_POST['group_id']);
    $this->_init_group_from_post_data($group);
    $this->_add_profile_breadcrumbs($group);
    $profile = new ProfileController($this->api);

    // Make sure that the data is complete and valid.
    $err = $group->check_complete();
    if ($err) {
      $profile->add_hint(new Error($err));
      return $profile->show_group_editor($group);
    }

    // Save the group.
    if (!$this->_get_groupdb()->save_group($group)) {
      $profile->add_hint(new Error(_('Failed to save the group.')));
      return $profile->show_group_editor($group);
    }

    // Done.
    $profile->add_hint(new Ack(_('Your changes have been saved.')));
    $profile->show_group_editor($group);
  }


  /*************************************************************
   * Action controllers for login and password forms.
   *************************************************************/
  function _import_login() {
    include_once 'controllers/login_controller.class.php';
  }


  function _show_login() {
    $this->_import_login();
    $user     = init_user_from_post_data();
    $login    = new LoginController($this->api);
    $refer_to = $this->_get_login_refer_url();
    if ($this->login_error)
      $login->add_hint(new Hint($this->login_error));
    $login->show($user, $refer_to);
  }


  // Show a form for changing the password.
  function _password_change() {
    $user = $this->_get_current_or_confirming_user();
    if ($user->is_anonymous())
      die('Invalid user');
    $this->_import_login();
    $registration = new LoginController($this->api);
    $registration->show_password_change($user);
  }


  // Submit a new password.
  function _password_submit() {
    $this->_import_login();
    $userdb     = $this->get_userdb();
    $user       = init_user_from_post_data();
    $user       = $userdb->get_user_from_name($user->get_name());
    $current    = $this->_get_current_or_confirming_user();
    $controller = new LoginController($this->api);

    if ($user->is_anonymous())
      die('Invalid user');
    elseif ($user->get_id() != $current->get_id())
      $this->_assert_may('administer');

    // Make sure that the passwords match.
    if ($_POST['password'] !== $_POST['password2']) {
      $controller->add_hint(new Error(_('Error: Passwords do not match.')));
      return $controller->show_password_change($user);
    }

    // Make sure that the password is valid.
    $err = $user->set_password($_POST['password']);
    if ($err) {
      $controller->add_hint(new Error($err));
      return $controller->show_password_change($user);
    }

    // Save the password.
    $user->set_status(USER_STATUS_ACTIVE);
    if (!$userdb->save_user($user)) {
      $controller->add_hint(new Error(_('Failed to save the user.')));
      return $controller->show_password_change($user);
    }

    // Done.
    $controller->show_password_changed($user);
  }


  // Show a form for requesting that the password should be reset.
  function _password_forgotten() {
    $this->_import_login();
    $user       = init_user_from_post_data();
    $controller = new LoginController($this->api);
    $controller->show_password_forgotten($user);
  }


  // Send an email with the URL for resetting the password.
  function _password_mail_submit() {
    $this->_import_login();
    $controller = new LoginController($this->api);
    $user       = init_user_from_post_data();

    // Make sure that the email address is valid.
    $err = $user->check_mail();
    if ($err) {
      $controller->add_hint(new Error($err));
      return $controller->show_password_forgotten($user);
    }

    // Find the user with the given mail address.
    $userdb = $this->get_userdb();
    $user   = $userdb->get_user_from_mail($user->get_mail());
    if (!$user) {
      $user = init_user_from_post_data();
      $msg  = _('The given email address was not found.');
      $controller->add_hint(new Error($msg));
      return $controller->show_password_forgotten($user);
    }

    // Send the mail.
    if (!$user->is_confirmed()) {
      $url = new FreechURL(cfg('site_url'));
      $url->set_var('action',   'account_reconfirm');
      $url->set_var('username', $user->get_name());
      $this->_refer_to($url->get_string());
    }
    elseif ($user->is_active())
      $this->_send_password_reset_mail($user);
    elseif ($user->is_locked()) {
      $controller->add_hint(new Error(_('Your account is locked.')));
      return $controller->show_password_forgotten($user);
    }
    else
      die('Invalid user status');

    // Done.
    $controller->show_password_mail_sent($user);
  }


  // Called when the user opens the link in the password reset mail.
  function _password_mail_confirm() {
    $user   = $this->_get_current_or_confirming_user();
    $userdb = $this->get_userdb();
    $user   = $userdb->get_user_from_name($user->get_name());
    assert_user_confirmation_hash_is_valid($user);

    if (!$user->is_active())
      die('Error: User status is not active.');

    $this->_password_change();
  }


  /*************************************************************
   * Forum editor.
   *************************************************************/
  function _import_forum_editor() {
    include 'controllers/forum_editor_controller.class.php';
  }


  function _forum_add() {
    $this->_assert_may('administer');
    $this->_import_forum_editor();
    $this->breadcrumbs()->add_text(_('Add a New Forum'));
    $controller = new ForumEditorController($this->api);
    $controller->show(new Forum);
  }


  function _forum_edit() {
    $this->_assert_may('administer');
    $this->_import_forum_editor();
    $forum = $this->forumdb->get_forum_from_id((int)$_GET['forum_id']);
    $this->breadcrumbs()->add_text(_('Edit a Forum'));
    $controller = new ForumEditorController($this->api);
    $controller->show($forum);
  }


  function _forum_submit() {
    $this->_assert_may('administer');
    $this->_import_forum_editor();
    $controller = new ForumEditorController($this->api);
    $forum_id   = (int)$_POST['forum_id'];

    // Fetch the forum and merge POST data.
    if ($forum_id)
      $forum = $this->forumdb->get_forum_from_id($forum_id);
    else {
      $user  = $this->get_current_user();
      $forum = new Forum;
      $forum->set_owner_id($user->get_id());
    }
    $forum->set_name($_POST['name']);
    $forum->set_description($_POST['description']);
    $forum->set_status($_POST['status']);

    // Check syntax.
    if ($err = $forum->check()) {
      $controller->add_hint(new Error($err));
      return $controller->show($forum);
    }

    // Save the data.
    $this->forumdb->save_forum($forum);
    $controller->add_hint(new Ack(_('The changes have been saved.')));
    $controller->show($forum);
  }


  /*************************************************************
   * Other action controllers.
   *************************************************************/
  function _show_moderation_log() {
    trace('enter');
    $this->_prepare_modlog();
    $this->breadcrumbs()->add_text(_('Moderation Log'));
    $controller = new ModLogController($this->api);
    $controller->show((int)$_GET['hs']);
    trace('leave');
  }


  function _print_breadcrumbs() {
    trace('enter');
    $show_page_links = (bool)$this->get_current_forum_id();
    $controller      = new BreadCrumbsController($this->api);
    $controller->show($this->breadcrumbs(), $show_page_links);
    trace('leave');
  }


  // Prints the footer of the page.
  function _print_footer() {
    trace('Enter');
    $footer = new FooterController($this->api);
    $footer->show($this->get_current_forum_id());
    trace('Leave');
  }


  // Prints an RSS feed.
  function print_rss($_forum_id,
                     $_title,
                     $_descr,
                     $_off,
                     $_n_entries) {
    include 'controllers/rss_controller.class.php';
    $this->content = '';
    $rss = new RSSController($this->api);
    $rss->set_base_url(cfg('site_url'));
    $rss->set_title($_title);
    $rss->set_description($_descr);
    $rss->set_language(cfg('content_language'));
    $rss->show((int)$_forum_id, (int)$_off, (int)$_n_entries);
    print $this->content;
  }


  /*************************************************************
   * Public.
   *************************************************************/
  function run() {
    trace('Enter');
    /* Plugin hook: on_run_before
     *   Called before the forum is run and the HTML is produced.
     *   Args: None.
     */
    $this->eventbus->emit('on_run_before', $this->api);
    trace('on_run_before completed');
    $this->title = '';
    $action      = $this->get_current_action();

    // Prevent from accessing non-existent forums.
    if ($this->get_current_forum_id() && !$this->get_current_forum())
      die('No such forum.');
    trace('made sure forum exists');

    $this->_init_breadcrumbs();
    trace('breadcrumbs initialized');
    ob_start();

    // Check whether a plugin registered the given action. This is done
    // first to allow plugins for overriding the default handler.
    if ($this->actions[$action]) {
      trace('plugin action started');
      call_user_func($this->actions[$action], $this->api);
      trace('plugin action completed');
      $this->_print_footer();
      $this->content = ob_get_contents();
      ob_end_clean();
      return;
    }

    trace('no plugin action found');
    switch ($action) {
    case 'read':
      if ($_GET['msg_id'])
        $this->_posting_read();
      else
        $this->_thread_read();
      break;

    case 'thread_move':
      $this->_thread_move();
      break;

    case 'thread_move_submit':
      $this->_thread_move_submit();
      break;

    case 'posting_prioritize':
      $this->_posting_prioritize();
      break;

    case 'posting_lock':
      $this->_posting_lock();
      break;

    case 'posting_lock_submit':
      $this->_posting_lock_submit();
      break;

    case 'posting_unlock':
      $this->_posting_unlock();
      break;

    case 'posting_stub':
      $this->_posting_stub();
      break;

    case 'posting_unstub':
      $this->_posting_unstub();
      break;

    case 'user_profile':
      $this->_show_user_profile();        // Show a user profile.
      break;

    case 'user_postings':
      $this->_show_user_postings();       // Show the postings of one user.
      break;

    case 'user_editor':
      $this->_show_user_editor();         // Form for editing user data.
      break;

    case 'user_submit':
      $this->_submit_user();              // Chang user data.
      break;

    case 'user_options':
      $this->_show_user_options();        // Show the user settings.
      break;

    case 'user_options_submit':
      $this->_submit_user_options();
      break;

    case 'group_profile':
      $this->_show_group_profile();       // Show a group profile.
      break;

    case 'group_editor':
      $this->_show_group_editor();        // Edit the group profile.
      break;

    case 'group_submit':
      $this->_group_submit();             // Save group changes.
      break;

    case 'login':
      $this->_show_login();               // Show a login form.
      break;

    case 'password_change':
      $this->_password_change();          // Form for changing the password.
      break;

    case 'password_submit':
      $this->_password_submit();          // Set the initial password.
      break;

    case 'password_forgotten':
      $this->_password_forgotten();       // Form for requesting password mail.
      break;

    case 'password_mail_submit':
      $this->_password_mail_submit();     // Send password mail request.
      break;

    case 'password_mail_confirm':
      $this->_password_mail_confirm();    // Form for resetting the password.
      break;

    case 'forum_add':
      $this->_forum_add();
      break;

    case 'forum_edit':
      $this->_forum_edit();
      break;

    case 'forum_submit':
      $this->_forum_submit();
      break;

    case 'moderation_log':
      $this->_show_moderation_log();      // Moderation log.
      break;

    case 'list':
      $this->_show_list();
      break;

    case 'homepage':
      $this->_show_homepage();
      break;

    default:
      break;
    }
    trace('action completed');

    $this->_print_footer();

    $this->content = ob_get_contents();
    ob_end_clean();
    trace('leave');
  }


  function &get_forumdb() {
    return $this->forumdb;
  }


  function &get_userdb() {
    if (!$this->userdb)
      $this->userdb = new UserDB($this->db);
    return $this->userdb;
  }


  function &get_eventbus() {
    return $this->eventbus;
  }


  function &_prepare_modlog() {
    include_once 'objects/modlog_item.class.php';
    include_once 'services/modlogdb.class.php';
    include_once 'controllers/modlog_controller.class.php';
  }


  function &get_modlogdb() {
    $this->_prepare_modlog();
    if (!$this->modlogdb)
      $this->modlogdb = new ModLogDB($this->db);
    return $this->modlogdb;
  }


  function get_current_user() {
    if ($this->current_user)
      return $this->current_user;
    if (session_id() !== '' && $_SESSION['user_id'])
      $this->current_user = $this->_get_user_from_id($_SESSION['user_id']);
    if ($this->current_user)
      return $this->current_user;
    elseif (cfg('manage_anonymous_users')) {
      $user_id            = cfg('anonymous_user_id');
      $this->current_user = $this->_get_user_from_id_or_die($user_id);
    }
    else {
      $this->current_user = new User;
      $this->current_user->set_id(cfg('anonymous_user_id'));
      $this->current_user->set_status(USER_STATUS_ACTIVE);
    }
    return $this->current_user;
  }


  function &get_current_group() {
    if ($this->current_group)
      return $this->current_group;
    $user = $this->get_current_user();
    if ($user->is_anonymous() && !cfg('manage_anonymous_users')) {
      $this->current_group = new Group;
      $this->current_group->set_id(cfg('anonymous_group_id'));
      $this->current_group->set_name(cfg('anonymous_group_name'));
      $this->current_group->set_special();
    }
    elseif ($user->is_anonymous()) {
      $group_id = cfg('anonymous_group_id');
      $this->current_group = $this->_get_group_from_id($group_id);
    }
    else {
      $group_id = $user->get_group_id();
      $this->current_group = $this->_get_group_from_id($group_id);
    }
    return $this->current_group;
  }


  function get_current_action() {
    if ($_GET['action'])
      return $_GET['action'];
    if ($_POST['action'])
      return $_POST['action'];
    if ($_GET['forum_id'] || cfg('default_forum_id', FALSE))
      return 'list';
    return 'homepage';
  }


  // Returns a title for the current site.
  function get_title() {
    if ($this->title)
      return $this->title.' - '.cfg('site_title');
    $forum = $this->get_current_forum();
    if (!$forum)
      return cfg('site_title');
    if ($forum->get_name() == cfg('site_title'))
      return cfg('site_title');
    return $forum->get_name().' - '.cfg('site_title');
  }


  function get_current_forum_id() {
    if ($this->get_current_action() == 'homepage')
      return NULL;
    if ($_GET['forum_id'])
      return (int)$_GET['forum_id'];
    if ($_POST['forum_id'])
      return (int)$_POST['forum_id'];
    $default = cfg('default_forum_id', FALSE);
    if (!$default)
      return NULL;
    return $default;
  }


  function &get_current_forum() {
    if ($this->current_forum)
      return $this->current_forum;
    $id = $this->get_current_forum_id();
    if (!$id)
      return NULL;
    $forum = $this->forumdb->get_forum_from_id($id);
    if (!$forum)
      return NULL;
    if (!$forum->is_active()
      && !$this->get_current_group()->may('administer'))
      return NULL;
    $this->current_forum = $forum;
    return $forum;
  }


  function register_url($_name, &$_url) {
    $this->urls[$_name] = $_url;
  }


  function &get_url($_name) {
    return $this->urls[$_name];
  }


  function register_action($_action, $_func) {
    $this->actions[$_action] = $_func;
  }


  function register_view($_name, $_view, $_caption, $_priority) {
    $this->views[$_name] = $_view;

    if ($this->get_current_action() != 'list'
      && $this->get_current_action() != 'read')
      return;

    if ($this->_get_current_view_name() === $_name)
      return $this->links('view')->add_text($_caption, $_priority);

    $refer_url = new FreechURL;
    $refer_url->set_var('action',   $_GET['action']);
    $refer_url->set_var('msg_id',   $_GET['msg_id']);
    $refer_url->set_var('forum_id', $_GET['forum_id']);

    $url = new FreechURL('', $_caption);
    $url->set_var('forum_id',   $this->get_current_forum_id());
    $url->set_var('changeview', $_name);
    $url->set_var('refer_to',   $refer_url->get_string());
    $this->links('view')->add_link($url, $_priority);
  }


  function get_js($_where) {
    return $this->js[$_where];
  }


  function add_js($_where, $_javascript) {
    if (!in_array($_where, array('head', 'onload', 'onsubmit')))
      die("add_js(): Unsupported location '$_where'.");
    $this->js[$_where] .= $_javascript . ';';
  }


  function get_style() {
    return $this->style;
  }


  function add_style($_css) {
    $this->style .= $_css . ';';
  }


  function get_html($_where) {
    return $this->html[$_where];
  }


  function add_html($_where, $_html) {
    if (!in_array($_where, array('form')))
      die("add_html(): Unsupported location '$_where'.");
    $this->html[$_where] .= $_html;
  }


  function &links($_where) {
    return $this->links[$_where];
  }


  function &breadcrumbs() {
    return $this->links['breadcrumbs'];
  }


  function print_head($_header = NULL) {
    trace('Enter');

    /* Plugin hook: on_header_print_before
     *   Called before the HTML header is sent.
     *   Args: $html: A reference to the HTML header.
     */
    $this->eventbus->emit('on_header_print_before', $this->api);

    if ($_header)
      echo $_header;
    elseif (!headers_sent()) {
      header('Content-Type: text/html; charset=utf-8');
      header('Pragma: no-cache');
      header('Cache-control: no-cache');
      trace('rendering header');
      include 'controllers/header_controller.class.php';
      $header = new HeaderController($this->api);
      $header->show($this->get_title());
      trace('rendered header');
    }

    trace('printed header');

    /* Plugin hook: on_header_print_before
     *   Called after the HTML header was sent.
     *   Args: none
     */
    $this->eventbus->emit('on_header_print_after', $this->api);
    trace('Leave');
  }


  function show() {
    trace('Enter');
    /* Plugin hook: on_content_print_before
     *   Called before the HTML content is sent.
     *   Args: $html: A reference to the content.
     */
    $this->eventbus->emit('on_content_print_before', $this->api);
    trace('on_content_print_before completed');

    $this->_print_breadcrumbs();
    echo $this->content;
    trace('print completed');

    $this->render_time = microtime(TRUE) - $this->start_time;
    if (cfg('show_total_render_time')) {
      $render_time = round($this->get_render_time(), 2);
      echo '<p id="rendered">Site rendered in ', $render_time, ' seconds.</p>';
    }

    /* Plugin hook: on_content_print_after
     *   Called after the HTML content was sent.
     *   Args: none.
     */
    $this->eventbus->emit('on_content_print_after', $this->api);
    trace('Leave');
  }


  function get_render_time() {
    return $this->render_time;
  }


  function destroy() {
    trace('Enter');
    unset($this->content);
    $this->db->Close();
    /* Plugin hook: on_destroy
     *   Called from within FreechForum->destroy().
     *   The return value of the callback is ignored.
     *   Args: None.
     */
    $this->eventbus->emit('on_destroy', $this->api);
    trace('Leave');
  }
}
?>
