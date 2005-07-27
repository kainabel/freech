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
      if (!$this->message) {
        call_user_func($_func);
        return;
      }
      
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('read',     1);
      $url->set_var('msg_id',   0);
      $url->set_var('forum_id', (int)$_GET[forum_id]);
      
      // "Previous/Next Entry" buttons.
      if ($this->message->get_prev_message_id() > 0) {
        $url->set_var('msg_id', $this->message->get_prev_message_id());
        call_user_func($_func, lang("prev_symbol"), $url);
      }
      else
        call_user_func($_func, lang("prev_symbol"));
      call_user_func($_func, lang("entry"));
      if ($this->message->get_next_message_id() > 0) {
        $url->set_var('msg_id', $this->message->get_next_message_id());
        call_user_func($_func, lang("next_symbol"), $url);
      }
      else
        call_user_func($_func, lang("next_symbol"));
      
      // "Previous/Next Thread" buttons.
      call_user_func($_func);
      if ($this->message->get_prev_thread_id() > 0) {
        $url->set_var('msg_id', $this->message->get_prev_thread_id());
        call_user_func($_func, lang("prev_symbol"), $url);
      }
      else
        call_user_func($_func, lang("prev_symbol"));
      call_user_func($_func, lang("thread"));
      if ($this->message->get_next_thread_id() > 0) {
        $url->set_var('msg_id', $this->message->get_next_thread_id());
        call_user_func($_func, lang("next_symbol"), $url);
      }
      else
        call_user_func($_func, lang("next_symbol"));
      
      // "Reply" button.
      call_user_func($_func);
      $url->delete_var('read');
      $url->set_var('write', 1);
      if ($this->message->is_active() && $this->message->get_allow_answer()) {
        $url->set_var('msg_id', $this->message->get_id());
        call_user_func($_func, lang("writeanswer"), $url);
      }
      else
        call_user_func($_func, lang("writeanswer"));
      
      // "New Thread" button.
      call_user_func($_func);
      $url->delete_var('msg_id');
      call_user_func($_func, lang("writemessage"), $url);
      
      // "Show/Hide Thread" button.
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('read',     1);
      $url->set_var('msg_id',   0);
      $url->set_var('forum_id', (int)$_GET[forum_id]);
      if ($this->message->has_thread()) {
        call_user_func($_func);
        $url->set_var('msg_id', $this->message->get_id());
        if ($_COOKIE[thread] === 'hide') {
          $url->set_var('showthread', 1);
          call_user_func($_func, lang("showthread"), $url);
        }
        else {
          $url->set_var('showthread', -1);
          call_user_func($_func, lang("hidethread"), $url);
        }
      }
    }
  }
?>
