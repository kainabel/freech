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
    var $results;

    function SearchPrinter(&$_forum) {
      $this->PrinterBase(&$_forum);
      $this->results = array();
    }


    function _append_message(&$_message, $_data) {
      // Required to enable correct formatting of the message.
      $msg_id = $this->parent->get_message_id();
      $_message->set_selected($_message->get_id() == $msg_id);
      $_message->apply_block();

      // Append everything to a list.
      array_push($this->results, $_message);
    }


    function show($_forum_id = '', $_query = '') {
      $this->smarty->clear_all_assign();
      $this->smarty->assign('forum_id', $_forum_id);
      $this->smarty->assign('query',    $_query);
      $this->parent->append_content($this->smarty->fetch('search.tmpl'));
    }


    function show_messages($_forum_id = NULL, $_query = NULL, $_offset = 0) {
      $this->smarty->clear_all_assign();
      $this->smarty->assign('query', $_query);

      // Parse the query.
      if ($_forum_id) {
        $this->smarty->assign('forum_id', $_forum_id);
        $_query = "forumid:$forum_id AND (".$_query.")";
      }
      $query = &new SearchQuery($_query);

      // Run the search.
      $func  = array(&$this, '_append_message');
      $total = $this->db->get_n_messages_from_query($query);
      $rows  = $this->db->foreach_message_from_query($query,
                                                     (int)$_offset,
                                                     cfg("epp"),
                                                     $func,
                                                     '');

      // Create the index bar.
      $args  = array(forum_id            => $_forum_id,
                     query               => $_query,
                     n_messages          => $total,
                     n_messages_per_page => cfg("epp"),
                     n_offset            => $_offset,
                     n_pages_per_index   => cfg("ppi"));
      $indexbar = &new IndexBarSearchResult($args);

      // Render the result.
      $this->smarty->assign_by_ref('indexbar',  $indexbar);
      $this->smarty->assign_by_ref('n_results', $total);
      $this->smarty->assign_by_ref('n_rows',    $rows);
      $this->smarty->assign_by_ref('messages',  $this->results);
      $this->parent->append_content($this->smarty->fetch('search.tmpl'));
    }


    function _append_user(&$_user, $_data) {
      array_push($this->results, $_user);
    }


    function show_users($_query = NULL, $_offset = 0) {
      $_query = trim($_query);
      $this->smarty->clear_all_assign();
      $this->smarty->assign('query', $_query);

      // Run the search.
      $search    = array('username' => '%'.trim($_query).'%');
      $accountdb = $this->parent->_get_accountdb();
      $func      = array(&$this, '_append_user');
      $n_rows    = $accountdb->foreach_user_from_query($search,
                                                       50,
                                                       (int)$_offset,
                                                       $func,
                                                       '');

      // Search for similar results.
      if ($n_rows == 0) {
        $user          = new User($_query);
        $this->results = $accountdb->get_similar_users($user, 50);
        $n_rows        = count($this->results);
      }

      // Render the result.
      $this->smarty->assign_by_ref('n_results', $n_rows);
      $this->smarty->assign_by_ref('n_rows',    $n_rows);
      $this->smarty->assign_by_ref('users',     $this->results);
      $this->parent->append_content($this->smarty->fetch('search.tmpl'));
    }
  }
?>
