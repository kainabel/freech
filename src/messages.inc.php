<?php
  /*
  Tefinch.
  Copyright (C) 2005 Samuel Abels, <spam debain org>
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

  include_once "language/$cfg[lang].inc.php";
  include_once "mysql_nested.inc.php";

  function _escape($_string) {
    return htmlentities($_string,ENT_QUOTES);    
  }
  function _unescape($_string) {
    return html_entity_decode($_string,ENT_QUOTES);
  }
  /* Escape special characters in the posting, wrap lines, if not quoted,
     make quotes red */
  function _escape_msg($_string) {
    foreach ( explode("\n",_escape($_string)) as $line ) {
      if (strpos($line,"&gt; ") === 0) {
        $text .= $line."\n";
      } else {
        $text .= wordwrap($line,80)."\n";
      }
    }
    return preg_replace("/^(&gt;&nbsp;.*)/m","<font color='red'>$1</font>",
           nl2br(
           preg_replace("/ /","&nbsp;",$text)));
  }
  /* print out a form to compose the message
     $_name, $_subject, $_message allow to give default values
     $_hint is useful to print an error message
  */
  function msg_compose ($_name,$_subject,$_message,$_hint) {
    global $cfg;
    global $lang;
    global $queryvars;
    $holdvars   = array_merge($cfg[urlvars],
                              array('forum_id', 'msg_id', 'fold', 'swap', 'hs'));
    print("<form action='?".build_url($queryvars, $holdvars, '')."' method='POST'>\n"
        . "<font size='+1' color='red'>$lang[writeamessage]</font>\n");
    if ($_hint) print("<br><font size='+1' color='red'>$_hint</font>\n");
    print("<p><b>$lang[msgtitle]</b>&nbsp;<i>$lang[required]</i><br>\n"
        . "<input type='text' size='80' name='subject' value='$_subject' maxlength='80'>\n"
        . "<p><b>$lang[name]</b>&nbsp;<i>$lang[required]</i><br>\n"
        . "<input type='text' size='80' name='name' value='$_name' maxlength='80'>\n"
        . "<p><b>$lang[msgbody]</b>&nbsp;<i>$lang[required]</i><br>\n"
        . "<textarea name='message' cols='80' rows='20' wrap='virtual'>$_message</textarea><br>\n"
        . ( $_GET[forum_id] ? "<input type='hidden' name='forum_id' value='$_GET[forum_id]'>\n" :
          "<input type='hidden' name='forum_id' value='$_POST[forum_id]'>\n" )
        . ( $_GET[msg_id] ? 
          "<input type='hidden' name='msg_id' value='$_GET[msg_id]'>\n" :
          "<input type='hidden' name='msg_id' value='$_POST[msg_id]'>\n" )
        . "<input type='submit' name='quote' value='$lang[quote]'>&nbsp;\n"
        . "<input type='submit' name='preview' value='$lang[preview]'>&nbsp;\n"
        . "<input type='submit' name='send' value='$lang[send]'>\n"
        . "</form>\n"); 
  }
  /* Show a preview of the message */
  function msg_preview ($_name,$_subject,$_message) {
    global $lang;
    global $queryvars;    
    $holdvars   = array_merge($cfg[urlvars],
                              array('forum_id', 'msg_id', 'fold', 'swap', 'hs'));
    print("<p><font color='red' size='+1'>$lang[preview]</font></p>\n"
         ."<p><table border='0' cellpadding='0' cellspacing='0' width='100%'>\n"
         ."<tbody><tr>");
         msg_print(_escape($_name),
                       _escape($_subject),
                       _escape_msg($_message),
                       time(),
                       $_POST[msg_id],
                       $_POST[forum_id]);
    print("<p><form action='?".build_url($queryvars, $holdvars, $query)."' method='POST'>\n"
        . "<input type='hidden' name='name' value='"._escape($_name)."'>\n"
        . "<input type='hidden' name='subject' value='"._escape($_subject)."'>\n"
        . "<input type='hidden' name='forum_id' value='$_POST[forum_id]'>\n"
        . ($_POST[msg_id] ? "<input type='hidden' name='msg_id' value='$_POST[msg_id]'>\n" : "" )
        . "<input type='hidden' name='message' value='"._escape($_message)."'>\n"
        . "<input type='submit' name='edit' value='$lang[change]'>\n"
        . "<input type='submit' name='send' value='$lang[send]'></p></table>\n");
  }
  function msg_created ($_queryvars,$_holdvars,$_forum,$_parent_msg,$_msg_id) {
    global $lang;
    // Give some status info and the usual links
    print("<p><h2>$lang[entrysuccess]</h2><br>");
    print("<a href='?".build_url($_queryvars, $_holdvars, 
        array('msg_id'=>$_msg_id,'forum_id'=>$_forum,'read'=>1))
        ."'>$lang[backtoentry]</a><br>");
    if ($_parent_msg) 
      print("<a href='?".build_url($_queryvars, $_holdvars, 
        array('msg_id'=>$_parent_msg,'forum_id'=>$_forum,'read'=>1))
        ."'>$lang[backtoparent]</a><br>");
    print("<a href='?".build_url($_queryvars, $_holdvars, 
        array('forum_id'=>$_forum,'list'=>1))
        ."'>$lang[backtoindex]</a></p>");  
  }
  /* print out a message well formated */
  function msg_print ($_name,$_subject,$_message,$_time,$_msg_id,$_forum_id) {
    global $lang;
    global $queryvars;
    global $db;
    print("<p><table border='0' cellpadding='5' cellspacing='0' width='100%'>\n"
         ."<tbody><tr><td><table border='0' cellpadding='0' cellspacing='0' width='100%'>\n"
         ."<tbody><tr valign='middle'><td>\n"
         ."<font color='#555555' size='-1'>$_time</font>\n"
         ."<br><b>$_subject</b><br><i>$_name</i></td><td></td></tr></tbody>\n"
         ."</table></td></tr><tr><td><br>\n"
         .$_message
         ."<br></td></tr><tr><td><table border='0' cellpadding='0' cellspacing='0' width='100%'>");
    $folding   = new ThreadFolding($_GET[fold], $_GET[swap]);
    $db->foreach_child_in_thread($_forum_id,
                                 $_msg_id,
                                 0,
                                 $folding,
                                 print_row,
                                 array($folding,$_queryvars));
    print("</table></td></tr></tbody></table></p>\n");
  }
?>
