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
  include_once "config.inc.php";
  include_once "language/$cfg[lang].inc.php";
  include_once "string.inc.php";
  include_once "message.inc.php";
  
  /* Show a preview of the message */
  function message_preview($_smarty,
                           $_name,
                           $_subject,
                           $_message,
                           $_msg_id) {
    global $cfg;
    global $lang;
    
    $err = message_check($_name, $_subject, $_message);
    if ($err)
      return $err;
    
    $holdvars = array_merge($cfg[urlvars], array('forum_id', 'msg_id', 'hs'));
    $url      = "?" . build_url($_GET, $holdvars, '');
    $time     = date(preg_replace("/%/","", $lang[dateformat]), time());
    
    $_smarty->assign_by_ref('title',   $lang[preview]);
    $_smarty->assign_by_ref('action',  $url);
    $_smarty->assign_by_ref('time',    $time);
    $_smarty->assign_by_ref('subject', $_subject);
    $_smarty->assign_by_ref('name',    $_name);
    $_smarty->assign_by_ref('message', string_escape($_message));
    $_smarty->assign_by_ref('body',    message_format($_message));
    $_smarty->assign_by_ref('msg_id',  $_msg_id);
    $_smarty->assign_by_ref('edit',    $lang[change]);
    $_smarty->assign_by_ref('send',    $lang[send]);
    $_smarty->display('message_preview.tmpl');
    
    return 0;
  }
?>
