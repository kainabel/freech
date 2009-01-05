<?php
  /*
  Freech.
  Copyright (C) 2003-2008 Samuel Abels, <http://debain.org>

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
  define("INDENT_DRAW_DASH",  1);
  define("INDENT_DRAW_SPACE", 2);

  class ForumDB {
    var $db;

    function ForumDB(&$_db) {
      $this->db = &$_db;
    }


    /***********************************************************************
     * Private API.
     ***********************************************************************/
    /* Given a decimal number, this function returns an 8 character wide
     * hexadecimal string representation of it.
     */
    function _int2hex($_n) {
      return substr("00000000" . dechex($_n), -8);
    }


    /* Given the id of any node, this function returns the hexadecimal string
     * representation of its binary path.
     */
    function _get_path($_id) {
      $sql  = "SELECT path FROM {t_posting} t1";
      $sql .= " WHERE t1.id={id}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('id', $_id);
      $row = $this->db->GetRow($query->sql()) or die("ForumDB::_get_path()");
      return $row[path];
    }


    function _get_thread_id($_id) {
      $sql = "SELECT thread_id FROM {t_posting} WHERE id={id}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('id', $_id);
      $row = $this->db->GetRow($query->sql());
      return $row && $row[thread_id] ? $row[thread_id] : 0;
    }


    function _is_parent(&$_row) {
      return $_row[path] == "";
    }


    function _has_children(&$_row) {
      return $_row[n_descendants] > 0;
    }


    function _is_childof(&$_row, &$_nextrow) {
      return strlen($_nextrow[path]) > strlen($_row[path]);
    }


    /* Given the id of any node, this function returns the id of the previous
     * entry in the same thread, or 0 if there is no previous entry.
     */
    function _get_prev_entry_id($_forum_id, $_thread_id, $_path) {
      if (!$_path)
        return 0;
      $sql  = "SELECT id FROM {t_posting}";
      $sql .= " WHERE thread_id={thread_id}";
      $sql .= " AND is_active";
      $sql .= " AND STRCMP(CONCAT('0x', HEX(path)), '{path}')=-1";
      $sql .= " ORDER BY HEX(path) DESC";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('thread_id', $_thread_id);
      $query->set_hex('path',     $_path);
      $res = $this->db->SelectLimit($query->sql(), 1)
                          or die("ForumDB::_get_prev_entry_id()");
      $row = $res->FetchRow($res);
      return $row[id];
    }


    /* Given the path of any node, this function returns the id of the next
     * entry in the same thread, or 0 if there is no next entry.
     */
    function _get_next_entry_id($_forum_id, $_thread_id, $_path) {
      $sql  = "SELECT id FROM {t_posting}";
      $sql .= " WHERE thread_id={thread_id}";
      $sql .= " AND is_active";
      $sql .= " AND NOT is_parent";
      if ($_path)
        $sql .= " AND STRCMP(CONCAT('0x', HEX(path)), '{path}')=1";
      $sql .= " ORDER BY HEX(path)";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('thread_id', $_thread_id);
      $query->set_hex('path',     $_path);
      $res = $this->db->SelectLimit($query->sql(), 1)
                          or die("ForumDB::_get_next_entry_id()");
      $row = $res->FetchRow($res);
      return $row[id];
    }


    /* Given a thread_id, this function returns the id of the previous
     * thread in the given forum, or 0 if there is no previous thread.
     * The thread_id equals the id of the toplevel node in a thread.
     */
    function _get_prev_thread_id($_forum_id, $_thread_id) {
      $sql  = "SELECT thread_id FROM {t_posting}";
      $sql .= " WHERE forum_id={forum_id} AND thread_id<{thread_id}";
      $sql .= " AND (is_active OR n_children)";
      $sql .= " ORDER BY thread_id DESC";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('forum_id',  $_forum_id);
      $query->set_int('thread_id', $_thread_id);
      $res = $this->db->SelectLimit($query->sql(), 1)
                          or die("ForumDB::_get_prev_thread_id()");
      $row = $res->FetchRow($res);
      return $row[thread_id];
    }


    /* Given a thread_id, this function returns the id of the next
     * thread in the given forum, or 0 if there is no next thread.
     * The thread_id equals the id of the toplevel node in a thread.
     */
    function _get_next_thread_id($_forum_id, $_thread_id) {
      $sql  = "SELECT thread_id FROM {t_posting}";
      $sql .= " WHERE forum_id={forum_id} AND thread_id>{thread_id}";
      $sql .= " AND (is_active OR n_children)";
      $sql .= " ORDER BY thread_id";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('forum_id',  $_forum_id);
      $query->set_int('thread_id', $_thread_id);
      $res = $this->db->SelectLimit($query->sql(), 1)
                          or die("ForumDB::_get_next_thread_id()");
      $row = $res->FetchRow($res);
      return $row[thread_id];
    }


    /***********************************************************************
     * Public API.
     ***********************************************************************/
    /* Insert a new child.
     *
     * $_forum:   The forum id.
     * $_parent:  The id of the entry under which the new entry is placed.
     * $_posting: The posting to be inserted.
     * Returns:   The id of the newly inserted entry.
     */
    function insert($_forum_id, $_parent_id, &$_posting) {
      $body = $_posting->get_body();
      if ($_posting->get_signature())
        $body .= "\n\n--\n" . $_posting->get_signature();
      //$this->db->debug = true;

      // Fetch the parent row.
      $sql  = "SELECT forum_id,thread_id,HEX(path) path,is_active";
      $sql .= " FROM {t_posting}";
      $sql .= " WHERE id={parent_id}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('parent_id', $_parent_id);
      $parentrow = $this->db->GetRow($query->sql());

      $this->db->StartTrans();

      // Insert the new node.
      if ($parentrow) {
        if (!$parentrow[is_active])
          die("ForumDB::insert(): Parent inactive.\n");
        if (strlen($parentrow[path]) / 2 > 252)
          die("ForumDB::insert(): Hierarchy too deep.\n");

        // Insert a new child.
        $sql  = "INSERT INTO {t_posting}";
        $sql .= " (forum_id, thread_id, priority,";
        $sql .= "  user_id, user_is_special, user_icon, user_icon_name,";
        $sql .= "  renderer, username, subject, body,";
        $sql .= "  hash, ip_hash, created)";
        $sql .= " VALUES (";
        $sql .= " {forum_id}, {thread_id}, {priority},";
        $sql .= " {user_id}, {user_is_special}, {user_icon}, {user_icon_name},";
        $sql .= " {renderer}, {username}, {subject}, {body},";
        $sql .= " {hash}, {ip_hash}, NULL";
        $sql .= ")";
        $query = &new FreechSqlQuery($sql);
        $query->set_int   ('forum_id',        $parentrow[forum_id]);
        $query->set_int   ('thread_id',       $parentrow[thread_id]);
        $query->set_int   ('priority',        $_posting->get_priority());
        $query->set_int   ('user_id',         $_posting->get_user_id());
        $query->set_bool  ('user_is_special', $_posting->get_user_is_special());
        $query->set_string('user_icon',       $_posting->get_user_icon());
        $query->set_string('user_icon_name',  $_posting->get_user_icon_name());
        $query->set_string('renderer',        $_posting->get_renderer());
        $query->set_string('username',        $_posting->get_username());
        $query->set_string('subject',         $_posting->get_subject());
        $query->set_string('body',            $body);
        $query->set_string('hash',            $_posting->get_hash());
        $query->set_string('ip_hash',         $_posting->get_ip_address_hash());
        $this->db->Execute($query->sql()) or die("ForumDB::insert(): Ins1");
        $_posting->set_id($this->db->Insert_Id());

        // Update the child's path.
        $sql  = "UPDATE {t_posting} SET path=";
        if ($parentrow[path] != '') {
          $len = strlen($parentrow[path]);
          $parentrow[path] = substr($parentrow[path], 0, $len - 2);
          $sql .= " CONCAT(0x$parentrow[path],";
          $sql .= "        0x" . $this->_int2hex($_posting->get_id()) . "00)";
        }
        else {
          $sql .= " 0x" . $this->_int2hex($_posting->get_id()) . "00";
        }
        $sql .= " WHERE id={newid}";
        $query = &new FreechSqlQuery($sql);
        $query->set_int('newid', $_posting->get_id());
        $this->db->Execute($query->sql())
                or die("ForumDB::insert(): Path.");

        // Update n_descendants and n_children in one run...
        if ($_parent_id == $parentrow[thread_id]) {
          $sql  = "UPDATE {t_posting}";
          $sql .= " SET n_children=n_children+1,";
          $sql .= " n_descendants=n_descendants+1";
          $sql .= " WHERE id={parent_id}";
          $query = &new FreechSqlQuery($sql);
          $query->set_int('parent_id', $_parent_id);
          $this->db->Execute($query->sql())
                  or die("ForumDB::insert(): n++");
        }

        // ...unless it is necessary to update two database sets.
        else {
          $sql  = "UPDATE {t_posting} SET n_children=n_children+1";
          $sql .= " WHERE id={thread_id}";
          $query = &new FreechSqlQuery($sql);
          $query->set_int('thread_id', $parentrow[thread_id]);
          $this->db->Execute($query->sql())
                  or die("ForumDB::insert(): n_child fail");

          $sql  = "UPDATE {t_posting} SET n_descendants=n_descendants+1";
          $sql .= " WHERE id={parent_id}";
          $query = &new FreechSqlQuery($sql);
          $query->set_int('parent_id', $_parent_id);
          $this->db->Execute($query->sql())
                  or die("ForumDB::insert(): n_desc");
        }
      }

      // Insert a new thread.
      else {
        $sql  = "INSERT INTO {t_posting}";
        $sql .= " (path, forum_id, priority,";
        $sql .= "  user_id, user_is_special, user_icon, user_icon_name,";
        $sql .= "  renderer, is_parent, username,";
        $sql .= "  subject, body, hash, ip_hash, created)";
        $sql .= " VALUES (";
        $sql .= " '', {forum_id}, {priority},";
        $sql .= " {user_id}, {user_is_special}, {user_icon}, {user_icon_name},";
        $sql .= " {renderer}, 1,";
        $sql .= " {username}, {subject}, {body}, {hash}, {ip_hash}, NULL";
        $sql .= ")";
        $query = &new FreechSqlQuery($sql);
        $query->set_int   ('forum_id',        $_forum_id);
        $query->set_int   ('priority',        $_posting->get_priority());
        $query->set_int   ('user_id',         $_posting->get_user_id());
        $query->set_bool  ('user_is_special', $_posting->get_user_is_special());
        $query->set_string('user_icon',       $_posting->get_user_icon());
        $query->set_string('user_icon_name',  $_posting->get_user_icon_name());
        $query->set_string('renderer',        $_posting->get_renderer());
        $query->set_string('username',        $_posting->get_username());
        $query->set_string('subject',         $_posting->get_subject());
        $query->set_string('body',            $body);
        $query->set_string('hash',            $_posting->get_hash());
        $query->set_string('ip_hash',         $_posting->get_ip_address_hash());
        $this->db->Execute($query->sql())
                or die("ForumDB::insert(): Insert2.".$query->sql());
        $_posting->set_id($this->db->Insert_Id());

        // Set the thread id.
        // FIXME: Is there a better way to do this?
        $sql  = "UPDATE {t_posting} SET thread_id={newid}";
        $sql .= " WHERE id={newid}";
        $query = &new FreechSqlQuery($sql);
        $query->set_int('newid', $_posting->get_id());
        $this->db->Execute($query->sql())
                or die("ForumDB::insert(): thread_id");
      }

      $this->db->CompleteTrans();
      return $_posting->get_id();
    }


    function save($_forum_id, $_parent_id, &$_posting) {
      //FIXME: This currently does not support moving postings (i.e. changing
      // the path, thread, or forum)
      //$this->db->debug = true;

      $this->db->StartTrans();
      $sql  = "UPDATE {t_posting} SET";
      $sql .= " forum_id={forum_id},";
      $sql .= " priority={priority},";
      $sql .= " user_id={user_id},";
      $sql .= " user_is_special={user_is_special},";
      $sql .= " user_icon={user_icon},";
      $sql .= " user_icon_name={user_icon_name},";
      $sql .= " renderer={renderer},";
      $sql .= " username={username},";
      $sql .= " subject={subject},";
      $sql .= " body={body},";
      $sql .= " hash={hash},";
      $sql .= " ip_hash={ip_hash},";
      $sql .= " is_active={is_active},";
      $sql .= " updated=FROM_UNIXTIME({updated})";
      $sql .= " WHERE id={id}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int   ('id',              $_posting->get_id());
      $query->set_int   ('forum_id',        $_forum_id);
      $query->set_int   ('priority',        $_posting->get_priority());
      $query->set_int   ('updated',         $_posting->get_updated_unixtime());
      $query->set_int   ('user_id',         $_posting->get_user_id());
      $query->set_bool  ('user_is_special', $_posting->get_user_is_special());
      $query->set_string('user_icon',       $_posting->get_user_icon());
      $query->set_string('user_icon_name',  $_posting->get_user_icon_name());
      $query->set_string('renderer',        $_posting->get_renderer());
      $query->set_string('username',        $_posting->get_username());
      $query->set_string('subject',         $_posting->get_subject());
      $query->set_string('body',            $_posting->get_body());
      $query->set_string('hash',            $_posting->get_hash());
      $query->set_string('ip_hash',         $_posting->get_ip_address_hash());
      $query->set_string('is_active',       $_posting->is_active());
      $this->db->Execute($query->sql()) or die("ForumDB::save(): 1");

      $this->db->CompleteTrans();
    }


    /* Returns a posting from the given forum.
     * This function is exceptional in that the returned posting object
     * will also have the current_username field set.
     * $_forum: The forum id.
     * $_id:    The id of the posting.
     * Returns: The posting.
     */
    function get_posting_from_id($_id) {
      $sql  = "SELECT m.*,";
      $sql .= "HEX(m.path) path,";
      $sql .= "UNIX_TIMESTAMP(m.updated) updated,";
      $sql .= "UNIX_TIMESTAMP(m.created) created,";
      $sql .= "u.name current_username";
      $sql .= " FROM {t_posting} m";
      $sql .= " JOIN {t_user} u ON u.id=m.user_id";
      $sql .= " WHERE m.id={id}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('id', $_id);
      if (!$row = $this->db->GetRow($query->sql()))
        return;
      $row[prev_thread_id]  = $this->_get_prev_thread_id($row[forum_id],
                                                         $row[thread_id]);
      $row[next_thread_id]  = $this->_get_next_thread_id($row[forum_id],
                                                         $row[thread_id]);
      $row[prev_posting_id] = $this->_get_prev_entry_id($row[forum_id],
                                                        $row[thread_id],
                                                        $row[path]);
      $row[next_posting_id] = $this->_get_next_entry_id($row[forum_id],
                                                        $row[thread_id],
                                                        $row[path]);
      if (strlen($row[path]) / 2 > 252)  // Path as long as the the DB field.
        $row[allow_answer] = FALSE;
      if ($row[id] == $row[thread_id])
        $row[relation] = MESSAGE_RELATION_PARENT_UNFOLDED;

      $posting = &new Posting;
      $posting->set_from_db($row);
      return $posting;
    }


    function _walk_tree($_res, $_thread_state, $_func, $_data) {
      $row     = $_res->FetchRow();
      $indent  = 0;
      $indents = array();
      $parents = array($row);
      while ($row) {
        $nextrow = $_res->FetchRow();

        // Parent node types.
        if ($this->_is_parent($row)
          && !$this->_has_children($row))
          $row[relation] = MESSAGE_RELATION_PARENT_STUB;
        else if ($this->_is_parent($row)
              && !$_thread_state->is_folded($row[id]))
          $row[relation] = MESSAGE_RELATION_PARENT_UNFOLDED;
        else if ($this->_is_parent($row))
          $row[relation] = MESSAGE_RELATION_PARENT_FOLDED;

        // Children at a branch end.
        else if ($parents[$indent - 1][n_descendants] == 1
               && !$this->_is_childof($row, $nextrow))
          $row[relation] = MESSAGE_RELATION_BRANCHEND_STUB;
        else if ($parents[$indent - 1][n_descendants] == 1)
          $row[relation] = MESSAGE_RELATION_BRANCHEND;

        // Other children.
        else if (!$this->_is_childof($row, $nextrow)) {
          $row[relation] = MESSAGE_RELATION_CHILD_STUB;
          $parents[$indent - 1][n_descendants]--;
        }
        else {
          $row[relation] = MESSAGE_RELATION_CHILD;
          $parents[$indent - 1][n_descendants]--;
        }
        //echo "$row[subject] ($row[id], $row[path]): $row[relation]<br>\n";

        $posting = &new Posting();
        $posting->set_from_db($row);
        $posting->set_indent($indents);
        call_user_func($_func, $posting, $_data);

        // Indent.
        $parents[$indent] = $row;
        if ($row[relation] == MESSAGE_RELATION_PARENT_UNFOLDED
          || $row[relation] == MESSAGE_RELATION_CHILD
          || $row[relation] == MESSAGE_RELATION_BRANCHEND) {
          if ($row[relation] == MESSAGE_RELATION_CHILD)
            $indents[$indent] = INDENT_DRAW_DASH;
          else
            $indents[$indent] = INDENT_DRAW_SPACE;
          $indent++;
        }
        // If the last row was a branch end, unindent.
        else if ($row[relation] == MESSAGE_RELATION_BRANCHEND_STUB) {
          $relation = $parents[$indent][relation];
          while ($relation == MESSAGE_RELATION_BRANCHEND_STUB
            || $relation == MESSAGE_RELATION_BRANCHEND) {
            $indent--;
            unset($indents[$indent]);
            $relation = $parents[$indent][relation];
          }
        }

        $row = $nextrow;
      }
    }


    /* Walks through the tree starting from $id, passing each posting to the
     * function given in $func.
     *
     * Args: $_forum   The forum id.
     *       $_id      The node whose children we want to print.
     *       $_offset  The offset.
     *       $_limit   The maximum number of threads to walk.
     *       $_thread_state An object identifying folded nodes.
     *       $_func    A reference to the function to which each row will be
     *                 passed.
     *       $_data    Passed through to $_func as an argument.
     *
     * Returns: The number of rows processed.
     */
    function foreach_child($_forum_id,
                           $_id,
                           $_offset,
                           $_limit,
                           $_updated_threads_first,
                           $_thread_state,
                           $_func,
                           $_data) {
      $limit  = $_limit  * 1;
      $offset = $_offset * 1;

      if ($_id != 0) {
        $sql  = "SELECT id";
        $sql .= " FROM {t_posting}";
        $sql .= " WHERE id={id}";
        $query = &new FreechSqlQuery($sql);
        $query->set_int('id', $_id);
        $res = $this->db->Execute($query->sql())
                                    or die("ForumDB::foreach_child(): 1");
      }
      else {
        // Select all root nodes.
        $sql  = "SELECT a.id";
        if ($_updated_threads_first)
          $sql .= " ,MAX(b.id) threadupdate";
        $sql .= " FROM {t_posting} a";
        if ($_updated_threads_first)
          $sql .= " JOIN {t_posting} b ON a.thread_id=b.thread_id";
        $sql .= " WHERE a.forum_id={forum_id} AND a.is_parent";
        if ($_updated_threads_first) {
          $sql .= " GROUP BY a.id";
          $sql .= " ORDER BY a.priority DESC, threadupdate DESC";
        }
        else
          $sql .= " ORDER BY a.priority DESC, a.id DESC";
        $query = &new FreechSqlQuery($sql);
        $query->set_int('forum_id', $_forum_id);
        //$this->db->debug=1;
        $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                       or die("ForumDB::foreach_child(): 2");
      }

      // Build the SQL request to grab the complete threads.
      if ($res->RecordCount() <= 0)
        return;
      $sql  = "SELECT a.*,";
      $sql .= " HEX(a.path) path,";
      if ($_updated_threads_first)
        $sql .= " MAX(b.id) threadupdate,";
      $sql .= " UNIX_TIMESTAMP(a.updated) updated,";
      $sql .= " UNIX_TIMESTAMP(a.created) created";
      $sql .= " FROM {t_posting} a";
      if ($_updated_threads_first)
        $sql .= " JOIN {t_posting} b ON a.thread_id=b.thread_id";
      $sql .= " JOIN {t_posting} c ON a.thread_id=c.id";
      $sql .= " WHERE (";

      $first = 1;
      while ($row = &$res->FetchRow()) {
        if (!$first)
          $sql .= " OR ";
        if ($_thread_state->is_folded($row[id]))
          $sql .= "a.id=$row[id]";
        else
          $sql .= "a.thread_id=$row[id]";
        $first = 0;
      }

      $sql .= ")";
      if ($_updated_threads_first) {
        $sql .= " GROUP BY a.id";
        $sql .= " ORDER BY c.priority DESC, threadupdate DESC,";
        $sql .= " a.thread_id DESC,a.path";
      }
      else
        $sql .= " ORDER BY c.priority DESC, a.thread_id DESC,a.path";

      // Walk through those threads.
      $query   = &new FreechSqlQuery($sql);
      $res     = $this->db->Execute($query->sql())
                              or die("ForumDB::foreach_child: 3");
      $numrows = $res->RecordCount();
      $this->_walk_tree($res, $_thread_state, $_func, $_data);
      return $numrows;
    }


    /* This function performs exactly as foreach_child(), except that given a
     * an id, it first looks up the top-level parent of that node and walks
     * through all children of the top level node. */
    function foreach_child_in_thread($_id,
                                     $_offset,
                                     $_limit,
                                     $_thread_state,
                                     $_func,
                                     $_data) {
      $thread_id = $this->_get_thread_id($_id);
      return $this->foreach_child(-1,
                                  $thread_id,
                                  $_offset,
                                  $_limit,
                                  FALSE,
                                  $_thread_state,
                                  $_func,
                                  $_data);
    }


    function _add_where_expression($_query, $_search_values) {
      $sql = $_query->get_sql();
      if ($_search_values['forum_id']) {
        $sql .= " AND forum_id={forum_id}";
        $_query->set_int('forum_id', $_search_values['forum_id']);
      }
      if ($_search_values['userid']) {
        $sql .= " AND user_id={userid}";
        $_query->set_int('userid', $_search_values['userid']);
      }
      if ($_search_values['username']) {
        $sql .= " AND username LIKE {username}";
        $_query->set_string('username', $_search_values['username']);
      }
      if ($_search_values['subject']) {
        $sql .= " AND subject LIKE {subject}";
        $_query->set_string('subject', $_search_values['subject']);
      }
      if ($_search_values['body']) {
        $sql .= " AND body LIKE {body}";
        $_query->set_string('body', $_search_values['body']);
      }
      $_query->set_sql($sql);
    }


    function _walk_list($_res, $_func, $_data) {
      $numrows = $_res->RecordCount();
      while ($row = $_res->FetchRow()) {
        $posting = &new Posting();
        $posting->set_from_db($row);
        call_user_func($_func, $posting, $_data);
      }
      return $numrows;
    }


    function foreach_posting_from_query($_search_query,
                                        $_offset,
                                        $_limit,
                                        $_func,
                                        $_data) {
      $limit  = $_limit  * 1;
      $offset = $_offset * 1;

      $sql  = "SELECT *,";
      $sql .= '(0';
      foreach ($_search_query->get_field_values('subject') as $value)
        $sql .= ' OR subject LIKE {'.$value.'}';
      $sql .= ') subject_matches,';
      $sql .= '(0';
      foreach ($_search_query->get_field_values('body') as $value)
        $sql .= ' OR body LIKE {'.$value.'}';
      $sql .= ') body_matches,';
      $sql  .= "UNIX_TIMESTAMP(updated) updated,";
      $sql  .= "UNIX_TIMESTAMP(created) created";
      $sql  .= " FROM {t_posting}";
      $sql  .= " WHERE is_active AND ";
      $query = &new FreechSqlQuery($sql);
      $_search_query->add_where_expression($query);
      $sql  = $query->sql();
      $sql .= " ORDER BY subject_matches DESC,body_matches DESC,created DESC";
      $query->set_sql($sql);
      $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                            or die("ForumDB::foreach_posting_from_query()");
      return $this->_walk_list($res, $_func, $_data);
    }


    function get_postings_from_query($_search_values,
                                     $_offset = 0,
                                     $_limit  = -1) {
      $limit  = $_limit  * 1;
      $offset = $_offset * 1;

      $sql  = "SELECT *,";
      $sql  .= "UNIX_TIMESTAMP(updated) updated,";
      $sql  .= "UNIX_TIMESTAMP(created) created";
      $sql  .= " FROM {t_posting}";
      $sql  .= " WHERE 1";
      $query = &new FreechSqlQuery($sql);
      if ($_search_values)
        $this->_add_where_expression($query, $_search_values);
      $sql  = $query->sql();
      $sql .= " ORDER BY created DESC";
      $query->set_sql($sql);
      $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                            or die("ForumDB::foreach_posting_from_query()");
      $postings = array();
      while (!$res->EOF) {
        $posting = new Posting;
        $posting->set_from_db($res->FetchRow());
        array_push($postings, $posting);
      }
      return $postings;
    }


    function get_posting_from_query($_search_values, $_offset = 0) {
      $posting = $this->get_postings_from_query($_search_values, $_offset, 1);
      return $posting[0];
    }


    /* Returns latest postings from the given forum.
     * $_forum:   The forum id.
     * $_offset:  The offset of the first posting.
     * $_limit:   The number of postings.
     * $_updates: Whether an updated entry is treated like a newly inserted one.
     * $_func:    A reference to the function to which each posting will be
     *            passed.
     * $_data:    User data, passed to $_func.
     *
     * Args passed to $_func:
     *  $posting: The Posting object.
     *  $data:    The data given this function in $_data.
     */
    function foreach_latest_posting($_forum_id,
                                    $_offset,
                                    $_limit,
                                    $_updates,
                                    $_func,
                                    $_data) {
      $limit  = $_limit  * 1;
      $offset = $_offset * 1;

      $sql  = "SELECT a.*,";
      $sql .= "UNIX_TIMESTAMP(a.updated) updated,";
      $sql .= "UNIX_TIMESTAMP(a.created) created";
      $sql .= " FROM {t_posting} a";
      if ($_forum_id)
        $sql .= " WHERE a.forum_id={forum_id}";
      if ($_updates)
        $sql .= " ORDER BY a.priority DESC,a.updated DESC";
      else
        $sql .= " ORDER BY a.priority DESC,a.created DESC";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('forum_id', $_forum_id);
      $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                          or die("ForumDB::foreach_latest_posting()");
      return $this->_walk_list($res, $_func, $_data);
    }


    /**
     * Returns postings of one particular user.
     * $_user_id: The user id of the user.
     * $_offset:  The offset of the first posting.
     * $_limit:   The number of postings.
     * $_updates: Whether an updated entry is treated like a newly inserted one.
     * $_func:    A reference to the function to which each posting will be
     *            passed.
     * $_data:    User data, passed to $_func.
     *
     * Args passed to $_func:
     *  $posting: The Posting object.
     *  $data:    The data given this function in $_data.
     */
    function foreach_posting_from_user($_user_id,
                                       $_offset,
                                       $_limit,
                                       $_updated_threads_first,
                                       $_thread_state,
                                       $_func,
                                       $_data) {
      $limit  = $_limit  * 1;
      $offset = $_offset * 1;

      // Select the postings of the user.
      $sql  = "SELECT a.id,HEX(a.path) path, a.n_children";
      if ($_updated_threads_first)
        $sql .= " ,MAX(b.id) threadupdate";
      $sql .= " FROM {t_posting} a";
      if ($_updated_threads_first) {
        $sql .= " JOIN {t_posting} b ON a.thread_id=b.thread_id";
        $sql .= " AND b.path LIKE CONCAT(REPLACE(REPLACE(REPLACE(a.path, '\\\\', '\\\\\\\\'), '_', '\\_'), '%', '\\%'), '%')";
        $sql .= " AND LENGTH(b.path)<=LENGTH(a.path)+5";
      }
      $sql .= " WHERE a.user_id={userid}";
      if ($_updated_threads_first) {
        $sql .= " GROUP BY a.id";
        $sql .= " ORDER BY threadupdate DESC,a.created DESC";
      }
      else
        $sql .= " ORDER BY a.created DESC";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('userid', $_user_id);
      //echo $query->sql();
      //$this->db->debug=1;
      $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                     or die("ForumDB::foreach_posting_from_user(): 1");

      // Grab the direct responses to those postings.
      if ($res->RecordCount() <= 0)
        return;
      $sql  = "SELECT b.*,";
      $sql .= " b.n_descendants n_children,";
      $sql .= " IF(a.id=b.id, '', HEX(SUBSTRING(b.path, -5))) path,";
      if ($_updated_threads_first)
        $sql .= " MAX(c.id) threadupdate,";
      $sql .= " UNIX_TIMESTAMP(b.updated) updated,";
      $sql .= " UNIX_TIMESTAMP(b.created) created";
      $sql .= " FROM {t_posting} a";
      $sql .= " JOIN {t_posting} b ON b.thread_id=a.thread_id";
      $sql .= " AND b.path LIKE CONCAT(REPLACE(REPLACE(REPLACE(a.path, '\\\\', '\\\\\\\\'), '_', '\\_'), '%', '\\%'), '%')";
      $sql .= " AND LENGTH(b.path)<=LENGTH(a.path)+5";
      if ($_updated_threads_first) {
        $sql .= " JOIN {t_posting} c ON c.thread_id=a.thread_id";
        $sql .= " AND c.path LIKE CONCAT(REPLACE(REPLACE(REPLACE(a.path, '\\\\', '\\\\\\\\'), '_', '\\_'), '%', '\\%'), '%')";
        $sql .= " AND LENGTH(c.path)<=LENGTH(a.path)+5";
      }
      $sql .= " WHERE (";

      $first = 1;
      while ($row = &$res->FetchRow()) {
        if (!$first)
          $sql .= " OR ";
        if ($_thread_state->is_folded($row[id]))
          $sql .= "(a.id=$row[id] AND b.id=$row[id])";
        else
          $sql .= "a.id=$row[id]";
        $first = 0;
      }

      $sql .= ")";
      if ($_updated_threads_first) {
        $sql .= " GROUP BY a.id,b.id";
        $sql .= " ORDER BY threadupdate DESC,b.id";
      }
      else
        $sql .= " ORDER BY a.id DESC,b.id";

      // Pass all postings to the given function.
      $query   = &new FreechSqlQuery($sql);
      $res     = $this->db->Execute($query->sql())
                          or die("ForumDB::foreach_posting_from_user()");
      $numrows = $res->RecordCount();
      $this->_walk_tree($res, $_thread_state, $_func, $_data);
      return $numrows;
    }


    /* Returns the total number of entries in the given forum. */
    function get_n_postings($_search_values = NULL, $_since = 0, $_until = 0) {
      $sql  = "SELECT COUNT(*)";
      $sql .= " FROM {t_posting}";
      $sql .= " WHERE 1";
      if ($_since)
        $sql .= " AND created > FROM_UNIXTIME({since})";
      if ($_until)
        $sql .= " AND created < FROM_UNIXTIME({until})";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('since', $_since);
      $query->set_int('until', $_until);
      if ($_search_values)
        $this->_add_where_expression($query, $_search_values);
      return $this->db->GetOne($query->sql());
    }


    function get_n_postings_from_query($_search_query) {
      $sql  = "SELECT COUNT(*)";
      $sql .= " FROM {t_posting}";
      $sql .= " WHERE is_active AND ";
      $query = &new FreechSqlQuery($sql);
      $_search_query->add_where_expression($query);
      return $this->db->GetOne($query->sql());
    }


     /* Returns the total number of threads in the given forum. */
    function get_n_threads($_forum_id) {
      $sql  = "SELECT COUNT(DISTINCT thread_id)";
      $sql .= " FROM {t_posting}";
      if ($_forum_id)
        $sql .= " WHERE forum_id={forum_id}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('forum_id', $_forum_id);
      $n = $this->db->GetOne($query->sql());
      return $n;
    }


    function get_n_postings_from_user_id($_user_id, $_since = 0) {
      $sql  = "SELECT COUNT(*)";
      $sql .= " FROM {t_posting}";
      $sql .= " WHERE user_id={user_id}";
      if ($_since)
        $sql .= " AND created > FROM_UNIXTIME({since})";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('user_id', $_user_id);
      $query->set_int('since',   $_since);
      return $this->db->GetOne($query->sql());
    }


    function get_n_postings_from_ip_hash($_ip_hash, $_since = 0) {
      $sql  = "SELECT COUNT(*)";
      $sql .= " FROM {t_posting}";
      $sql .= " WHERE ip_hash={ip_hash}";
      if ($_since)
        $sql .= " AND created > FROM_UNIXTIME({since})";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('ip_hash', $_ip_hash);
      $query->set_int('since',   $_since);
      return $this->db->GetOne($query->sql());
    }


    function get_duplicate_id_from_posting($_posting) {
      $sql  = "SELECT id";
      $sql .= " FROM {t_posting}";
      $sql .= " WHERE created > FROM_UNIXTIME({since}) AND hash={hash}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('since', time() - 60 * 60 * 2);
      $query->set_string('hash', $_posting->get_hash());
      $res = $this->db->Execute($query->sql())
                            or die("ForumDB::get_duplicate_id_from_posting()");
      if ($res->EOF)
        return;
      $row = $res->FetchRow();
      return $row[id];
    }
  }
?>
