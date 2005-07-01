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
  include_once 'string.inc.php';
  include_once 'httpquery.inc.php';
  
  include_once 'message.inc.php';
  include_once 'message_index.inc.php';
  include_once 'message_compose.inc.php';
  include_once 'message_preview.inc.php';
  include_once 'message_created.inc.php';
  
  include_once 'thread.inc.php';
  include_once 'thread_index.inc.php';
  
  include_once 'latest.inc.php';
  include_once 'latest_index.inc.php';
  
  $db = new TefinchDB($cfg[db_host], $cfg[db_usr], $cfg[db_pass],
                      $cfg[db_name], $cfg[db_tablebase]);
  $db->set_n_threads_per_page($cfg[tpp]);
  $db->set_timeformat($lang[dateformat]);
  
  if (get_magic_quotes_gpc()) {
    $_GET    = array_map('stripslashes_deep', $_GET);
    $_POST   = array_map('stripslashes_deep', $_POST);
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
  }
  
  $_GET[hs]       = $_GET[hs]       ? $_GET[hs]       * 1 : 0;
  $_GET[forum_id] = $_GET[forum_id] ? $_GET[forum_id] * 1 : 1;
  $_queryvars     = $_GET;
  
  // process cookie-changes
  // FIXME: move elsewhere (httpquery??)
  function cookie_change($_name,$_value) {
    if ($_COOKIE[$_name] != $_value) {
      setcookie($_name,$_value);
      $_COOKIE[$_name] = $_value;
    }
  }
  if ($_GET['changeview'] === 't')
    cookie_change('view','thread');
  elseif ($_GET['changeview'] === 'c')
    cookie_change('view','plain');
  if ($_GET['showthread'] === '-1')
    cookie_change('thread','hide');
  elseif ($_GET['showthread'] === '1')
    cookie_change('thread','show');
  if ($_GET['fold'] === '1') {
    cookie_change('fold','1');
    cookie_change('swap','');
  } elseif ($_GET['fold'] === '2') {
    cookie_change('fold','2');
    cookie_change('swap','');
  }
  $folding = new ThreadFolding($_COOKIE['fold'], $_COOKIE['swap']);
  if ($_GET['swap']) {
    $folding->swap($_GET['swap']);
    cookie_change('swap', $folding->get_string());
  }
  
  // Print the page header.
  $holdvars = array_merge($cfg[urlvars],
                          array('forum_id', 'hs'));
  header("Content-Type: text/html; charset=utf-8");
  print("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"".
        "\"http://www.w3.org/TR/html4/loose.dtd\">");
  print("<html>\n"
      . "<head>\n"
      . "<meta http-equiv=Content-Type content=\"text/html; charset=utf-8\">\n"
      . "<title>Tefinch</title>"
      . "</head>\n"
      . "<body bgcolor='#FFFFFF' text='#000000' link='#003399' vlink='#666666'"
      . " alink='#5566DD'>\n");
  
  // Choose an action  
  if ($_queryvars['write'] == '1' AND $_queryvars['msg_id']) {
    // Write an answer.
    $entry = $db->get_entry($_queryvars[forum_id], $_queryvars[msg_id]);
    message_compose_reply($entry->title, '', $_queryvars);
  } elseif ($_queryvars['write'] == '1') {
    message_compose('', '', '', '', FALSE, $_queryvars);
  } elseif (($_POST['preview'] || $_POST['send']) &&
            ( ctype_space($_POST['name'])
           || ctype_space($_POST['subject'])
           || ctype_space($_POST['message']) ) ) {
    message_compose($_POST['name'],
                    $_POST['subject'],
                    $_POST['message'],
                    $lang['somethingmissing'],
                    $_POST[msg_id] ? TRUE : FALSE,
                    $_queryvars);
  } elseif (($_POST['preview'] || $_POST['send']) &&
            ( strlen($_POST['message']) > $cfg[max_msglength])) {
    message_compose($_POST['name'],
                    $_POST['subject'],
                    $_POST['message'],
                    $lang['messagetoolong'],
                    $_POST[msg_id] ? TRUE : FALSE,
                    $_queryvars);
  } elseif (($_POST['preview'] || $_POST['send']) && 
            ( strlen($_POST['name']) > $cfg[max_namelength])) {
    message_compose($_POST['name'],
                    $_POST['subject'],
                    $_POST['message'],
                    $lang['nametoolong'],
                    $_POST[msg_id] ? TRUE : FALSE,
                    $_queryvars);
  } elseif (($_POST['preview'] || $_POST['send']) && 
            ( strlen($_POST['subject']) > $cfg[max_titlelength])) {
    message_compose($_POST['name'],
                    $_POST['subject'],
                    $_POST['message'],
                    $lang['titletoolong'],
                    $_POST[msg_id] ? TRUE : FALSE,
                    $_queryvars);
  } elseif ($_POST['preview']) {
    // Preview the article.
    message_preview($_POST['name'],
                    $_POST['subject'],
                    $_POST['message'],
                    $_POST['msg_id'],
                    $_queryvars);
  } elseif ($_POST['edit']) {
    // Edit the message.
    message_compose($_POST['name'],
                    $_POST['subject'],
                    $_POST['message'],
                    '',
                    $_POST[msg_id] ? TRUE : FALSE,
                    $_queryvars);
  } elseif ($_POST['send']) {
    // Insert the message into db.
    $newmsg_id = $db->insert_entry($_queryvars['forum_id'],
                                   $_queryvars['msg_id'],
                                   $_POST['name'],
                                   $_POST['subject'],
                                   message_wrapline($_POST['message']));
    message_created($newmsg_id, $_queryvars);
  } elseif ($_POST['quote']) {
    // Insert a quote.
    $entry = $db->get_entry($_queryvars['forum_id'], $_queryvars['msg_id']);
    if ($_queryvars['msg_id'] && $entry->active) {
      // Add a line "user wrote date" and add "> " at the beginning of each line
      $text = strip_tags($entry->text);
      $text = string_unescape($entry->name)
            . " " . $lang[wrote]
            . " " . string_unescape($entry->time) . "\n\n"
            . preg_replace("/^/m","> ", string_unescape($text)) . "\n\n";
    }
    $text .= $_POST['message'];
    message_compose($_POST['name'], $_POST['subject'], $text, '', FALSE, $_queryvars);
  } elseif ($_queryvars['read'] === '1') {
    // read a message
    $entry    = $db->get_entry($_queryvars['forum_id'], $_queryvars['msg_id']);
    $haschild = !($entry->id == $entry->threadid && $entry->n_children == 0);
    // print treeview or not
    //$folding = new ThreadFolding($_queryvars[fold], $_queryvars[swap]);
    if ($_COOKIE[thread] === 'hide' OR ! $haschild)
      $thread = 0;
    else
      $thread = 1;
    // print top navi-bars
    if ($entry->active)
      heading_print($_queryvars,string_escape($entry->title));
    elseif (!$entry)
      heading_print($_queryvars,$lang[noentrytitle]);    
    else
      heading_print($_queryvars,$lang[blockedtitle]);
    message_index_print($entry->id,
                        $entry->prev_thread,
                        $entry->next_thread,
                        $entry->prev_entry,
                        $entry->next_entry,
                        $haschild,
                        $_queryvars);
    if ($entry->active) {
      message_print(string_unescape($entry->name),
                    string_unescape($entry->title),
                    string_unescape($entry->text),
                    $entry->time,
                    $_queryvars);
      if ($thread) {
        print("<tr><td><table border='0' cellpadding='0' cellspacing='0' width='100%'>");
        $folding = new ThreadFolding(0,0);
        $db->foreach_child_in_thread($_queryvars['forum_id'],
                                     $_queryvars['msg_id'],
                                     0,
                                     $folding,
                                     thread_print_row,
                                     array($folding, $_queryvars));
        print("</table></td></tr>");
      }
    } elseif (! $entry) {
      message_print ('',$lang[noentrytitle],$lang[noentrybody],'',$_queryvars);
    } else {
      message_print ('',
                     $lang[blockedtitle],
                     $lang[blockedentry],
                     '',
                     $_queryvars);
    }
    message_index_print($entry->id,
                        $entry->prev_thread,
                        $entry->next_thread,
                        $entry->prev_entry,
                        $entry->next_entry,
                        $haschild,
                        $_queryvars);      
  } elseif (($_queryvars['list'] || $_queryvars['forum_id'] ) && $_COOKIE['view'] === 'plain') {
    heading_print($_queryvars,'');
    $n_entries = $db->get_n_entries($_queryvars[forum_id]);
    $tpp = $db->get_n_threads_per_page();
    latest_index_print($n_entries,
                       $_queryvars[hs],
                       $tpp,
                       $cfg[ppi],
                       '',
                       $_queryvars);
    print("<table border=0 width=100% cellpadding=0 cellspacing=0>\n");
    $db->foreach_latest_entry($_queryvars[forum_id],
                              $_queryvars[hs],
                              FALSE,
                              latest_print_row,
                              $_queryvars);
    print("</table>\n");
    latest_index_print($n_entries,
                       $_queryvars[hs],
                       $tpp,
                       $cfg[ppi],
                       '',
                       $_queryvars);
    footer_print($_queryvars);
  } elseif ($_queryvars['list'] === '1' || $_queryvars['forum_id']) {
    // show the message-tree
    $n_threads = $db->get_n_threads($_queryvars[forum_id]);
    $tpp       = $db->get_n_threads_per_page();
    //$folding   = new ThreadFolding($_queryvars[fold], $_queryvars[swap]);
    heading_print($_queryvars,'');
    thread_index_print($n_threads, $_queryvars[hs], $tpp, $cfg[ppi], $folding, $_queryvars);
    print("<table border=0 width=100% cellpadding=0 cellspacing=0>\n");
    $db->foreach_child($_queryvars[forum_id],
                       0,
                       $_queryvars[hs],
                       $folding,
                       thread_print_row,
                       array($folding, $_queryvars));
    if ($n_threads == 0) {
      print("<tr><td height='4'></td></tr>");
      print("<tr><td align='center'><i>$lang[noentries]</i></td></tr>");
      print("<tr><td height='4'></td></tr>");
    }
    print("</table>\n");
  
    thread_index_print($n_threads, $_queryvars[hs], $tpp, $cfg[ppi], $folding, $_queryvars);
    footer_print($_queryvars);    
  } else {
    /* Wenn oben aus der Bedingung "|| $_queryvars['forum_id']" entfernt wird, dann ist
       hier Platz für eine Art Forenübersicht, auf der man zuerst landet und von
       der aus die Foren mit &list=1&forum_id= verlinkt sind. */
    print "internal error";  
  }  
  print("</body>\n"
      . "</html>\n");
  
  $db->close();
?>
