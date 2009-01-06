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
  define('FREECH_VERSION', '0.9.12');

  require_once 'smarty/Smarty.class.php';
  require_once 'adodb/adodb.inc.php';
  include_once 'libuseful/SqlQuery.class.php5';
  include_once 'services/trackable.class.php';

  include_once 'functions/config.inc.php';
  include_once 'functions/language.inc.php';
  include_once 'functions/string.inc.php';
  include_once 'functions/httpquery.inc.php';
  include_once 'functions/files.inc.php';

  include_once 'error.inc.php';

  include_once 'objects/url.class.php';
  include_once 'objects/posting.class.php';
  include_once 'objects/user.class.php';
  include_once 'objects/group.class.php';
  include_once 'objects/posting_decorator.class.php';
  include_once 'objects/unknown_posting.class.php';
  include_once 'objects/thread_state.class.php';
  include_once 'objects/indexbar_item.class.php';
  include_once 'objects/indexbar.class.php';
  include_once 'objects/indexbar_by_time.class.php';
  include_once 'objects/indexbar_by_thread.class.php';
  include_once 'objects/indexbar_group_profile.class.php';
  include_once 'objects/indexbar_read_posting.class.php';
  include_once 'objects/indexbar_user_postings.class.php';
  include_once 'objects/indexbar_search_result.class.php';
  include_once 'objects/indexbar_search_users.class.php';
  include_once 'objects/parser.class.php';
  include_once 'objects/search_query.class.php';

  include_once 'actions/printer_base.class.php';
  include_once 'actions/thread_printer.class.php';
  include_once 'actions/latest_printer.class.php';
  include_once 'actions/rss_printer.class.php';
  include_once 'actions/posting_printer.class.php';
  include_once 'actions/breadcrumbs_printer.class.php';
  include_once 'actions/list_printer.class.php';
  include_once 'actions/login_printer.class.php';
  include_once 'actions/profile_printer.class.php';
  include_once 'actions/search_printer.class.php';
  include_once 'actions/statistics_printer.class.php';
  include_once 'actions/header_printer.class.php';
  include_once 'actions/footer_printer.class.php';

  include_once 'services/groupdb.class.php';
  include_once 'services/sql_query.class.php';
  include_once 'services/forumdb.class.php';
  include_once 'services/userdb.class.php';
  include_once 'services/visitordb.class.php';
  include_once 'services/plugin_registry.class.php';

  class FreechForum {
    var $db;
    var $forum;
    var $registry;
    var $eventbus;
    var $smarty;
    var $thread_state;

    // Prepare the forum, set cookies, etc. To be called before the http header
    // was sent.
    function FreechForum() {
      // Select a language.
      $l = cfg("lang");
      if ($l == 'auto')
        $l = ($_REQUEST[language] ? $_REQUEST[language] : cfg('lang_default'));
      //putenv("LANG=$l");
      setlocale(LC_MESSAGES, $l);

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
          setcookie('permanent_session', session_id(), time() + cfg("login_time"));
      }
      $this->_handle_cookies();

      // Only now start the timer, as cookie handling may scew the result.
      $this->start_time = microtime(TRUE);

      // (Ab)use a Trackable as an eventbus.
      $this->eventbus             = &new Trackable;
      $this->actions              = array();
      $this->renderers            = array();
      $this->extra_indexbar_links = array();

      // Connect to the DB.
      $this->db    = &ADONewConnection(cfg('db_dbn'))
        or die("FreechForum::FreechForum(): Error: Can't connect."
             . " Please check username, password and hostname.");
      $this->forumdb   = &new ForumDB($this->db);
      $this->visitordb = &new VisitorDB($this->db);
      $this->visitordb->count();

      $this->registry = &new PluginRegistry();
      $this->registry->read_plugins('plugins');
      $this->registry->activate_plugins($this); //FIXME: Make activation configurable.

      /* Plugin hook: on_construct
       *   Called from within the FreechForum() constructor before any
       *   other output is produced.
       *   The return value of the callback is ignored.
       *   Args: None.
       */
      $this->eventbus->emit('on_construct', $this);

      // Init Smarty.
      $this->smarty = &new Smarty();
      $this->smarty->template_dir  = 'themes/' . cfg('theme');
      $this->smarty->compile_dir   = 'data/smarty_templates_c';
      $this->smarty->cache_dir     = 'data/smarty_cache';
      $this->smarty->config_dir    = 'data/smarty_configs';
      $this->smarty->compile_check = cfg('check_cache');
      $this->smarty->register_function('lang', 'smarty_lang');

      // Attempt to login, if requested.
      $this->current_user = FALSE;
      $this->login_error  = 0;
      if ($this->get_current_action() == 'login' && $_POST['username'])
        $this->login_error = $this->_try_login();
      if ($this->get_current_action() == 'logout') {
        session_unset();
        $this->_refer_to($this->_get_forum_url()->get_string());
      }

      // Go.
      $this->_run();
      $this->render_time = microtime(TRUE) - $this->start_time;
    }


    /*************************************************************
     * Login and cookie handling.
     *************************************************************/
    function _try_login() {
      $userdb = $this->get_userdb();
      $user   = $userdb->get_user_from_name($_POST['username']);
      if (!$user)
        return ERR_LOGIN_FAILED;
      if ($user->get_status() == USER_STATUS_UNCONFIRMED)
        return ERR_LOGIN_UNCONFIRMED;
      if ($user->get_status() != USER_STATUS_ACTIVE)
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
    function _set_cookie($_name, $_value) {
      if ($_COOKIE[$_name] != $_value) {
        setcookie($_name, $_value);
        $_COOKIE[$_name] = $_value;
      }
    }


    function _handle_cookies() {
      if (get_magic_quotes_gpc()) {
        $_GET    = array_map('stripslashes_deep', $_GET);
        $_POST   = array_map('stripslashes_deep', $_POST);
        $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
      }

      $thread_state        = &new ThreadState($_COOKIE['fold'],
                                              $_COOKIE['c']);
      $user_postings_state = &new ThreadState($_COOKIE['user_postings_fold'],
                                              $_COOKIE['user_postings_c']);
      if ($_GET['c']) {
        $thread_state->swap($_GET['c']);
        $this->_set_cookie('c', $thread_state->get_string());
        $this->_refer_to($_GET['refer_to']);
      }

      if ($_GET['user_postings_c']) {
        $user_postings_state->swap($_GET['user_postings_c']);
        $this->_set_cookie('user_postings_c',
                           $user_postings_state->get_string());
        $this->_refer_to($_GET['refer_to']);
      }

      if ($_GET['changeview'] === 't') {
        $this->_set_cookie('view', 'thread');
        $this->_refer_to($_GET['refer_to']);
      }
      elseif ($_GET['changeview'] === 'c') {
        $this->_set_cookie('view', 'plain');
        $this->_refer_to($_GET['refer_to']);
      }

      if ($_GET['showthread'] === '-1') {
        $this->_set_cookie('thread', 'hide');
        $this->_refer_to($_GET['refer_to']);
      }
      elseif ($_GET['showthread'] === '1') {
        $this->_set_cookie('thread', 'show');
        $this->_refer_to($_GET['refer_to']);
      }

      if ($_GET['fold'] === '1') {
        $this->_set_cookie('fold', '1');
        $this->_set_cookie('c', '');
        $this->_refer_to($_GET['refer_to']);
      } elseif ($_GET['fold'] === '2') {
        $this->_set_cookie('fold', '2');
        $this->_set_cookie('c', '');
        $this->_refer_to($_GET['refer_to']);
      }

      if ($_GET['user_postings_fold'] === '1') {
        $this->_set_cookie('user_postings_fold', '1');
        $this->_set_cookie('user_postings_c', '');
        $this->_refer_to($_GET['refer_to']);
      } elseif ($_GET['user_postings_fold'] === '2') {
        $this->_set_cookie('user_postings_fold', '2');
        $this->_set_cookie('user_postings_c', '');
        $this->_refer_to($_GET['refer_to']);
      }
    }


    /*************************************************************
     * Private utilities.
     *************************************************************/
    function get_userdb() {
      if (!$this->userdb)
        $this->userdb = &new UserDB($this->db);
      return $this->userdb;
    }


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
      $renderer = $this->renderers[$_posting->get_renderer()];
      if ($renderer)
        return new $renderer($_posting, $this);
      return new UnknownPosting($_posting, $this);
    }


    // Returns an URL that points to the current forum.
    function _get_forum_url() {
      $forum_id = $this->get_current_forum_id();
      $forum_url = &new URL('?', cfg('urlvars'));
      $forum_url->set_var('forum_id', $forum_id);
      return $forum_url;
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
      if ($this->get_current_action() == 'account_confirm'
        || $this->get_current_action() == 'password_mail_confirm'
        || $this->get_current_action() == 'reset_password_submit') {
        $userdb = $this->get_userdb();
        $user   = $userdb->get_user_from_name($_GET['username']);
        $this->_assert_confirmation_hash_is_valid($user);
        return $user;
      }

      return $this->get_current_user();
    }


    // Dies if the confirmation hash passed in through GET is not valid.
    function _assert_confirmation_hash_is_valid(&$user) {
      if (!$user)
        die("Invalid user");
      $hash = $user->get_confirmation_hash();
      if ($user->get_confirmation_hash() !== $_GET['hash'])
        die("Invalid confirmation hash");
      if ($user->get_status() == USER_STATUS_BLOCKED)
        die("User is blocked");
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
      $head  = "From: ".cfg("mail_from")."\r\n";
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
      $subject  = lang("reset_mail_subject");
      $body     = lang("reset_mail_body");
      $username = urlencode($user->get_name());
      $hash     = urlencode($user->get_confirmation_hash());
      $url      = cfg('site_url') . "?action=password_mail_confirm"
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
      elseif (!in_array($this->get_current_action(), $do_refer))
        return $this->_get_forum_url()->get_string();
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


    function &_get_registry() {
      return $this->registry;
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


    /*************************************************************
     * Action controllers for the forum overview.
     *************************************************************/
    // Shows the breadcrumbs for the forum in thread or time order.
    function _print_list_breadcrumbs() {
      $forum_id    = $this->get_current_forum_id();
      $breadcrumbs = &new BreadCrumbsPrinter($this);
      if (cfg('disable_posting_counter')) {
        $breadcrumbs->add_item(lang('forum'), $this->_get_forum_url());
        $breadcrumbs->show();
        return;
      }
      $search      = array('forum_id' => $forum_id);
      $n_postings  = $this->forumdb->get_n_postings($search);
      $start       = time() - cfg("new_post_time");
      $n_new       = $this->forumdb->get_n_postings($search, $start);
      $n_online    = $this->visitordb->get_n_visitors(time() - 60 * 5);
      $vars        = array('postings'    => $n_postings,
                           'newpostings' => $n_new,
                           'onlineusers' => $n_online);
      $text        = lang('forum_long', $vars);
      $breadcrumbs->add_item($text, $this->_get_forum_url());
      $breadcrumbs->show();
    }


    // Shows the forum, time order.
    function _list_by_time() {
      $this->_print_list_breadcrumbs('');
      $forum_id = $this->get_current_forum_id();
      $latest   = &new LatestPrinter($this);
      $latest->show($forum_id, (int)$_GET['hs']);
      $this->_print_footer();
    }


    // Shows the forum, thread order.
    function _list_by_thread() {
      $this->_print_list_breadcrumbs('');
      $thread_state = &new ThreadState($_COOKIE['fold'], $_COOKIE['c']);
      $forum_id     = $this->get_current_forum_id();
      $thread       = &new ThreadPrinter($this);
      $thread->show($forum_id, 0, (int)$_GET['hs'], $thread_state);
      $this->_print_footer();
    }


    /*************************************************************
     * Action controllers for reading and editing postings.
     *************************************************************/
    // Prints the breadcrumbs pointing to the given posting.
    function _print_posting_breadcrumbs($_posting) {
      $breadcrumbs = &new BreadCrumbsPrinter($this);
      $breadcrumbs->add_item(lang('forum'), $this->_get_forum_url());
      if (!$_posting)
        $breadcrumbs->add_item(lang('noentrytitle'));
      elseif (!$_posting->is_active())
        $breadcrumbs->add_item(lang('blockedtitle'));
      else
        $breadcrumbs->add_item($_posting->get_subject());
      $breadcrumbs->show();
    }


    // Read a posting.
    function _posting_read() {
      $posting = $this->forumdb->get_posting_from_id($_GET['msg_id']);
      $posting = $this->_decorate_posting($posting);
      $printer = &new PostingPrinter($this);
      $this->_print_posting_breadcrumbs($posting);

      /* Plugin hook: on_message_read_print
       *   Called before the HTML for the posting is produced.
       *   Args: posting: The posting that is about to be shown.
       */
      $this->eventbus->emit('on_message_read_print', $this, $posting);
      $printer->show($posting);
    }


    // Changes the priority of an existing posting.
    function _posting_prioritize() {
      $this->_assert_may('moderate');
      $posting = $this->_get_posting_from_id_or_die((int)$_GET['msg_id']);
      $posting->set_priority((int)$_GET['priority']);
      $this->forumdb->save($this->get_current_forum_id(), -1, $posting);
      $this->_refer_to(urldecode($_GET['refer_to']));
    }


    // Locks an existing posting.
    function _posting_lock() {
      $this->_assert_may('moderate');
      $posting = $this->_get_posting_from_id_or_die((int)$_GET['msg_id']);
      $posting->set_active(FALSE);
      $this->forumdb->save($this->get_current_forum_id(), -1, $posting);
      $this->_refer_to(urldecode($_GET['refer_to']));
    }


    // Unlocks an existing posting.
    function _posting_unlock() {
      $this->_assert_may('moderate');
      $posting = $this->_get_posting_from_id_or_die((int)$_GET['msg_id']);
      $posting->set_active();
      $this->forumdb->save($this->get_current_forum_id(), -1, $posting);
      $this->_refer_to(urldecode($_GET['refer_to']));
    }


    /*************************************************************
     * Action controllers for the user profile.
     *************************************************************/
    function _print_profile_breadcrumbs($_named_item) {
      $breadcrumbs = &new BreadCrumbsPrinter($this);
      $breadcrumbs->add_item(lang("forum"), $this->_get_forum_url());
      $breadcrumbs->add_item($_named_item->get_name());
      $breadcrumbs->show();
    }


    // Lists all postings of one user.
    function _show_user_postings() {
      $user = $this->_get_user_from_name_or_die($_GET['username']);
      $this->_print_profile_breadcrumbs($user);
      $thread_state = &new ThreadState($_COOKIE['user_postings_fold'],
                                       $_COOKIE['user_postings_c']);
      $profile = &new ProfilePrinter($this);
      $profile->show_user_postings($user, $thread_state, (int)$_GET['hs']);
    }


    // Display information of one user.
    function _show_user_profile() {
      $user = $this->_get_user_from_name_or_die($_GET['username']);
      $this->_print_profile_breadcrumbs($user);
      $thread_state = &new ThreadState($_COOKIE['user_postings_fold'],
                                       $_COOKIE['user_postings_c']);
      $profile = &new ProfilePrinter($this);
      $profile->show_user_profile($user, $thread_state, (int)$_GET['hs']);
    }


    // Edit personal data.
    function _show_user_editor() {
      // Check permissions.
      $user = $this->get_current_user();
      if ($user->is_anonymous())
        die('Not logged in');
      if ($_GET['username'] != $user->get_name())
        $this->_assert_may('administer');

      // Accepted.
      $user = $this->_get_user_from_name_or_die($_GET['username']);
      $this->_print_profile_breadcrumbs($user);
      $profile = &new ProfilePrinter($this);
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
      }
      elseif ($is_self) {
        if ($_POST['status'] == USER_STATUS_DELETED
         || $_POST['status'] == USER_STATUS_ACTIVE)
          $user->set_status($_POST['status']);
        else
          die("Invalid status");
      }
      else
        die("Permission denied");

      $this->_print_profile_breadcrumbs($user);

      // If the user status is now DELETED, remove any related attributes.
      $this->_init_user_from_post_data($user);
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
      $profile->show_user_editor($user, lang("account_saved"));
    }


    function _show_user_options() {
      $user = $this->get_current_user();
      $this->_print_profile_breadcrumbs($user);
      $profile = &new ProfilePrinter($this);
      $profile->show_user_options($user);
    }


    /*************************************************************
     * Action controllers for the group profile.
     *************************************************************/
    // Shows group info and lists all users of one group.
    function _show_group_profile() {
      $group = $this->_get_group_from_name_or_die($_GET['groupname']);
      $this->_print_profile_breadcrumbs($group);
      $profile = &new ProfilePrinter($this);
      $profile->show_group_profile($group, (int)$_GET['hs']);
    }


    // Edits the group profile.
    function _show_group_editor() {
      $this->_assert_may('administer');
      $group = $this->_get_group_from_name_or_die($_GET['groupname']);
      $this->_print_profile_breadcrumbs($group);
      $profile = &new ProfilePrinter($this);
      $profile->show_group_editor($group);
    }


    // Saves the group.
    function _group_submit() {
      $this->_assert_may('administer');
      $group = $this->_get_group_from_id_or_die($_POST['group_id']);
      $this->_init_group_from_post_data($group);
      $this->_print_profile_breadcrumbs($group);
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
     * Action controllers for the search.
     *************************************************************/
    function _show_search_form() {
      if (cfg('disable_search'))
        die("Search is currently disabled.");
      $printer = &new SearchPrinter($this);
      $printer->show((int)$_GET['forum_id'], $_GET['q']);
    }


    function _show_search_result() {
      if (cfg('disable_search'))
        die("Search is currently disabled.");
      if (!$_GET['q'] || trim($_GET['q']) == '')
        return $this->_show_search_form();

      // Search for postings or users.
      $printer  = &new SearchPrinter($this);
      $forum_id = (int)$_GET['forum_id'];
      if ($_GET['user_search'])
        $printer->show_users($_GET['q'], $_GET['hs']);
      else
        $printer->show_postings($forum_id, $_GET['q'], $_GET['hs']);
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
      if (!$user || $user->is_anonymous())
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
      $printer = new LoginPrinter($this);

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
      if ($user->get_status() == USER_STATUS_UNCONFIRMED) {
        $url = new URL(cfg('site_url').'?', cfg('urlvars'));
        $url->set_var('action',   'account_reconfirm');
        $url->set_var('username', $user->get_name());
        $this->_refer_to($url->get_string());
      }
      elseif ($user->get_status() == USER_STATUS_ACTIVE)
        $this->_send_password_reset_mail($user);
      elseif ($user->get_status() == USER_STATUS_BLOCKED) {
        $msg = $err[ERR_LOGIN_LOCKED];
        return $printer->show_password_forgotten($user, $msg);
      }
      else
        die("Invalid user status");

      // Done.
      $printer->show_password_mail_sent($user);
    }


    // Called when the user opens the link in the password reset mail.
    function _password_mail_confirm() {
      $user   = $this->_get_current_or_confirming_user();
      $userdb = $this->get_userdb();
      $user   = $userdb->get_user_from_name($user->get_name());
      $this->_assert_confirmation_hash_is_valid($user);

      if ($user->get_status() != USER_STATUS_ACTIVE)
        die("Error: User status is not active.");

      $this->_password_change();
    }


    /*************************************************************
     * Other action controllers.
     *************************************************************/
    // Prints the footer of the page.
    function _print_footer() {
      $footer = &new FooterPrinter($this);
      $footer->show($this->get_current_forum_id());
    }


    function _show_top_posters() {
      $printer = new ListPrinter($this);
      $printer->show_top_users();
    }


    function _show_statistics() {
      $printer = new StatisticsPrinter($this);
      $printer->show();
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
     * Main entry point. Called by the constructor.
     *************************************************************/
    function _run() {
      /* Plugin hook: on_run_before
       *   Called before the forum is run and the HTML is produced.
       *   Args: None.
       */
      $this->eventbus->emit('on_run_before', $this);
      $this->title   = "";
      $this->content = "";
      $action        = $this->get_current_action();

      // Check whether a plugin registered the given action. This is done
      // first to allow plugins for overriding the default handler.
      if ($this->actions[$action])
        return call_user_func($this->actions[$action], $this);

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

      case 'posting_unlock':
        $this->_posting_unlock();
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

      case 'search':
        if ($_GET['q'])
          $this->_show_search_result();     // Run a search.
        else
          $this->_show_search_form();       // Show the search form.
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

      case 'top_posters':
        $this->_show_top_posters();         // List of top posters.
        break;

      case 'statistics':
        $this->_show_statistics();          // Activity charts.
        break;

      case 'list':
      case '':
        if ($_COOKIE['view'] === 'plain')
          $this->_list_by_time();           // Show the forum, time order.
        else
          $this->_list_by_thread();         // Show the forum, thread order.
        break;

      default:
        break;
      }
    }


    /*************************************************************
     * Public.
     *************************************************************/
    function get_forumdb() {
      return $this->forumdb;
    }


    function get_eventbus() {
      return $this->eventbus;
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
      return 'list';
    }


    // Returns a title for the current site.
    function get_current_title() {
      if (!$this->title)
        return cfg('site_title');
      return $this->title.' - '.cfg('site_title');
    }


    function get_current_forum_id() {
      return $_GET['forum_id'] ? (int)$_GET['forum_id'] : 1;
    }


    function get_current_posting_id() {
      return $_GET['msg_id'] ? (int)$_GET['msg_id'] : '';
    }


    function register_action($_action, $_func) {
      $this->actions[$_action] = $_func;
    }


    function register_renderer($_name, $_decorator_name) {
      $this->renderers[$_name] = $_decorator_name;
    }


    function add_extra_indexbar_link($_url) {
      array_push($this->extra_indexbar_links, $_url);
    }


    function get_extra_indexbar_links() {
      return $this->extra_indexbar_links;
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


    function get_statistics_url() {
      $url = new URL('?', cfg('urlvars'), lang('statistics'));
      $url->set_var('action', 'statistics');
      return $url;
    }


    function get_registration_url() {
      $url = new URL('?', cfg('urlvars'), lang('register'));
      $url->set_var('action', 'account_register');
      return $url;
    }


    function get_newest_users($_limit) {
      return $this->get_userdb()->get_newest_users($_limit);
    }


    function print_head($_header = NULL) {
      $oldcontent    = $this->content;
      $this->content = "";

      if ($_header)
        $this->_append_content($_header);
      elseif (!headers_sent()) {
        header("Content-Type: text/html; charset=utf-8");
        $header = &new HeaderPrinter($this);
        $header->show($this->get_current_title());
      }

      /* Plugin hook: on_header_print_before
       *   Called before the HTML header is sent.
       *   Args: $html: A reference to the HTML header.
       */
      $this->eventbus->emit("on_header_print_before", &$this);

      print($this->content);

      /* Plugin hook: on_header_print_before
       *   Called after the HTML header was sent.
       *   Args: none
       */
      $this->eventbus->emit("on_header_print_after", &$this);
      $this->content = $oldcontent;
    }


    function show() {
      /* Plugin hook: on_content_print_before
       *   Called before the HTML content is sent.
       *   Args: $html: A reference to the content.
       */
      $this->eventbus->emit("on_content_print_before", &$this);

      print($this->content);

      /* Plugin hook: on_content_print_after
       *   Called after the HTML content was sent.
       *   Args: none.
       */
      $this->eventbus->emit("on_content_print_after", &$this);
    }


    function get_render_time() {
      return $this->render_time;
    }


    function get_total_time() {
      return microtime(TRUE) - $this->start_time;
    }


    function destroy() {
      unset($this->content);
      $this->db->Close();
      /* Plugin hook: on_destroy
       *   Called from within FreechForum->destroy().
       *   The return value of the callback is ignored.
       *   Args: None.
       */
      $this->eventbus->emit("on_destroy", &$this);
    }
  }
?>
