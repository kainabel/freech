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
  /**
   * Concrete strategy, prints the index bar for the "threaded" list.
   */
  class IndexBarStrategy_read_message extends IndexBarStrategy {
    var $message;
    
    
    /// Constructor.
    function IndexBarStrategy_read_message(&$_args) {
      $this->message = $_args[message];
    }
    
    
    function foreach_link($_func) {
      global $lang; //FIXME
      global $cfg; //FIXME
      
      if (!$this->message) {
        call_user_func($_func);
        return;
      }
      
      $url = new URL('?', $cfg[urlvars]);  //FIXME: cfg
      $url->set_var('read',     1);
      $url->set_var('msg_id',   0);
      $url->set_var('forum_id', $_GET[forum_id]);
      
      // "Previous/Next Entry" buttons.
      if ($this->message->get_prev_message_id() > 0) {
        $url->set_var('msg_id', $this->message->get_prev_message_id());
        call_user_func($_func, $lang[prev_symbol], $url); //FIXME: lang
      }
      else
        call_user_func($_func, $lang[prev_symbol]); //FIXME: lang
      call_user_func($_func, $lang[entry]);         //FIXME: lang
      if ($this->message->get_next_message_id() > 0) {
        $url->set_var('msg_id', $this->message->get_next_message_id());
        call_user_func($_func, $lang[next_symbol], $url); //FIXME: lang
      }
      else
        call_user_func($_func, $lang[next_symbol]); //FIXME: lang
      
      // "Previous/Next Thread" buttons.
      call_user_func($_func);
      if ($this->message->get_prev_thread_id() > 0) {
        $url->set_var('msg_id', $this->message->get_prev_thread_id());
        call_user_func($_func, $lang[prev_symbol], $url); //FIXME: lang
      }
      else
        call_user_func($_func, $lang[prev_symbol]); //FIXME: lang
      call_user_func($_func, $lang[thread]);         //FIXME: lang
      if ($this->message->get_next_thread_id() > 0) {
        $url->set_var('msg_id', $this->message->get_next_thread_id());
        call_user_func($_func, $lang[next_symbol], $url); //FIXME: lang
      }
      else
        call_user_func($_func, $lang[next_symbol]); //FIXME: lang
      
      // "Reply" button.
      call_user_func($_func);
      $url->delete_var('read');
      $url->set_var('write', 1);
      if ($this->message->is_active() && $this->message->get_allow_answer()) {
        $url->set_var('msg_id', $this->message->get_id());
        call_user_func($_func, $lang[writeanswer], $url); //FIXME: lang
      }
      else
        call_user_func($_func, $lang[writeanswer]); //FIXME: lang
      
      // "New Thread" button.
      call_user_func($_func);
      $url->delete_var('msg_id');
      call_user_func($_func, $lang[writemessage], $url); //FIXME: lang
      
      // "Show/Hide Thread" button.
      $url = new URL('?', $cfg[urlvars]);  //FIXME: cfg
      $url->set_var('read',     1);
      $url->set_var('msg_id',   0);
      $url->set_var('forum_id', $_GET[forum_id]);
      if ($this->message->has_thread()) {
        call_user_func($_func);
        $url->set_var('msg_id', $this->message->get_id());
        if ($_COOKIE[thread] === 'hide') {
          $url->set_var('showthread', 1);
          call_user_func($_func, $lang[showthread], $url); //FIXME: lang
        }
        else {
          $url->set_var('showthread', -1);
          call_user_func($_func, $lang[hidethread], $url); //FIXME: lang
        }
      }
    }
  }
?>
