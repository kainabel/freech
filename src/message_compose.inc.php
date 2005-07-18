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
  
  /* Print out a form to compose the message.
   * $_name, $_subject, $_message allow to give default values
   * $_hint is useful to print an error message
   * $_quotebutton: When TRUE, the "Quote" button is shown.
   */
  function message_compose($_smarty,
                           $_name,
                           $_subject,
                           $_message,
                           $_hint,
                           $_quotebutton) {
    global $cfg;
    global $lang;
    $holdvars = array_merge($cfg[urlvars], array('forum_id', 'msg_id', 'hs'));
    $action = "?" . build_url($_GET, $holdvars, '');
    
    $_smarty->assign_by_ref('lang',            $lang);
    $_smarty->assign_by_ref('action',          $action);
    $_smarty->assign_by_ref('hint',            $_hint);
    $_smarty->assign_by_ref('subject',         $_subject);
    $_smarty->assign_by_ref('name',            $_name);
    $_smarty->assign_by_ref('message',         $_message);
    $_smarty->assign_by_ref('max_namelength',  $cfg[max_namelength]);
    $_smarty->assign_by_ref('max_titlelength', $cfg[max_titlelength]);
    if ($_quotebutton)
      $_smarty->assign_by_ref('msg_id', $_GET[msg_id]);
    $_smarty->display('message_compose.tmpl');
  }
  
  
  /* Same as above, but grabs the title from the database instead, leaving
   * all other fields empty.
   */
  function message_compose_reply($_smarty, $_title, $_hint) {
    global $lang;
    // Prepend 'Re: ' if necessary
    if (strpos($_title, $lang[answer]) !== 0) { 
       $_title = $lang[answer] . $_title;
    } else { 
       $_title = $_title;
    }
    message_compose($_smarty, '', $_title, '', $_hint, TRUE);
  }
?>
