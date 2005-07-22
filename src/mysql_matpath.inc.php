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
  
  class TefinchDB {
    var $db;
    var $dh;
    var $tablebase;
    var $timeformat = '%y-%m-%d %H:%i';
    
    function TefinchDB($_host, $_user, $_pass, $_db, $_tablebase) {
      $this->db        = $_db;
      $this->tablebase = $_tablebase;
      $this->dh        = mysql_connect($_host, $_user, $_pass)
        or die("TefinchDB::TefinchDB(): Error: Can't connect."
             . " Please check username, password and hostname.");
      mysql_select_db($this->db)
        or die("Error: db_connect(): No database with the given name found.");
    }
    
    
    function close() {
      mysql_close($this->dh)
        or die("Error: db_connect(): Database close failed.");
    }
    
    
    /***********************************************************************
     * Private API.
     ***********************************************************************/
    function _lock_write($_forum) {
      $query = new SqlQuery("LOCK TABLE {$_forum} WRITE");
      //mysql_query($query->sql()) or die("TefinchDB::lock_write()");
    }
    
    
    function _unlock_write() {
      $query = new SqlQuery("UNLOCK TABLES");
      //mysql_query($query->sql()) or die("TefinchDB::unlock_write()");
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
      $query = new SqlQuery($sql);
      $query->set_int('id', $_id);
      $res = mysql_query($query->sql()) or die("TefinchDB::_get_path()");
      $row = mysql_fetch_object($res);
      return $row->path;
    }
    
    
    function _get_threadid($_id) {
      $sql = "SELECT threadid FROM {t_message} WHERE id={id}";
      $query = new SqlQuery($sql);
      $query->set_int('id', $_id);
      $res = mysql_query($query->sql()) or die("TefinchDB::_get_threadid()");
      $row = mysql_fetch_object($res);
      return $row->threadid ? $row->threadid : 0;
    }
    
    
    function _is_parent($_row) {
      return $_row->path == "";
    }
    
    
    function _has_children($_row) {
      return $_row->n_descendants > 0;
    }
    
    
    function _is_childof($_row, $_nextrow) {
      return strlen($_nextrow->path) > strlen($_row->path);
    }
    
    
    /* Given the id of any node, this function returns the id of the previous
     * entry in the same thread, or 0 if there is no previous entry.
     */
    function _get_prev_entry_id($_forumid, $_threadid, $_path) {
      if (!$_path)
        return 0;
      $sql  = "SELECT id,HEX(path) hexpath FROM {t_message}";
      $sql .= " WHERE threadid={threadid}";
      $sql .= " AND active=1";
      $sql .= " AND STRCMP(path, {path})=-1";
      $sql .= " ORDER BY hexpath DESC LIMIT 1";
      $query = new SqlQuery($sql);
      $query->set_int('threadid', $_threadid);
      $query->set_hex('path',     $_path);
      $res = mysql_query($query->sql())
               or die("TefinchDB::_get_prev_entry_id()");
      $row = mysql_fetch_object($res);
      return $row->id;
    }
    
    
    /* Given the path of any node, this function returns the id of the next
     * entry in the same thread, or 0 if there is no next entry.
     */
    function _get_next_entry_id($_forumid, $_threadid, $_path) {
      $sql  = "SELECT id,HEX(path) hexpath FROM {t_message}";
      $sql .= " WHERE threadid={threadid}";
      $sql .= " AND active=1";
      $sql .= " AND is_parent=0";
      if ($_path)
        $sql .= " AND STRCMP(path, {path})=1";
      $sql .= " ORDER BY hexpath LIMIT 1";
      $query = new SqlQuery($sql);
      $query->set_int('threadid', $_threadid);
      $query->set_hex('path',     $_path);
      $res = mysql_query($query->sql())
               or die("TefinchDB::_get_next_entry_id()");
      $row = mysql_fetch_object($res);
      return $row->id;
    }
    
    
    /* Given a threadid, this function returns the id of the previous
     * thread in the given forum, or 0 if there is no previous thread.
     * The threadid equals the id of the toplevel node in a thread.
     */
    function _get_prev_thread_id($_forumid, $_threadid) {
      $sql  = "SELECT threadid FROM {t_message}";
      $sql .= " WHERE forumid={forumid} AND threadid<{threadid}";
      $sql .= " AND (active=1 OR n_children>0)";
      $sql .= " ORDER BY threadid DESC LIMIT 1";
      $query = new SqlQuery($sql);
      $query->set_int('forumid',  $_forumid);
      $query->set_int('threadid', $_threadid);
      $res = mysql_query($query->sql())
               or die("TefinchDB::_get_prev_thread_id()");
      $row = mysql_fetch_object($res);
      return $row->threadid;
    }
    
    
    /* Given a threadid, this function returns the id of the next
     * thread in the given forum, or 0 if there is no next thread.
     * The threadid equals the id of the toplevel node in a thread.
     */
    function _get_next_thread_id($_forumid, $_threadid) {
      $sql  = "SELECT threadid FROM {t_message}";
      $sql .= " WHERE forumid={forumid} AND threadid>{threadid}";
      $sql .= " AND (active=1 OR n_children>0)";
      $sql .= " ORDER BY threadid LIMIT 1";
      $query = new SqlQuery($sql);
      $query->set_int('forumid',  $_forumid);
      $query->set_int('threadid', $_threadid);
      $res = mysql_query($query->sql())
               or die("TefinchDB::get_next_thread_id()");
      $row = mysql_fetch_object($res);
      return $row->threadid;
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
      $query = new SqlQuery($sql);
      $query->set_int('id', $_id);
      $res = mysql_query($query->sql())
               or die("TefinchDB::get_entry(): Failed.");
      $row = mysql_fetch_object($res);
      if (!$row)
        return;
      $row->prev_thread_id   = $this->_get_prev_thread_id($_forumid,
                                                          $row->threadid);
      $row->next_thread_id   = $this->_get_next_thread_id($_forumid,
                                                          $row->threadid);
      $row->prev_message_id  = $this->_get_prev_entry_id($_forumid,
                                                         $row->threadid,
                                                         $row->path);
      $row->next_message_id  = $this->_get_next_entry_id($_forumid,
                                                         $row->threadid,
                                                         $row->path);
      if (strlen($row->path) / 2 > 252)  // Path as long as the the DB field.
        $row->allow_answer = FALSE;
      if ($row->id == $row->threadid)
        $row->relation = MESSAGE_RELATION_PARENT_UNFOLDED;
      
      $message = new Message;
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
    function insert_entry($_forumid, $_parentid, &$_message) {
      $this->_lock_write("t_message");
      
      // Fetch the parent row.
      $sql  = "SELECT forumid,threadid,HEX(path) path,active";
      $sql .= " FROM {t_message}";
      $sql .= " WHERE id={parentid}";
      $query = new SqlQuery($sql);
      $query->set_int('parentid', $_parentid);
      $res = mysql_query($query->sql()) or die("TefinchDB::insert_entry(): 1");
      $parentrow = mysql_fetch_object($res);
      
      $query = new SqlQuery("SET AUTOCOMMIT=0;");
      mysql_query($query->sql()) or die("TefinchDB::insert_entry(): AC0.");
      $query = new SqlQuery("BEGIN;");
      mysql_query($query->sql()) or die("TefinchDB::insert_entry(): Begin.");
      
      // Insert the new node.
      $username = mysql_escape_string($_message->get_username());
      $subject  = mysql_escape_string($_message->get_subject());
      $body     = mysql_escape_string($_message->get_body());
      if ($parentrow) {
        if (!$parentrow->active)
          die("TefinchDB::insert_entry(): Parent inactive.\n");
        if (strlen($parentrow->path) / 2 > 252)
          die("TefinchDB::insert_entry(): Hierarchy too deep.\n");
        
        // Insert a new child.
        //FIXME: u_id as an arg, as soon as logins are implemented.
        $sql  = "INSERT INTO {t_message}";
        $sql .= " (forumid, threadid, u_id, name, title, text, created)";
        $sql .= " VALUES (";
        $sql .= " {forumid}, {threadid}, 2, {name}, {subject}, {body}, NULL";
        $sql .= ")";
        $query = new SqlQuery($sql);
        $query->set_int('forumid',  $parentrow->forumid);
        $query->set_int('threadid', $parentrow->threadid);
        $query->set_string('name',    $_message->get_username());
        $query->set_string('subject', $_message->get_subject());
        $query->set_string('body',    $_message->get_body());
        mysql_query($query->sql())
          or die("TefinchDB::insert_entry(): Insert1.");
        $newid = mysql_insert_id();
        
        // Update the child's path.
        $sql  = "UPDATE {t_message} SET path=";
        if ($parentrow->path != '') {
          $parentrow->path = substr($parentrow->path,
                                    0,
                                    strlen($parentrow->path) - 2);
          $sql .= " CONCAT(0x$parentrow->path,";
          $sql .= "        0x" . $this->_int2hex($newid) . "00)";
        }
        else {
          $sql .= " 0x" . $this->_int2hex($newid) . "00";
        }
        $sql .= " WHERE id={newid}";
        $query = new SqlQuery($sql);
        $query->set_int('newid', $newid);
        mysql_query($query->sql()) or die("TefinchDB::insert_entry(): Path");
        
        // Update n_descendants and n_children in one run...
        if ($_parentid == $parentrow->threadid) {
          $sql  = "UPDATE {t_message}";
          $sql .= " SET n_children=n_children+1,";
          $sql .= " n_descendants=n_descendants+1";
          $sql .= " WHERE id={parentid}";
          $query = new SqlQuery($sql);
          $query->set_int('parentid', $_parentid);
          mysql_query($query->sql()) or die("TefinchDB::insert_entry(): n++");
        }
        
        // ...unless it is necessary to update two database sets.
        else {
          $sql  = "UPDATE {t_message} SET n_children=n_children+1";
          $sql .= " WHERE id={threadid}";
          $query = new SqlQuery($sql);
          $query->set_int('threadid', $parentrow->threadid);
          mysql_query($query->sql())
            or die("TefinchDB::insert_entry(): n_child fail.");
          
          $sql  = "UPDATE {t_message} SET n_descendants=n_descendants+1";
          $sql .= " WHERE id={parentid}";
          $query = new SqlQuery($sql);
          $query->set_int('parentid', $_parentid);
          mysql_query($query->sql())
            or die("TefinchDB::insert_entry(): n_desc");
        }
      }
      
      // Insert a new thread.
      else {
        //FIXME: u_id as an arg, as soon as logins are implemented.
        $sql  = "INSERT INTO {t_message}";
        $sql .= " (path, forumid, threadid, is_parent, u_id, name, title,";
        $sql .= "  text, created)";
        $sql .= " VALUES (";
        $sql .= " '', {forumid}, 0, 1, 2, {name}, {subject}, {body}, NULL";
        $sql .= ")";
        $query = new SqlQuery($sql);
        $query->set_int('forumid', $_forumid);
        $query->set_string('name',    $_message->get_username());
        $query->set_string('subject', $_message->get_subject());
        $query->set_string('body',    $_message->get_body());
        mysql_query($query->sql()) or die("TefinchDB::insert_entry(): Insert2");
        $newid = mysql_insert_id();
        
        // Set the thread id.
        // FIXME: Is there a better way to do this?
        $sql  = "UPDATE {t_message} SET threadid={newid}";
        $sql .= " WHERE id={newid}";
        $query = new SqlQuery($sql);
        $query->set_int('newid', $newid);
        mysql_query($query->sql())
          or die("TefinchDB::insert_entry(): Threadid");
      }
      
      $query = new SqlQuery("COMMIT;");
      mysql_query($query->sql()) or die("TefinchDB::insert_entry(): Commit");
      
      $this->_unlock_write();
      return $newid;
    }
    
    
    /* Walks through the tree starting from $id, passing each row to the
     * function given in $func.
     * Note that the row that is passed to the handler has an n_children field
     * attached, this field is ONLY valid for relation 1 and 2.
     *
     * Relations:
     *   1 Parent without children.
     *   2 Parent with children.
     *   3 Branch-bottom child without children.
     *   4 Branch-bottom child with children.
     *   5 Non-branch-bottom child without children.
     *   6 Non-branch-bottom child with children.
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
        $query = new SqlQuery($sql);
        $query->set_int('id', $_id);
        $res = mysql_query($query->sql())
          or die("TefinchDB::foreach_child(): 1");
      }
      else {
        // Select all root nodes.
        $sql  = "SELECT id,HEX(path) path, n_children";
        $sql .= " FROM {t_message}";
        $sql .= " WHERE forumid={forumid} AND is_parent=1";
        $sql .= " ORDER BY threadid DESC,path";
        $sql .= " LIMIT {offset}, {limit}";
        $query = new SqlQuery($sql);
        $query->set_int('forumid', $_forumid);
        $query->set_int('offset',  $_offset);
        $query->set_int('limit',   $_limit);
        $res = mysql_query($query->sql())
          or die("TefinchDB::foreach_child(): 2: Fail.");
      }
      
      // Build the SQL request to grab the complete threads.
      if (mysql_num_rows($res) <= 0)
        return;
      $sql  = "SELECT id,forumid,HEX(path) path,n_children,n_descendants,";
      $sql .= "name username,title subject,text body,active,";
      $sql .= "UNIX_TIMESTAMP(updated) updated,";
      $sql .= "UNIX_TIMESTAMP(created) created";
      $sql .= " FROM {t_message}";
      $sql .= " WHERE (";
      
      $first = 1;
      while ($row = mysql_fetch_object($res)) {
        if (!$first)
          $sql .= " OR ";
        if ($_fold->is_folded($row->id))
          $sql .= "id=$row->id";
        else
          $sql .= "threadid=$row->id";
        $first = 0;
      }
      
      $sql .= ") ORDER BY threadid DESC,path";
      
      // Walk through those threads.
      $query = new SqlQuery($sql);
      $res = mysql_query($query->sql())
        or die("TefinchDB::foreach_child(): 3");
      $row = mysql_fetch_object($res);
      $numrows = mysql_num_rows($res);
      $indent  = 0;
      $indents = array();
      $parents = array($row);
      while ($row) {
        $nextrow = mysql_fetch_object($res);
        
        // Parent node types.
        if ($this->_is_parent($row)
          && !$this->_has_children($row))
          $row->relation = MESSAGE_RELATION_PARENT_STUB;
        else if ($this->_is_parent($row) && !$_fold->is_folded($row->id))
          $row->relation = MESSAGE_RELATION_PARENT_UNFOLDED;
        else if ($this->_is_parent($row))
          $row->relation = MESSAGE_RELATION_PARENT_FOLDED;
        
        // Children at a branch end.
        else if ($parents[$indent - 1]->n_descendants == 1
               && !$this->_is_childof($row, $nextrow))
          $row->relation = MESSAGE_RELATION_BRANCHEND_STUB;
        else if ($parents[$indent - 1]->n_descendants == 1)
          $row->relation = MESSAGE_RELATION_BRANCHEND;
        
        // Other children.
        else if (!$this->_is_childof($row, $nextrow)) {
          $row->relation = MESSAGE_RELATION_CHILD_STUB;
          $parents[$indent - 1]->n_descendants--;
        }
        else {
          $row->relation = MESSAGE_RELATION_CHILD;
          $parents[$indent - 1]->n_descendants--;
        }
        //echo "$row->title ($row->id, $row->path): $row->relation<br>\n";
        
        $message = new Message();
        $message->set_from_db($row);
        call_user_func($_func, $message, $indents, $_data);
        
        // Indent.
        $parents[$indent] = $row;
        if ($row->relation == MESSAGE_RELATION_PARENT_UNFOLDED
          || $row->relation == MESSAGE_RELATION_CHILD
          || $row->relation == MESSAGE_RELATION_BRANCHEND) {
          if ($row->relation == MESSAGE_RELATION_CHILD)
            $indents[$indent] = INDENT_DRAW_DASH;
          else
            $indents[$indent] = INDENT_DRAW_SPACE;
          $indent++;
        }
        // If the last row was a branch end, unindent.
        else if ($row->relation == MESSAGE_RELATION_BRANCHEND_STUB) {
          $relation = $parents[$indent]->relation;
          while ($relation == MESSAGE_RELATION_BRANCHEND_STUB
            || $relation == MESSAGE_RELATION_BRANCHEND) {
            $indent--;
            unset($indents[$indent]);
            $relation = $parents[$indent]->relation;
          }
        }
        
        $row = $nextrow;
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
    
    
    /* Returns latest entries from the given forum.
     * $_forum:   The forum id.
     * $_offset:  The offset of the first entry.
     * $_n:       The number of entries.
     * $_updates: Whether an updated entry is treated like a newly inserted one.
     * $_func:    A reference to the function to which each row will be
     *            passed.
     * $_data:    User data, passed to $_func.
     *
     * Args passed to $_func:
     *  $row:  An object containing the fields
     *            - id
     *            - name
     *            - title
     *            - text
     *            - active
     *            - time
     *  $data: The data given this function in $_data.
     */
    function foreach_latest_entry($_forumid,
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
      $sql .= " DESC LIMIT {offset}, {limit}";
      $query = new SqlQuery($sql);
      $query->set_int('forumid', $_forumid);
      $query->set_int('offset',  $_offset);
      $query->set_int('limit',   $_limit);
      $res = mysql_query($query->sql())
               or die("TefinchDB::foreach_latest_entry(): Failed.");
      $numrows = mysql_num_rows($res);
      $message = new Message();
      while ($row = mysql_fetch_object($res)) {
        $message = new Message();
        $message->set_from_db($row);
        call_user_func($_func, $message, $_data);
      }
      return $numrows;
    }
    
    
    /* Returns the total number of entries in the given forum. */
    function get_n_entries($_forumid) {
      $sql  = "SELECT COUNT(*) entries";
      $sql .= " FROM {t_message}";
      if ($_forumid)
        $sql .= " WHERE forumid={forumid}";
      $query = new SqlQuery($sql);
      $query->set_int('forumid', $_forumid);
      $res = mysql_query($query->sql()) or die("TefinchDB::get_n_entries()");
      $row = mysql_fetch_object($res);
      return $row->entries;
    }
    
    
     /* Returns the total number of threads in the given forum. */
    function get_n_threads($_forumid) {
      $sql  = "SELECT COUNT(DISTINCT threadid) threads";
      $sql .= " FROM {t_message}";
      if ($_forumid)
        $sql .= " WHERE forumid={forumid}";
      $query = new SqlQuery($sql);
      $query->set_int('forumid', $_forumid);
      $res = mysql_query($query->sql()) or die("TefinchDB::get_n_threads()");
      $row = mysql_fetch_object($res);
      return $row->threads;
    }
    
    
    /* Returns the number of nodes below $id. */
    function get_n_children($_forumid, $_id) {
      $sql = "SELECT n_children FROM {t_message} WHERE id={id}";
      $query = new SqlQuery($sql);
      $query->set_int('id', $_id);
      $res = mysql_query($query->sql()) or die("TefinchDB::get_n_children()");
      $row = mysql_fetch_object($res);
      return $row->n_children;
    }
    
    
    function set_timeformat($_format) {
      $this->timeformat = $_format;
    }
  }
?>
