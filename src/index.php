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
  include_once 'mysql_nested.inc.php';
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
  $db->set_timeformat($lang[dateformat]);
  
  if (get_magic_quotes_gpc()) {
    $_GET    = array_map('stripslashes_deep', $_GET);
    $_POST   = array_map('stripslashes_deep', $_POST);
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
  }
  
  $_GET[hs]       = $_GET[hs]       ? $_GET[hs]       * 1 : 0;
  $_GET[forum_id] = $_GET[forum_id] ? $_GET[forum_id] * 1 : 1;
  $_queryvars     = $_GET;
  
  // Print the page header.
  //parse_str($_SERVER['QUERY_STRING'], $_queryvars);
  $holdvars = array_merge($cfg[urlvars],
                          array('forum_id', 'fold', 'swap', 'hs', 'thread'));
  print("<html>\n"
      . "<head>\n"
      . "<title>Tefinch</title>"
      . "</head>\n"
      . "<body bgcolor='#FFFFFF' text='#000000' link='#003399' vlink='#666666'"
      . " alink='#5566DD'>\n");
  
  // Write an answer.
  if ($_queryvars['write'] == '1' AND $_queryvars['msg_id']) {
    $entry = $db->get_entry($_queryvars[forum_id], $_queryvars[msg_id]);
    message_reply($entry->title, '', $_queryvars);
  // Send, with incomplete data.
  } elseif (($_POST['preview'] || $_POST['send'])
           && ( ctype_space($_POST['name'])
             || ctype_space($_POST['subject'])
             || ctype_space($_POST['message']) ) ) {
    message_compose($_POST['name'],
                    $_POST['subject'],
                    $_POST['message'],
                    $lang['somethingmissing'],
                    $_queryvars);
  } elseif ($_POST['preview'] === $lang['preview']) {
    // Preview the article.
    message_preview($_POST['name'],$_POST['subject'],$_POST['message'],$_queryvars);
  } elseif ($_POST['edit'] === $lang['change']) {
    // Edit the message.
    message_compose($_POST['name'],$_POST['subject'],$_POST['message'],'',$_queryvars);
  } elseif ($_POST['send'] === $lang['send']) {
    // Insert the message into db.
    $newmsg_id = $db->insert_entry($_queryvars['forum_id'],
                                   $_queryvars['msg_id'],
                                   $_POST['name'],
                                   $_POST['subject'],
                                   $_POST['message']);
    message_created($newmsg_id, $_queryvars);
  } elseif ($_POST['quote'] === $lang['quote']) {
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
    message_compose($_POST['name'], $_POST['subject'], $text, '', $_queryvars);
  } elseif ($_queryvars['read'] === '1') {
    // read a message
    $entry = $db->get_entry($_queryvars['forum_id'], $_queryvars['msg_id']);
    // TODO: geht das einfacher? bsp: $entry->rgt - $entry->lgt ??
    if ($db->get_n_children($_queryvars['forum_id'],
                            $db->_get_threadid($db->tablebase.$_queryvars['forum_id'],
                            $_queryvars['msg_id'])) > 1) $haschild = 1;
                      else $haschild = 0;
    // print treeview or not
    $folding   = new ThreadFolding($_queryvars[fold], $_queryvars[swap]);
    if ($_queryvars['thread'] === "0" || ! $haschild)
      $thread = 0;
    elseif ($_queryvars['thread'] === '1')
      $thread = 1;
    elseif ($folding->is_folded($_queryvars['msg_id'])) {
      $thread = 0;
      $_queryvars['thread'] = '0';
    } else
      $thread = 1; 
    // print top navi-bars
    if ($entry->active)
      heading_print($_queryvars,$entry->title);
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
      message_print ('',$lang[noentrytitle],$lang[noentrybody],'',0,$_queryvars);
    } else {
      message_print ('',
                     $lang[blockedtitle],
                     $lang[blockedentry],
                     '',
                     $thread,
                     $_queryvars);
    }
    message_index_print($entry->id,
                        $entry->prev_thread,
                        $entry->next_thread,
                        $entry->prev_entry,
                        $entry->next_entry,
                        $haschild,
                        $_queryvars);      
  } elseif ($_queryvars['llist']) {
    heading_print($_queryvars,'');
    latest_index_print(); //FIXME
    print("<table border=0 width=100% cellpadding=0 cellspacing=0>\n");
    $db->foreach_latest_entry($_queryvars[forum_id],
                              $_queryvars[hs],
                              FALSE,
                              latest_print_row,
                              $_queryvars);
    print("</table>\n");
    latest_index_print(); //FIXME
    footer_print($queryvars);
  } elseif ($_queryvars['list'] === '1' || $_queryvars['forum_id']) {
    // show the message-tree
    $n_threads = $db->get_n_threads($_queryvars[forum_id]);
    $tpp       = $db->get_n_threads_per_page();
    $ppi       = 5;
    $folding   = new ThreadFolding($_queryvars[fold], $_queryvars[swap]);
    heading_print($_queryvars,'');
    thread_index_print($n_threads, $_queryvars[hs], $tpp, $ppi, $folding, $_queryvars);
    print("<table border=0 width=100% cellpadding=0 cellspacing=0>\n");
    $db->foreach_child($_queryvars[forum_id],
                       1,
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
  
    thread_index_print($n_threads, $_queryvars[hs], $tpp, $ppi, $folding, $_queryvars);
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
