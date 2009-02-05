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
class Thread {
  function Thread() {
    $this->postings = array();
  }


  function _get_posting_path(&$_posting) {
    return '00000000' . substr($_posting->_get_path(), 0, -2);
  }


  function _get_posting_depth(&$_posting) {
    if (!$_posting)
      return 0;
    return (strlen($this->_get_posting_path($_posting)) / 8) - 1;
  }


  function _get_next_sibling($_start, $_depth) {
    $postings   = array_values($this->postings);
    $n_postings = count($postings);
    for ($i = $_start; $i != $n_postings; $i++) {
      $depth = $this->_get_posting_depth($postings[$i]);
      if ($depth == $_depth)
        return $postings[$i];
      elseif ($depth < $_depth)
        return NULL;
    }
    return NULL;
  }


  function _posting_has_unlocked_children(&$_unlocked, &$_posting) {
    if (!$_posting->has_descendants())
      return FALSE;
    $path = $this->_get_posting_path($_posting);
    $len  = strlen($path);
    foreach ($_unlocked as $unlocked) {
      $path2 = $this->_get_posting_path($unlocked);
      if (substr($path2, 0, $len) == $path)
        return TRUE;
    }
    return FALSE;
  }


  function _update_relations() {
    ksort($this->postings, SORT_STRING);
    $postings   = array_values($this->postings);
    $n_postings = count($postings);

    // Parent node types.
    if ($n_postings == 1)
      $postings[0]->set_relation(POSTING_RELATION_PARENT_STUB);
    else
      $postings[0]->set_relation(POSTING_RELATION_PARENT_UNFOLDED);

    // Walk through all nodes (except the parent node).
    $indent = array(INDENT_DRAW_SPACE);
    for ($i = 1; $i != $n_postings; $i++) {
      $current       = $postings[$i];
      $current_depth = $this->_get_posting_depth($current);
      $next          = $postings[$i + 1];
      $next_depth    = $this->_get_posting_depth($next);
      $next_sibling  = $this->_get_next_sibling($i + 1, $current_depth);

      $current->set_indent($indent);

      // Children at a branch end.
      if (!$next_sibling && $next_depth <= $current_depth)
        $current->set_relation(POSTING_RELATION_BRANCHEND_STUB);
      elseif (!$next_sibling)
        $current->set_relation(POSTING_RELATION_BRANCHEND);

      // Other children.
      elseif ($next_depth <= $current_depth)
        $current->set_relation(POSTING_RELATION_CHILD_STUB);
      else
        $current->set_relation(POSTING_RELATION_CHILD);

      // Indent.
      if ($current->get_relation() == POSTING_RELATION_CHILD)
        $indent[$current_depth] = INDENT_DRAW_DASH;
      elseif ($current->get_relation() == POSTING_RELATION_BRANCHEND_STUB)
        for ($f = $current_depth; $f >= $next_depth; $f--)
          unset($indent[$f]);
      elseif ($current->get_relation() == POSTING_RELATION_BRANCHEND)
        for ($f = $current_depth; $f < $next_depth; $f++)
          $indent[$f] = INDENT_DRAW_SPACE;
    }
  }


  function set_from_db(&$forumdb, &$_res) {
    while (!$_res->EOF) {
      $row = $_res->FetchObj();
      if (!isset($thread_id))
        $thread_id = $row->thread_id;
      if ($thread_id != $row->thread_id)
        break;

      $posting = new Posting;
      $posting->set_from_db($row);
      $posting = $forumdb->_decorate_posting($posting);
      $this->postings[$this->_get_posting_path($posting)] = $posting;

      $_res->MoveNext();
    }

    $this->_update_relations();
  }


  function &get_parent() {
    return $this->postings['00000000'];
  }


  function get_parent_id() {
    return $this->get_parent()->get_id();
  }


  function fold() {
    if (count($this->postings) == 1)
      return;
    $parent = $this->get_parent();
    $parent->set_relation(POSTING_RELATION_PARENT_FOLDED);
    $this->postings = array($this->_get_posting_path($parent) => $parent);
  }


  function remove_locked_postings() {
    $locked   = array();
    $unlocked = array();
    foreach ($this->postings as $posting)
      if ($posting->is_locked())
        array_push($locked, $posting);
      else
        array_push($unlocked, $posting);

    foreach ($locked as $posting)
      if (!$this->_posting_has_unlocked_children($unlocked, $posting))
        unset($this->postings[$this->_get_posting_path($posting)]);
    $this->_update_relations();
  }


  function get_postings() {
    ksort($this->postings, SORT_STRING);
    return array_values($this->postings);
  }


  function foreach_posting($_func, $_data = NULL) {
    ksort($this->postings, SORT_STRING);
    foreach ($this->postings as $posting)
      call_user_func($_func, $posting, $_data);
  }


  function get_n_postings() {
    return count($this->postings);
  }


  function get_n_new_postings() {
    $n = 0;
    foreach ($this->postings as $posting)
      if ($posting->is_new())
        $n++;
    return $n;
  }


  function get_id() {
    return $this->get_parent()->get_id();
  }


  function get_priority() {
    return $this->get_parent()->get_priority();
  }


  function was_moved() {
    return $this->get_parent()->was_moved();
  }


  function is_new() {
    return $this->get_parent()->is_new();
  }


  function get_url() {
    return $this->get_parent()->get_url();
  }


  function get_url_html() {
    return $this->get_parent()->get_url_html();
  }


  function get_subject() {
    return $this->get_parent()->get_subject();
  }


  function get_username() {
    return $this->get_parent()->get_username();
  }


  function get_user_is_special() {
    return $this->get_parent()->get_user_is_special();
  }


  function get_user_icon() {
    return $this->get_parent()->get_user_icon();
  }


  function get_user_icon_name() {
    return $this->get_parent()->get_user_icon_name();
  }


  function get_created_time() {
    return $this->get_parent()->get_created_time();
  }
}
?>
