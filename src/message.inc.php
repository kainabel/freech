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
  include_once "error.inc.php";
  
  /* Escape special characters in the posting, wrap lines */
  // NOT private.
  
  function message_wrapline($_string) {
    global $cfg;
    foreach ( explode("\n",$_string) as $line ) {
      if (strpos($line,"> ") === 0) {
        $text .= $line."\n";
      } else {
        $text .= wordwrap(wordwrap($line, $cfg[max_linelength_soft]),
                          $cfg[max_linelength_hard],
                          "\n",
                          TRUE) . "\n";
      }
    }
    return $text;
  }
  
  function message_format($_string) {
    return nl2br(preg_replace("/ /","&nbsp;", string_escape(message_wrapline($_string))));
  }
  
  
  function message_check($_name, $_subject, $_message) {
    global $cfg;
    
    if (ctype_space($_name)
     || ctype_space($_subject)
     || ctype_space($_message))
      return ERR_MESSAGE_INCOMPLETE;
    
    if (strlen($_name) > $cfg[max_namelength])
      return ERR_MESSAGE_NAME_TOO_LONG;
    
    if (strlen($_title) > $cfg[max_titlelength])
      return ERR_MESSAGE_TITLE_TOO_LONG;
    
    if (strlen($_message) > $cfg[max_msglength])
      return ERR_MESSAGE_BODY_TOO_LONG;
    
    return 0;
  }
  
  
  /* print out a message well formated
    $_name, $_subject, $_message, $_time - values which are shown
  */
  function message_print ($_name,$_subject,$_message,$_time,$_queryvars) {
    global $lang;
    $message = preg_replace("/^(&gt;&nbsp;.*)/m",
                            "<font color='#990000'>$1</font>",
                            message_format($_message));
    print("\n<p>\n"
        . "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n"
        . " <tr valign='middle'>\n"
        . "  <td>\n"
        . "   <font color='#555555' size='-1'>$_time</font><br>\n"
        . "   <b>" . string_escape($_subject) . "</b><br>\n"
        . "   <i>" . string_escape($_name)    . "</i>\n"
        . "  </td>\n"
        . " </tr>\n"
        . " <tr>\n"
        . "  <td><br>\n"
        . "<!-- message body -->\n"
        . $message
        . "<!-- end message body -->\n"
        . "   <br>\n"
        . "  </td>\n"
        . " </tr>\n"
        . "</table>\n");
  }
?>
