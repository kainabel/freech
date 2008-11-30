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
  class SearchPrinter extends PrinterBase {
    var $messages;

    function SearchPrinter(&$_forum) {
      $this->PrinterBase(&$_forum);
      $this->messages = array();
    }


    function _append_row(&$_message, $_data) {
      // The URL to the message.
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('action',   'read');
      $url->set_var('msg_id',   $_message->get_id());
      $url->set_var('forum_id', $_message->get_forum_id());
      if (cfg("remember_page"))
        $url->set_var('hs', (int)$_GET[hs]);

      // Required to enable correct formatting of the message.
      $_message->set_selected($_message->get_id() == $_GET[msg_id]);
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


    function show($_query = NULL, $_offset = 0) {
      $this->smarty->clear_all_assign();
      $this->smarty->assign('forum_id', (int)$_GET['forum_id']);
      $this->smarty->assign_by_ref('query', $_GET['q']);

      if (!$_query) {
        $this->parent->append_content($this->smarty->fetch('search.tmpl'));
        return;
      }

      $func  = array(&$this, '_append_row');
      $total = $this->db->get_n_messages_from_query($_query);
      $rows  = $this->db->foreach_message_from_query($_query,
                                                     (int)$_offset,
                                                     cfg("epp"),
                                                     $func,
                                                     '');
      $args  = array(n_messages          => $total,
                     n_messages_per_page => cfg("epp"),
                     n_offset            => $_offset,
                     n_pages_per_index   => cfg("ppi"));
      $indexbar = &new IndexBarSearchResult($args);

      $this->smarty->assign_by_ref('indexbar',  $indexbar);
      $this->smarty->assign_by_ref('n_results', $total);
      $this->smarty->assign_by_ref('n_rows',    $rows);
      $this->smarty->assign_by_ref('messages',  $this->messages);
      $this->parent->append_content($this->smarty->fetch('search.tmpl'));
    }
  }
?>
