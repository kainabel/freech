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
  class LatestPrinter extends PrinterBase {
    var $messages;

    function LatestPrinter(&$_forum) {
      $this->PrinterBase(&$_forum);
      $this->messages = array();
    }


    function _append_row(&$_message, $_data) {
      // The URL to the message.
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('read',     1);
      $url->set_var('msg_id',   $_message->get_id());
      $url->set_var('forum_id', $_message->get_forum_id());
      if (cfg("remember_page"))
        $url->set_var('hs', (int)$_GET[hs]);

      // Required to enable correct formatting of the message.
      $_message->set_selected($_row[id] == $_GET[msg_id] && $_GET[read]);
      if (!$_message->is_active()) {
        $_message->set_subject(lang("blockedtitle"));
        $_message->set_username('------');
        $_message->set_body('');
        unset($url);
      }

      // Append everything to a list.
      $_message->url = $url ? $url->get_string() : '';
      array_push($this->messages, $_message);
    }


    function show($_forum_id, $_offset) {
      $n = $this->db->foreach_latest_message((int)$_forum_id,
                                             (int)$_offset,
                                             cfg("epp"),
                                             FALSE,
                                             array(&$this, '_append_row'),
                                             '');
      $n_entries = $this->db->get_n_messages($_forum_id);
      $args      = array(n_messages          => $n_entries,
                         n_messages_per_page => cfg("epp"),
                         n_offset            => $_offset,
                         n_pages_per_index   => cfg("ppi"));
      $indexbar = &new IndexBarByTime($args);

      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('indexbar', $indexbar);
      $this->smarty->assign_by_ref('n_rows',   $n);
      $this->smarty->assign_by_ref('messages', $this->messages);
      $this->parent->append_content($this->smarty->fetch('list_by_time.tmpl'));
    }
  }
?>
