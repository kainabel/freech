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
  include_once 'config.inc.php';
  include_once "mysql_$cfg[db_backend].inc.php";
  include_once "language/$cfg[lang].inc.php";
  include_once 'error.inc.php';
  include_once 'string.inc.php';
  include_once 'httpquery.inc.php';
  
  include_once 'message.inc.php';
  include_once 'message_index.inc.php';
  include_once 'message_compose.inc.php';
  include_once 'message_preview.inc.php';
  include_once 'message_submit.inc.php';
  include_once 'message_created.inc.php';
  
  include_once 'thread.inc.php';
  include_once 'thread_index.inc.php';
  
  include_once 'latest.inc.php';
  include_once 'latest_index.inc.php';
  
  include_once 'login.inc.php';
  
  
  class TefinchForum {
    var $db;
    var $smarty;
    var $folding;
    
    // Prepare the forum, set cookies, etc. To be called before the http header 
    // was sent.
    function TefinchForum() {
      global $cfg;
      global $lang;
      $this->db = new TefinchDB($cfg[db_host],
                                $cfg[db_usr],
                                $cfg[db_pass],
                                $cfg[db_name],
                                $cfg[db_tablebase] . "_message");
      $this->db->set_timeformat($lang[dateformat]);
      
      $this->smarty = new Smarty();
      $this->smarty->template_dir = "themes/$cfg[theme]";
      $this->smarty->compile_dir  = "smarty/templates_c";
      $this->smarty->cache_dir    = "smarty/cache";
      $this->smarty->config_dir   = "smarty/configs";
      
      $this->folding = new ThreadFolding($_COOKIE['fold'], $_COOKIE['swap']);
      if ($_GET['swap']) {
        $this->folding->swap($_GET['swap']);
        $this->_set_cookie('swap', $this->folding->get_string());
      }
      
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
        $this->_set_cookie('swap', '');
      } elseif ($_GET['fold'] === '2') {
        $this->_set_cookie('fold', '2');
        $this->_set_cookie('swap', '');
      }
    }
    
    
    // Read a message.
    function _message_read() {
      $folding   = new ThreadFolding(UNFOLDED, '');
      $entry     = $this->db->get_entry($_GET['forum_id'], $_GET['msg_id']);
      $hasthread = $entry && (!$entry->is_toplevel || $entry->n_children != 0);
      $this->_print_navbar($entry);
      message_index_print($this->smarty,
                          $entry->id,
                          $entry->prev_thread,
                          $entry->next_thread,
                          $entry->prev_entry,
                          $entry->next_entry,
                          $hasthread,
                          $entry->active && $entry->can_answer);
      message_print($this->smarty, $entry);
      if ($hasthread && $_COOKIE[thread] != 'hide') {
        $threadprinter = new ThreadPrinter($this->smarty, $this->db, $folding);
        $threadprinter->show($_GET['forum_id'], $_GET['msg_id'], 0);
      }
      message_index_print($this->smarty,
                          $entry->id,
                          $entry->prev_thread,
                          $entry->next_thread,
                          $entry->prev_entry,
                          $entry->next_entry,
                          $hasthread,
                          $entry->active && $entry->can_answer);
    }
    
    
    // Write an answer to a message.
    function _message_answer() {
      $entry = $this->db->get_entry($_GET[forum_id], $_GET[msg_id]);
      message_compose_reply($this->smarty, $entry->title, '');
    }
    
    
    // Write a new message.
    function _message_compose() {
      message_compose($this->smarty, '', '', '', '', FALSE);
    }
    
    
    // Edit a message.
    function _message_edit() {
      message_compose($this->smarty,
                      $_POST['name'],
                      $_POST['subject'],
                      $_POST['message'],
                      '',
                      $_POST[msg_id] ? TRUE : FALSE);
    }
    
    
    // Insert a quote from the parent message.
    function _message_quote() {
      global $lang;
      // FIXME: String stuff should be moved elsewhere.
      $entry = $this->db->get_entry($_GET['forum_id'], $_GET['msg_id']);
      if ($_GET['msg_id'] && $entry->active) {
        // Add a line "user wrote date" and add "> " at the beginning of
        // each line.
        $text = preg_replace("/\[USER\]/", $entry->name, $lang[wrote])
              . " $entry->time\n\n"
              . preg_replace("/^/m","> ",
                             message_wrapline($entry->text)) . "\n";
      }
      $text .= $_POST['message'];
      message_compose($this->smarty,
                      $_POST['name'],
                      $_POST['subject'],
                      $text,
                      '',
                      FALSE);
    }
    
    
    // Print a preview of a message.
    function _message_preview() {
      global $err;
      $ret = message_preview($this->smarty,
                             $_POST['name'],
                             $_POST['subject'],
                             $_POST['message'],
                             $_POST['msg_id']);
      if ($ret < 0)
        message_compose($this->smarty,
                        $_POST['name'],
                        $_POST['subject'],
                        $_POST['message'],
                        $err[$ret],
                        $_POST[msg_id] ? TRUE : FALSE);
    }
    
    
    // Saves the posted message.
    function _message_send() {
      global $err;
      $new_id = message_submit($this->db,
                               $_GET[forum_id],
                               $_GET[msg_id],
                               $_POST['name'],
                               $_POST['subject'],
                               $_POST['message']);
      if ($new_id < 0)
        message_compose($this->smarty,
                        $_POST['name'],
                        $_POST['subject'],
                        $_POST['message'],
                        $err[$new_id],
                        $_POST[msg_id] ? TRUE : FALSE);
      else
        message_created($this->smarty, $new_id);
    }
    
    
    // Shows the forum, time order.
    function _list_by_time() {
      global $cfg;
      $this->_print_navbar('');
      $n_entries = $this->db->get_n_entries($_GET[forum_id]);
      $latest    = new LatestPrinter($this->smarty, $this->db);
      latest_index_print($this->smarty,
                         $n_entries,
                         $_GET[hs],
                         $cfg[epp],
                         $cfg[ppi],
                         $_GET);
      $latest->show();
      latest_index_print($this->smarty,
                         $n_entries,
                         $_GET[hs],
                         $cfg[epp],
                         $cfg[ppi],
                         $_GET);
      $this->_print_footer();
    }
    
    
    // Shows the forum, thread order.
    function _list_by_thread() {
      global $cfg;
      $folding   = new ThreadFolding($_COOKIE['fold'], $_COOKIE['swap']);
      $n_threads = $this->db->get_n_threads($_GET[forum_id]);
      $this->_print_navbar('');
      thread_index_print($this->smarty,
                         $n_threads,
                         $_GET[hs],
                         $cfg[tpp],
                         $cfg[ppi],
                         $folding,
                         $_GET);
      $threadprinter = new ThreadPrinter($this->smarty, $this->db, $folding);
      $threadprinter->show($_GET['forum_id'], 0, $_GET[hs]);
      thread_index_print($this->smarty,
                         $n_threads,
                         $_GET[hs],
                         $cfg[tpp],
                         $cfg[ppi],
                         $folding,
                         $_GET);
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
    function _print_navbar($_entry) {
      global $lang;
      global $cfg;
      
      $holdvars = array_merge($cfg[urlvars], array('forum_id'));
      if ($cfg[remember_page])
        array_push($holdvars, 'hs');
      $query['list'] = 1;
      $url           = build_url($_GET, $holdvars, $query);
      
      $this->smarty->assign('layer1', "<a href='?$url'>$lang[forum]</a>");
      if ($_GET['read'] === '1' || $_GET['llist']) {
        if ($entry)
          $this->smarty->assign('layer2', $lang[noentrytitle]);
        elseif (!$_entry->active)
          $this->smarty->assign('layer2', $lang[blockedtitle]);
        else
          $this->smarty->assign('layer2', string_escape($_entry->title));
      }
      
      $this->smarty->display("navbar.tmpl");
      print("\n");
    } 
    
    
    // Prints the footer of the page.
    function _print_footer() {
      global $lang;
      global $cfg;
      
      $holdvars = array_merge($cfg[urlvars], array('forum_id', 'list'));
      $query = "";
      if ($_COOKIE[view] === 'plain') {
        $query[changeview] = 't';
        $order_by_thread   = "?" . build_url($_GET, $holdvars, $query);
        $order_by_time     = '';
      } else {
        $query[changeview] = 'c';
        $order_by_thread   = '';
        $order_by_time     = "?" . build_url($_GET, $holdvars, $query);
      }
      $version[url]  = "http://debain.org/software/tefinch/";
      $version[text] = "Tefinch Forum v0.9.2";
      $this->smarty->assign_by_ref('lang',            $lang);
      $this->smarty->assign_by_ref('order_by_thread', $order_by_thread);
      $this->smarty->assign_by_ref('order_by_time',   $order_by_time);
      $this->smarty->assign_by_ref('version',         $version);
      
      $this->smarty->display("footer.tmpl");
      print("\n");
    }
    
    
    function _show_login() {
      login_print($this->smarty);
    }
    
    
    function print_head() {
      $this->smarty->assign_by_ref('lang', $lang);
      $this->smarty->display("header.tmpl");
      print("\n");
    }
    
    
    function show() {
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
        $this->_message_send();     // A message was posted and should be saved.
      elseif ($_GET['login'])
        $this->_show_login();
      elseif (($_GET['list'] || $_GET['forum_id'])
              && $_COOKIE['view'] === 'plain')
        $this->_list_by_time();     // Show the forum, time order.
      elseif ($_GET['list'] || $_GET['forum_id'])
        $this->_list_by_thread();   // Show the forum, thread order.
      else
        print("internal error");
    }
    
    
    function destroy() {
      $this->db->close();
    }
  }
?>
