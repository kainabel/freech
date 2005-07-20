<?php
  /*
  Tefinch.
  Copyright (C) 2003 Samuel Abels, <spam debain org>
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
?>
<?php
  function login_print($_smarty) {
    global $lang;
    $hint = $lang[havetoregister] . " " . $lang[passwdforgotten];
    
    $holdvars = array_merge($cfg[urlvars], array('forum_id', 'msg_id', 'hs'));
    $action = "?" . build_url($_GET, $holdvars, array('do_login' => '1'));
    
    $_smarty->assign_by_ref('hint',$hint);
    $_smarty->assign_by_ref('action',$action);
    $_smarty->assign_by_ref('enter_user_data',$lang[enteruserdata]);
    $_smarty->assign_by_ref('username',$lang[username]);
    $_smarty->assign_by_ref('password',$lang[passwd]);
    $_smarty->assign_by_ref('remember_pass',$lang[rememberpasswd]);
    $_smarty->assign_by_ref('remember_pass_long',$lang[remembpasswdlong]);
    $_smarty->assign_by_ref('login',$lang[login]);
    
    $_smarty->display('login.tmpl');
  }
  
?>
