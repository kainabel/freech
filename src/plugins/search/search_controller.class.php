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
  class SearchController extends Controller {
    function SearchController(&$_forum) {
      $this->Controller(&$_forum);
      $this->results = array();
    }


    function _append_posting(&$_posting, $_data) {
      // Required to enable correct formatting of the posting.
      $current_id = $this->api->get_current_posting_id();
      $_posting->set_selected($_posting->get_id() == $current_id);
      $_posting->apply_block();

      // Append everything to a list.
      array_push($this->results, $_posting);
    }


    function show($_forum_id = '', $_query = '') {
      $this->clear_all_assign();
      $this->assign('forum_id', $_forum_id);
      $this->assign('query',    $_query);
      $this->render(dirname(__FILE__).'/search.tmpl');
      $this->api->set_title(_('Search'));
    }


    function show_postings($_forum_id = NULL, $_query = NULL, $_offset = 0) {
      $this->clear_all_assign();
      $this->assign('query', $_query);

      // Parse the query.
      $query_string = $_query;
      if ($_forum_id) {
        $this->assign('forum_id', $_forum_id);
        $query_string = "forumid:$_forum_id AND ($_query)";
      }
      $query = new SearchQuery($query_string);

      // Run the search.
      $func  = array(&$this, '_append_posting');
      $total = $this->forumdb->get_n_postings_from_query($query);
      $rows  = $this->forumdb->foreach_posting_from_query($query,
                                                          (int)$_offset,
                                                          cfg('epp'),
                                                          $func,
                                                          '');

      // Create the index bar.
      $args  = array(forum_id            => $_forum_id,
                     query               => $_query,
                     n_postings          => $total,
                     n_postings_per_page => cfg('epp'),
                     n_offset            => $_offset,
                     n_pages_per_index   => cfg('ppi'));
      $indexbar = new IndexBarSearchResult($args);

      // Render the result.
      $this->assign_by_ref('posting_search', 1);
      $this->assign_by_ref('indexbar',       $indexbar);
      $this->assign_by_ref('n_results',      $total);
      $this->assign_by_ref('n_rows',         $rows);
      $this->assign_by_ref('postings',       $this->results);
      $this->render(dirname(__FILE__).'/search.tmpl');
      $this->api->set_title(_('Search'));
    }


    function _append_user(&$_user, $_data) {
      array_push($this->results, $_user);
    }


    function show_users($_query = NULL, $_offset = 0) {
      $_query = trim($_query);
      $this->clear_all_assign();
      $this->assign('query', $_query);

      // Run the search.
      $search    = array('name' => '%'.trim($_query).'%');
      $userdb    = $this->api->userdb();
      $n_entries = $userdb->get_n_users_from_query($search);
      $n_rows    = 0;
      if ($n_entries > 0) {
        $func   = array(&$this, '_append_user');
        $n_rows = $userdb->foreach_user_from_query($search,
                                                   cfg('epp'),
                                                   (int)$_offset,
                                                   $func,
                                                   '');
      }

      // Search for similar results.
      if ($n_rows == 0) {
        $n_entries     = $userdb->count_similar_users_from_name($_query);
        $this->results = $userdb->get_similar_users_from_name($_query,
                                                              cfg('epp'),
                                                              (int)$_offset);
        $n_rows        = count($this->results);
      }

      // Create the index bar.
      $args      = array(query             => $_query,
                         n_users           => $n_entries,
                         n_users_per_page  => cfg("epp"),
                         n_offset          => $_offset,
                         n_pages_per_index => cfg("ppi"));
      $indexbar = new IndexBarSearchUsers($args);

      // Render the result.
      $this->assign_by_ref('user_search', 1);
      $this->assign_by_ref('indexbar',    $indexbar);
      $this->assign_by_ref('n_results',   $n_entries);
      $this->assign_by_ref('n_rows',      $n_rows);
      $this->assign_by_ref('users',       $this->results);
      $this->render(dirname(__FILE__).'/search.tmpl');
      $this->api->set_title(_('Search'));
    }
  }
?>
