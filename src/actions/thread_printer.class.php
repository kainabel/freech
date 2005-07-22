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
  class ThreadPrinter {
    var $smarty;
    var $folding;
    var $db;
    var $messages;
    var $urls;
    var $foldurls;
    
    function ThreadPrinter(&$_smarty, &$_db, &$_folding) {
      $this->smarty   = &$_smarty;
      $this->db       = &$_db;
      $this->folding  = &$_folding;
      $this->messages = array();
      $this->urls     = array();
      $this->foldurls = array();
    }
    
    
    function _append_row(&$_message, $_indents, $_data) {
      
      // The URL to the message.
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('read',     1);
      $url->set_var('msg_id',   $_message->get_id());
      $url->set_var('forum_id', $_message->get_forum_id());
      if (cfg("remember_page"))
        $url->set_var('hs', $_GET[hs]);
      
      // The url behind the "+/-" folding toggle button.
      if ($_GET[read]) {
        $foldurl = $url;
        $foldurl->delete_var[hs];
        $foldurl->set_var('showthread', -1);
      }
      else {
        $foldurl = new URL('?', cfg("urlvars"));
        $foldurl->set_var('list',     1);
        $foldurl->set_var('hs',       $_GET[hs]);
        $foldurl->set_var('forum_id', $_message->get_forum_id());
        $foldurl->set_var('c', $_message->get_id());
      }
      
      // Required to enable correct formatting of the message.
      if ($_message->get_id() == $_GET[msg_id] && $_GET[read])
        $_message->set_selected();
      if (!$_message->is_active()) {
        $_message->set_subject(lang("blockedtitle"));
        $_message->set_username('------');
        $_message->set_body('');
        unset($url);
      }
      
      // Append everything to a list.
      $_message->indent  = $_indents;
      $_message->url     = $url     ? $url->get_string()     : '';
      $_message->foldurl = $foldurl ? $foldurl->get_string() : '';
      array_push($this->messages, $_message);
    }
    
    
    function show($_forum_id, $_msg_id, $_offset) {
      
      if ($_msg_id == 0)
        $n = $this->db->foreach_child($_forum_id,
                                      $_msg_id,
                                      $_offset,
                                      cfg("tpp"),
                                      $this->folding,
                                      array(&$this, '_append_row'),
                                      '');
      else
        $n = $this->db->foreach_child_in_thread($_forum_id,
                                                $_msg_id,
                                                $_offset,
                                                cfg("tpp"),
                                                $this->folding,
                                                array(&$this, '_append_row'),
                                                '');
      
      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('n_rows',   $n);
      $this->smarty->assign_by_ref('lang',     lang());
      $this->smarty->assign_by_ref('messages', $this->messages);
      $this->smarty->display('thread.tmpl');
      print("\n");
    }
  }
?>
