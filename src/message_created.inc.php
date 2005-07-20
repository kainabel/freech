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
  
  /* Show created message 
    $_msg_id - id of the created message
  */
  function message_created($_smarty, $_newmsg_id) {
    global $cfg;
    global $lang;
    
    $messageurl = new URL('?', $cfg[urlvars]);
    $messageurl->set_var('read',     1);
    $messageurl->set_var('msg_id',   $_newmsg_id);
    $messageurl->set_var('forum_id', $_GET[forum_id]);
    
    $parenturl = new URL('?', $cfg[urlvars]);
    $parenturl->set_var('read',     1);
    $parenturl->set_var('msg_id',   $_GET[msg_id]);
    $parenturl->set_var('forum_id', $_GET[forum_id]);
    
    $forumurl = new URL('?', $cfg[urlvars]);
    $forumurl->set_var('list',     1);
    $forumurl->set_var('forum_id', $_GET[forum_id]);
    
    // Give some status info and the usual links.
    $_smarty->assign_by_ref('lang',       $lang);
    $_smarty->assign_by_ref('messageurl', $messageurl->get_string());
    $_smarty->assign_by_ref('forumurl',   $forumurl->get_string());
    if ($_GET['msg_id']) 
      $_smarty->assign_by_ref('parenturl',  $parenturl->get_string());
    $_smarty->display('message_created.tmpl');
  }
?>
