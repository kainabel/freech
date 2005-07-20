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
  function message_format($_string) {
    $_string = wordwrap_smart($_string);
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
  function message_print($_smarty, $_message) {
    global $lang;
    
    if (!$_message) {
      $subject = $lang[noentrytitle];
      $body    = message_format($lang[noentrybody]);
    }
    elseif (!$_message->is_active()) {
      $subject = $lang[blockedtitle];
      $body    = message_format($lang[blockedentry]);
      $time    = $_message->get_created_time();
    }
    else {
      $name    = $_message->get_username();
      $subject = $_message->get_subject();
      $body    = message_format($_message->get_body());
      $time    = $_message->get_created_time();
    }
    
    $_smarty->assign_by_ref('time',    $time);
    $_smarty->assign_by_ref('subject', $subject);
    $_smarty->assign_by_ref('name',    $name);
    $_smarty->assign_by_ref('body',    $body);
    $_smarty->display('message.tmpl');
  }
?>
