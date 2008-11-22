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

  include_once 'services/thread_folding.class.php';
  include_once 'services/search_query.class.php';
  include_once 'services/sql_query.class.php';
  include_once 'services/forumdb.class.php';
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
    var $folding;

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
      if ($_GET['do_login'] && $_POST['login'])
        $this->login_error = $this->_try_login();
      if ($_GET['do_logout'])
        session_unset();
    }


    function _get_accountdb() {
      if (!$this->accountdb)
        $this->accountdb = &new AccountDB($this->db);
      return $this->accountdb;
    }


    function _try_login() {
      $accountdb = $this->_get_accountdb();
      $user      = $accountdb->get_user_from_login($_POST['login']);
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
      $_SESSION['login'] = $user->get_login();
      $_GET['do_login']  = 0;
      return 0;
    }


    function get_current_user() {
      if (session_id() === '')
        return FALSE;
      if (!$_SESSION['login'])
        return FALSE;
      if ($this->current_user)
        return $this->current_user;
      $accountdb          = $this->_get_accountdb();
      $this->current_user = $accountdb->get_user_from_login($_SESSION['login']);
      return $this->current_user;
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
      $_GET[hs]       = $_GET[hs]       ? $_GET[hs]       * 1 : 0;
      $_GET[forum_id] = $_GET[forum_id] ? $_GET[forum_id] * 1 : 1;

      $folding         = &new ThreadFolding($_COOKIE['fold'],
                                            $_COOKIE['c']);
      $profile_folding = &new ThreadFolding($_COOKIE['profile_fold'],
                                            $_COOKIE['profile_c']);
      if ($_GET['c']) {
        $folding->swap($_GET['c']);
        $this->_set_cookie('c', $folding->get_string());
      }

      if ($_GET['profile_c']) {
        $profile_folding->swap($_GET['profile_c']);
        $this->_set_cookie('profile_c', $profile_folding->get_string());
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

      if ($_GET['profile_fold'] === '1') {
        $this->_set_cookie('profile_fold', '1');
        $this->_set_cookie('profile_c', '');
      } elseif ($_GET['profile_fold'] === '2') {
        $this->_set_cookie('profile_fold', '2');
        $this->_set_cookie('profile_c', '');
      }
    }


    // Read a message.
    function _message_read() {
      $msg = $this->forum->get_message($_GET['forum_id'], $_GET['msg_id']);
      $this->_print_message_breadcrumbs($msg);
      $msgprinter = &new MessagePrinter($this);
      $msgprinter->show($_GET['forum_id'], $msg);
    }


    // Write an answer to a message.
    function _message_answer() {
      $message    = $this->forum->get_message($_GET[forum_id], $_GET[msg_id]);
      $msgprinter = &new MessagePrinter($this);
      $msgprinter->show_compose_reply($message, '', TRUE);
    }


    // Write a new message.
    function _message_compose() {
      $message    = &new Message;
      $msgprinter = &new MessagePrinter($this);
      $msgprinter->show_compose($message, '', FALSE);
    }


    // Edit a message.
    function _message_edit() {
      $message    = &new Message;
      $msgprinter = &new MessagePrinter($this);
      $message->set_username($_POST[name]);
      $message->set_subject($_POST[subject]);
      $message->set_body($_POST[message]);
      $msgprinter->show_compose($message, '', $_POST[msg_id] ? TRUE : FALSE);
    }


    // Insert a quote from the parent message.
    function _message_quote() {
      $quoted_msg = $this->forum->get_message($_GET[forum_id], $_GET[msg_id]);
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
      $msgprinter = &new MessagePrinter($this);
      $message    = &new Message;
      $message->set_username($_POST['name']);
      $message->set_subject($_POST['subject']);
      $message->set_body($_POST['message']);

      $user = $this->get_current_user();
      if ($user)
        $message->set_user_id($user->get_id());
      elseif (!$this->_username_available($message->get_username()))
         return $msgprinter->show_compose($message,
                                          lang("usernamenotavailable"),
                                          $_POST[msg_id] ? TRUE : FALSE);

      $ret = $message->check_complete();
      if ($ret < 0)
        $msgprinter->show_compose($message,
                                  $err[$ret],
                                  $_POST[msg_id] ? TRUE : FALSE);
      else
        $msgprinter->show_preview($message, $_POST['msg_id']);
    }


    // Saves the posted message.
    function _message_submit() {
      global $err;
      $msgprinter = &new MessagePrinter($this);
      $user       = $this->get_current_user();
      $message    = &new Message;
      $message->set_username($_POST['name']);
      $message->set_subject($_POST['subject']);
      $message->set_body($_POST['message']);

      if ($user && $user->get_login() !== $message->get_username())
        die("Username does not match currently logged in user");

      if ($user)
        $message->set_user_id($user->get_id());
      elseif (!$this->_username_available($message->get_username()))
         return $msgprinter->show_compose($message,
                                          lang("usernamenotavailable"),
                                          $_POST[msg_id] ? TRUE : FALSE);

      $duplicate_id = $this->forum->find_duplicate($message);
      if ($duplicate_id)
        return $msgprinter->show_created($duplicate_id,
                                         lang("messageduplicate"));

      $ret = $message->check_complete();
      if ($ret == 0)
        $newmsg_id = $this->forum->insert_entry($_GET[forum_id],
                                                $_GET[msg_id],
                                                $message);
      if ($ret < 0 || $new_id < 0)
        $msgprinter->show_compose($message,
                                  $err[$ret],
                                  $_POST[msg_id] ? TRUE : FALSE);
      else
        $msgprinter->show_created($newmsg_id);
    }


    // Shows the forum, time order.
    function _list_by_time() {
      $this->_print_list_breadcrumbs('');
      $latest = &new LatestPrinter($this);
      $latest->show($_GET['forum_id'], $_GET['hs']);
      $this->_print_footer();
    }


    // Shows the forum, thread order.
    function _list_by_thread() {
      $this->_print_list_breadcrumbs('');
      $folding = &new ThreadFolding($_COOKIE['fold'], $_COOKIE['c']);
      $thread  = &new ThreadPrinter($this, $folding);
      $thread->show($_GET['forum_id'], 0, $_GET[hs]);
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
      $forumurl = &new URL('?', cfg("urlvars"));
      $forumurl->set_var('list',     1);
      $forumurl->set_var('forum_id', $_GET[forum_id]);
      return $forumurl;
    }


    function _print_list_breadcrumbs() {
      $breadcrumbs = &new BreadCrumbsPrinter($this);
      $search      = array('forumid' => $_GET['forum_id']);
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
      $breadcrumbs->add_item($_user->get_login());
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
      $printer->show();
    }


    function _show_search_result() {
      if (cfg("disable_search"))
        die("Search is currently disabled.");
      $query = $_GET['q'];
      if ($_GET['forum_id'])
        $query = 'forumid:"'.(int)$_GET['forum_id'].'" AND ('.$_GET['q'].')';
      $search  = &new SearchQuery($query);
      $printer = &new SearchPrinter($this);
      $printer->show($search, $_GET['hs']);
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


    function &_fetch_user_data() {
      $user = &new User($_POST['login']);
      $user->set_password($_POST['password']);
      $user->set_firstname($_POST['acc_firstname']);
      $user->set_lastname($_POST['acc_lastname']);
      $user->set_mail($_POST['acc_mail'],
                      $_POST['acc_publicmail'] ? TRUE : FALSE);
      return $user;
    }


    function _username_available(&$_username) {
      $accountdb = $this->_get_accountdb();
      $user      = new User($_username);
      if (count($accountdb->get_similiar_users($user)) == 0)
        return TRUE;
      return FALSE;
    }


    function _send_account_mail(&$user, $subject, $body, $url) {
      // Send a registration mail.
      $head  = "From: ".cfg("mail_from")."\r\n";
      $body  = preg_replace("/\[LOGIN\]/",     $user->get_login(),     $body);
      $body  = preg_replace("/\[FIRSTNAME\]/", $user->get_firstname(), $body);
      $body  = preg_replace("/\[LASTNAME\]/",  $user->get_lastname(),  $body);
      $body  = preg_replace("/\[URL\]/",       $url,                   $body);
      mail($user->get_mail(), $subject, $body, $head);
    }


    function _send_confirmation_mail(&$user) {
      // Send a registration mail.
      $subject = lang("registration_mail_subject");
      $body    = lang("registration_mail_body");
      $login   = urlencode($user->get_login());
      $hash    = urlencode($user->get_confirmation_hash());
      $url     = cfg('site_url') . "?confirm_account=1&login=$login&hash=$hash";
      $this->_send_account_mail($user, $subject, $body, $url);
      $registration = &new RegistrationPrinter($this);
      $registration->show_mail_sent($user);
    }


    function _resend_confirmation_mail() {
      $accountdb = $this->_get_accountdb();
      $user      = $accountdb->get_user_from_login($_GET['login']);
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

      if (!$this->_username_available($user->get_login()))
        return $registration->show($user, $err[ERR_REGISTER_USER_EXISTS]);

      $accountdb = $this->_get_accountdb();
      $ret       = $accountdb->save_user($user);
      if ($ret < 0)
        return $registration->show($user, $err[$ret]);

      $this->_send_confirmation_mail($user);
    }


    function _get_current_or_confirming_user() {
      if ($_GET['confirm_account']
        || $_GET['confirm_password_mail']
        || $_GET['reset_password_submit']) {
        $accountdb = $this->_get_accountdb();
        $user      = $accountdb->get_user_from_login($_GET['login']);
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
      $user      = $accountdb->get_user_from_login($user->get_login());
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

      $subject = lang("reset_mail_subject");
      $body    = lang("reset_mail_body");
      $login   = urlencode($user->get_login());
      $hash    = urlencode($user->get_confirmation_hash());
      $url     = cfg('site_url') . "?confirm_password_mail=1"
               . "&login=$login&hash=$hash";
      $this->_send_account_mail($user, $subject, $body, $url);
      $registration = &new RegistrationPrinter($this);
      $registration->show_forgot_password_mail_sent($user);
    }


    function _confirm_password_mail() {
      $user      = $this->_get_current_or_confirming_user();
      $accountdb = $this->_get_accountdb();
      $user      = $accountdb->get_user_from_login($user->get_login());
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
      $user      = $accountdb->get_user_from_login($_GET['login']);
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
      if ($_GET['login']) {
        $accountdb = $this->_get_accountdb();
        $user      = $accountdb->get_user_from_login($_GET['login']);
      }
      else
        $user = $this->get_current_user();
      $this->_print_profile_breadcrumbs($user);
      $folding = &new ThreadFolding($_COOKIE['profile_fold'],
                                    $_COOKIE['profile_c']);
      $profile = &new ProfilePrinter($this, $folding);
      $profile->show($user);
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
      if ($_GET['read'])
        $this->_message_read();     // Read a message.
      elseif ($_GET['write'] && $_GET['msg_id'])
        $this->_message_answer();   // Write an answer.
      elseif ($_GET['write'])
        $this->_message_compose();  // Write a new message.
      elseif ($_POST['edit'])
        $this->_message_edit();     // Edit a message.
      elseif ($_POST['quote'])
        $this->_message_quote();    // Insert a quote from the parent message.
      elseif ($_POST['preview'])
        $this->_message_preview();  // A message preview was requested.
      elseif ($_POST['send'])
        $this->_message_submit();   // A message was posted and should be saved.
      elseif ($_GET['profile'])
        $this->_show_profile();             // Show a user profile.
      elseif ($_GET['search'] && $_GET['q'])
        $this->_show_search_result();       // Run a search.
      elseif ($_GET['search'])
        $this->_show_search_form();         // Show the search form.
      elseif ($_GET['do_login'])
        $this->_show_login();               // Show a login form.
      elseif ($_GET['register'])
        $this->_register();                 // Show a registration form.
      elseif ($_GET['create_account'])
        $this->_account_create();           // Register a new user.
      elseif ($_GET['confirm_account'])
        $this->_account_confirm();          // Confirm a new user.
      elseif ($_GET['resend_confirm'])
        $this->_resend_confirmation_mail(); // Send a confirmation mail.
      elseif ($_GET['change_password'])
        $this->_change_password();          // Form for changing the password.
      elseif ($_GET['submit_password'])
        $this->_change_password_submit();   // Set the initial password.
      elseif ($_GET['forgot_password'])
        $this->_forgot_password();          // Form for requesting password mail.
      elseif ($_GET['password_mail_submit'])
        $this->_password_mail_submit();     // Send password mail request.
      elseif ($_GET['confirm_password_mail'])
        $this->_confirm_password_mail();    // Form for resetting the password.
      elseif (($_GET['list'] || $_GET['forum_id'])
              && $_COOKIE['view'] === 'plain')
        $this->_list_by_time();      // Show the forum, time order.
      elseif ($_GET['list'] || $_GET['forum_id'])
        $this->_list_by_thread();    // Show the forum, thread order.
      else
        die("internal error");

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
