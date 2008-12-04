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
  include_once 'actions/login_printer.class.php';
  include_once 'actions/profile_printer.class.php';
  include_once 'actions/search_printer.class.php';
  include_once 'actions/header_printer.class.php';
  include_once 'actions/footer_printer.class.php';
  include_once 'actions/registration_printer.class.php';

  include_once 'services/search_query.class.php';
  include_once 'services/sql_query.class.php';
  include_once 'services/forumdb.class.php';
  include_once 'services/thread_loader.class.php';
  include_once 'services/accountdb.class.php';
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
      $this->smarty->compile_dir   = "smarty/templates_c";
      $this->smarty->cache_dir     = "smarty/cache";
      $this->smarty->config_dir    = "smarty/configs";
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
      if ($this->get_action() == 'do_login' && $_POST['username'])
        $this->login_error = $this->_try_login();
      if ($this->get_action() == 'do_logout') {
        session_unset();
        unset($_GET['action']);
        unset($_POST['action']);
      }
    }


    function _get_accountdb() {
      if (!$this->accountdb)
        $this->accountdb = &new AccountDB($this->db);
      return $this->accountdb;
    }


    function _try_login() {
      $accountdb = $this->_get_accountdb();
      $user      = $accountdb->get_user_from_name($_POST['username']);
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
      $ret = $accountdb->save_user($user);
      if ($ret < 0)
        die("Failed to log in user, return code $ret");
      $_SESSION['username'] = $user->get_username();
      unset($_GET['action']);
      unset($_POST['action']);
      return 0;
    }


    function get_current_user() {
      if (session_id() === '')
        return FALSE;
      if (!$_SESSION['username'])
        return FALSE;
      if ($this->current_user)
        return $this->current_user;
      $accountdb          = $this->_get_accountdb();
      $sessionuser        = $_SESSION['username'];
      $this->current_user = $accountdb->get_user_from_name($sessionuser);
      return $this->current_user;
    }


    function get_action() {
      if ($_GET['action'])
        return $_GET['action'];
      if ($_POST['action'])
        return $_POST['action'];
      return 'list';
    }


    function get_forum_id() {
      return $_GET['forum_id'] ? (int)$_GET['forum_id'] : 1;
    }


    function get_message_id() {
      return $_GET['msg_id'] ? (int)$_GET['msg_id'] : '';
    }


    function get_newest_users($_limit) {
      return $this->_get_accountdb()->get_newest_users($_limit);
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


    // Read a message.
    function _message_read() {
      $forum_id   = $this->get_forum_id();
      $msg        = $this->forum->get_message($forum_id, $_GET['msg_id']);
      $msgprinter = &new MessagePrinter($this);
      $this->_print_message_breadcrumbs($msg);
      $msgprinter->show($forum_id, $msg);
    }


    // Write an answer to a message.
    function _message_answer() {
      $forum_id   = $this->get_forum_id();
      $parent_id  = (int)$_GET[parent_id];
      $message    = $this->forum->get_message($forum_id, $parent_id);
      $msgprinter = &new MessagePrinter($this);
      $msgprinter->show_compose_reply($message, '', TRUE);
    }


    // Edit a saved message.
    function _message_edit_saved() {
      $forum_id   = $this->get_forum_id();
      $user       = $this->get_current_user();
      $message    = $this->forum->get_message($forum_id, $_GET[msg_id]);
      $msgprinter = &new MessagePrinter($this);

      if (!cfg("postings_editable"))
        die("Postings may not be changed as per configuration.");
      if ($message->get_user_type() == 'anonymous')
        die("Anonymous postings may not be changed.");
      elseif (!$user)
        die("You are not logged in.");
      elseif ($user->get_id() != $message->get_user_id())
        die("You are not the owner.");

      $msgprinter->show_compose($message, '', 0, FALSE);
    }


    // Write a new message.
    function _message_compose() {
      $parent_id  = (int)$_POST[parent_id];
      $message    = &new Message;
      $msgprinter = &new MessagePrinter($this);
      $msgprinter->show_compose($message, '', $parent_id, FALSE);
    }


    // Edit an unsaved message.
    function _message_edit_unsaved() {
      $parent_id  = (int)$_POST[parent_id];
      $may_quote  = $parent_id ? TRUE : FALSE;
      $message    = &new Message;
      $msgprinter = &new MessagePrinter($this);
      $message->set_id($_POST['msg_id']);
      $message->set_username($_POST[name]);
      $message->set_subject($_POST[subject]);
      $message->set_body($_POST[message]);
      $msgprinter->show_compose($message, '', $parent_id, $may_quote);
    }


    // Insert a quote from the parent message.
    function _message_quote() {
      $forum_id   = $this->get_forum_id();
      $parent_id  = (int)$_POST[parent_id];
      $quoted_msg = $this->forum->get_message($forum_id, $parent_id);
      $message    = &new Message;
      $msgprinter = &new MessagePrinter($this);
      $message->set_username($_POST[name]);
      $message->set_subject($_POST[subject]);
      $message->set_body($_POST[message]);
      $msgprinter->show_compose_quoted($message, $quoted_msg, '', FALSE);
    }


    // Print a preview of a message.
    function _message_preview() {
      global $err;
      $parent_id  = (int)$_POST[parent_id];
      $may_quote  = $parent_id ? TRUE : FALSE;
      $msgprinter = &new MessagePrinter($this);
      $message    = &new Message;
      $message->set_id($_POST['msg_id']);
      $message->set_username($_POST['name']);
      $message->set_subject($_POST['subject']);
      $message->set_body($_POST['message']);

      $user = $this->get_current_user();
      if ($user) {
        $message->set_user_id($user->get_id());
        $message->set_signature($user->get_signature());
      }
      elseif (!$this->_username_available($message->get_username()))
         return $msgprinter->show_compose($message,
                                          lang("usernamenotavailable"),
                                          $parent_id,
                                          $may_quote);

      $ret = $message->check_complete();
      if ($ret < 0)
        $msgprinter->show_compose($message,
                                  $err[$ret],
                                  $parent_id,
                                  $may_quote);
      else
        $msgprinter->show_preview($message, $parent_id);
    }


    // Saves the posted message.
    function _message_send() {
      global $err;
      $msgprinter = &new MessagePrinter($this);
      $user       = $this->get_current_user();
      $parent_id  = (int)$_POST[parent_id];
      $forum_id   = $this->get_forum_id();
      $may_quote  = $parent_id ? TRUE : FALSE;
      if ($_POST['msg_id'] && !cfg("postings_editable"))
        die("Postings may not be changed as per configuration.");
      elseif ($_POST['msg_id'])
        $message = $this->forum->get_message($forum_id, $_POST['msg_id']);
      else {
        $message = &new Message;
        $message->set_username($_POST['name']);
      }
      $message->set_subject($_POST['subject']);
      $message->set_body($_POST['message']);

      if ($user && $user->get_username() !== $message->get_username())
        die("Username does not match currently logged in user");

      if ($user) {
        $message->set_user_id($user->get_id());
        $message->set_signature($user->get_signature());
      }
      elseif (!$this->_username_available($message->get_username()))
         return $msgprinter->show_compose($message,
                                          lang("usernamenotavailable"),
                                          $parent_id,
                                          $may_quote);

      $duplicate_id = $this->forum->find_duplicate($message);
      if ($duplicate_id)
        return $msgprinter->show_created($duplicate_id,
                                         $parent_id,
                                         lang("messageduplicate"));

      $ret = $message->check_complete();
      if ($ret == 0 && !$message->get_id())
        $newmsg_id = $this->forum->insert_entry($forum_id,
                                                $parent_id,
                                                $message);
      elseif ($message->get_id()) {
        $this->forum->save_entry($forum_id, $parent_id, $message);
        $newmsg_id = $message->get_id();
      }
      if ($ret < 0 || $new_id < 0)
        $msgprinter->show_compose($message,
                                  $err[$ret],
                                  $parent_id,
                                  $may_quote);
      else
        $msgprinter->show_created($newmsg_id, $parent_id);
    }


    // Shows the forum, time order.
    function _list_by_time() {
      $this->_print_list_breadcrumbs('');
      $forum_id = $this->get_forum_id();
      $latest   = &new LatestPrinter($this);
      $latest->show($forum_id, $_GET['hs']);
      $this->_print_footer();
    }


    // Shows the forum, thread order.
    function _list_by_thread() {
      $this->_print_list_breadcrumbs('');
      $thread_state = &new ThreadState($_COOKIE['fold'], $_COOKIE['c']);
      $forum_id     = $this->get_forum_id();
      $thread       = &new ThreadPrinter($this);
      $thread->show($forum_id, 0, $_GET[hs], $thread_state);
      $this->_print_footer();
    }


    // Changes a cookie only if necessary.
    function _set_cookie($_name, $_value) {
      if ($_COOKIE[$_name] != $_value) {
        setcookie($_name, $_value);
        $_COOKIE[$_name] = $_value;
      }
    }


    function _get_forumurl() {
      $forum_id = $this->get_forum_id();
      $forumurl = &new URL('?', cfg("urlvars"));
      $forumurl->set_var('action',   'list');
      $forumurl->set_var('forum_id', $forum_id);
      return $forumurl;
    }


    function _print_list_breadcrumbs() {
      $forum_id    = $this->get_forum_id();
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


    function _print_profile_breadcrumbs($_user) {
      $breadcrumbs = &new BreadCrumbsPrinter($this);
      $breadcrumbs->add_item(lang("forum"), $this->_get_forumurl());
      $breadcrumbs->add_item($_user->get_username());
      $breadcrumbs->show();
    }


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


    // Prints the footer of the page.
    function _print_footer() {
      $footer = &new FooterPrinter($this);
      $footer->show();
    }


    function _show_search_form() {
      if (cfg("disable_search"))
        die("Search is currently disabled.");
      $printer = &new SearchPrinter($this);
      $printer->show($_GET['forum_id'] ? (int)$_GET['forum_id'] : '',
                     $_GET['q']);
    }


    function _show_search_result() {
      if (cfg("disable_search"))
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


    function _show_login() {
      global $err;
      $user  = $this->_fetch_user_data();
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


    function &_fetch_user_data($user = '') {
      if (!$user)
        $user = &new User($_POST['username']);
      $user->set_password($_POST['password']);
      $user->set_firstname($_POST['firstname']);
      $user->set_lastname($_POST['lastname']);
      $user->set_mail($_POST['mail'], $_POST['publicmail'] == 'on');
      $user->set_homepage($_POST['homepage']);
      $user->set_im($_POST['im']);
      $user->set_signature($_POST['signature']);
      return $user;
    }


    function _username_available(&$_username) {
      $accountdb = $this->_get_accountdb();
      $user      = new User($_username);
      if (count($accountdb->get_similar_users($user)) == 0)
        return TRUE;
      return FALSE;
    }


    function _send_account_mail(&$user, $subject, $body, $url) {
      // Send a registration mail.
      $head  = "From: ".cfg("mail_from")."\r\n";
      $body  = preg_replace("/\[LOGIN\]/",     $user->get_username(),  $body);
      $body  = preg_replace("/\[FIRSTNAME\]/", $user->get_firstname(), $body);
      $body  = preg_replace("/\[LASTNAME\]/",  $user->get_lastname(),  $body);
      $body  = preg_replace("/\[URL\]/",       $url,                   $body);
      mail($user->get_mail(), $subject, $body, $head);
    }


    function _send_confirmation_mail(&$user) {
      // Send a registration mail.
      $subject  = lang("registration_mail_subject");
      $body     = lang("registration_mail_body");
      $username = urlencode($user->get_username());
      $hash     = urlencode($user->get_confirmation_hash());
      $url      = cfg('site_url') . "?action=account_confirm"
                . "&username=$username&hash=$hash";
      $this->_send_account_mail($user, $subject, $body, $url);
      $registration = &new RegistrationPrinter($this);
      $registration->show_mail_sent($user);
    }


    function _resend_confirmation_mail() {
      $accountdb = $this->_get_accountdb();
      $user      = $accountdb->get_user_from_name($_GET['username']);
      if ($user->get_status() != USER_STATUS_UNCONFIRMED)
        die("User is already confirmed.");
      $this->_send_confirmation_mail($user);
    }


    function _account_create() {
      global $err;
      $registration = &new RegistrationPrinter($this);
      $user         = $this->_fetch_user_data();

      $ret = $user->check_complete();
      if ($ret < 0)
        return $registration->show($user, $err[$ret]);
      if ($_POST['password'] !== $_POST['password2'])
        return $registration->show($user, $err[ERR_REGISTER_PASSWORDS_DIFFER]);

      if (!$this->_username_available($user->get_username()))
        return $registration->show($user, $err[ERR_REGISTER_USER_EXISTS]);

      $accountdb = $this->_get_accountdb();
      $ret       = $accountdb->save_user($user);
      if ($ret < 0)
        return $registration->show($user, $err[$ret]);

      $this->_send_confirmation_mail($user);
    }


    function _get_current_or_confirming_user() {
      if ($this->get_action() == 'account_confirm'
        || $this->get_action() == 'confirm_password_mail'
        || $this->get_action() == 'reset_password_submit') {
        $accountdb = $this->_get_accountdb();
        $user      = $accountdb->get_user_from_name($_GET['username']);
        $this->_check_confirmation_hash($user);
        return $user;
      }

      $user = $this->get_current_user();
      if (!$user)
        die("Invalid user");
      return $user;
    }


    function _change_password() {
      $user         = $this->_get_current_or_confirming_user();
      $registration = &new RegistrationPrinter($this);
      $registration->show_change_password($user);
    }


    function _change_password_submit() {
      global $err;
      $accountdb = $this->_get_accountdb();
      $user      = $this->_fetch_user_data();
      $user      = $accountdb->get_user_from_name($user->get_username());
      $regist = &new RegistrationPrinter($this);
      if ($_POST['password'] !== $_POST['password2']) {
        $error = lang("passwordsdonotmatch");
        return $regist->show_change_password($user, $error);
      }

      $ret = $user->set_password($_POST['password']);
      if ($ret < 0) {
        $regist->show_change_password($user, $err[$ret]);
        return;
      }

      $user->set_status(USER_STATUS_ACTIVE);
      $ret       = $accountdb->save_user($user);
      if ($ret < 0) {
        $regist->show_change_password($user, $err[$ret]);
        return;
      }

      $regist->show_done($user);
    }


    function _forgot_password() {
      $user         = $this->_fetch_user_data();
      $registration = &new RegistrationPrinter($this);
      $registration->show_forgot_password($user);
    }


    function _password_mail_submit() {
      global $err;
      $registration = &new RegistrationPrinter($this);
      $user         = $this->_fetch_user_data();
      $ret          = $user->check_mail();
      if ($ret != 0)
        return $registration->show_forgot_password($user, $err[$ret]);

      $accountdb = $this->_get_accountdb();
      $user      = $accountdb->get_user_from_mail($user->get_mail());
      if (!$user) {
        $msg = $err[ERR_LOGIN_NO_SUCH_MAIL];
        return $registration->show_forgot_password($user, $msg);
      }

      if ($user->get_status() != USER_STATUS_ACTIVE) {
        $msg = $err[ERR_LOGIN_UNCONFIRMED];
        return $registration->show_forgot_password($user, $msg);
      }

      $subject  = lang("reset_mail_subject");
      $body     = lang("reset_mail_body");
      $username = urlencode($user->get_username());
      $hash     = urlencode($user->get_confirmation_hash());
      $url      = cfg('site_url') . "?action=confirm_password_mail"
                . "&username=$username&hash=$hash";
      $this->_send_account_mail($user, $subject, $body, $url);
      $registration = &new RegistrationPrinter($this);
      $registration->show_forgot_password_mail_sent($user);
    }


    function _confirm_password_mail() {
      $user      = $this->_get_current_or_confirming_user();
      $accountdb = $this->_get_accountdb();
      $user      = $accountdb->get_user_from_name($user->get_username());
      $this->_check_confirmation_hash($user);

      if ($user->get_status() != USER_STATUS_ACTIVE)
        die("Error: User status is not active.");

      $this->_change_password();
    }


    function _check_confirmation_hash(&$user) {
      $hash = $user->get_confirmation_hash();
      if (!$user)
        die("Invalid user name");
      if ($user->get_confirmation_hash() !== $_GET['hash'])
        die("Invalid confirmation hash");
      if ($user->get_status() == USER_STATUS_BLOCKED)
        die("User is blocked");
    }


    function _account_confirm() {
      $accountdb = $this->_get_accountdb();
      $user      = $accountdb->get_user_from_name($_GET['username']);
      $this->_check_confirmation_hash($user);

      if (!$user->get_password_hash())
        return $this->_change_password();

      $user->set_status(USER_STATUS_ACTIVE);
      $ret = $accountdb->save_user($user);
      if ($ret < 0)
        die("User activation failed");

      $registration = &new RegistrationPrinter($this);
      $registration->show_done($user);
    }


    function _show_profile() {
      $accountdb = $this->_get_accountdb();
      $user      = $accountdb->get_user_from_name($_GET['username']);
      if (!$user)
        die("No such user.");
      $this->_print_profile_breadcrumbs($user);
      $profile = &new ProfilePrinter($this);
      $profile->show($user);
    }


    function _show_user_postings() {
      if ($_GET['username']) {
        $accountdb = $this->_get_accountdb();
        $user      = $accountdb->get_user_from_name($_GET['username']);
      }
      else
        $user = $this->get_current_user();
      $this->_print_profile_breadcrumbs($user);
      $thread_state = &new ThreadState($_COOKIE['user_postings_fold'],
                                       $_COOKIE['user_postings_c']);
      $profile = &new ProfilePrinter($this);
      $profile->show_user_postings($user, $thread_state, (int)$_GET['hs']);
    }


    function _show_user_data() {
      $user = $this->get_current_user();
      $this->_print_profile_breadcrumbs($user);
      $profile = &new ProfilePrinter($this);
      $profile->show_user_data($user);
    }


    function _submit_user_data() {
      global $err;
      $profile = &new ProfilePrinter($this);
      $user    = $this->get_current_user();
      if (!$user)
        die("Not logged in.");

      $this->_print_profile_breadcrumbs($user);
      $this->_fetch_user_data($user);
      $ret = $user->check_complete();
      if ($ret < 0)
        return $profile->show_user_data($user, $err[$ret]);
      if ($_POST['password'] !== $_POST['password2'])
        return $profile->show_user_data($user,
                                        $err[ERR_REGISTER_PASSWORDS_DIFFER]);
      if ($_POST['password'] != '')
        $user->set_password($_POST['password']);

      $accountdb = $this->_get_accountdb();
      $ret       = $accountdb->save_user($user);
      if ($ret < 0)
        return $profile->show_user_data($user, $err[$ret]);

      $profile->show_user_data($user, lang("account_saved"));
    }


    function _show_user_options() {
      $user = $this->get_current_user();
      $this->_print_profile_breadcrumbs($user);
      $profile = &new ProfilePrinter($this);
      $profile->show_user_options($user);
    }


    function &get_registry() {
      return $this->registry;
    }


    function &get_eventbus() {
      return $this->eventbus;
    }


    function &get_smarty() {
      return $this->smarty;
    }


    function &get_forumdb() {
      return $this->forum;
    }


    function append_content(&$_content) {
      $this->content .= $_content . "\n";
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
      switch ($this->get_action()) {
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

      case 'do_login':
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


    // Prints an RSS page.
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
