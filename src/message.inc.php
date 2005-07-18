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
  if (preg_match("/^[a-z0-9_]+$/i", $cfg[lang]))
    include_once "language/$cfg[lang].inc.php";
  include_once "string.inc.php";
  include_once "error.inc.php";
  
  /* Escape special characters in the posting, wrap lines */
  // NOT private.
  function message_wrapline($_string) {
    global $cfg;
    foreach ( explode("\n",$_string) as $line ) {
      if (strpos($line,"> ") === 0) {
        $text .= $line . "\n";
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
    $_string = message_wrapline($_string);
    $_string = string_escape($_string);
    $_string = preg_replace("/ /", "&nbsp;", $_string);
    $_string = nl2br($_string);
    $_string = preg_replace("/^(&gt;&nbsp;.*)/m",
                            "<font color='#990000'>$1</font>",
                            $_string);
    return $_string;
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
  function message_print($_smarty, $_entry) {
    global $lang;
    
    if (!$_entry) {
      $subject = $lang[noentrytitle];
      $body    = message_format($lang[noentrybody]);
    }
    elseif (!$_entry->active) {
      $subject = $lang[blockedtitle];
      $body    = message_format($lang[blockedentry]);
    }
    else {
      $name    = $_entry->name;
      $subject = $_entry->title;
      $body    = message_format($_entry->text);
    }
    
    $_smarty->assign_by_ref('time',    $_entry->time);
    $_smarty->assign_by_ref('subject', $subject);
    $_smarty->assign_by_ref('name',    $name);
    $_smarty->assign_by_ref('body',    $body);
    $_smarty->display('message.tmpl');
  }
?>
