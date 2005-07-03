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
  
  
  // Changes a cookie only if necessary.
  function _forum_set_cookie($_name, $_value) {
    if ($_COOKIE[$_name] != $_value) {
      setcookie($_name, $_value);
      $_COOKIE[$_name] = $_value;
    }
  }
  
  
  function _forum_print_heading($_queryvars, $_entry) {
    global $lang;
    global $cfg;
    
    $holdvars = array_merge($cfg[urlvars], array('forum_id'));
    if ($cfg[remember_page])
      array_push($holdvars, 'hs');
    
    // Print "index".
    print("<table width='100%' cellspacing='0' cellpadding='5' border='0'>\n");
    print("\t<tr>\n");
    print("\t\t<td align='left'>\n");
    print("\t\t<font size='-1'>\n");
    
    $query['list'] = 1;
    $url           = build_url($_queryvars, $holdvars, $query);
    print("&nbsp;&nbsp;<a href='?$url'>Forum</a>");
    if ($_GET['read'] === '1' || $_GET['llist']) {
      if (!$_entry)
        print("&nbsp;&nbsp;&gt;&nbsp;&nbsp;$lang[noentrytitle]");
      elseif (!$_entry->active)
        print("&nbsp;&nbsp;&gt;&nbsp;&nbsp;$lang[blockedtitle]");
      else
        print("&nbsp;&nbsp;&gt;&nbsp;&nbsp;" . string_escape($_entry->title));
    }
    
    print("</font>\n");
    print("\t\t</td>\n");
    
    /* FIXME: only useful if more than one forum exists
    print("\t\t<td align='right'>\n");
    print("\t\t<font size='-1'>\n");
    
    $query = "";
    $query['llist'] = 1;
    print("&nbsp;&nbsp;<a href='?"
          . build_url($_queryvars, $holdvars, $query) . "'>$lang[entryindex]</a>\n");
    print("</font>\n");
    print("\t\t</td>\n");*/
    
    print("\t</tr>\n");
    print("</table>\n");
  } 
  
  
  function _forum_print_footer($_queryvars) {
    global $lang;
    global $cfg;
    print("<table width='100%' cellspacing='0' cellpadding='3' border='0'>\n");
    print("\t<tr>\n");
    print("\t\t<td align='left'>\n");
    print("\t\t<font size='-1'>\n");
    
    $holdvars = array_merge($cfg[urlvars], array('forum_id', 'list'));
    $query = "";
    if ($_COOKIE[view] === 'plain') {
      $query[changeview] = 't';
      $url               = build_url($_queryvars, $holdvars, $query);
      print("<a href='?$url'>$lang[threadview]</a>");
      print("&nbsp;&nbsp;&nbsp;");
      print("$lang[plainview]");
    } else {
      $query[changeview] = 'c';
      $url               = build_url($_queryvars, $holdvars, $query);
      print("$lang[threadview]");
      print("&nbsp;&nbsp;&nbsp;");
      print("<a href='?$url'>$lang[plainview]</a>");
    }
    
    print("</font>\n");
    print("\t\t</td>\n");
    print("\t</tr>\n");
    print("</table>\n");  
  }
  
  
  // Prepare the forum, set cookies, etc. To be called before the http header 
  // was sent.
  function forum_init() {
    if (get_magic_quotes_gpc()) {
      $_GET    = array_map('stripslashes_deep', $_GET);
      $_POST   = array_map('stripslashes_deep', $_POST);
      $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
    }
    $_GET[hs]       = $_GET[hs]       ? $_GET[hs]       * 1 : 0;
    $_GET[forum_id] = $_GET[forum_id] ? $_GET[forum_id] * 1 : 1;
    
    if ($_GET['changeview'] === 't')
      _forum_set_cookie('view', 'thread');
    elseif ($_GET['changeview'] === 'c')
      _forum_set_cookie('view', 'plain');
    
    if ($_GET['showthread'] === '-1')
      _forum_set_cookie('thread', 'hide');
    elseif ($_GET['showthread'] === '1')
      _forum_set_cookie('thread', 'show');
    
    if ($_GET['fold'] === '1') {
      _forum_set_cookie('fold', '1');
      _forum_set_cookie('swap', '');
    } elseif ($_GET['fold'] === '2') {
      _forum_set_cookie('fold', '2');
      _forum_set_cookie('swap', '');
    }
    
    $folding = new ThreadFolding($_COOKIE['fold'], $_COOKIE['swap']);
    if ($_GET['swap']) {
      $folding->swap($_GET['swap']);
      _forum_set_cookie('swap', $folding->get_string());
    }
  }
  
  
  function forum_print() {
    global $cfg;
    global $lang;
    global $err;
    
    $db = new TefinchDB($cfg[db_host],
                        $cfg[db_usr],
                        $cfg[db_pass],
                        $cfg[db_name],
                        $cfg[db_tablebase]);
    $db->set_timeformat($lang[dateformat]);
    
    // Print the page header.
    $holdvars = array_merge($cfg[urlvars], array('forum_id', 'hs'));
  
    // Read a message.
    if ($_GET['read']) {
      $folding    = new ThreadFolding($_COOKIE['fold'], $_COOKIE['swap']);
      $entry      = $db->get_entry($_GET['forum_id'], $_GET['msg_id']);
      $hasthread  = (!$entry->is_toplevel || $entry->n_children != 0);
      _forum_print_heading($_GET, $entry);
      message_index_print($entry->id,
                          $entry->prev_thread,
                          $entry->next_thread,
                          $entry->prev_entry,
                          $entry->next_entry,
                          $hasthread,
                          $entry->active,
                          $_GET);
      message_print($entry);
      if ($hasthread && $_COOKIE[thread] != 'hide')
        thread_print($db, $_GET['forum_id'], $_GET['msg_id'], 0, $folding);
      message_index_print($entry->id,
                          $entry->prev_thread,
                          $entry->next_thread,
                          $entry->prev_entry,
                          $entry->next_entry,
                          $hasthread,
                          $entry->active,
                          $_GET);
    }
    
    // Write an answer.
    elseif ($_GET['write'] && $_GET['msg_id']) {
      $entry = $db->get_entry($_GET[forum_id], $_GET[msg_id]);
      message_compose_reply($entry->title, '', $_GET);
    }
    
    // Write a new message.
    elseif ($_GET['write']) {
      message_compose('', '', '', '', FALSE, $_GET);
    }
    
    // Edit a message.
    elseif ($_POST['edit']) {
      message_compose($_POST['name'],
                      $_POST['subject'],
                      $_POST['message'],
                      '',
                      $_POST[msg_id] ? TRUE : FALSE,
                      $_GET);
    }
    
    // Insert a quote from the parent message.
    // FIXME: String stuff should be moved elsewhere.
    elseif ($_POST['quote']) {
      $entry = $db->get_entry($_GET['forum_id'], $_GET['msg_id']);
      if ($_GET['msg_id'] && $entry->active) {
        // Add a line "user wrote date" and add "> " at the beginning of
        // each line.
        $text = preg_replace("/\[USER\]/", $entry->name, $lang[wrote])
              . " $entry->time\n\n"
              . preg_replace("/^/m","> ",
                             message_wrapline($entry->text)) . "\n";
      }
      $text .= $_POST['message'];
      message_compose($_POST['name'],
                      $_POST['subject'],
                      $text,
                      '',
                      FALSE,
                      $_GET);
    }
    
    // A message preview was requested.
    elseif ($_POST['preview']) {
      $ret = message_preview($_POST['name'],
                             $_POST['subject'],
                             $_POST['message'],
                             $_POST['msg_id'],
                             $_GET);
      if ($ret < 0)
        message_compose($_POST['name'],
                        $_POST['subject'],
                        $_POST['message'],
                        $err[$ret],
                        $_POST[msg_id] ? TRUE : FALSE,
                        $_GET);
    }
    
    // A message was posted and should be saved.
    elseif ($_POST['send']) {
      $new_id = message_submit($db,
                               $_GET[forum_id],
                               $_GET[msg_id],
                               $_POST['name'],
                               $_POST['subject'],
                               $_POST['message']);
      if ($new_id < 0)
        message_compose($_POST['name'],
                        $_POST['subject'],
                        $_POST['message'],
                        $err[$new_id],
                        $_POST[msg_id] ? TRUE : FALSE,
                        $_GET);
      else
        message_created($new_id, $_GET);
    }
    
    // Show the forum, time order.
    elseif (($_GET['list'] || $_GET['forum_id'])
            && $_COOKIE['view'] === 'plain') {
      _forum_print_heading($_GET, '');
      $n_entries = $db->get_n_entries($_GET[forum_id]);
      latest_index_print($n_entries,
                         $_GET[hs],
                         $cfg[epp],
                         $cfg[ppi],
                         '',
                         $_GET);
      latest_print($db);
      latest_index_print($n_entries,
                         $_GET[hs],
                         $cfg[epp],
                         $cfg[ppi],
                         '',
                         $_GET);
      _forum_print_footer($_GET);
    }
    
    // Show the forum, thread order.
    elseif ($_GET['list'] || $_GET['forum_id']) {
      $folding   = new ThreadFolding($_COOKIE['fold'], $_COOKIE['swap']);
      $n_threads = $db->get_n_threads($_GET[forum_id]);
      _forum_print_heading($_GET,'');
      thread_index_print($n_threads,
                         $_GET[hs],
                         $cfg[tpp],
                         $cfg[ppi],
                         $folding,
                         $_GET);
      thread_print($db, $_GET[forum_id], 0, $_GET[hs], $folding);
      thread_index_print($n_threads,
                         $_GET[hs],
                         $cfg[tpp],
                         $cfg[ppi],
                         $folding,
                         $_GET);
      _forum_print_footer($_GET);
    }
    
    else {
    /* Wenn oben aus der Bedingung "|| $_GET[forum_id]" entfernt wird, dann ist
       hier Platz für eine Art Forenübersicht, auf der man zuerst landet und von
       der aus die Foren mit &list=1&forum_id= verlinkt sind. */
      print("internal error");
    }
    
    $db->close();
  }
?>
