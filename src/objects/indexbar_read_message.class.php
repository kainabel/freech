<?php
  /*
  Freech.
  Copyright (C) 2008 Samuel Abels, <http://debain.org>

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
   * Represents the IndexBar that is shown when reading a message.
   */
  class IndexBarReadMessage extends IndexBar {
    var $items;


    // Constructor.
    function IndexBarReadMessage($_message, $may_edit = FALSE) {
      $this->IndexBar();
      $this->message = $_message;

      $additem = array(&$this, 'add_item');
      if (!$this->message) {
        call_user_func($additem);
        return;
      }

      $url = new URL('?', cfg("urlvars"));
      $url->set_var('action',   'read');
      $url->set_var('msg_id',   1);
      $url->set_var('forum_id', $_message->get_forum_id());

      // "Previous/Next Entry" buttons.
      if ($this->message->get_prev_message_id() > 0) {
        $url->set_var('msg_id', $this->message->get_prev_message_id());
        call_user_func($additem, lang("prev_symbol"), $url);
      }
      else
        call_user_func($additem, lang("prev_symbol"));
      call_user_func($additem, lang("entry"));
      if ($this->message->get_next_message_id() > 0) {
        $url = clone($url);
        $url->set_var('msg_id', $this->message->get_next_message_id());
        call_user_func($additem, lang("next_symbol"), $url);
      }
      else
        call_user_func($additem, lang("next_symbol"));

      // "Previous/Next Thread" buttons.
      call_user_func($additem);
      $prev_url = clone($url);
      $next_url = clone($url);
      if (cfg("thread_arrow_rev") == TRUE) {
        // Heise style (reversed) thread buttons
        $prev_url->set_var('msg_id', $this->message->get_next_thread_id());
        $next_url->set_var('msg_id', $this->message->get_prev_thread_id());
        if ($this->message->get_prev_thread_id() <= 0)
          $next_url = NULL;
        if ($this->message->get_next_thread_id() <= 0)
          $prev_url = NULL;
      } else {
        // Freech style thread buttons
        $prev_url->set_var('msg_id', $this->message->get_prev_thread_id());
        $next_url->set_var('msg_id', $this->message->get_next_thread_id());
        if ($this->message->get_prev_thread_id() <= 0)
          $prev_url = NULL;
        if ($this->message->get_next_thread_id() <= 0)
          $next_url = NULL;
      }
      call_user_func($additem, lang("prev_symbol"), $prev_url);
      call_user_func($additem, lang("thread"));
      call_user_func($additem, lang("next_symbol"), $next_url);

      // "Edit" button.
      if ($may_edit) {
        $url = clone($url);
        $url->set_var('msg_id', $this->message->get_id());
        call_user_func($additem);
        $url->set_var('action', 'edit');
        call_user_func($additem, lang("editmessage"), $url);
      }

      // "Reply" button.
      $url = clone($url);
      call_user_func($additem);
      $url->delete_var('msg_id');
      $url->set_var('action', 'respond');
      if ($this->message->is_active() && $this->message->get_allow_answer()) {
        $url->set_var('parent_id', $this->message->get_id());
        call_user_func($additem, lang("writeanswer"), $url);
      }
      else
        call_user_func($additem, lang("writeanswer"));

      // "New Thread" button.
      $url = clone($url);
      call_user_func($additem);
      $url->delete_var('parent_id');
      $url->set_var('action', 'write');
      call_user_func($additem, lang("writemessage"), $url);

      // "Show/Hide Thread" button.
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('action',   'read');
      $url->set_var('msg_id',   0);
      $url->set_var('forum_id', $_message->get_forum_id());
      if ($this->message->has_thread()) {
        call_user_func($additem);
        $url->set_var('msg_id', $this->message->get_id());
        if ($_COOKIE[thread] === 'hide') {
          $url->set_var('showthread', 1);
          call_user_func($additem, lang("showthread"), $url);
        }
        else {
          $url->set_var('showthread', -1);
          call_user_func($additem, lang("hidethread"), $url);
        }
      }
    }
  }
?>
