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
    function _lock_write($_forum) {
      $query = &new FreechSqlQuery("LOCK TABLE {$_forum} WRITE");
      //$this->db->execute($query->sql()) or die("ForumDB::lock_write()");
    }


    function _unlock_write() {
      $query = &new FreechSqlQuery("UNLOCK TABLES");
      //$this->db->execute($query->sql()) or die("ForumDB::unlock_write()");
    }


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
      $sql  = "SELECT path FROM {t_message} t1";
      $sql .= " WHERE t1.id={id}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('id', $_id);
      $row = $this->db->GetRow($query->sql()) or die("ForumDB::_get_path()");
      return $row[path];
    }


    function _get_thread_id($_id) {
      $sql = "SELECT thread_id FROM {t_message} WHERE id={id}";
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
      $sql  = "SELECT id FROM {t_message}";
      $sql .= " WHERE thread_id={thread_id}";
      $sql .= " AND active=1";
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
      $sql  = "SELECT id FROM {t_message}";
      $sql .= " WHERE thread_id={thread_id}";
      $sql .= " AND active=1";
      $sql .= " AND is_parent=0";
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
      $sql  = "SELECT thread_id FROM {t_message}";
      $sql .= " WHERE forum_id={forum_id} AND thread_id<{thread_id}";
      $sql .= " AND (active=1 OR n_children>0)";
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
      $sql  = "SELECT thread_id FROM {t_message}";
      $sql .= " WHERE forum_id={forum_id} AND thread_id>{thread_id}";
      $sql .= " AND (active=1 OR n_children>0)";
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
    /* Returns a message from the given forum.
     * $_forum: The forum id.
     * $_id:    The id of the message.
     * Returns: An object containing the fields
     *            - id
     *            - username
     *            - subject
     *            - body
     *            - active
     *            - time
     */
    function &get_message($_forum_id, $_id) {
      $sql  = "SELECT id,forum_id,priority,user_id,thread_id,HEX(path) path,";
      $sql .= "n_children,username,subject,body,active,";
      $sql .= "ip_hash,";
      $sql .= "UNIX_TIMESTAMP(updated) updated,";
      $sql .= "UNIX_TIMESTAMP(created) created";
      $sql .= " FROM {t_message}";
      $sql .= " WHERE id={id}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('id', $_id);
      if (!$row = $this->db->GetRow($query->sql()))
        return;
      $row[prev_thread_id]   = $this->_get_prev_thread_id($_forum_id,
                                                          $row[thread_id]);
      $row[next_thread_id]   = $this->_get_next_thread_id($_forum_id,
                                                          $row[thread_id]);
      $row[prev_message_id]  = $this->_get_prev_entry_id($_forum_id,
                                                         $row[thread_id],
                                                         $row[path]);
      $row[next_message_id]  = $this->_get_next_entry_id($_forum_id,
                                                         $row[thread_id],
                                                         $row[path]);
      if (strlen($row[path]) / 2 > 252)  // Path as long as the the DB field.
        $row[allow_answer] = FALSE;
      if ($row[id] == $row[thread_id])
        $row[relation] = MESSAGE_RELATION_PARENT_UNFOLDED;

      $message = &new Message;
      $message->set_from_db($row);
      return $message;
    }


    /* Insert a new child.
     *
     * $_forum:   The forum id.
     * $_parent:  The id of the entry under which the new entry is placed.
     * $_message: The message to be inserted.
     * Returns:   The id of the newly inserted entry.
     */
    function insert_entry($_forum_id, $_parentid, &$_message) {
      $body = trim($_message->get_body() . "\n\n" . $_message->get_signature());
      $this->_lock_write("t_message");
      //$this->db->debug = true;

      // Fetch the parent row.
      $sql  = "SELECT forum_id,thread_id,HEX(path) path,active";
      $sql .= " FROM {t_message}";
      $sql .= " WHERE id={parentid}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('parentid', $_parentid);
      $parentrow = $this->db->GetRow($query->sql());

      $this->db->StartTrans();

      // Insert the new node.
      if ($parentrow) {
        if (!$parentrow[active])
          die("ForumDB::insert_entry(): Parent inactive.\n");
        if (strlen($parentrow[path]) / 2 > 252)
          die("ForumDB::insert_entry(): Hierarchy too deep.\n");

        // Insert a new child.
        //FIXME: user_id as an arg, as soon as logins are implemented.
        $sql  = "INSERT INTO {t_message}";
        $sql .= " (forum_id, thread_id, priority, user_id, username, subject, body,";
        $sql .= " hash, ip_hash, created)";
        $sql .= " VALUES (";
        $sql .= " {forum_id}, {thread_id}, {priority}, {user_id}, {username},";
        $sql .= " {subject}, {body}, {hash}, {ip_hash}, NULL";
        $sql .= ")";
        $query = &new FreechSqlQuery($sql);
        $query->set_int('forum_id',  $parentrow[forum_id]);
        $query->set_int('thread_id', $parentrow[thread_id]);
        $query->set_int('priority',  $_message->get_priority());
        $query->set_int('user_id',   $_message->get_user_id());
        $query->set_string('username', $_message->get_username());
        $query->set_string('subject',  $_message->get_subject());
        $query->set_string('body',     $body);
        $query->set_string('hash',     $_message->get_hash());
        $query->set_string('ip_hash',  $_message->get_ip_address_hash());
        $this->db->Execute($query->sql())
                or die("ForumDB::insert_entry(): Insert1.");
        $newid = $this->db->Insert_Id();

        // Update the child's path.
        $sql  = "UPDATE {t_message} SET path=";
        if ($parentrow[path] != '') {
          $len = strlen($parentrow[path]);
          $parentrow[path] = substr($parentrow[path], 0, $len - 2);
          $sql .= " CONCAT(0x$parentrow[path],";
          $sql .= "        0x" . $this->_int2hex($newid) . "00)";
        }
        else {
          $sql .= " 0x" . $this->_int2hex($newid) . "00";
        }
        $sql .= " WHERE id={newid}";
        $query = &new FreechSqlQuery($sql);
        $query->set_int('newid', $newid);
        $this->db->Execute($query->sql())
                or die("ForumDB::insert_entry(): Path.");

        // Update n_descendants and n_children in one run...
        if ($_parentid == $parentrow[thread_id]) {
          $sql  = "UPDATE {t_message}";
          $sql .= " SET n_children=n_children+1,";
          $sql .= " n_descendants=n_descendants+1";
          $sql .= " WHERE id={parentid}";
          $query = &new FreechSqlQuery($sql);
          $query->set_int('parentid', $_parentid);
          $this->db->Execute($query->sql())
                  or die("ForumDB::insert_entry(): n++");
        }

        // ...unless it is necessary to update two database sets.
        else {
          $sql  = "UPDATE {t_message} SET n_children=n_children+1";
          $sql .= " WHERE id={thread_id}";
          $query = &new FreechSqlQuery($sql);
          $query->set_int('thread_id', $parentrow[thread_id]);
          $this->db->Execute($query->sql())
                  or die("ForumDB::insert_entry(): n_child fail");

          $sql  = "UPDATE {t_message} SET n_descendants=n_descendants+1";
          $sql .= " WHERE id={parentid}";
          $query = &new FreechSqlQuery($sql);
          $query->set_int('parentid', $_parentid);
          $this->db->Execute($query->sql())
                  or die("ForumDB::insert_entry(): n_desc");
        }
      }

      // Insert a new thread.
      else {
        $sql  = "INSERT INTO {t_message}";
        $sql .= " (path, forum_id, priority, user_id, thread_id, is_parent, username,";
        $sql .= "  subject, body, hash, ip_hash, created)";
        $sql .= " VALUES (";
        $sql .= " '', {forum_id}, {priority}, {user_id}, 0, 1, {username},";
        $sql .= " {subject}, {body}, {hash}, {ip_hash}, NULL";
        $sql .= ")";
        $query = &new FreechSqlQuery($sql);
        $query->set_int('forum_id', $_forum_id);
        $query->set_int('priority', $_message->get_priority());
        $query->set_int('user_id',  $_message->get_user_id());
        $query->set_string('username', $_message->get_username());
        $query->set_string('subject',  $_message->get_subject());
        $query->set_string('body',     $body);
        $query->set_string('hash',     $_message->get_hash());
        $query->set_string('ip_hash',  $_message->get_ip_address_hash());
        $this->db->Execute($query->sql())
                or die("ForumDB::insert_entry(): Insert2.".$query->sql());
        $newid = $this->db->Insert_Id();

        // Set the thread id.
        // FIXME: Is there a better way to do this?
        $sql  = "UPDATE {t_message} SET thread_id={newid}";
        $sql .= " WHERE id={newid}";
        $query = &new FreechSqlQuery($sql);
        $query->set_int('newid', $newid);
        $this->db->Execute($query->sql())
                or die("ForumDB::insert_entry(): thread_id");
      }

      $this->db->CompleteTrans();

      $this->_unlock_write();
      return $newid;
    }


    function save_entry($_forum_id, $_parent_id, &$_message) {
      //FIXME: This currently does not support moving messages (i.e. changing
      // the path, thread, or forum)

      $this->_lock_write("t_message");
      //$this->db->debug = true;

      $this->db->StartTrans();
      $sql  = "UPDATE {t_message} SET";
      $sql .= " forum_id={forum_id},";
      $sql .= " priority={priority},";
      $sql .= " user_id={user_id},";
      $sql .= " username={username},";
      $sql .= " subject={subject},";
      $sql .= " body={body},";
      $sql .= " hash={hash},";
      $sql .= " ip_hash={ip_hash},";
      $sql .= " updated=FROM_UNIXTIME({updated})";
      $sql .= " WHERE id={id}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('id',       $_message->get_id());
      $query->set_int('forum_id', $_forum_id);
      $query->set_int('priority', $_message->get_priority());
      $query->set_int('user_id',  $_message->get_user_id());
      $query->set_int('updated',  time());
      $query->set_string('username', $_message->get_username());
      $query->set_string('subject',  $_message->get_subject());
      $query->set_string('body',     $_message->get_body());
      $query->set_string('hash',     $_message->get_hash());
      $query->set_string('ip_hash',  $_message->get_ip_address_hash());
      $this->db->Execute($query->sql()) or die("ForumDB::save_entry(): 1");

      $this->db->CompleteTrans();
      $this->_unlock_write();
    }


    function find_duplicate($_message) {
      $sql  = "SELECT id";
      $sql .= " FROM {t_message}";
      $sql .= " WHERE created > FROM_UNIXTIME({since}) AND hash={hash}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('since', time() - 60 * 60 * 2);
      $query->set_string('hash', $_message->get_hash());
      $res = $this->db->Execute($query->sql())
                or die("ForumDB::find_duplicate()");
      if ($res->EOF)
        return;
      $row = $res->FetchRow();
      return $row[id];
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

        $message = &new Message();
        $message->set_from_db($row);
        call_user_func($_func, $message, $indents, $_data);

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


    /* Walks through the tree starting from $id, passing each message to the
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
        $sql  = "SELECT id,HEX(path) path";
        $sql .= " FROM {t_message}";
        $sql .= " WHERE id={id}";
        $query = &new FreechSqlQuery($sql);
        $query->set_int('id', $_id);
        $res = $this->db->Execute($query->sql())
                       or die("ForumDB::foreach_child(): 1");
      }
      else {
        // Select all root nodes.
        $sql  = "SELECT a.id,HEX(a.path) path, a.n_children";
        if ($_updated_threads_first)
          $sql .= " ,MAX(b.id) threadupdate";
        $sql .= " FROM {t_message} a";
        if ($_updated_threads_first)
          $sql .= " LEFT JOIN {t_message} b ON a.thread_id=b.thread_id";
        $sql .= " WHERE a.forum_id={forum_id} AND a.is_parent=1";
        if ($_updated_threads_first) {
          $sql .= " GROUP BY a.id";
          $sql .= " ORDER BY a.priority DESC, threadupdate DESC,";
          $sql .= " a.thread_id DESC,path";
        }
        else
          $sql .= " ORDER BY a.priority DESC, a.thread_id DESC,path";
        $query = &new FreechSqlQuery($sql);
        $query->set_int('forum_id', $_forum_id);
        //$this->db->debug=1;
        $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                       or die("ForumDB::foreach_child(): 2");
      }

      // Build the SQL request to grab the complete threads.
      if ($res->RecordCount() <= 0)
        return;
      $sql  = "SELECT a.id,a.forum_id,a.priority,a.user_id,HEX(a.path) path,";
      $sql .= " a.n_children,a.n_descendants, a.username, a.subject,";
      $sql .= " a.body,a.active,a.ip_hash,";
      if ($_updated_threads_first)
        $sql .= " MAX(b.id) threadupdate,";
      $sql .= " UNIX_TIMESTAMP(a.updated) updated,";
      $sql .= " UNIX_TIMESTAMP(a.created) created";
      $sql .= " FROM {t_message} a";
      if ($_updated_threads_first)
        $sql .= " LEFT JOIN {t_message} b ON a.thread_id=b.thread_id";
      $sql .= " LEFT JOIN {t_message} c ON a.thread_id=c.id";
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
      $sql .= " GROUP BY a.id";
      if ($_updated_threads_first) {
        $sql .= " ORDER BY c.priority DESC, threadupdate DESC,";
        $sql .= " a.thread_id DESC,path";
      }
      else
        $sql .= " ORDER BY c.priority DESC, a.thread_id DESC,path";

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
    function foreach_child_in_thread($_forum_id,
                                     $_id,
                                     $_offset,
                                     $_limit,
                                     $_thread_state,
                                     $_func,
                                     $_data) {
      $thread_id = $this->_get_thread_id($_id);
      return $this->foreach_child($_forum_id,
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
        $message = &new Message();
        $message->set_from_db($row);
        call_user_func($_func, $message, $_data);
      }
      return $numrows;
    }


    function foreach_message($_search_values,
                             $_offset,
                             $_limit,
                             $_func,
                             $_data) {
      $limit  = $_limit  * 1;
      $offset = $_offset * 1;

      $sql  = "SELECT id,forum_id,priority,user_id,username,";
      $sql .= "subject,body,active,";
      if ($_search_values['username'])
        $sql .= "username LIKE {username} name_matches,";
      else
        $sql .= "0 name_matches,";
      if ($_search_values['subject'])
        $sql .= "subject LIKE {subject} subject_matches,";
      else
        $sql .= "0 subject_matches,";
      if ($_search_values['body'])
        $sql .= "body LIKE {body} body_matches,";
      else
        $sql .= "0 body_matches,";
      $sql  .= "UNIX_TIMESTAMP(updated) updated,";
      $sql  .= "UNIX_TIMESTAMP(created) created";
      $sql  .= " FROM {t_message}";
      $sql  .= " WHERE active=1";
      $query = &new FreechSqlQuery($sql);
      $this->_add_where_expression($query, $_search_values);
      $sql  = $query->sql();
      $sql .= " ORDER BY subject_matches DESC,body_matches DESC,created DESC";
      $query->set_sql($sql);
      $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                                      or die("ForumDB::foreach_message()");
      return $this->_walk_list($res, $_func, $_data);
    }


    function foreach_message_from_query($_search_query,
                                        $_offset,
                                        $_limit,
                                        $_func,
                                        $_data) {
      $limit  = $_limit  * 1;
      $offset = $_offset * 1;

      $sql  = "SELECT id,forum_id,priority,user_id,username,";
      $sql .= "subject,body,active,";
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
      $sql  .= " FROM {t_message}";
      $sql  .= " WHERE active=1 AND ";
      $query = &new FreechSqlQuery($sql);
      $_search_query->add_where_expression($query);
      $sql  = $query->sql();
      $sql .= " ORDER BY subject_matches DESC,body_matches DESC,created DESC";
      $query->set_sql($sql);
      $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                            or die("ForumDB::foreach_message_from_query()");
      return $this->_walk_list($res, $_func, $_data);
    }


    /* Returns latest messages from the given forum.
     * $_forum:   The forum id.
     * $_offset:  The offset of the first message.
     * $_limit:   The number of messages.
     * $_updates: Whether an updated entry is treated like a newly inserted one.
     * $_func:    A reference to the function to which each message will be
     *            passed.
     * $_data:    User data, passed to $_func.
     *
     * Args passed to $_func:
     *  $message: The Message object.
     *  $data:    The data given this function in $_data.
     */
    function foreach_latest_message($_forum_id,
                                    $_offset,
                                    $_limit,
                                    $_updates,
                                    $_func,
                                    $_data) {
      $limit  = $_limit  * 1;
      $offset = $_offset * 1;

      $sql  = "SELECT a.id,a.forum_id,a.priority,a.user_id,a.username,";
      $sql .= "a.subject,a.body,a.active,";
      $sql .= "UNIX_TIMESTAMP(a.updated) updated,";
      $sql .= "UNIX_TIMESTAMP(a.created) created";
      $sql .= " FROM {t_message} a";
      if ($_forum_id)
        $sql .= " WHERE a.forum_id={forum_id}";
      if ($_updates)
        $sql .= " ORDER BY a.priority DESC,a.updated DESC";
      else
        $sql .= " ORDER BY a.priority DESC,a.created DESC";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('forum_id', $_forum_id);
      $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                          or die("ForumDB::foreach_latest_message()");
      return $this->_walk_list($res, $_func, $_data);
    }


    /**
     * Returns messages of one particular user.
     * $_user_id: The user id of the user.
     * $_offset:  The offset of the first message.
     * $_limit:   The number of messages.
     * $_updates: Whether an updated entry is treated like a newly inserted one.
     * $_func:    A reference to the function to which each message will be
     *            passed.
     * $_data:    User data, passed to $_func.
     *
     * Args passed to $_func:
     *  $message: The Message object.
     *  $data:    The data given this function in $_data.
     */
    function foreach_message_from_user($_user_id,
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
      $sql .= " FROM {t_message} a";
      if ($_updated_threads_first) {
        $sql .= " LEFT JOIN {t_message} b ON a.thread_id=b.thread_id";
        $sql .= " AND b.path LIKE CONCAT(a.path, '%')";
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
                     or die("ForumDB::foreach_message_from_user(): 1");

      // Grab the direct responses to those postings.
      if ($res->RecordCount() <= 0)
        return;
      $sql  = "SELECT b.id,b.forum_id,b.priority,b.user_id,";
      $sql .= " b.n_descendants n_children,";
      $sql .= " b.n_descendants,";
      $sql .= " b.username,";
      $sql .= " b.subject,b.body,b.active,b.ip_hash,";
      $sql .= " IF(a.id=b.id, '', HEX(SUBSTRING(b.path, -5))) path,";
      $sql .= " a.id=b.id is_parent,";
      if ($_updated_threads_first)
        $sql .= " MAX(c.id) threadupdate,";
      $sql .= " UNIX_TIMESTAMP(b.updated) updated,";
      $sql .= " UNIX_TIMESTAMP(b.created) created";
      $sql .= " FROM {t_message} a";
      $sql .= " LEFT JOIN {t_message} b ON b.thread_id=a.thread_id";
      $sql .= " AND b.path LIKE CONCAT(REPLACE(REPLACE(REPLACE(a.path, '\\\\', '\\\\\\\\'), '_', '\\_'), '%', '\\%'), '%')";
      $sql .= " AND LENGTH(b.path)<=LENGTH(a.path)+5";
      if ($_updated_threads_first) {
        $sql .= " LEFT JOIN {t_message} c ON c.thread_id=a.thread_id";
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
        $sql .= " GROUP BY b.id";
        $sql .= " ORDER BY threadupdate DESC,created";
      }
      else
        $sql .= " ORDER BY a.created DESC,created";

      // Pass all postings to the given function.
      $query   = &new FreechSqlQuery($sql);
      //echo $query->sql();
      $res     = $this->db->Execute($query->sql())
                          or die("ForumDB::foreach_message_from_user()");
      $numrows = $res->RecordCount();
      $this->_walk_tree($res, $_thread_state, $_func, $_data);
      return $numrows;
    }


    /* Returns the total number of entries in the given forum. */
    function get_n_messages($_search_values, $_since = 0) {
      $sql  = "SELECT COUNT(*)";
      $sql .= " FROM {t_message}";
      $sql .= " WHERE 1";
      if ($_since)
        $sql .= " AND created > FROM_UNIXTIME({since})";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('since', $_since);
      $this->_add_where_expression($query, $_search_values);
      return $this->db->GetOne($query->sql());
    }


    function get_n_messages_from_query($_search_query) {
      $sql  = "SELECT COUNT(*)";
      $sql .= " FROM {t_message}";
      $sql .= " WHERE active=1 AND ";
      $query = &new FreechSqlQuery($sql);
      $_search_query->add_where_expression($query);
      return $this->db->GetOne($query->sql());
    }


     /* Returns the total number of threads in the given forum. */
    function get_n_threads($_forum_id) {
      $sql  = "SELECT COUNT(DISTINCT thread_id)";
      $sql .= " FROM {t_message}";
      if ($_forum_id)
        $sql .= " WHERE forum_id={forum_id}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('forum_id', $_forum_id);
      $n = $this->db->GetOne($query->sql());
      return $n;
    }


    /* Returns the number of nodes below $id. */
    function get_n_children($_forum_id, $_id) {
      $sql = "SELECT n_children FROM {t_message} WHERE id={id}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('id', $_id);
      $n = $this->db->GetOne($query->sql());
      return $n;
    }
  }
?>
