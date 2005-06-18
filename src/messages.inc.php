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
    foreach ( explode("\n",$_string) as $line ) {
      if (strpos($line,"> ") === 0) {
        $text .= $line."\n";
      } else {
        $text .= wordwrap($line,80)."\n";
      }
    }
    return preg_replace("/ /","&nbsp;",_escape($text));
  }
  /* print out a form to compose the message
     $_name, $_subject, $_message allow to give default values
     $_hint is useful to print an error message
  */
  function msg_compose ($_name,$_subject,$_message,$_hint,$_queryvars) {
    global $cfg;
    global $lang;
    $holdvars   = array_merge($cfg[urlvars],
                              array('forum_id', 'msg_id', 'fold', 'swap', 'hs'));
    print("<form action='?".build_url($_queryvars, $holdvars,'')
        ."' method='POST'>\n"
        . "<font size='+1' color='red'>$lang[writeamessage]</font>\n");
    if ($_hint) print("<br><font size='+1' color='red'>$_hint</font>\n");
    print("<p><b>$lang[msgtitle]</b>&nbsp;<i>$lang[required]</i><br>\n"
        . "<input type='text' size='80' name='subject' value='"
        ._escape($_subject)."' maxlength='80'>\n"
        . "<p><b>$lang[name]</b>&nbsp;<i>$lang[required]</i><br>\n"
        . "<input type='text' size='80' name='name' value='"
        ._escape($_name)."' maxlength='80'>\n"
        . "<p><b>$lang[msgbody]</b>&nbsp;<i>$lang[required]</i><br>\n"
        . "<textarea name='message' cols='80' rows='20' wrap='virtual'>"
        ._escape($_message)."</textarea><br>\n"
        . "<input type='submit' name='quote' value='$lang[quote]'>&nbsp;\n"
        . "<input type='submit' name='preview' value='$lang[preview]'>&nbsp;\n"
        . "<input type='submit' name='send' value='$lang[send]'>\n"
        . "</form>\n"); 
  }
  /* Show a preview of the message */
  function msg_preview ($_name,$_subject,$_message,$_queryvars) {
    global $lang;
    global $cfg;
    $holdvars   = array_merge($cfg[urlvars],
                              array('forum_id', 'msg_id', 'fold', 'swap', 'hs'));
    print("<p><font color='red' size='+1'>$lang[preview]</font></p>\n"
         ."<p><table border='0' cellpadding='0' cellspacing='0' width='100%'>\n"
         ."<tbody><tr>");
         msg_print($_name,$_subject,$_message,date(preg_replace("/%/","",$cfg[timeformat]),time()),$_queryvars);
    print("<p><form action='?".build_url($_queryvars, $holdvars,'')
        ."' method='POST'>\n"
        . "<input type='hidden' name='name' value='"._escape($_name)."'>\n"
        . "<input type='hidden' name='subject' value='"._escape($_subject)."'>\n"
        . "<input type='hidden' name='message' value='"._escape($_message)."'>\n"
        . "<input type='submit' name='edit' value='$lang[change]'>\n"
        . "<input type='submit' name='send' value='$lang[send]'></p></table>\n");
  }
  /* Show created message 
    $_msg_id - id of the created message
  */
  function msg_created ($_msg_id,$_queryvars) {
    global $lang;
    $holdvars   = array_merge($cfg[urlvars],
                              array('forum_id', 'fold', 'swap', 'hs'));
    // Give some status info and the usual links
    print("<p><h2>$lang[entrysuccess]</h2><br>");
    print("<a href='?".build_url($_queryvars, $holdvars, 
        array('msg_id'=>$_msg_id,'forum_id'=>$_queryvars['forum_id'],'read'=>1))
        ."'>$lang[backtoentry]</a><br>");
    if ($_queryvars['msg_id']) 
      print("<a href='?".build_url($_queryvars, $holdvars, 
        array('msg_id'=>$_queryvars['msg_id'],'forum_id'=>$_queryvars['forum_id'],'read'=>1))
        ."'>$lang[backtoparent]</a><br>");
    print("<a href='?".build_url($_queryvars, $holdvars, 
        array('forum_id'=>$_queryvars['forum_id'],'list'=>1))
        ."'>$lang[backtoindex]</a></p>");  
  }
  /* print out a message well formated
    $_name, $_subject, $_message, $_time - values which are shown
    $_showthread - whether to show (1) or to hide (0) the messagetree
  */
  function msg_print ($_name,$_subject,$_message,$_time,$_queryvars) {
    global $lang;
    global $db;
    print("<p><table border='0' cellpadding='5' cellspacing='0' width='100%'>\n"
         ."<tbody><tr><td><table border='0' cellpadding='0' cellspacing='0' width='100%'>\n"
         ."<tbody><tr valign='middle'><td>\n"
         ."<font color='#555555' size='-1'>$_time</font>\n"
         ."<br><b>"._escape($_subject)."</b><br><i>"._escape($_name)."</i></td><td></td></tr></tbody>\n"
         ."</table></td></tr><tr><td><br>\n"
         .preg_replace("/^(&gt;&nbsp;.*)/m","<font color='red'>$1</font>",nl2br(_escape_msg($_message)))
         ."<br></td></tr></tbody></table></p>\n");
  }
?>
