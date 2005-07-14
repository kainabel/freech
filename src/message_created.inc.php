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
  
  /* Show created message 
    $_msg_id - id of the created message
  */
  function message_created($_smarty, $_newmsg_id) {
    global $cfg;
    global $lang;
    $holdvars      = array_merge($cfg[urlvars], array('forum_id', 'hs'));
    $query         = array('list' => 1, 'forum_id' => $_GET['forum_id']);
    $forumurl      = "?" . build_url($_GET, $holdvars, $query);
    $query         = array('forum_id' => $_GET[forum_id],
                           'msg_id'   => $_newmsg_id,
                           'read'     => 1);
    $messageurl    = "?" . build_url($_GET, $holdvars, $query);
    $query         = array('forum_id' => $_GET[forum_id],
                           'msg_id'   => $_GET[msg_id],
                           'read'     => 1);
    $parenturl     = "?" . build_url($_GET, $holdvars, $query);
    
    // Give some status info and the usual links.
    $_smarty->assign_by_ref('lang',       $lang);
    $_smarty->assign_by_ref('messageurl', $messageurl);
    $_smarty->assign_by_ref('forumurl',   $forumurl);
    if ($_GET['msg_id']) 
      $_smarty->assign_by_ref('parenturl',  $parenturl);
    $_smarty->display('message_created.tmpl');
  }
?>
