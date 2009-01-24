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

  require_once 'smarty/Smarty.class.php';
  require_once 'adodb/adodb.inc.php';
  include_once 'libuseful/SqlQuery.class.php5';
  include_once 'libuseful/string.inc.php';
  include_once 'services/trackable.class.php';
  include_once 'objects/thread_state.class.php';

  include_once 'functions/config.inc.php';
  include_once 'functions/language.inc.php';
  include_once 'functions/httpquery.inc.php';
  include_once 'functions/files.inc.php';

  include_once 'error.inc.php';

  include_once 'objects/url.class.php';
  include_once 'objects/posting.class.php';
  include_once 'objects/forum.class.php';
  include_once 'objects/user.class.php';
  include_once 'objects/group.class.php';
  include_once 'objects/modlog_item.class.php';
  include_once 'objects/posting_decorator.class.php';
  include_once 'objects/unknown_posting.class.php';
  include_once 'objects/menu_item.class.php';
  include_once 'objects/menu.class.php';
  include_once 'objects/indexbar_group_profile.class.php';
  include_once 'objects/indexbar_user_postings.class.php';
  include_once 'objects/indexbar_footer.class.php';
  include_once 'objects/parser.class.php';

  include_once 'actions/printer_base.class.php';
  include_once 'actions/modlog_printer.class.php';
  include_once 'actions/rss_printer.class.php';
  include_once 'actions/breadcrumbs_printer.class.php';
  include_once 'actions/login_printer.class.php';
  include_once 'actions/profile_printer.class.php';
  include_once 'actions/homepage_printer.class.php';
  include_once 'actions/header_printer.class.php';
  include_once 'actions/footer_printer.class.php';
  include_once 'actions/forum_editor_printer.class.php';
  include_once 'actions/view.class.php';

  include_once 'services/groupdb.class.php';
  include_once 'services/sql_query.class.php';
  include_once 'services/forumdb.class.php';
  include_once 'services/userdb.class.php';
  include_once 'services/modlogdb.class.php';
  include_once 'services/visitordb.class.php';
  include_once 'services/plugin_registry.class.php';
  ini_set('arg_separator.output', '&');

  class FreechForum {
    var $db;
    var $forum;
    var $eventbus;
    var $smarty;
    var $thread_state;

    // Prepare the forum, set cookies, etc. To be called before the http header
    // was sent.
    function FreechForum() {
      // Select a language.
      $l = cfg('lang');
      if ($l == 'auto')
        $l = ($_REQUEST[language] ? $_REQUEST[language] : cfg('lang_default'));
      //putenv("LANG=$l");
      @setlocale(LC_MESSAGES, $l);

      if (cfg_is('salt', ''))
        die('Error: Please define the salt variable in config.inc.php!');

      // Setup gettext.
      if (!function_exists('gettext'))
        die('This webserver does not have gettext installed.<br/>'
          . 'Please contact your webspace provider.');
      $domain = 'freech';
      bindtextdomain($domain, './language');
      textdomain($domain);
      bind_textdomain_codeset($domain, 'UTF-8');

      // Start the PHP session.
      session_set_cookie_params(time() + cfg('login_time'));
      if ($_COOKIE['permanent_session']) {
        session_id($_COOKIE['permanent_session']);
        session_start();
      }
      else {
        session_start();
        if ($_POST['permanent'] === 'ON')
          setcookie('permanent_session', session_id(), time() + cfg('login_time'));
      }

      // Only now start the timer, as cookie handling may scew the result.
      $this->start_time = microtime(TRUE);

      // (Ab)use a Trackable as an eventbus.
      $this->eventbus      = new Trackable;
      $this->forum_links   = new Menu;
      $this->page_links    = new Menu;
      $this->search_links  = new Menu;
      $this->footer_links  = new Menu;
      $this->account_links = new Menu;
      $this->breadcrumbs   = new Menu;
      $this->actions       = array();
      $this->views         = array();
      $this->renderers     = array();
      $this->current_user  = NULL;
      $this->current_forum = NULL;

      // Connect to the DB.
      $this->db = ADONewConnection(cfg('db_dbn'))
        or die('FreechForum::FreechForum(): Error: Can\'t connect.'
             . ' Please check username, password and hostname.');
      $this->forumdb   = new ForumDB($this->db);

      $registry = new PluginRegistry;
      foreach (cfg('plugins') as $plugin => $active)
        if ($active)
          $registry->activate_plugin_from_dirname('plugins/'.$plugin, $this);

      $this->_handle_cookies();

      // Initialize the visitordb after cookie handling to prevent useless
      // updates.
      $this->visitordb = new VisitorDB($this->db);
      $this->visitordb->count();

      /* Plugin hook: on_construct
       *   Called from within the FreechForum() constructor before any
       *   other output is produced.
       *   The return value of the callback is ignored.
       *   Args: None.
       */
      $this->eventbus->emit('on_construct', $this);

      // Init Smarty.
      $this->smarty = new Smarty();
      $this->smarty->template_dir  = 'templates';
      $this->smarty->compile_dir   = 'data/smarty_templates_c';
      $this->smarty->cache_dir     = 'data/smarty_cache';
      $this->smarty->config_dir    = 'data/smarty_configs';
      $this->smarty->compile_check = cfg('check_cache');
      $this->smarty->register_function('lang', 'smarty_lang');

      // Attempt to login, if requested.
      $this->login_error = 0;
      if ($this->get_current_action() == 'login' && $_POST['username'])
        $this->login_error = $this->_try_login();
      if ($this->get_current_action() == 'logout') {
        session_unset();
        $url = new URL('.', cfg('urlvars'));
        $this->_refer_to($url->get_string());
      }

      // Add the modlog URL to the forum links.
      $url = new URL('?', cfg('urlvars'), lang('modlog'));
      $url->set_var('action', 'moderation_log');
      $this->forum_links->add_link($url);

      // Add user-specific links.
      //FIXME: this probably should not be here.
      $user = $this->get_current_user();
      if (!$user->is_active())
        return $this->_refer_to($this->get_logout_url()->get_string());
      elseif ($user->is_anonymous()) {
        $this->account_links->add_link($this->get_registration_url());
        $this->account_links->add_link($this->get_login_url());
      }
      else {
        $url = $user->get_postings_url();
        $url->set_label(lang('mypostings'));
        $this->account_links->add_link($url);

        $url = $user->get_profile_url();
        $url->set_label(lang('myprofile'));
        $this->account_links->add_link($url);

        $this->account_links->add_link($this->get_logout_url());
      }
    }


    /*************************************************************
     * Initialization and login/cookie handling.
     *************************************************************/
    function _try_login() {
      $userdb = $this->get_userdb();
      $user   = $userdb->get_user_from_name($_POST['username']);
      if (!$user)
        return ERR_LOGIN_FAILED;
      if (!$user->is_confirmed())
        return ERR_LOGIN_UNCONFIRMED;
      if (!$user->is_active())
        return ERR_LOGIN_FAILED;
      if (!$user->is_valid_password($_POST['password']))
        return ERR_LOGIN_FAILED;

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
      $this->breadcrumbs->add_link($url);

      if ($this->get_current_forum_id() == NULL)
        return;
      $url = $this->get_current_forum()->get_url();
      $this->breadcrumbs->add_separator();
      $this->breadcrumbs->add_link($url);
    }


    /*************************************************************
     * Private utilities.
     *************************************************************/
    function _get_user_from_id($_id) {
      return $this->get_userdb()->get_user_from_id((int)$_id);
    }


    function _get_user_from_id_or_die($_id) {
      $user = $this->_get_user_from_id($_id);
      if (!$user)
        die('No such user');
      return $user;
    }


    function _get_user_from_name($_name) {
      return $this->get_userdb()->get_user_from_name($_name);
    }


    function _get_user_from_name_or_die($_name) {
      $user = $this->_get_user_from_name($_name);
      if (!$user)
        die('No such user');
      return $user;
    }


    function _get_groupdb() {
      if (!$this->groupdb)
        $this->groupdb = &new GroupDB($this->db);
      return $this->groupdb;
    }


    function _get_group_from_id($_id) {
      $query = array('id' => (int)$_id);
      return $this->_get_groupdb()->get_group_from_query($query);
    }


    function _get_group_from_id_or_die($_id) {
      $group = $this->_get_group_from_id($_id);
      if (!$group)
        die('No such group');
      return $group;
    }


    function _get_group_from_name($_name) {
      $query = array('name' => $_name);
      return $this->_get_groupdb()->get_group_from_query($query);
    }


    function _get_group_from_name_or_die($_name) {
      $group = $this->_get_group_from_name($_name);
      if (!$group)
        die('No such group');
      return $group;
    }


    function _get_posting_from_id_or_die($_id) {
      $posting = $this->forumdb->get_posting_from_id((int)$_id);
      if (!$posting)
        die('No such posting.');
      return $posting;
    }


    function _assert_may($_action) {
      if (!$this->get_current_group()->may($_action))
        die('Permission denied.');
    }


    function _init_user_from_post_data($_user = NULL) {
      if (!$_user)
        $_user = new User($_POST['username']);
      $_user->set_password($_POST['password']);
      $_user->set_firstname($_POST['firstname']);
      $_user->set_lastname($_POST['lastname']);
      $_user->set_mail($_POST['mail'], $_POST['publicmail'] == 'on');
      $_user->set_homepage($_POST['homepage']);
      $_user->set_im($_POST['im']);
      $_user->set_signature($_POST['signature']);
      return $_user;
    }


    function _init_group_from_post_data($_group = NULL) {
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


    function _decorate_posting($_posting) {
      if (!$_posting)
        return $_posting;
      $renderer = $this->renderers[$_posting->get_renderer()];
      if ($renderer)
        return new $renderer($_posting, $this);
      return new UnknownPosting($_posting, $this);
    }


    // Returns an URL that points to the homepage.
    function _get_homepage_url() {
      if (!cfg('default_forum_id', FALSE))
        return new URL('.', cfg('urlvars'), lang('home'));

      $url = new URL('?', cfg('urlvars'), lang('home'));
      $url->set_var('action', 'homepage');
      return $url;
    }


    // Returns TRUE if the username is available, FALSE otherwise.
    function _username_available(&$_username) {
      $userdb = $this->get_userdb();
      $needle = new User($_username);
      $users  = $userdb->get_similar_users_from_name($_username);
      foreach ($users as $user)
        if ($user->is_lexically_similar_to($needle))
          return FALSE;
      return TRUE;
    }


    // Wrapper around get_current_user() that also works if a matching
    // user/hash combination was passed in through the GET request.
    function _get_current_or_confirming_user() {
      $userdb   = $this->get_userdb();
      $username = $_GET['username'] ? $_GET['username'] : $_POST['username'];
      if (!$username)
        return $this->get_current_user();

      $user = $userdb->get_user_from_name($username);
      if (!$user|| $user->is_anonymous())
        return $this->get_current_user();

      $this->_assert_confirmation_hash_is_valid($user);
      return $user;
    }


    // Dies if the confirmation hash passed in through GET is not valid.
    function _assert_confirmation_hash_is_valid(&$user) {
      if (!$user)
        die('Invalid user');
      $given_hash = $_GET['hash'] ? $_GET['hash'] : $_POST['hash'];
      $hash       = $user->get_confirmation_hash();
      if ($user->get_confirmation_hash() !== $given_hash)
        die('Invalid confirmation hash');
      if ($user->is_locked())
        die('User is locked');
    }


    function _posting_is_spam($_posting) {
      return $this->forumdb->is_spam($_posting);
    }


    function _flood_blocked_until($_posting) {
      $forumdb = $this->forumdb;
      $user    = $this->get_current_user();
      $since   = time() - cfg('max_postings_time');
      $offset  = cfg('max_postings') * -1;

      // Find out how many postings were sent from the given user lately.
      if (!$user->is_anonymous()) {
        $uid        = $user->get_id();
        $n_postings = $forumdb->get_n_postings_from_user_id($uid, $since);
        if ($n_postings < cfg('max_postings'))
          return;
        $search       = array('user_id' => $uid);
        $last_posting = $forumdb->get_posting_from_query($search, $offset);
      }

      // Find out how many postings were sent from the given IP lately.
      if (!$last_posting) {
        $ip_hash    = $_posting->get_ip_address_hash();
        $n_postings = $forumdb->get_n_postings_from_ip_hash($ip_hash, $since);
        if ($n_postings < cfg('max_postings'))
          return;
        $search       = array('ip_hash' => $ip_hash);
        $last_posting = $forumdb->get_posting_from_query($search, $offset);
      }

      if (!$last_posting)
        return;

      // If the too many postings were posted, block this.
      $post_time = $last_posting->get_created_unixtime();
      return $post_time + cfg('max_postings_time');
    }


    // Sends an email to the given user.
    function _send_account_mail(&$user, $subject, $body, $vars) {
      $head  = 'From: '.cfg('mail_from').'\r\n';
      $vars['login']     = $user->get_name();
      $vars['firstname'] = $user->get_firstname();
      $vars['lastname']  = $user->get_lastname();
      foreach ($vars as $key => $value) {
        $subject = str_replace('['.strtoupper($key).']', $value, $subject);
        $body    = str_replace('['.strtoupper($key).']', $value, $body);
      }
      mail($user->get_mail(), $subject, $body, $head);
    }


    // Convenience wrapper around _send_account_mail().
    function _send_password_reset_mail($user) {
      $subject  = lang('reset_mail_subject');
      $body     = lang('reset_mail_body');
      $username = urlencode($user->get_name());
      $hash     = urlencode($user->get_confirmation_hash());
      $url      = cfg('site_url') . '?action=password_mail_confirm'
                . "&username=$username&hash=$hash";
      $this->_send_account_mail($user, $subject, $body, array('url' => $url));
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
        $url = new URL('.', cfg('urlvars'));
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
      $url = &new URL(cfg('site_url').'?', cfg('urlvars'));
      $url->set_var('action',   'read');
      $url->set_var('msg_id',   $_posting_id);
      $url->set_var('forum_id', $this->get_current_forum_id());
      $this->_refer_to($url->get_string());
    }


    function &_get_smarty() {
      return $this->smarty;
    }


    function &_get_db() {
      return $this->db;
    }


    function _set_title(&$_title) {
      $this->title = $_title;
    }


    function _append_content(&$_content) {
      $this->content .= $_content . "\n";
    }


    function _get_current_view_name() {
      $name = $_COOKIE['view'];
      if ($name && $this->views[$name])
        return $name;
      return 'thread'; //FIXME: make configurable
    }


    function _get_current_view_class() {
      $view_name  = $this->_get_current_view_name();
      $view_class = $this->views[$view_name];
      if (!$view_class)
        die('Plugin for current view is not installed (or active).');
      return $view_class;
    }


    function _get_current_view() {
      $view_class = $this->_get_current_view_class();
      return new $view_class($this);
    }


    /*************************************************************
     * Action controllers for the forum overview.
     *************************************************************/
    // Shows the homepage.
    function _show_homepage() {
      $printer = new HomepagePrinter($this);
      $printer->show();
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
    function _add_posting_breadcrumbs($_posting) {
      $this->breadcrumbs->add_separator();
      if (!$_posting)
        $this->breadcrumbs->add_text(lang('noentrytitle'));
      elseif (!$_posting->is_active())
        $this->breadcrumbs->add_text(lang('blockedtitle'));
      else
        $this->breadcrumbs->add_text($_posting->get_subject());
    }


    // Logs a change to the moderation log.
    function _log_posting_moderation($_action, $_posting, $_reason) {
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


    // Read a posting.
    function _posting_read() {
      $posting = $this->forumdb->get_posting_from_id($_GET['msg_id']);
      $posting = $this->_decorate_posting($posting);
      $this->_add_posting_breadcrumbs($posting);

      /* Plugin hook: on_message_read_print
       *   Called before the HTML for the posting is produced.
       *   Args: posting: The posting that is about to be shown.
       */
      $this->eventbus->emit('on_message_read_print', $this, $posting);

      // Hide subject and body if the message is locked.
      if ($posting)
        $posting->apply_block();
      else {
        $posting = new Posting;
        $posting->set_subject(lang('noentrytitle'));
        $posting->set_body(lang('noentrybody'));
      }

      $view = $this->_get_current_view();
      $view->show_posting($posting);
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
      $this->_assert_may('moderate');
      $posting = $this->_get_posting_from_id_or_die((int)$_GET['msg_id']);
      $printer = new ModLogPrinter($this);
      $printer->show_lock_posting($posting);
    }


    // Locks an existing posting.
    function _posting_lock_submit() {
      $this->_assert_may('moderate');
      $posting = $this->_get_posting_from_id_or_die((int)$_POST['msg_id']);

      // Check for completeness.
      $reason = $_POST['reason'];
      if ($_POST['spam'] == 'on') {
        $reason = lang('moderate_reason_spam');
        $posting->set_status(POSTING_STATUS_SPAM);
      }
      else
        $posting->set_status(POSTING_STATUS_LOCKED);

      if (!$reason) {
        $printer = new ModLogPrinter($this);
        return $printer->show_lock_posting($posting,
                                           lang('moderate_no_reason'));
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
    // Logs a change to the moderation log.
    function _log_user_moderation($_action, $_user, $_reason) {
      $change = new ModLogItem($_action);
      $change->set_reason($_reason);
      $change->set_from_user($this->get_current_user());
      $change->set_from_moderator_group($this->get_current_group());
      $change->set_attribute('username', $_user->get_name());
      $this->get_modlogdb()->log($change);
    }


    function _add_profile_breadcrumbs($_named_item) {
      $this->breadcrumbs->add_separator();
      $this->breadcrumbs->add_text($_named_item->get_name());
    }


    // Lists all postings of one user.
    function _show_user_postings() {
      $user         = $this->_get_user_from_name_or_die($_GET['username']);
      $thread_state = $this->get_thread_state('user_postings_');
      $profile      = new ProfilePrinter($this);
      $this->_add_profile_breadcrumbs($user);
      $profile->show_user_postings($user, $thread_state, (int)$_GET['hs']);
    }


    // Display information of one user.
    function _show_user_profile() {
      $user         = $this->_get_user_from_name_or_die($_GET['username']);
      $thread_state = $this->get_thread_state('user_postings_');
      $profile      = new ProfilePrinter($this);
      $this->_add_profile_breadcrumbs($user);
      $profile->show_user_profile($user, $thread_state, (int)$_GET['hs']);
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
      $profile = new ProfilePrinter($this);
      $profile->show_user_editor($user);
    }


    // Submit personal data.
    function _submit_user() {
      global $err;
      $profile = &new ProfilePrinter($this);
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
        $this->_init_user_from_post_data($user);
      }
      elseif ($is_self) {
        if ($_POST['status'] != USER_STATUS_DELETED
         && $_POST['status'] != USER_STATUS_ACTIVE)
          die('Invalid status');
        $this->_init_user_from_post_data($user);
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
        $ret = $user->check_complete();
        if ($ret < 0)
          return $profile->show_user_editor($user, $err[$ret]);

        // Make sure that the passwords match.
        if ($_POST['password'] !== $_POST['password2']) {
          $hint = $err[ERR_REGISTER_PASSWORDS_DIFFER];
          return $profile->show_user_editor($user, $hint);
        }

        if ($_POST['password'] != '')
          $user->set_password($_POST['password']);
      }

      // Save the user.
      $ret = $this->get_userdb()->save_user($user);
      if ($ret < 0)
        return $profile->show_user_editor($user, $err[$ret]);

      // Done.
      if ($user->is_deleted() && $is_self)
        return $this->_refer_to($this->get_logout_url()->get_string());
      $profile->show_user_editor($user, lang('account_saved'));
    }


    function _show_user_options() {
      $user = $this->get_current_user();
      $this->_add_profile_breadcrumbs($user);
      $profile = &new ProfilePrinter($this);
      $profile->show_user_options($user);
    }


    /*************************************************************
     * Action controllers for the group profile.
     *************************************************************/
    // Shows group info and lists all users of one group.
    function _show_group_profile() {
      $group = $this->_get_group_from_name_or_die($_GET['groupname']);
      $this->_add_profile_breadcrumbs($group);
      $profile = &new ProfilePrinter($this);
      $profile->show_group_profile($group, (int)$_GET['hs']);
    }


    // Edits the group profile.
    function _show_group_editor() {
      $this->_assert_may('administer');
      $group = $this->_get_group_from_name_or_die($_GET['groupname']);
      $this->_add_profile_breadcrumbs($group);
      $profile = &new ProfilePrinter($this);
      $profile->show_group_editor($group);
    }


    // Saves the group.
    function _group_submit() {
      $this->_assert_may('administer');
      $group = $this->_get_group_from_id_or_die($_POST['group_id']);
      $this->_init_group_from_post_data($group);
      $this->_add_profile_breadcrumbs($group);
      $profile = &new ProfilePrinter($this);

      // Make sure that the data is complete and valid.
      $ret = $group->check_complete();
      if ($ret < 0)
        return $profile->show_group_editor($group, $err[$ret]);

      // Save the group.
      $ret = $this->_get_groupdb()->save_group($group);
      if ($ret < 0)
        return $profile->show_group_editor($group, $err[$ret]);

      // Done.
      $profile->show_group_editor($group, lang('group_saved'));
    }


    /*************************************************************
     * Action controllers for login and password forms.
     *************************************************************/
    function _show_login() {
      global $err;
      $user  = $this->_init_user_from_post_data();
      $login = &new LoginPrinter($this);
      $user->set_status(USER_STATUS_ACTIVE);
      $refer_to = $this->_get_login_refer_url();
      if ($this->login_error == 0)
        $login->show($user, '', $refer_to);
      elseif ($this->login_error == ERR_LOGIN_UNCONFIRMED) {
        $user->set_status(USER_STATUS_UNCONFIRMED);
        $login->show($user, $err[$this->login_error], $refer_to);
      }
      else
        $login->show($user, $err[$this->login_error], $refer_to);
    }


    // Show a form for changing the password.
    function _password_change() {
      $user = $this->_get_current_or_confirming_user();
      if ($user->is_anonymous())
        die('Invalid user');
      $registration = new LoginPrinter($this);
      $registration->show_password_change($user);
    }


    // Submit a new password.
    function _password_submit() {
      global $err;
      $userdb  = $this->get_userdb();
      $user    = $this->_init_user_from_post_data();
      $user    = $userdb->get_user_from_name($user->get_name());
      $current = $this->_get_current_or_confirming_user();
      $printer = new LoginPrinter($this);

      if ($user->is_anonymous())
        die('Invalid user');
      elseif ($user->get_id() != $current->get_id())
        $this->_assert_may('administer');

      // Make sure that the passwords match.
      if ($_POST['password'] !== $_POST['password2']) {
        $error = lang('passwordsdonotmatch');
        return $printer->show_password_change($user, $error);
      }

      // Make sure that the password is valid.
      $ret = $user->set_password($_POST['password']);
      if ($ret < 0)
        return $printer->show_password_change($user, $err[$ret]);

      // Save the password.
      $user->set_status(USER_STATUS_ACTIVE);
      $ret = $userdb->save_user($user);
      if ($ret < 0)
        return $printer->show_password_change($user, $err[$ret]);

      // Done.
      $printer->show_password_changed($user);
    }


    // Show a form for requesting that the password should be reset.
    function _password_forgotten() {
      $user    = $this->_init_user_from_post_data();
      $printer = new LoginPrinter($this);
      $printer->show_password_forgotten($user);
    }


    // Send an email with the URL for resetting the password.
    function _password_mail_submit() {
      global $err;
      $printer = new LoginPrinter($this);
      $user    = $this->_init_user_from_post_data();

      // Make sure that the email address is valid.
      $ret = $user->check_mail();
      if ($ret != 0)
        return $printer->show_password_forgotten($user, $err[$ret]);

      // Find the user with the given mail address.
      $userdb = $this->get_userdb();
      $user   = $userdb->get_user_from_mail($user->get_mail());
      if (!$user) {
        $user = $this->_init_user_from_post_data();
        $msg  = $err[ERR_LOGIN_NO_SUCH_MAIL];
        return $printer->show_password_forgotten($user, $msg);
      }

      // Send the mail.
      if (!$user->is_confirmed()) {
        $url = new URL(cfg('site_url').'?', cfg('urlvars'));
        $url->set_var('action',   'account_reconfirm');
        $url->set_var('username', $user->get_name());
        $this->_refer_to($url->get_string());
      }
      elseif ($user->is_active())
        $this->_send_password_reset_mail($user);
      elseif ($user->is_locked()) {
        $msg = $err[ERR_LOGIN_LOCKED];
        return $printer->show_password_forgotten($user, $msg);
      }
      else
        die('Invalid user status');

      // Done.
      $printer->show_password_mail_sent($user);
    }


    // Called when the user opens the link in the password reset mail.
    function _password_mail_confirm() {
      $user   = $this->_get_current_or_confirming_user();
      $userdb = $this->get_userdb();
      $user   = $userdb->get_user_from_name($user->get_name());
      $this->_assert_confirmation_hash_is_valid($user);

      if (!$user->is_active())
        die('Error: User status is not active.');

      $this->_password_change();
    }


    /*************************************************************
     * Forum editor.
     *************************************************************/
    function _forum_add() {
      $this->_assert_may('administer');
      $this->breadcrumbs()->add_separator();
      $this->breadcrumbs()->add_text(lang('forum_add'));
      $printer = new ForumEditorPrinter($this);
      $printer->show(new Forum);
    }


    function _forum_edit() {
      $this->_assert_may('administer');
      $forum = $this->forumdb->get_forum_from_id((int)$_GET['forum_id']);
      $this->breadcrumbs()->add_separator();
      $this->breadcrumbs()->add_text(lang('forum_editor'));
      $printer = new ForumEditorPrinter($this);
      $printer->show($forum);
    }


    function _forum_submit() {
      $this->_assert_may('administer');
      $printer  = new ForumEditorPrinter($this);
      $forum_id = (int)$_POST['forum_id'];

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

      // Check syntax.
      if ($err = $forum->check())
        return $printer->show($forum, '', $err);

      // Save the data.
      $this->forumdb->save_forum($forum);
      $printer->show($forum, lang('forum_saved'));
    }


    /*************************************************************
     * Other action controllers.
     *************************************************************/
    function _show_moderation_log() {
      $this->breadcrumbs()->add_separator();
      $this->breadcrumbs()->add_text(lang('modlog'));
      $footer = new ModLogPrinter($this);
      $footer->show((int)$_GET['hs']);
    }


    // Prints the footer of the page.
    function _print_breadcrumbs() {
      $show_page_links = (bool)$this->get_current_forum_id();
      $printer         = new BreadCrumbsPrinter($this);
      $printer->show($this->breadcrumbs, $show_page_links);
    }


    // Prints the footer of the page.
    function _print_footer() {
      $footer = new FooterPrinter($this);
      $footer->show($this->get_current_forum_id());
    }


    // Prints an RSS feed.
    function print_rss($_forum_id,
                       $_title,
                       $_descr,
                       $_off,
                       $_n_entries) {
      $this->content = '';
      $rss = &new RSSPrinter($this);
      $rss->set_base_url(cfg('site_url'));
      $rss->set_title($_title);
      $rss->set_description($_descr);
      $rss->set_language(lang('countrycode'));
      $rss->show($_forum_id, $_off, $_n_entries);
      print($this->content);
    }


    /*************************************************************
     * Public.
     *************************************************************/
    function run() {
      /* Plugin hook: on_run_before
       *   Called before the forum is run and the HTML is produced.
       *   Args: None.
       */
      $this->eventbus->emit('on_run_before', $this);
      $this->title   = '';
      $this->content = '';
      $action        = $this->get_current_action();

      // Prevent from accessing non-existent forums.
      if ($this->get_current_forum_id() && !$this->get_current_forum())
        die('No such forum.');

      $this->_init_breadcrumbs();

      // Check whether a plugin registered the given action. This is done
      // first to allow plugins for overriding the default handler.
      if ($this->actions[$action]) {
        call_user_func($this->actions[$action], $this);
        return $this->_print_footer();
      }

      switch ($action) {
      case 'read':
        $this->_posting_read();             // Read a posting.
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

      $this->_print_footer();
    }


    function get_forumdb() {
      return $this->forumdb;
    }


    function get_userdb() {
      if (!$this->userdb)
        $this->userdb = &new UserDB($this->db);
      return $this->userdb;
    }


    function get_eventbus() {
      return $this->eventbus;
    }


    function get_modlogdb() {
      if (!$this->modlogdb)
        $this->modlogdb = &new ModLogDB($this->db);
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
      }
      return $this->current_user;
    }


    function get_current_group() {
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
    function get_current_title() {
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


    function get_current_forum() {
      if ($this->current_forum)
        return $this->current_forum;
      $id = $this->get_current_forum_id();
      if (!$id)
        return NULL;
      $this->current_forum = $this->forumdb->get_forum_from_id($id);
      return $this->current_forum;
    }


    function get_current_posting_id() {
      return $_GET['msg_id'] ? (int)$_GET['msg_id'] : '';
    }


    function register_action($_action, $_func) {
      $this->actions[$_action] = $_func;
    }


    function register_view($_name, $_view, $_caption, $_priority) {
      $this->views[$_name] = $_view;

      if ($this->get_current_action() != 'list'
        && $this->get_current_action() != 'read')
        return;

      if ($this->_get_current_view_name() == $_name)
        return $this->footer_links->add_text($_caption, $_priority);

      $url = new URL('?', cfg('urlvars'), $_caption);
      $url->set_var('forum_id',   $this->get_current_forum_id());
      $url->set_var('changeview', $_name);
      $url->set_var('refer_to',   $_SERVER['REQUEST_URI']);
      $this->footer_links->add_link($url, $_priority);
    }


    function register_renderer($_name, $_decorator_name) {
      $this->renderers[$_name] = $_decorator_name;
    }


    function forum_links() {
      return $this->forum_links;
    }


    function page_links() {
      return $this->page_links;
    }


    function search_links() {
      return $this->search_links;
    }


    function account_links() {
      return $this->account_links;
    }


    function footer_links() {
      return $this->footer_links;
    }


    function get_login_url() {
      $refer_to = $this->_get_login_refer_url();
      $url      = new URL('?', cfg('urlvars'), lang('login'));
      $url->set_var('action',   'login');
      $url->set_var('refer_to', $refer_to);
      return $url;
    }


    function get_logout_url() {
      $url = new URL('?', cfg('urlvars'), lang('logout'));
      $url->set_var('action', 'logout');
      return $url;
    }


    function get_registration_url() {
      //FIXME: should not be here.
      $url = new URL('?', cfg('urlvars'), lang('register'));
      $url->set_var('action', 'account_register');
      return $url;
    }


    function breadcrumbs() {
      return $this->breadcrumbs;
    }


    function get_online_users() {
      return $this->visitordb->get_n_visitors(time() - 60 * 5);
    }


    function get_newest_users($_limit) {
      return $this->get_userdb()->get_newest_users($_limit);
    }


    function print_head($_header = NULL) {
      $oldcontent    = $this->content;
      $this->content = '';

      if ($_header)
        $this->_append_content($_header);
      elseif (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
        header('Pragma: no-cache');
        header('Cache-control: no-cache');
        $header = new HeaderPrinter($this);
        $header->show($this->get_current_title());
      }

      /* Plugin hook: on_header_print_before
       *   Called before the HTML header is sent.
       *   Args: $html: A reference to the HTML header.
       */
      $this->eventbus->emit('on_header_print_before', $this);

      print($this->content);

      /* Plugin hook: on_header_print_before
       *   Called after the HTML header was sent.
       *   Args: none
       */
      $this->eventbus->emit('on_header_print_after', $this);
      $this->content = $oldcontent;
    }


    function show() {
      /* Plugin hook: on_content_print_before
       *   Called before the HTML content is sent.
       *   Args: $html: A reference to the content.
       */
      $this->eventbus->emit('on_content_print_before', $this);

      $body          = $this->content;
      $this->content = '';
      $this->_print_breadcrumbs();
      print($this->content);
      print($body);

      $this->render_time = microtime(TRUE) - $this->start_time;
      if (cfg('show_total_render_time')) {
        $render_time = round($this->get_render_time(), 2);
        print("<p id='rendered'>Site rendered in $render_time seconds.</p>");
      }

      /* Plugin hook: on_content_print_after
       *   Called after the HTML content was sent.
       *   Args: none.
       */
      $this->eventbus->emit('on_content_print_after', $this);
    }


    function get_render_time() {
      return $this->render_time;
    }


    function destroy() {
      unset($this->content);
      $this->db->Close();
      /* Plugin hook: on_destroy
       *   Called from within FreechForum->destroy().
       *   The return value of the callback is ignored.
       *   Args: None.
       */
      $this->eventbus->emit('on_destroy', $this);
    }
  }
?>
