<?php
  /*
  Tefinch.
  Copyright (C) 2005 Robert Weidlich, <tefinch xenim de>

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
  include_once "language/$cfg[lang].inc.php";
  include_once "string.inc.php";
  
  /* Escape special characters in the posting, wrap lines */
  // NOT private.
  
  function message_wrapline($_string) {
    global $cfg;
    foreach ( explode("\n",$_string) as $line ) {
      if (strpos($line,"> ") === 0) {
        $text .= $line."\n";
      } else {
        $text .= wordwrap(wordwrap($line,$cfg[linelength]),
                          ceil($cfg[linelength]*1.25),"\n",TRUE)."\n";
      }
    }
    return $text;
  }
  
  function message_format($_string) {
    return nl2br(preg_replace("/ /","&nbsp;", string_escape(message_wrapline($_string))));
  }
  
  
  /* print out a message well formated
    $_name, $_subject, $_message, $_time - values which are shown
  */
  function message_print ($_name,$_subject,$_message,$_time,$_queryvars) {
    global $lang;
    print("<p><table border='0' cellpadding='5' cellspacing='0' width='100%'>\n"
         ."<tbody><tr><td><table border='0' cellpadding='0' cellspacing='0' width='100%'>\n"
         ."<tbody><tr valign='middle'><td>\n"
         ."<font color='#555555' size='-1'>$_time</font>\n"
         ."<br><b>".string_escape($_subject)."</b><br><i>".string_escape($_name)."</i></td><td></td></tr></tbody>\n"
         ."</table></td></tr><tr><td><br>\n"
         .preg_replace("/^(&gt;&nbsp;.*)/m","<font color='red'>$1</font>", message_format($_message))
         ."<br></td></tr></tbody></table></p>\n");
  }
?>
