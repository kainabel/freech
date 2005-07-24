<?php
  /*
  Tefinch.
  Copyright (C) 2003 Samuel Abels, <spam debain org>

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
      $query = &new SqlQuery("LOCK TABLE {$_forum} WRITE");
      //$this->db->execute($query->sql()) or die("ForumDB::lock_write()");
    }
    
    
    function _unlock_write() {
      $query = &new SqlQuery("UNLOCK TABLES");
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
      $query = &new SqlQuery($sql);
      $query->set_int('id', $_id);
      $row = $this->db->GetRow($query->sql()) or die("ForumDB::_get_path()");
      return $row[path];
    }
    
    
    function _get_threadid($_id) {
      $sql = "SELECT threadid FROM {t_message} WHERE id={id}";
      $query = &new SqlQuery($sql);
      $query->set_int('id', $_id);
      $row = $this->db->GetRow($query->sql())
                          or die("ForumDB::_get_threadid()");
      return $row[threadid] ? $row[threadid] : 0;
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
    function _get_prev_entry_id($_forumid, $_threadid, $_path) {
      if (!$_path)
        return 0;
      $sql  = "SELECT id FROM {t_message}";
      $sql .= " WHERE threadid={threadid}";
      $sql .= " AND active=1";
      $sql .= " AND STRCMP(CONCAT('0x', HEX(path)), '{path}')=-1";
      $sql .= " ORDER BY HEX(path) DESC";
      $query = &new SqlQuery($sql);
      $query->set_int('threadid', $_threadid);
      $query->set_hex('path',     $_path);
      $res = $this->db->SelectLimit($query->sql(), 1)
                          or die("ForumDB::_get_prev_entry_id()");
      $row = $res->FetchRow($res);
      return $row[id];
    }
    
    
    /* Given the path of any node, this function returns the id of the next
     * entry in the same thread, or 0 if there is no next entry.
     */
    function _get_next_entry_id($_forumid, $_threadid, $_path) {
      $sql  = "SELECT id FROM {t_message}";
      $sql .= " WHERE threadid={threadid}";
      $sql .= " AND active=1";
      $sql .= " AND is_parent=0";
      if ($_path)
        $sql .= " AND STRCMP(CONCAT('0x', HEX(path)), '{path}')=1";
      $sql .= " ORDER BY HEX(path)";
      $query = &new SqlQuery($sql);
      $query->set_int('threadid', $_threadid);
      $query->set_hex('path',     $_path);
      $res = $this->db->SelectLimit($query->sql(), 1)
                          or die("ForumDB::_get_next_entry_id()");
      $row = $res->FetchRow($res);
      return $row[id];
    }
    
    
    /* Given a threadid, this function returns the id of the previous
     * thread in the given forum, or 0 if there is no previous thread.
     * The threadid equals the id of the toplevel node in a thread.
     */
    function _get_prev_thread_id($_forumid, $_threadid) {
      $sql  = "SELECT threadid FROM {t_message}";
      $sql .= " WHERE forumid={forumid} AND threadid<{threadid}";
      $sql .= " AND (active=1 OR n_children>0)";
      $sql .= " ORDER BY threadid DESC";
      $query = &new SqlQuery($sql);
      $query->set_int('forumid',  $_forumid);
      $query->set_int('threadid', $_threadid);
      $res = $this->db->SelectLimit($query->sql(), 1)
                          or die("ForumDB::_get_prev_thread_id()");
      $row = $res->FetchRow($res);
      return $row[threadid];
    }
    
    
    /* Given a threadid, this function returns the id of the next
     * thread in the given forum, or 0 if there is no next thread.
     * The threadid equals the id of the toplevel node in a thread.
     */
    function _get_next_thread_id($_forumid, $_threadid) {
      $sql  = "SELECT threadid FROM {t_message}";
      $sql .= " WHERE forumid={forumid} AND threadid>{threadid}";
      $sql .= " AND (active=1 OR n_children>0)";
      $sql .= " ORDER BY threadid";
      $query = &new SqlQuery($sql);
      $query->set_int('forumid',  $_forumid);
      $query->set_int('threadid', $_threadid);
      $res = $this->db->SelectLimit($query->sql(), 1)
                          or die("ForumDB::_get_next_thread_id()");
      $row = $res->FetchRow($res);
      return $row[threadid];
    }
    
    
    /***********************************************************************
     * Public API.
     ***********************************************************************/
    /* Returns a message from the given forum.
     * $_forum: The forum id.
     * $_id:    The id of the message.
     * Returns: An object containing the fields
     *            - id
     *            - name
     *            - title
     *            - text
     *            - active
     *            - time
     */
    function &get_message($_forumid, $_id) {
      $sql  = "SELECT id,forumid,threadid,HEX(path) path,n_children,";
      $sql .= "name username,title subject,text body,active,";
      $sql .= "UNIX_TIMESTAMP(updated) updated,";
      $sql .= "UNIX_TIMESTAMP(created) created";
      $sql .= " FROM {t_message}";
      $sql .= " WHERE id={id}";
      $query = &new SqlQuery($sql);
      $query->set_int('id', $_id);
      if (!$row = $this->db->GetRow($query->sql()))
        return;
      $row[prev_thread_id]   = $this->_get_prev_thread_id($_forumid,
                                                          $row[threadid]);
      $row[next_thread_id]   = $this->_get_next_thread_id($_forumid,
                                                          $row[threadid]);
      $row[prev_message_id]  = $this->_get_prev_entry_id($_forumid,
                                                         $row[threadid],
                                                         $row[path]);
      $row[next_message_id]  = $this->_get_next_entry_id($_forumid,
                                                         $row[threadid],
                                                         $row[path]);
      if (strlen($row[path]) / 2 > 252)  // Path as long as the the DB field.
        $row[allow_answer] = FALSE;
      if ($row[id] == $row[threadid])
        $row[relation] = MESSAGE_RELATION_PARENT_UNFOLDED;
      
      $message = &new Message;
      $message->set_from_db($row);
      return $message;
    }
    
    
    /* Insert a &new child.
     *
     * $_forum:   The forum id.
     * $_parent:  The id of the entry under which the &new entry is placed.
     * $_message: The message to be inserted.
     * Returns:   The id of the newly inserted entry.
     */
    function insert_entry($_forumid, $_parentid, &$_message) {
      $this->_lock_write("t_message");
      
      // Fetch the parent row.
      $sql  = "SELECT forumid,threadid,HEX(path) path,active";
      $sql .= " FROM {t_message}";
      $sql .= " WHERE id={parentid}";
      $query = &new SqlQuery($sql);
      $query->set_int('parentid', $_parentid);
      $parentrow = $this->db->GetRow($query->sql());
      
      $query = &new SqlQuery("SET AUTOCOMMIT=0;");
      $this->db->Execute($query->sql()) or die("ForumDB::insert_entry(): AC0");
      $query = &new SqlQuery("BEGIN;");
      $this->db->Execute($query->sql()) or die("ForumDB::insert_entry(): Beg");
      
      // Insert the &new node.
      $username = mysql_escape_string($_message->get_username());
      $subject  = mysql_escape_string($_message->get_subject());
      $body     = mysql_escape_string($_message->get_body());
      if ($parentrow) {
        if (!$parentrow[active])
          die("ForumDB::insert_entry(): Parent inactive.\n");
        if (strlen($parentrow[path]) / 2 > 252)
          die("ForumDB::insert_entry(): Hierarchy too deep.\n");
        
        // Insert a &new child.
        //FIXME: u_id as an arg, as soon as logins are implemented.
        $sql  = "INSERT INTO {t_message}";
        $sql .= " (forumid, threadid, u_id, name, title, text, created)";
        $sql .= " VALUES (";
        $sql .= " {forumid}, {threadid}, 2, {name}, {subject}, {body}, NULL";
        $sql .= ")";
        $query = &new SqlQuery($sql);
        $query->set_int('forumid',  $parentrow[forumid]);
        $query->set_int('threadid', $parentrow[threadid]);
        $query->set_string('name',    $_message->get_username());
        $query->set_string('subject', $_message->get_subject());
        $query->set_string('body',    $_message->get_body());
        $this->db->Execute($query->sql())
                or die("ForumDB::insert_entry(): Insert1.");
        $newid = $this->db->Insert_Id();
        
        // Update the child's path.
        $sql  = "UPDATE {t_message} SET path=";
        if ($parentrow[path] != '') {
          $parentrow[path] = substr($parentrow[path],
                                    0,
                                    strlen($parentrow[path]) - 2);
          $sql .= " CONCAT(0x$parentrow[path],";
          $sql .= "        0x" . $this->_int2hex($newid) . "00)";
        }
        else {
          $sql .= " 0x" . $this->_int2hex($newid) . "00";
        }
        $sql .= " WHERE id={newid}";
        $query = &new SqlQuery($sql);
        $query->set_int('newid', $newid);
        $this->db->Execute($query->sql())
                or die("ForumDB::insert_entry(): Path");
        
        // Update n_descendants and n_children in one run...
        if ($_parentid == $parentrow[threadid]) {
          $sql  = "UPDATE {t_message}";
          $sql .= " SET n_children=n_children+1,";
          $sql .= " n_descendants=n_descendants+1";
          $sql .= " WHERE id={parentid}";
          $query = &new SqlQuery($sql);
          $query->set_int('parentid', $_parentid);
          $this->db->Execute($query->sql())
                  or die("ForumDB::insert_entry(): n++");
        }
        
        // ...unless it is necessary to update two database sets.
        else {
          $sql  = "UPDATE {t_message} SET n_children=n_children+1";
          $sql .= " WHERE id={threadid}";
          $query = &new SqlQuery($sql);
          $query->set_int('threadid', $parentrow[threadid]);
          $this->db->Execute($query->sql())
                  or die("ForumDB::insert_entry(): n_child fail");
          
          $sql  = "UPDATE {t_message} SET n_descendants=n_descendants+1";
          $sql .= " WHERE id={parentid}";
          $query = &new SqlQuery($sql);
          $query->set_int('parentid', $_parentid);
          $this->db->Execute($query->sql())
                  or die("ForumDB::insert_entry(): n_desc");
        }
      }
      
      // Insert a &new thread.
      else {
        //FIXME: u_id as an arg, as soon as logins are implemented.
        $sql  = "INSERT INTO {t_message}";
        $sql .= " (path, forumid, threadid, is_parent, u_id, name, title,";
        $sql .= "  text, created)";
        $sql .= " VALUES (";
        $sql .= " '', {forumid}, 0, 1, 2, {name}, {subject}, {body}, NULL";
        $sql .= ")";
        $query = &new SqlQuery($sql);
        $query->set_int('forumid', $_forumid);
        $query->set_string('name',    $_message->get_username());
        $query->set_string('subject', $_message->get_subject());
        $query->set_string('body',    $_message->get_body());
        $this->db->Execute($query->sql())
                or die("ForumDB::insert_entry(): Insert2.");
        $newid = $this->db->Insert_Id();
        
        // Set the thread id.
        // FIXME: Is there a better way to do this?
        $sql  = "UPDATE {t_message} SET threadid={newid}";
        $sql .= " WHERE id={newid}";
        $query = &new SqlQuery($sql);
        $query->set_int('newid', $newid);
        $this->db->Execute($query->sql())
                or die("ForumDB::insert_entry(): threadid");
      }
      
      $query = &new SqlQuery("COMMIT;");
      $this->db->Execute($query->sql()) or die("ForumDB::insert_entry(): Com");
      
      $this->_unlock_write();
      return $newid;
    }
    
    
    /* Walks through the tree starting from $id, passing each message to the
     * function given in $func.
     *
     * Args: $_forum   The forum id.
     *       $_id      The node whose children we want to print.
     *       $_offset  The offset.
     *       $_limit   The maximum number of threads to walk.
     *       $_fold    An object identifying folded nodes.
     *                 UNFOLDED).
     *       $_func    A reference to the function to which each row will be
     *                 passed.
     *       $_data    Passed through to $_func as an argument.
     *
     * Returns: The number of rows processed.
     */
    function foreach_child($_forumid,
                           $_id,
                           $_offset,
                           $_limit,
                           $_fold,
                           $_func,
                           $_data) {
      if ($_id != 0) {
        $sql  = "SELECT id,HEX(path) path";
        $sql .= " FROM {t_message}";
        $sql .= " WHERE id={id}";
        $query = &new SqlQuery($sql);
        $query->set_int('id', $_id);
        $res = $this->db->Execute($query->sql())
                       or die("ForumDB::foreach_child(): 1");
      }
      else {
        // Select all root nodes.
        $sql  = "SELECT id,HEX(path) path, n_children";
        $sql .= " FROM {t_message}";
        $sql .= " WHERE forumid={forumid} AND is_parent=1";
        $sql .= " ORDER BY threadid DESC,path";
        $query = &new SqlQuery($sql);
        $query->set_int('forumid', $_forumid);
        $res = $this->db->SelectLimit($query->sql(), $_limit, $_offset)
                       or die("ForumDB::foreach_child(): 2");
      }
      
      // Build the SQL request to grab the complete threads.
      if ($res->RecordCount() <= 0)
        return;
      $sql  = "SELECT id,forumid,HEX(path) path,n_children,n_descendants,";
      $sql .= "name username,title subject,text body,active,";
      $sql .= "UNIX_TIMESTAMP(updated) updated,";
      $sql .= "UNIX_TIMESTAMP(created) created";
      $sql .= " FROM {t_message}";
      $sql .= " WHERE (";
      
      $first = 1;
      while ($row = &$res->FetchRow()) {
        if (!$first)
          $sql .= " OR ";
        if ($_fold->is_folded($row[id]))
          $sql .= "id=$row[id]";
        else
          $sql .= "threadid=$row[id]";
        $first = 0;
      }
      
      $sql .= ") ORDER BY threadid DESC,path";
      
      // Walk through those threads.
      $query   = &new SqlQuery($sql);
      $res     = $this->db->Execute($query->sql())
                              or die("ForumDB::foreach_child: 3");
      $row     = &$res->FetchRow();
      $numrows = $res->RecordCount();
      $indent  = 0;
      $indents = array();
      $parents = array(&$row);
      while ($row) {
        $nextrow = &$res->FetchRow();
        
        // Parent node types.
        if ($this->_is_parent($row)
          && !$this->_has_children($row))
          $row[relation] = MESSAGE_RELATION_PARENT_STUB;
        else if ($this->_is_parent($row) && !$_fold->is_folded($row[id]))
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
        //echo "$row[title] ($row[id], $row[path]): $row[relation]<br>\n";
        
        $message = &new Message();
        $message->set_from_db($row);
        call_user_func($_func, $message, $indents, $_data);
        
        // Indent.
        $parents[$indent] = &$row;
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
        
        $row = &$nextrow;
      }
      
      return $numrows;
    }
    
    
    /* This function performs exactly as foreach_child(), except that given a
     * an id, it first looks up the top-level parent of that node and walks
     * through all children of the top level node. */
    function foreach_child_in_thread($_forumid, $_id, $_offset, $_limit,
                                     $_fold, $_func, $_data) {
      $threadid = $this->_get_threadid($_id);
      return $this->foreach_child($_forumid,
                                  $threadid,
                                  $_offset,
                                  $_limit,
                                  $_fold,
                                  $_func,
                                  $_data);
    }
    
    
    /* Returns latest messages from the given forum.
     * $_forum:   The forum id.
     * $_offset:  The offset of the first message.
     * $_n:       The number of messages.
     * $_updates: Whether an updated entry is treated like a newly inserted one.
     * $_func:    A reference to the function to which each message will be
     *            passed.
     * $_data:    User data, passed to $_func.
     *
     * Args passed to $_func:
     *  $message: The Message object.
     *  $data:    The data given this function in $_data.
     */
    function foreach_latest_message($_forumid,
                                    $_offset,
                                    $_limit,
                                    $_updates,
                                    $_func,
                                    $_data) {
      $sql  = "SELECT id,forumid,name username,title subject,text body,active,";
      $sql .= "UNIX_TIMESTAMP(updated) updated,";
      $sql .= "UNIX_TIMESTAMP(created) created";
      $sql .= " FROM {t_message}";
      if ($_forumid)
        $sql .= " WHERE forumid={forumid}";
      if ($updates)
        $sql .= " ORDER BY updated";
      else
        $sql .= " ORDER BY created";
      $sql .= " DESC";
      $query = &new SqlQuery($sql);
      $query->set_int('forumid', $_forumid);
      $res = $this->db->SelectLimit($query->sql(), $_limit, $_offset)
                          or die("ForumDB::foreach_latest_message()");
      $numrows = $res->RecordCount();
      while ($row = $res->FetchRow()) {
        $message = &new Message();
        $message->set_from_db($row);
        call_user_func($_func, $message, $_data);
      }
      return $numrows;
    }
    
    
    /* Returns the total number of entries in the given forum. */
    function get_n_messages($_forumid) {
      $sql  = "SELECT COUNT(*)";
      $sql .= " FROM {t_message}";
      if ($_forumid)
        $sql .= " WHERE forumid={forumid}";
      $query = &new SqlQuery($sql);
      $query->set_int('forumid', $_forumid);
      $n = $this->db->GetOne($query->sql());
      return $n;
    }
    
    
     /* Returns the total number of threads in the given forum. */
    function get_n_threads($_forumid) {
      $sql  = "SELECT COUNT(DISTINCT threadid)";
      $sql .= " FROM {t_message}";
      if ($_forumid)
        $sql .= " WHERE forumid={forumid}";
      $query = &new SqlQuery($sql);
      $query->set_int('forumid', $_forumid);
      $n = $this->db->GetOne($query->sql());
      return $n;
    }
    
    
    /* Returns the number of nodes below $id. */
    function get_n_children($_forumid, $_id) {
      $sql = "SELECT n_children FROM {t_message} WHERE id={id}";
      $query = &new SqlQuery($sql);
      $query->set_int('id', $_id);
      $n = $this->db->GetOne($query->sql());
      return $n;
    }
  }
?>
