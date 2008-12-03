<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels, <http://debain.org>

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
  class ThreadLoader {
    var $thread_state;
    var $messages;

    function ThreadLoader($_db, $_thread_state) {
      $this->db           = $_db;
      $this->thread_state = $_thread_state;
      $this->messages     = array();
    }


    function load_threads_from_forum($_forum_id, $_offset) {
      $this->db->foreach_child($_forum_id,
                               0,
                               $_offset,
                               cfg("tpp"),
                               cfg("updated_threads_first"),
                               $this->thread_state,
                               array(&$this, '_append_row'),
                               '');
    }


    function load_threads_from_user($_user_id, $_offset) {
      $this->db->foreach_message_from_user($_user_id,
                                           $_offset,
                                           cfg("epp"),
                                           cfg("updated_threads_first"),
                                           $this->thread_state,
                                           array(&$this, '_append_row'),
                                           '');
    }


    function load_thread_from_message($_forum_id, $_msg_id, $_offset) {
      $this->db->foreach_child_in_thread($_forum_id,
                                         $_msg_id,
                                         $_offset,
                                         cfg("tpp"),
                                         $this->thread_state,
                                         array(&$this, '_append_row'),
                                         '');
    }


    function _append_row(&$_message, $_indents, $_data) {
      if ($_GET['profile']) {
        $action  = 'profile';
        $actionc = 'profile_c';
      }
      else {
        $action  = 'list';
        $actionc = 'c';
      }

      // The URL to the message.
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('action',   'read');
      $url->set_var('msg_id',   $_message->get_id());
      $url->set_var('forum_id', $_message->get_forum_id());
      if (cfg("remember_page"))
        $url->set_var('hs', (int)$_GET[hs]);

      // The url behind the "+/-" thread_state toggle button.
      if ($_GET['action'] == 'read') {
        $foldurl = clone($url);
        $foldurl->delete_var[hs];
        $foldurl->set_var('showthread', -1);
      }
      else {
        $foldurl = new URL('?', cfg("urlvars"));
        $foldurl->set_var('action',   $action);
        $foldurl->set_var('hs',       (int)$_GET[hs]);
        $foldurl->set_var('forum_id', $_message->get_forum_id());
        $foldurl->set_var($actionc,   $_message->get_id());
      }

      // Required to enable correct formatting of the message.
      if ($_message->get_id() == $_GET[msg_id])
        $_message->set_selected();
      if (!$_message->is_active()) {
        $_message->set_subject(lang("blockedtitle"));
        $_message->set_username('------');
        $_message->set_body('');
        unset($url);
      }

      // Append everything to a list.
      $_message->indent = $_indents;
      $_message->url    = $url ? $url->get_string() : '';
      array_push($this->messages, $_message);
    }
  }
?>
