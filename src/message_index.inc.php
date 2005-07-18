<?php
  /*
  Tefinch.
  Copyright (C) 2003 Samuel Abels, <spam debain org>

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
  if (preg_match("/^[a-z0-9_]+$/i", $cfg[lang]))
    include_once "language/$cfg[lang].inc.php";
  
  /* Prints the indexbar that is shown above a single entry out.
   * Args: $_prev_thread_id  The id of the previous thread, if any.
   *       $_next_thread_id  The id of the next thread, if any.
   *       $_prev_entry_id   The id of the previous entry, if any.
   *       $_next_entry_id   The id of the next entry, if any.
   *       $_has_thread      Set TRUE, unless the entry is a leaf parent.
   *       $_queryvars       Variables that are appended to every link.
   */
  function message_index_print($_smarty,
                               $_msg_id,
                               $_prev_thread_id,
                               $_next_thread_id,
                               $_prev_entry_id,
                               $_next_entry_id,
                               $_has_thread,
                               $_can_answer) {
    global $lang;
    global $cfg;
    
    $_smarty->clear_all_assign();
    $holdvars = array_merge($cfg[urlvars], array('forum_id'));
    if ($cfg[remember_page])
      array_push($holdvars, 'hs');
    
    // "Previous/Next Entry" buttons.
    $preventry[text] = "&lt;&lt;";
    if ($_prev_entry_id > 0) {
      $query          = "";
      $query[msg_id]  = $_prev_entry_id * 1;
      $query[read]    = 1;
      $preventry[url] = "?" . build_url($_GET, $holdvars, $query);
    }
    $nextentry[text] = "&gt;&gt;";
    if ($_next_entry_id > 0) {
      $query          = "";
      $query[msg_id]  = $_next_entry_id * 1;
      $query[read]    = 1;
      $nextentry[url] = "?" . build_url($_GET, $holdvars, $query);
    }
    $_smarty->assign_by_ref('preventry', $preventry);
    $_smarty->assign_by_ref('entry',     $lang[entry]);
    $_smarty->assign_by_ref('nextentry', $nextentry);
    
    // "Previous/Next Thread" buttons.
    $prevthread[text] = "&lt;&lt;";
    if ($_prev_thread_id > 0) {
      $query           = "";
      $query[msg_id]   = $_prev_thread_id * 1;
      $query[read]     = 1;
      $prevthread[url] = "?" . build_url($_GET, $holdvars, $query);
    }
    $nextthread[text] = "&gt;&gt;";
    if ($_next_thread_id > 0) {
      $query           = "";
      $query[msg_id]   = $_next_thread_id * 1;
      $query[read]     = 1;
      $nextthread[url] = "?" . build_url($_GET, $holdvars, $query);
    }
    $_smarty->assign_by_ref('prevthread', $prevthread);
    $_smarty->assign_by_ref('thread',     $lang[thread]);
    $_smarty->assign_by_ref('nextthread', $nextthread);
    
    // "Reply" button.
    if ($_can_answer) {
      $query         = "";
      $query[write]  = 1;
      $query[msg_id] = $_GET[msg_id];
      $answer[text]  = $lang[writeanswer];
      $answer[url]   = "?" . build_url($_GET, $holdvars, $query);
      $_smarty->assign_by_ref('answer', $answer);
    }
    
    // "New Thread" button.
    $query           = "";
    $query[write]    = 1;
    $newthread[text] = $lang[writemessage];
    $newthread[url]  = "?" . build_url($_GET, $holdvars, $query);
    $_smarty->assign_by_ref('new_thread', $newthread);
    
    // "Show/Hide Thread" button.
    if ($_has_thread) {
      $query       = "";
      $query[read] = 1;
      array_push($holdvars, 'msg_id');
      if ($_COOKIE[thread] === 'hide') {
        $query[showthread] = '1';
        $showthread[text]  = $lang[showthread];
        $showthread[url]   = "?" . build_url($_GET, $holdvars, $query);
      }
      else {
        $query[showthread] = '-1';
        $showthread[text]  = $lang[hidethread];
        array_push($holdvars, 'msg_id');
        $showthread[url]   = "?" . build_url($_GET, $holdvars, $query);
      }
      $_smarty->assign_by_ref('togglethread', $showthread);
    }
    
    $_smarty->display('message_index.tmpl');
  } 
?>
