<?php
  /*
  Ammerum.
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
  include_once 'mysql_nested.inc.php';
  include_once 'thread_index.inc.php';
  include_once 'thread.inc.php';
  include_once 'httpquery.inc.php';
  include_once 'messages.inc.php';
  
  $db = new AmmerumDB($cfg[db_host], $cfg[db_usr], $cfg[db_pass],
                      $cfg[db_name], $cfg[db_tablebase]);
  //$db->insert_entry(1, 2, "Samuel", "Testtitle4", "Testtext");
  
  $_GET[hs]       = $_GET[hs] ? $_GET[hs] * 1 : 0;
  $_GET[forum_id] = $_GET[forum_id] ? $_GET[forum_id] * 1 : 1;
  parse_str($_SERVER['QUERY_STRING'], $queryvars);
  $holdvars   = array_merge($cfg[urlvars],
                              array('forum_id', 'fold', 'swap', 'hs'));
  print("<html>\n"
      . "<head>\n"
      . "<title>Ammerum2</title>"
      . "</head>\n"
      . "<body bgcolor='#FFFFFF' text='#000000' link='#003399' vlink='#666666'"
      . " alink='#5566DD'>\n");
  // Choose action
  if ($_GET['write'] === '1') {
    // if writing an answer insert the title of the answered message
    if ($_GET['msg_id']) {
      $entry = $db->get_entry($_GET['forum_id'], $_GET['msg_id']);
      // add a 'Re: ' if necessary
      if (strpos($entry->title,$lang[answer]) !== 0 and $entry->title != "") { 
         $re = $lang[answer].$entry->title;
      } else { 
         $re = $entry->title;
      }
    }
    compose('',$re,'','');
  } elseif (($_POST['preview'] === $lang['preview'] || $_POST['send'] === $lang['send'])
       && ( ctype_space($_POST['name']) 
         || ctype_space($_POST['subject']) 
         || ctype_space($_POST['message']) ) ) {
    // Check if everything is filled out
    compose(_escape($_POST['name']),
            _escape($_POST['subject']),
            _escape($_POST['message']),
            $lang['somethingmissing']);
  } elseif ($_POST['preview'] === $lang['preview']) {
    // preview the article, escaping is done in preview()
    preview($_POST['name'],$_POST['subject'],$_POST['message']);
  } elseif ($_POST['edit'] === $lang['change']) {
    // Edit the message
    compose(_escape($_POST['name']),_escape($_POST['subject']),_escape($_POST['message']),'');
  } elseif ($_POST['send'] === $lang['send']) {
    // insert the message into db
    $db->insert_entry($_GET['forum_id'],
                 $_POST['msg_id'] ? $_POST['msg_id'] : 0,
                 _escape($_POST['name']),
                 _escape($_POST['subject']),
                 _escape_msg($_POST['message']));
    // Give some status info and the usual links
    print("<p><h2>$lang[entrysuccess]</h2><br>");
    // Need to know msg_id of created message TODO
    /* print("<a href='?".build_url($queryvars, $holdvars, 
        array('msg_id'=>????,'forum_id'=>$_POST['forum_id'],'read'=>1))
        ."'>$lang[backtoentry]</a><br>"); */
    if ($_POST[msg_id]) 
      print("<a href='?".build_url($queryvars, $holdvars, 
        array('msg_id'=>$_POST[msg_id],'forum_id'=>$_POST[forum_id],'read'=>1))
        ."'>$lang[backtoparent]</a><br>");
    print("<a href='?".build_url($queryvars, $holdvars, 
        array('forum_id'=>$_POST[forum_id],'list'=>1))
        ."'>$lang[backtoindex]</a></p>");
  } elseif ($_POST['quote'] === $lang['quote']) {
    // insert a quote
    $entry = $db->get_entry($_GET['forum_id'], $_GET['msg_id']);
    // add a line "user wrote date" and add "> " at the beginning of each line
    $text = _unescape($entry->name)." ".$lang[wrote]." "._unescape($entry->time)."\n\n"
            .preg_replace("/^/m","> ",_unescape(strip_tags($entry->text)));
    compose(_escape($_POST['name']),
            _escape($_POST['subject']),
            _escape($text."\n\n".$_POST['message']),'');
  } elseif ($_GET['read'] === '1') {
    // read a message
    $entry = $db->get_entry($_GET['forum_id'], $_GET['msg_id']);
    // print top navi-bars
    heading_print($queryvars,$entry->title);
    messageindex_print($queryvars,$entry->id);
    // TODO
    if ($entry->active)
      message_print ($entry->name,$entry->title,$entry->text,$entry->time,$entry->id);
    else
      print "<h2>Gesperrter Beitrag</h2><p>Der an dieser Stelle platzierte "
           ."Kommentar enthielt eine rechtswidrige &Auml;u&szlig;erung oder verletzte "
           ."grob die Nutzungsbedingungen f&uuml;r unsere Diskussionsforen. Er "
           ."ist daher gel&ouml;scht worden.";
    messageindex_print($queryvars,$entry->id);      
  } elseif ($_GET['llist']) {
    $n_threads = $db->get_n_threads($_GET[forum_id]);
    $tpp       = $db->get_n_threads_per_page();
    $ppi       = 5;
    $folding   = new ThreadFolding($_GET[fold], $_GET[swap]);
    heading_print($queryvars,$lang[entryindex]);
    threadindex_print($n_threads, $_GET[hs], $tpp, $ppi, $folding, $queryvars);
    print("<table border=0 width=100% cellpadding=0 cellspacing=0>\n");
    $db->foreach_latest_entry($_GET[forum_id],
                              $tpp,
                              0,
                              print_row_simple,
                              array($folding, $queryvars));
    print("</table>\n");
    threadindex_print($n_threads, $_GET[hs], $tpp, $ppi, $folding, $queryvars);      
  } elseif ($_GET['list'] === '1' || $_GET['forum_id']) {
    // show the message-tree
    $n_threads = $db->get_n_threads($_GET[forum_id]);
    $tpp       = $db->get_n_threads_per_page();
    $ppi       = 5;
    $folding   = new ThreadFolding($_GET[fold], $_GET[swap]);
    heading_print($queryvars,'');
    threadindex_print($n_threads, $_GET[hs], $tpp, $ppi, $folding, $queryvars);
  
    print("<table border=0 width=100% cellpadding=0 cellspacing=0>\n");
    $db->foreach_child($_GET[forum_id],
                       1,
                       $_GET[hs],
                       $folding,
                       print_row,
                       array($folding, $queryvars));
    print("</table>\n");
  
    threadindex_print($n_threads, $_GET[hs], $tpp, $ppi, $folding, $queryvars);
  } else {
    /* Wenn oben aus der Bedingung "|| $_GET['forum_id']" entfernt wird, dann ist
       hier Platz für eine Art Forenübersicht, auf der man zuerst landet und von
       der aus die Foren mit &list=1&forum_id= verlinkt sind. */
    print "internal error";  
  }  
  print("</body>\n"
      . "</html>\n");
  
  $db->close();
?>
