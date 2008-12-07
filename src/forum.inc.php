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
  require_once 'smarty/Smarty.class.php';
  require_once 'adodb/adodb.inc.php';
  include_once 'libuseful/SqlQuery.class.php5';

  include_once 'functions/config.inc.php';
  include_once 'functions/language.inc.php';
  include_once 'functions/string.inc.php';
  include_once 'functions/httpquery.inc.php';
  include_once 'functions/files.inc.php';

  include_once 'error.inc.php';

  include_once 'objects/url.class.php';
  include_once 'objects/message.class.php';
  include_once 'objects/user.class.php';
  include_once 'objects/group.class.php';
  include_once 'objects/thread_state.class.php';
  include_once 'objects/indexbar_item.class.php';
  include_once 'objects/indexbar.class.php';
  include_once 'objects/indexbar_by_time.class.php';
  include_once 'objects/indexbar_by_thread.class.php';
  include_once 'objects/indexbar_read_message.class.php';
  include_once 'objects/indexbar_user_postings.class.php';
  include_once 'objects/indexbar_search_result.class.php';

  include_once 'actions/printer_base.class.php';
  include_once 'actions/thread_printer.class.php';
  include_once 'actions/latest_printer.class.php';
  include_once 'actions/rss_printer.class.php';
  include_once 'actions/message_printer.class.php';
  include_once 'actions/breadcrumbs_printer.class.php';
  include_once 'actions/list_printer.class.php';
  include_once 'actions/login_printer.class.php';
  include_once 'actions/profile_printer.class.php';
  include_once 'actions/search_printer.class.php';
  include_once 'actions/header_printer.class.php';
  include_once 'actions/footer_printer.class.php';
  include_once 'actions/registration_printer.class.php';

  include_once 'services/groupdb.class.php';
  include_once 'services/search_query.class.php';
  include_once 'services/sql_query.class.php';
  include_once 'services/forumdb.class.php';
  include_once 'services/userdb.class.php';
  include_once 'services/visitordb.class.php';
  include_once 'services/trackable.class.php';
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
        $l = ($_REQUEST[language] ? $_REQUEST[language] : cfg("lang_default"));
      //putenv("LANG=$l");
      setlocale(LC_MESSAGES, $l);

      if (cfg_is("salt", ""))
        die("Error: Please define the salt variable in config.inc.php!");

      // Setup gettext.
      if (!function_exists("gettext"))
        die("This webserver does not have gettext installed.<br/>"
          . "Please contact your webspace provider.");
      $domain = 'freech';
      bindtextdomain($domain, "./language");
      textdomain($domain);
      bind_textdomain_codeset($domain, 'UTF-8');

      // (Ab)use a Trackable as an eventbus.
      $this->eventbus = &new Trackable;

      // Connect to the DB.
      $this->db    = &ADONewConnection(cfg("db_dbn"))
        or die("FreechForum::FreechForum(): Error: Can't connect."
             . " Please check username, password and hostname.");
      $this->forum     = &new ForumDB($this->db);
      $this->visitordb = &new VisitorDB($this->db);
      $this->visitordb->count();

      $this->registry = &new PluginRegistry();
      $this->registry->read_plugins("plugins");
      $this->registry->activate_plugins($this); //FIXME: Make activation configurable.

      /* Plugin hook: on_construct
       *   Called from within the FreechForum() constructor before any
       *   other output is produced.
       *   The return value of the callback is ignored.
       *   Args: None.
       */
      $this->eventbus->emit("on_construct", &$this);

      // Init Smarty.
      $this->smarty = &new Smarty();
      $this->smarty->template_dir  = "themes/" . cfg("theme");
      $this->smarty->compile_dir   = "data/smarty_templates_c";
      $this->smarty->cache_dir     = "data/smarty_cache";
      $this->smarty->config_dir    = "data/smarty_configs";
      $this->smarty->compile_check = cfg("check_cache");
      $this->smarty->register_function('lang', 'smarty_lang');

      session_set_cookie_params(time() + cfg("login_time"));
      if ($_COOKIE['permanent_session']) {
        session_id($_COOKIE['permanent_session']);
        session_start();
      }
      else {
        session_start();
        if ($_POST['permanent'] === "ON")
          setcookie('permanent_session', session_id(), time() + cfg("login_time"));
      }
      $this->_handle_cookies();

      $this->current_user = FALSE;
      $this->login_error  = 0;
      if ($this->get_current_action() == 'login' && $_POST['username'])
        $this->login_error = $this->_try_login();
      if ($this->get_current_action() == 'logout') {
        session_unset();
        unset($_GET['action']);
        unset($_POST['action']);
      }
    }


    /*************************************************************
     * Login and cookie handling.
     *************************************************************/
    function _try_login() {
      $userdb = $this->_get_userdb();
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
      unset($_GET['action']);
      unset($_POST['action']);
      return 0;
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
      }

      if ($_GET['user_postings_c']) {
        $user_postings_state->swap($_GET['user_postings_c']);
        $this->_set_cookie('user_postings_c',
                           $user_postings_state->get_string());
      }

      if ($_GET['changeview'] === 't')
        $this->_set_cookie('view', 'thread');
      elseif ($_GET['changeview'] === 'c')
        $this->_set_cookie('view', 'plain');

      if ($_GET['showthread'] === '-1')
        $this->_set_cookie('thread', 'hide');
      elseif ($_GET['showthread'] === '1')
        $this->_set_cookie('thread', 'show');

      if ($_GET['fold'] === '1') {
        $this->_set_cookie('fold', '1');
        $this->_set_cookie('c', '');
      } elseif ($_GET['fold'] === '2') {
        $this->_set_cookie('fold', '2');
        $this->_set_cookie('c', '');
      }

      if ($_GET['user_postings_fold'] === '1') {
        $this->_set_cookie('user_postings_fold', '1');
        $this->_set_cookie('user_postings_c', '');
      } elseif ($_GET['user_postings_fold'] === '2') {
        $this->_set_cookie('user_postings_fold', '2');
        $this->_set_cookie('user_postings_c', '');
      }
    }


    /*************************************************************
     * Private utilities.
     *************************************************************/
    function _get_userdb() {
      if (!$this->userdb)
        $this->userdb = &new UserDB($this->db);
      return $this->userdb;
    }


    function _get_user_from_id($_id) {
      return $this->_get_userdb()->get_user_from_id((int)$_id);
    }


    function _get_user_from_id_or_die($_id) {
      $user = $this->_get_user_from_id($_id);
      if (!$user)
        die('No such user');
      return $user;
    }


    function _get_user_from_name($_name) {
      return $this->_get_userdb()->get_user_from_name($_name);
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


    function _init_user_from_post_data($_user = NULL) {
      if (!$_user)
        $_user = new User;
      $_user->set_username($_POST['username']);
      $_user->set_password($_POST['password']);
      $_user->set_firstname($_POST['firstname']);
      $_user->set_lastname($_POST['lastname']);
      $_user->set_mail($_POST['mail'], $_POST['publicmail'] == 'on');
      $_user->set_homepage($_POST['homepage']);
      $_user->set_im($_POST['im']);
      $_user->set_signature($_POST['signature']);
      return $_user;
    }


    function _init_message_from_post_data($_message = NULL) {
      if (!$_message)
        $_message = new Message;
      $_message->set_id($_POST['msg_id']);
      $_message->set_username($_POST['username']);
      $_message->set_subject($_POST['subject']);
      $_message->set_body($_POST['body']);
      return $_message;
    }


    // Returns an new Message object that is initialized for the current 
    // user/group.
    function _get_new_message() {
      $message = new Message;
      $message->set_from_group($this->get_current_group());
      $message->set_from_user($this->get_current_user());
      return $message;
    }


    // Returns an URL that points to the current forum.
    function _get_forumurl() {
      $forum_id = $this->get_current_forum_id();
      $forumurl = &new URL('?', cfg('urlvars'));
      $forumurl->set_var('action',   'list');
      $forumurl->set_var('forum_id', $forum_id);
      return $forumurl;
    }


    // Returns TRUE if the username is available, FALSE otherwise.
    function _username_available(&$_username) {
      $userdb = $this->_get_userdb();
      return count($userdb->get_similar_users_from_name($_username)) == 0;
    }


    // Wrapper around get_current_user() that also works if a matching
    // user/hash combination was passed in through the GET request.
    function _get_current_or_confirming_user() {
      if ($this->get_current_action() == 'account_confirm'
        || $this->get_current_action() == 'confirm_password_mail'
        || $this->get_current_action() == 'reset_password_submit') {
        $userdb = $this->_get_userdb();
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


    // Sends an email to the given user.
    function _send_account_mail(&$user, $subject, $body, $vars) {
      $head  = "From: ".cfg("mail_from")."\r\n";
      $vars['login']     = $user->get_username();
      $vars['firstname'] = $user->get_firstname();
      $vars['lastname']  = $user->get_lastname();
      foreach ($vars as $key => $value) {
        $subject = str_replace('['.strtoupper($key).']', $value, $subject);
        $body    = str_replace('['.strtoupper($key).']', $value, $body);
      }
      mail($user->get_mail(), $subject, $body, $head);
    }


    // Convenience wrapper around _send_account_mail().
    function _send_confirmation_mail(&$user) {
      $subject  = lang("registration_mail_subject");
      $body     = lang("registration_mail_body");
      $username = urlencode($user->get_username());
      $hash     = urlencode($user->get_confirmation_hash());
      $url      = cfg('site_url') . "?action=account_confirm"
                . "&username=$username&hash=$hash";
      $this->_send_account_mail($user, $subject, $body, array('url' => $url));
      $registration = &new RegistrationPrinter($this);
      $registration->show_mail_sent($user);
    }


    // Convenience wrapper around _send_confirmation_mail().
    function _resend_confirmation_mail() {
      $userdb = $this->_get_userdb();
      $user   = $userdb->get_user_from_name($_GET['username']);
      if ($user->get_status() != USER_STATUS_UNCONFIRMED)
        die("User is already confirmed.");
      $this->_send_confirmation_mail($user);
    }


    // Convenience wrapper around _send_account_mail().
    function _send_password_reset_mail($user) {
      $subject  = lang("reset_mail_subject");
      $body     = lang("reset_mail_body");
      $username = urlencode($user->get_username());
      $hash     = urlencode($user->get_confirmation_hash());
      $url      = cfg('site_url') . "?action=confirm_password_mail"
                . "&username=$username&hash=$hash";
      $this->_send_account_mail($user, $subject, $body, array('url' => $url));
    }


    function &_get_registry() {
      return $this->registry;
    }


    function &_get_smarty() {
      return $this->smarty;
    }


    function &_get_forumdb() {
      return $this->forum;
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
      $search      = array('forum_id' => $forum_id);
      $n_messages  = $this->forum->get_n_messages($search);
      $start       = time() - cfg("new_post_time");
      $n_new       = $this->forum->get_n_messages($search, $start);
      $n_online    = $this->visitordb->get_n_visitors(time() - 60 * 5);
      $text        = lang("forum_long");
      $text        = preg_replace("/\[MESSAGES\]/",    $n_messages, $text);
      $text        = preg_replace("/\[NEWMESSAGES\]/", $n_new,      $text);
      $text        = preg_replace("/\[ONLINEUSERS\]/", $n_online,   $text);
      $breadcrumbs->add_item($text, $this->_get_forumurl());
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
     * Action controllers for reading and editing messages.
     *************************************************************/
    // Prints the breadcrumbs pointing to the given message.
    function _print_message_breadcrumbs($_message) {
      $breadcrumbs = &new BreadCrumbsPrinter($this);
      $breadcrumbs->add_item(lang("forum"), $this->_get_forumurl());
      if (!$_message)
        $breadcrumbs->add_item(lang("noentrytitle"));
      elseif (!$_message->is_active())
        $breadcrumbs->add_item(lang("blockedtitle"));
      else
        $breadcrumbs->add_item($_message->get_subject());
      $breadcrumbs->show();
    }


    // Read a message.
    function _message_read() {
      $msg        = $this->forum->get_message_from_id($_GET['msg_id']);
      $msgprinter = &new MessagePrinter($this);
      $this->_print_message_breadcrumbs($msg);
      $msgprinter->show($msg);
    }


    // Write a new message.
    function _message_compose() {
      $parent_id  = (int)$_POST['parent_id'];
      $message    = &new Message;
      $msgprinter = &new MessagePrinter($this);
      $msgprinter->show_compose($message, '', $parent_id, FALSE);
    }


    // Write a response to a message.
    function _message_answer() {
      $parent_id  = (int)$_GET['parent_id'];
      $message    = $this->forum->get_message_from_id($parent_id);
      $msgprinter = &new MessagePrinter($this);
      $msgprinter->show_compose_reply($message, '');
    }


    // Edit a saved message.
    function _message_edit_saved() {
      $user       = $this->get_current_user();
      $message    = $this->forum->get_message_from_id($_GET['msg_id']);
      $msgprinter = &new MessagePrinter($this);

      if (!cfg("postings_editable"))
        die("Postings may not be changed as per configuration.");
      if ($message->get_user_is_anonymous())
        die("Anonymous postings may not be changed.");
      elseif ($user->is_anonymous())
        die("You are not logged in.");
      elseif ($user->get_id() != $message->get_user_id())
        die("You are not the owner.");

      $msgprinter->show_compose($message, '', 0, FALSE);
    }


    // Edit an unsaved message.
    function _message_edit_unsaved() {
      $parent_id  = (int)$_POST['parent_id'];
      $may_quote  = (int)$_POST['may_quote'];
      $message    = $this->_init_message_from_post_data();
      $msgprinter = &new MessagePrinter($this);
      $msgprinter->show_compose($message, '', $parent_id, $may_quote);
    }


    // Insert a quote from the parent message.
    function _message_quote() {
      $parent_id  = (int)$_POST['parent_id'];
      $quoted_msg = $this->forum->get_message_from_id($parent_id);
      $message    = $this->_init_message_from_post_data();
      $msgprinter = &new MessagePrinter($this);
      $msgprinter->show_compose_quoted($message, $quoted_msg, '');
    }


    // Print a preview of a message.
    function _message_preview() {
      global $err;
      $parent_id  = (int)$_POST['parent_id'];
      $may_quote  = (int)$_POST['may_quote'];
      $msgprinter = &new MessagePrinter($this);
      $user       = $this->get_current_user();
      $message    = $this->_get_new_message();
      $this->_init_message_from_post_data($message);

      // Make sure that the username is not in use.
      if ($user->is_anonymous()
        && !$this->_username_available($message->get_username()))
         return $msgprinter->show_compose($message,
                                          lang("usernamenotavailable"),
                                          $parent_id,
                                          $may_quote);

      // Check the message for completeness.
      $ret = $message->check_complete();
      if ($ret < 0)
        return $msgprinter->show_compose($message,
                                         $err[$ret],
                                         $parent_id,
                                         $may_quote);

      // Success.
      $msgprinter->show_preview($message, $parent_id, $may_quote);
    }


    // Saves the posted message.
    function _message_send() {
      global $err;
      $parent_id  = (int)$_POST['parent_id'];
      $may_quote  = (int)$_POST['may_quote'];
      $msgprinter = &new MessagePrinter($this);
      $user       = $this->get_current_user();
      $forum_id   = $this->get_current_forum_id();

      // Check whether editing is allowed per configuration.
      if ($_POST['msg_id'] && !cfg("postings_editable"))
        die("Postings may not be changed as per configuration.");

      // Fetch the message from the database (when editing an existing one) or
      // create a new one from the POST data.
      if ($_POST['msg_id']) {
        $message = $this->forum->get_message_from_id($_POST['msg_id']);
        $message->set_subject($_POST['subject']);
        $message->set_body($_POST['body']);
      }
      else {
        $message = $this->_get_new_message();
        $this->_init_message_from_post_data($message);
      }

      // Make sure that the user is not trying to spoof a name.
      if (!$user->is_anonymous()
        && $user->get_username() !== $message->get_username())
        die("Username does not match currently logged in user");

      // Make sure that the username is not in use.
      if ($user->is_anonymous()
        && !$this->_username_available($message->get_username()))
         return $msgprinter->show_compose($message,
                                          lang("usernamenotavailable"),
                                          $parent_id,
                                          $may_quote);

      // If the message a new one (not an edited one), check for duplicates.
      if ($message->get_id() <= 0) {
        $duplicate_id = $this->forum->get_duplicate_id_from_message($message);
        if ($duplicate_id)
          return $msgprinter->show_created($duplicate_id,
                                           $parent_id,
                                           lang("messageduplicate"));
      }

      // Check the message for completeness.
      $ret = $message->check_complete();
      if ($ret < 0)
        return $msgprinter->show_compose($message,
                                         $err[$ret],
                                         $parent_id,
                                         $may_quote);

      // Save the message.
      if ($message->get_id())
        $this->forum->save($forum_id, $parent_id, $message);
      else
        $this->forum->insert($forum_id, $parent_id, $message);
      if (!$message->get_id())
        return $msgprinter->show_compose($message,
                                         lang("message_save_failed"),
                                         $parent_id,
                                         $may_quote);

      // Success!
      $msgprinter->show_created($message->get_id(), $parent_id);
    }


    /*************************************************************
     * Action controllers for the user profile.
     *************************************************************/
    function _print_profile_breadcrumbs($_user) {
      $breadcrumbs = &new BreadCrumbsPrinter($this);
      $breadcrumbs->add_item(lang("forum"), $this->_get_forumurl());
      $breadcrumbs->add_item($_user->get_username());
      $breadcrumbs->show();
    }


    // Lists all postings of one user.
    function _show_user_postings() {
      if ($_GET['username'])
        $user = $this->_get_user_from_name_or_die($_GET['username']);
      else
        $user = $this->get_current_user();
      $this->_print_profile_breadcrumbs($user);
      $thread_state = &new ThreadState($_COOKIE['user_postings_fold'],
                                       $_COOKIE['user_postings_c']);
      $profile = &new ProfilePrinter($this);
      $profile->show_user_postings($user, $thread_state, (int)$_GET['hs']);
    }


    // Display information of one user.
    function _show_profile() {
      $user = $this->_get_user_from_name_or_die($_GET['username']);
      $this->_print_profile_breadcrumbs($user);
      $profile = &new ProfilePrinter($this);
      $profile->show_user_profile($user);
    }


    // Edit personal data.
    function _show_user_data() {
      $user     = $this->get_current_user();
      $username = $_GET['username'] ? $_GET['username'] : $user->get_username();

      // Check permissions.
      if ($user->is_anonymous())
        die('Not logged in');
      if ($username != $user->get_username()) {
        if (!$this->get_current_group()->may('administer'))
          die("Permission denied");
        $user = $this->_get_user_from_name_or_die($_GET['username']);
      }

      // Accepted.
      $this->_print_profile_breadcrumbs($user);
      $profile = &new ProfilePrinter($this);
      $profile->show_user_data($user);
    }


    // Submit personal data.
    function _submit_user_data() {
      global $err;
      $profile = &new ProfilePrinter($this);
      $user    = $this->get_current_user();

      // Check permissions.
      if ($user->is_anonymous())
        die('Not logged in');
      if ($_POST['user_id'] != $user->get_id()) {
        if (!$this->get_current_group()->may('administer'))
          die("Permission denied");
        $user = $this->_get_user_from_id_or_die($_POST['user_id']);
        $user->set_username($_POST['username']);
        $user->set_group_id($_POST['group_id']);
        $user->set_status($_POST['status']);
      }

      $this->_print_profile_breadcrumbs($user);

      // Make sure that the data is complete and valid.
      $this->_init_user_from_post_data($user);
      $ret = $user->check_complete();
      if ($ret < 0)
        return $profile->show_user_data($user, $err[$ret]);

      // Make sure that the passwords match.
      if ($_POST['password'] !== $_POST['password2'])
        return $profile->show_user_data($user,
                                        $err[ERR_REGISTER_PASSWORDS_DIFFER]);
      if ($_POST['password'] != '')
        $user->set_password($_POST['password']);

      // Save the user.
      $ret = $this->_get_userdb()->save_user($user);
      if ($ret < 0)
        return $profile->show_user_data($user, $err[$ret]);

      // Done.
      $profile->show_user_data($user, lang("account_saved"));
    }


    function _show_user_options() {
      $user = $this->get_current_user();
      $this->_print_profile_breadcrumbs($user);
      $profile = &new ProfilePrinter($this);
      $profile->show_user_options($user);
    }


    /*************************************************************
     * Action controllers for the page header and page footer.
     *************************************************************/
    // Prints the footer of the page.
    function _print_footer() {
      $footer = &new FooterPrinter($this);
      $footer->show($this->get_current_forum_id());
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

      // Search for messages or users.
      $printer  = &new SearchPrinter($this);
      $forum_id = (int)$_GET['forum_id'];
      if ($_GET['user_search'])
        $printer->show_users($_GET['q'], $_GET['hs']);
      else
        $printer->show_messages($forum_id, $_GET['q'], $_GET['hs']);
    }


    /*************************************************************
     * Action controllers for login and user registration.
     *************************************************************/
    function _show_login() {
      global $err;
      $user  = $this->_init_user_from_post_data();
      $login = &new LoginPrinter($this);
      $user->set_status(USER_STATUS_ACTIVE);
      if ($this->login_error == 0)
        $login->show($user);
      elseif ($this->login_error == ERR_LOGIN_UNCONFIRMED) {
        $user->set_status(USER_STATUS_UNCONFIRMED);
        $login->show($user, $err[$this->login_error]);
      }
      else
        $login->show($user, $err[$this->login_error]);
    }


    function _register() {
      $registration = &new RegistrationPrinter($this);
      $registration->show(new User);
    }


    function _account_create() {
      global $err;
      $registration = &new RegistrationPrinter($this);
      $user         = $this->_init_user_from_post_data();

      // Check the data for completeness.
      $ret = $user->check_complete();
      if ($ret < 0)
        return $registration->show($user, $err[$ret]);
      if ($_POST['password'] !== $_POST['password2'])
        return $registration->show($user, $err[ERR_REGISTER_PASSWORDS_DIFFER]);

      // Make sure that the name is available.
      if (!$this->_username_available($user->get_username()))
        return $registration->show($user, $err[ERR_REGISTER_USER_EXISTS]);

      // Create the user.
      $user->set_group_id(cfg('default_group_id'));
      $userdb = $this->_get_userdb();
      $ret    = $userdb->save_user($user);
      if ($ret < 0)
        return $registration->show($user, $err[$ret]);

      // Done.
      $this->_send_confirmation_mail($user);
    }


    // Show a form for changing the password.
    function _change_password() {
      $user = $this->_get_current_or_confirming_user();
      if (!$user || $user->is_anonymous())
        die("Invalid user");
      $registration = &new RegistrationPrinter($this);
      $registration->show_change_password($user);
    }


    // Submit a new password.
    function _change_password_submit() {
      global $err;
      $userdb   = $this->_get_userdb();
      $user     = $this->_init_user_from_post_data();
      $user     = $userdb->get_user_from_name($user->get_username());
      $register = &new RegistrationPrinter($this);

      // Make sure that the passwords match.
      if ($_POST['password'] !== $_POST['password2']) {
        $error = lang("passwordsdonotmatch");
        return $register->show_change_password($user, $error);
      }

      // Make sure that the password is valid.
      $ret = $user->set_password($_POST['password']);
      if ($ret < 0)
        return $register->show_change_password($user, $err[$ret]);

      // Save the password.
      $user->set_status(USER_STATUS_ACTIVE);
      $ret = $userdb->save_user($user);
      if ($ret < 0)
        return $register->show_change_password($user, $err[$ret]);

      // Done.
      $register->show_done($user);
    }


    // Show a form for requesting that the password should be reset.
    function _forgot_password() {
      $user         = $this->_init_user_from_post_data();
      $registration = &new RegistrationPrinter($this);
      $registration->show_forgot_password($user);
    }


    // Send an email with the URL for resetting the password.
    function _password_mail_submit() {
      global $err;
      $registration = &new RegistrationPrinter($this);
      $user         = $this->_init_user_from_post_data();

      // Make sure that the email address is valid.
      $ret = $user->check_mail();
      if ($ret != 0)
        return $registration->show_forgot_password($user, $err[$ret]);

      // Find the user with the given mail address.
      $userdb = $this->_get_userdb();
      $user   = $userdb->get_user_from_mail($user->get_mail());
      if (!$user) {
        $msg = $err[ERR_LOGIN_NO_SUCH_MAIL];
        return $registration->show_forgot_password($user, $msg);
      }

      // Send the mail.
      if ($user->get_status() == USER_STATUS_UNCONFIRMED)
        $this->_resend_confirmation_mail($user);
      elseif ($user->get_status() == USER_STATUS_ACTIVE)
        $this->_send_password_reset_mail($user);
      elseif ($user->get_status() == USER_STATUS_BLOCKED) {
        $msg = $err[ERR_LOGIN_LOCKED];
        return $registration->show_forgot_password($user, $msg);
      }
      else
        die("Invalid user status");

      // Done.
      $registration = &new RegistrationPrinter($this);
      $registration->show_forgot_password_mail_sent($user);
    }


    // Called when the user opens the link in the password reset mail.
    function _confirm_password_mail() {
      $user   = $this->_get_current_or_confirming_user();
      $userdb = $this->_get_userdb();
      $user   = $userdb->get_user_from_name($user->get_username());
      $this->_assert_confirmation_hash_is_valid($user);

      if ($user->get_status() != USER_STATUS_ACTIVE)
        die("Error: User status is not active.");

      $this->_change_password();
    }


    // Called when the user opens the link in the initial account confirmation
    // mail.
    function _account_confirm() {
      $userdb = $this->_get_userdb();
      $user   = $userdb->get_user_from_name($_GET['username']);
      $this->_assert_confirmation_hash_is_valid($user);

      // See if the user still needs to set a password.
      if (!$user->get_password_hash())
        return $this->_change_password();

      // Make the user active.
      $user->set_status(USER_STATUS_ACTIVE);
      $ret = $userdb->save_user($user);
      if ($ret < 0)
        die("User activation failed");

      // Done.
      $registration = &new RegistrationPrinter($this);
      $registration->show_done($user);
    }


    /*************************************************************
     * Other action controllers.
     *************************************************************/
    function _show_top_posters() {
      $printer = new ListPrinter($this);
      $printer->show_top_posters();
    }


    // Prints an RSS feed.
    function print_rss($_forum_id,
                       $_title,
                       $_descr,
                       $_off,
                       $_n_entries) {
      $this->content = "";
      $rss = &new RSSPrinter($this);
      $rss->set_base_url(cfg("site_url"));
      $rss->set_title($_title);
      $rss->set_description($_descr);
      $rss->set_language(lang("countrycode"));
      $rss->show($_forum_id, $_off, $_n_entries);
      print($this->content);
    }


    /*************************************************************
     * Public.
     *************************************************************/
    function &get_eventbus() {
      return $this->eventbus;
    }


    function get_current_user() {
      if ($this->current_user)
        return $this->current_user;
      if (session_id() !== '' && $_SESSION['user_id'])
        $this->current_user = $this->_get_user_from_id($_SESSION['user_id']);
      elseif (cfg('manage_anonymous_users')) {
        $user_id            = cfg('anonymous_user_id');
        $this->current_user = $this->_get_user_from_id($user_id);
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


    function get_current_forum_id() {
      return $_GET['forum_id'] ? (int)$_GET['forum_id'] : 1;
    }


    function get_current_message_id() {
      return $_GET['msg_id'] ? (int)$_GET['msg_id'] : '';
    }


    function get_newest_users($_limit) {
      return $this->_get_userdb()->get_newest_users($_limit);
    }


    function print_head() {
      $this->content = "";

      if (!headers_sent()) {
        header("Content-Type: text/html; charset=utf-8");
        $header = &new HeaderPrinter($this);
        $header->show();
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
    }


    function show() {
      $this->content = "";
      switch ($this->get_current_action()) {
      case 'read':
        $this->_message_read();             // Read a message.
        break;

      case 'write':
        $this->_message_compose();          // Write a new message.
        break;

      case 'respond':
        $this->_message_answer();           // Write an answer.
        break;

      case 'edit':
        $this->_message_edit_saved();       // Edit a saved message.
        break;

      case 'message_submit':
        if ($_POST['quote'])
          $this->_message_quote();          // Quote the parent message.
        elseif ($_POST['preview'])
          $this->_message_preview();        // A message preview.
        elseif ($_POST['send'])
          $this->_message_send();           // Save posted message.
        elseif ($_POST['edit'])
          $this->_message_edit_unsaved();   // Edit the unsaved message.
        break;

      case 'profile':
        $this->_show_profile();             // Show a user profile.
        break;

      case 'user_postings':
        $this->_show_user_postings();       // Show the postings of one user.
        break;

      case 'user_data':
        $this->_show_user_data();           // Form for editing user data.
        break;

      case 'user_data_submit':
        $this->_submit_user_data();
        break;

      case 'user_options':
        $this->_show_user_options();        // Show the user settings.
        break;

      case 'user_options_submit':
        $this->_submit_user_options();
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

      case 'register':
        $this->_register();                 // Show a registration form.
        break;

      case 'account_create':
        $this->_account_create();           // Register a new user.
        break;

      case 'account_confirm':
        $this->_account_confirm();          // Confirm a new user.
        break;

      case 'resend_confirm':
        $this->_resend_confirmation_mail(); // Send a confirmation mail.
        break;

      case 'change_password':
        $this->_change_password();          // Form for changing the password.
        break;

      case 'submit_password':
        $this->_change_password_submit();   // Set the initial password.
        break;

      case 'forgot_password':
        $this->_forgot_password();          // Form for requesting password mail.
        break;

      case 'password_mail_submit':
        $this->_password_mail_submit();     // Send password mail request.
        break;

      case 'confirm_password_mail':
        $this->_confirm_password_mail();    // Form for resetting the password.
        break;

      case 'top_posters':
        $this->_show_top_posters();         // List of top posters.
        break;

      case 'list':
      case '':
        if ($_COOKIE['view'] === 'plain')
          $this->_list_by_time();           // Show the forum, time order.
        else
          $this->_list_by_thread();         // Show the forum, thread order.
        break;

      default:
        die("internal error");
      }

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
