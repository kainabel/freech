<?php
  /*
  Tefinch.
  Copyright (C) 2003 Samuel Abels, <spam debain org>
                     Robert Weidlich, <tefinch xenim de>
  
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
  
  include_once 'functions/config.inc.php';
  include_once 'functions/language.inc.php';
  include_once 'functions/table_names.inc.php';
  include_once 'functions/string.inc.php';
  include_once 'functions/httpquery.inc.php';
  include_once 'functions/files.inc.php';
  
  include_once 'error.inc.php';
  
  include_once 'objects/url.class.php';
  include_once 'objects/message.class.php';
  include_once 'objects/user.class.php';
  include_once 'objects/group.class.php';
  
  include_once 'actions/printer_base.class.php';
  include_once 'actions/indexbar_strategy.class.php';
  include_once 'actions/indexbar_strategy_list_by_time.class.php';
  include_once 'actions/indexbar_strategy_list_by_thread.class.php';
  include_once 'actions/indexbar_strategy_read_message.class.php';
  include_once 'actions/indexbar_printer.class.php';
  include_once 'actions/thread_printer.class.php';
  include_once 'actions/latest_printer.class.php';
  include_once 'actions/rss_printer.class.php';
  include_once 'actions/message_printer.class.php';
  include_once 'actions/breadcrumbs_printer.class.php';
  include_once 'actions/login_printer.class.php';
  include_once 'actions/header_printer.class.php';
  include_once 'actions/footer_printer.class.php';
  include_once 'actions/registration_printer.class.php';
  
  include_once 'services/thread_folding.class.php';
  include_once 'services/sql_query.class.php';
  include_once 'services/forumdb.class.php';
  include_once 'services/accountdb.class.php';
  include_once 'services/trackable.class.php';
  include_once 'services/plugin_registry.class.php';
  
  
  class TefinchForum {
    var $db;
    var $forum;
    var $registry;
    var $eventbus;
    var $smarty;
    var $folding;
    
    // Prepare the forum, set cookies, etc. To be called before the http header 
    // was sent.
    function TefinchForum() {
      // Select a language.
      $l = cfg("lang");
      if ($l == 'auto')
        $l = ($_REQUEST[language] ? $_REQUEST[language] : cfg("lang_default"));
      //putenv("LANG=$l");
      setlocale(LC_MESSAGES, $l);
      
      // Setup gettext.
      if (!function_exists("gettext"))
        die("This webserver does not have gettext installed.<br/>"
          . "Please contact your webspace provider.");
      $domain = 'tefinch';
      bindtextdomain($domain, "./language");
      textdomain($domain);
      bind_textdomain_codeset($domain, 'UTF-8');
      
      // (Ab)use a Trackable as an eventbus.
      $this->eventbus = &new Trackable;

      // Connect to the DB.
      $this->db    = &ADONewConnection(cfg("db_dbn"))
        or die("TefinchForum::TefinchForum(): Error: Can't connect."
             . " Please check username, password and hostname.");
      $this->forum = &new ForumDB($this->db);

      $this->registry = &new PluginRegistry();
      $this->registry->read_plugins("plugins");
      $this->registry->activate_plugins($this); //FIXME: Make activation configurable.

      /* Plugin hook: on_construct
       *   Called from within the TefinchForum() constructor before any
       *   other output is produced.
       *   The return value of the callback is ignored.
       *   Args: None.
       */
      $this->eventbus->emit("on_construct");
      
      // Init Smarty.
      $this->smarty = &new Smarty();
      $this->smarty->template_dir = "themes/" . cfg("theme");
      $this->smarty->compile_dir  = "smarty/templates_c";
      $this->smarty->cache_dir    = "smarty/cache";
      $this->smarty->config_dir   = "smarty/configs";
      $this->smarty->register_function('lang', 'smarty_lang');
      
      $this->_handle_cookies();
    }
    
    
    function _handle_cookies() {
      if (get_magic_quotes_gpc()) {
        $_GET    = array_map('stripslashes_deep', $_GET);
        $_POST   = array_map('stripslashes_deep', $_POST);
        $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
      }
      $_GET[hs]       = $_GET[hs]       ? $_GET[hs]       * 1 : 0;
      $_GET[forum_id] = $_GET[forum_id] ? $_GET[forum_id] * 1 : 1;
      
      $this->folding = &new ThreadFolding($_COOKIE['fold'], $_COOKIE['c']);
      if ($_GET['c']) {
        $this->folding->swap($_GET['c']);
        $this->_set_cookie('c', $this->folding->get_string());
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
    }
    
    
    // Read a message.
    function _message_read() {
      $message    = $this->forum->get_message($_GET[forum_id], $_GET[msg_id]);
      $folding    = &new ThreadFolding(UNFOLDED, '');
      $msgprinter = &new MessagePrinter($this);
      $index      = &new IndexBarPrinter($this,
                                         'read_message',
                                         array(message => $message));
      $this->_print_breadcrumbs($message);
      $index->show();
      $msgprinter->show($message);
      if ($message && $message->has_thread() && $_COOKIE[thread] != 'hide') {
        $threadprinter = &new ThreadPrinter($this, $folding);
        $threadprinter->show($_GET[forum_id], $_GET[msg_id], 0);
      }
      $index->show();
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
      $message    = &new Message;
      $message->set_username($_POST['name']);
      $message->set_subject($_POST['subject']);
      $message->set_body($_POST['message']);
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
      $this->_print_breadcrumbs('');
      $n_entries = $this->forum->get_n_messages($_GET[forum_id]);
      $latest    = &new LatestPrinter($this);
      $index     = &new IndexBarPrinter($this,
                                        'list_by_time',
                                        array(n_messages          => $n_entries,
                                              n_messages_per_page => cfg("epp"),
                                              n_offset            => $_GET[hs],
                                              n_pages_per_index   => cfg("ppi")));
      $index->show();
      $latest->show();
      $index->show();
      $this->_print_footer();
    }
    
    
    // Shows the forum, thread order.
    function _list_by_thread() {
      $n_threads = $this->forum->get_n_threads($_GET[forum_id]);
      $this->_print_breadcrumbs('');
      $folding = &new ThreadFolding($_COOKIE['fold'], $_COOKIE['c']);
      $thread  = &new ThreadPrinter($this, $folding);
      $index   = &new IndexBarPrinter($this,
                                      'list_by_thread',
                                      array(n_threads          => $n_threads,
                                            n_threads_per_page => cfg("tpp"),
                                            n_offset           => $_GET[hs],
                                            n_pages_per_index  => cfg("ppi"),
                                            folding            => $folding));
      $index->show();
      $thread->show($_GET['forum_id'], 0, $_GET[hs]);
      $index->show();
      $this->_print_footer();
    }
    
    
    // Changes a cookie only if necessary.
    function _set_cookie($_name, $_value) {
      if ($_COOKIE[$_name] != $_value) {
        setcookie($_name, $_value);
        $_COOKIE[$_name] = $_value;
      }
    }
    
    
    // Prints the head of the page.
    function _print_breadcrumbs($_message) {
      $forumurl = &new URL('?', cfg("urlvars"));
      $forumurl->set_var('list',     1);
      $forumurl->set_var('forum_id', $_GET[forum_id]);
      
      $breadcrumbs = &new BreadCrumbsPrinter($this);
      $breadcrumbs->add_item(lang("forum"), $forumurl);
      
      if ($_GET[read] || $_GET[llist]) {
        if (!$_message)
          $breadcrumbs->add_item(lang("noentrytitle"));
        elseif (!$_message->is_active())
          $breadcrumbs->add_item(lang("blockedtitle"));
        else
          $breadcrumbs->add_item($_message->get_subject());
      }
      
      $breadcrumbs->show();
    } 
    
    
    // Prints the footer of the page.
    function _print_footer() {
      $footer = &new FooterPrinter($this);
      $footer->show();
    }
    
    
    function _show_login() {
      $login = &new LoginPrinter($this);
      $login->show();
    }
    
    
    function _user_print($_user, $_data) { print $_user->get_login() . "<br>"; } //FIXME
    function _register() {
      $registration = &new RegistrationPrinter($this);
      $registration->show();
      //$group     = &new Group;
      //$accountdb = &new AccountDB($this->db);
      //$accountdb->foreach_user(-1, 0, -1, array(&$this, "_user_print"), '');
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
      if (!headers_sent())
        header("Content-Type: text/html; charset=utf-8");
      $this->content = "";
      $header = &new HeaderPrinter($this);
      $header->show();
      
      /* Plugin hook: on_header_print_before
       *   Called before the HTML header is sent.
       *   Args: $html: A reference to the HTML header.
       */
      $this->eventbus->emit("on_header_print_before", &$this->content);
      print($this->content);
      
      /* Plugin hook: on_header_print_before
       *   Called after the HTML header was sent.
       *   Args: none
       */
      $this->eventbus->emit("on_header_print_after");
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
      elseif ($_GET['do_login'])
        $this->_show_login();       // Show a login form.
      elseif ($_GET['register'])
        $this->_register();         // Show a registration form.
      elseif (($_GET['list'] || $_GET['forum_id'])
              && $_COOKIE['view'] === 'plain')
        $this->_list_by_time();     // Show the forum, time order.
      elseif ($_GET['list'] || $_GET['forum_id'])
        $this->_list_by_thread();   // Show the forum, thread order.
      else
        die("internal error");

      /* Plugin hook: on_content_print_before
       *   Called before the HTML content is sent.
       *   Args: $html: A reference to the content.
       */
      $this->eventbus->emit("on_content_print_before", &$this->content);
      print($this->content);

      /* Plugin hook: on_content_print_after
       *   Called after the HTML content was sent.
       *   Args: none.
       */
      $this->eventbus->emit("on_content_print_after");
    }
    
    
    // Prints an RSS page.
    function print_rss($_forum_id,
                       $_title,
                       $_descr,
                       $_off,
                       $_n_entries) {
      $this->content = "";
      $rss = &new RSSPrinter($this);
      $rss->set_base_url(cfg("rss_url"));
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
       *   Called from within TefinchForum->destroy().
       *   The return value of the callback is ignored.
       *   Args: None.
       */
      $this->eventbus->emit("on_destroy");
    }
  }
?>
