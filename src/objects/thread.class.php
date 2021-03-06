<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels

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
  function Thread(&$_api) {
    $this->api           = $_api;
    $this->postings_map  = array();
    $this->postings_list = array();
    $this->fields        = array();
    $this->dirty         = FALSE;
    $this->n_new         = 0;
    $this->max_priority  = 0;
  }


  function _get_posting_path(&$_posting) {
    return '00000000' . substr($_posting->_get_path(), 0, -2);
  }


  function _get_posting_depth(&$_posting) {
    if (!$_posting)
      return 0;
    return max(0, strlen($_posting->_get_path()) - 2) / 8;
  }


  function _get_next_sibling($_start, $_depth) {
    $n_postings = count($this->postings_list);
    for ($i = $_start; $i != $n_postings; $i++) {
      $depth = $this->_get_posting_depth($this->postings_list[$i]);
      if ($depth == $_depth)
        return $this->postings_list[$i];
      elseif ($depth < $_depth)
        return NULL;
    }
    return NULL;
  }


  function _add_posting(&$_posting) {
    $path = $this->_get_posting_path($_posting);
    $this->postings_map[$path] = $_posting;
    if ($_posting->is_new())
      $this->n_new++;
  }


  function _create_posting_at($_path) {
    $posting = new Posting;
    $posting->_set_path(substr($_path, 8) . '00');
    $posting->set_status(POSTING_STATUS_LOCKED);
    $posting->apply_block();
    // TODO: faked timestamp against the time bug in ThreadView
    $posting->set_created_unixtime(1118786400);
    $posting->set_updated_unixtime(1118786400);
    $posting = $this->api->_decorate_posting($posting);
    $this->_add_posting($posting);
    return $posting;
  }


  function _create_missing_parents(&$_path) {
    while ($_path = substr($_path, 0, -8))
      if (!isset($this->postings_map[$_path]))
        $this->_create_posting_at($_path);
  }


  function _update_relations() {
    trace('enter');
    $this->dirty = FALSE;

    // Since it is allowed to remove abituary postings from the tree, we
    // need to create "fake" postings where children would otherwise
    // have a missing parent.
    foreach (array_keys($this->postings_map) as $path)
      $this->_create_missing_parents($path);
    ksort($this->postings_map, SORT_STRING);

    $this->postings_list = array_values($this->postings_map);
    $n_postings          = count($this->postings_list);

    // Parent node types.
    if ($n_postings == 1) {
      $this->postings_list[0]->set_relation(POSTING_RELATION_PARENT_STUB);
      trace('no children');
      return;
    }
    $this->postings_list[0]->set_relation(POSTING_RELATION_PARENT_UNFOLDED);

    // Walk through all nodes (except the parent node).
    $indent = array(INDENT_DRAW_SPACE);
    for ($i = 1; $i != $n_postings; $i++) {
      $current       = $this->postings_list[$i];
      $current_depth = $this->_get_posting_depth($current);
      $next          = $this->postings_list[$i + 1];
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
    trace('leave');
  }


  function set_from_db(&$forumdb, &$_res) {
    trace('enter');
    $row                      = $_res->fields;
    $this->dirty              = TRUE;
    $this->fields['id']       = $row['thread_id'];
    $this->fields['forum_id'] = $row['forum_id'];
    $this->fields['updated']  = $row['threadupdate'];
    while (!$_res->EOF) {
      if ($this->fields['id'] != $row['thread_id'])
        break;
      $this->max_priority = max($this->max_priority, $row['priority']);

      $posting = new Posting($row);
      $posting = $forumdb->_decorate_posting($posting);
      $posting->apply_block();
      $this->_add_posting($posting);

      $_res->MoveNext();
      $row = $_res->fields;
    }

    trace('leave');
  }


  function get_thread_id() {
    return $this->fields['id'];
  }


  function get_parent() {
    $parent = $this->postings_map['00000000'];
    if ($parent)
      return $parent;
    return $this->_create_posting_at('00000000');
  }


  function fold() {
    $this->dirty = FALSE;

    // Fold the parent.
    $parent = $this->get_parent();
    if (count($this->postings_map) > 1)
      $parent->set_relation(POSTING_RELATION_PARENT_FOLDED);
    else
      $parent->set_relation(POSTING_RELATION_PARENT_STUB);

    // Remove the children.
    $this->postings_list = array($parent);
    $this->postings_map  = array($this->_get_posting_path($parent) => $parent);
  }


  function get_postings() {
    if ($this->dirty)
      $this->_update_relations();
    return $this->postings_list;
  }


  function foreach_posting(&$_func) {
    if ($this->dirty)
      $this->_update_relations();
    foreach ($this->postings_list as $posting)
      call_user_func_array($_func, array(&$posting));
  }


  function get_n_children() {
    return $this->get_parent()->get_n_children();
  }


  function get_n_postings() {
    return $this->get_n_children() + 1;
  }


  function get_n_new_postings() {
    return $this->n_new;
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
    return $this->get_parent()->get_thread_url();
  }


  function get_url_html() {
    return $this->get_parent()->get_url_thread_html();
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


  function get_updated_time($_format = '') {
    if (!$_format)
      $_format = cfg('dateformat');
    return strftime($_format, $this->fields['updated']);
  }


  function get_created_time() {
    return $this->get_parent()->get_created_time();
  }
}
?>
