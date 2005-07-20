<?php
  /*
  Tefinch.
  Copyright (C) 2003 Samuel Abels, <spam debain org>

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
  class MessagePrinter {
    var $smarty;
    var $db;
    
    function MessagePrinter($_smarty, $_db) {
      $this->smarty  = $_smarty;
      $this->db      = $_db;
    }
    
    
    function show($_message) {
      global $cfg;
      global $lang;
      
      if (!$_message) {
        $_message = new Message;
        $_message->set_subject($lang[noentrytitle]);
        $_message->set_body($lang[noentrybody]);
      }
      elseif (!$_message->is_active()) {
        $_message->set_subject($lang[blockedtitle]);
        $_message->set_body($lang[blockedentry]);
      }
      
      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('message', $_message);
      $this->smarty->display('message.tmpl');
      print("\n");
    }
    
    
    /* Show a preview form of the message. */
    function show_preview($_message, $_parent_id = '') {
      global $cfg;
      global $lang;
      
      $url  = new URL('?', array_merge($_GET, $cfg[urlvars]));
      $url->mask(array('forum_id', 'msg_id', 'hs'));
      
      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('pagetitle', $lang[preview]);
      $this->smarty->assign_by_ref('action',    $url->get_string());
      $this->smarty->assign_by_ref('message',   $_message);
      $this->smarty->assign_by_ref('msg_id',    $_parent_id);
      $this->smarty->assign_by_ref('lang',      $lang);
      $this->smarty->display('message_preview.tmpl');
      
      return 0;
    }
  }
?>
