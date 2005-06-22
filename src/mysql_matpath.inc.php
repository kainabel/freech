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
  include_once 'thread_folding.inc.php';
  
  define("PARENT_WITHOUT_CHILDREN",             1);
  define("PARENT_WITH_CHILDREN_UNFOLDED",       2);
  define("PARENT_WITH_CHILDREN_FOLDED",         3);
  define("BRANCHBOTTOM_CHILD_WITHOUT_CHILDREN", 4);
  define("BRANCHBOTTOM_CHILD_WITH_CHILDREN",    5);
  define("CHILD_WITHOUT_CHILDREN",              6);
  define("CHILD_WITH_CHILDREN",                 7);
  
  define("INDENT_DRAW_DASH",  1);
  define("INDENT_DRAW_SPACE", 2);
  
  class TefinchDB {
    var $db;
    var $dh;
    var $tablebase;
    var $timeformat = '%y-%m-%d %H:%i';
    var $threadsperpage = 4;
    
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
    function _lock_write() {
      $sql = "LOCK TABLE $this->tablebase WRITE";
      //mysql_query($sql) or die("TefinchDB::lock_write(): Failed!\n");
    }
    
    
    function _unlock_write() {
      $sql = "UNLOCK TABLES";
      //mysql_query($sql) or die("TefinchDB::unlock_write(): Failed!\n");
    }
    
    
    /* Given an decimal number, this function returns an 8 character wide
     * hexadecimal string representation of it.
     */
    function _int2hex($_n) {
      return substr("00000000" . dechex($_n), -8);
    }
    
    
    /* Given the id of any node, this function returns the hexadecimal string
     * representation of its binary path.
     */
    function _get_path($_id) {
      $id = $_id * 1;
      $sql  = "SELECT path FROM $this->tablebase t1";
      $sql .= " WHERE t1.id=$id";
      $res = mysql_query($sql) or die("TefinchDB::_get_path(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->path;
    }
    
    
    function _get_threadid($_id) {
      $id = $_id * 1;
      $sql = "SELECT threadid FROM $this->tablebase WHERE id=$id";
      $res = mysql_query($sql) or die("TefinchDB::_get_threadid(): Failed.");
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
      $threadid = $_threadid * 1;
      $path     = mysql_escape_string($_path);
      $sql  = "SELECT id,HEX(path) hexpath FROM $this->tablebase";
      $sql .= " WHERE threadid=$threadid";
      $sql .= " AND active=1";
      $sql .= " AND STRCMP(path, 0x$path)=-1";
      $sql .= " ORDER BY hexpath DESC LIMIT 1";
      $res = mysql_query($sql)
              or die("TefinchDB::_get_prev_entry_id(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->id;
    }


    /* Given the path of any node, this function returns the id of the next
     * entry in the same thread, or 0 if there is no next entry.
     */
    function _get_next_entry_id($_forumid, $_threadid, $_path) {
      $threadid = $_threadid * 1;
      $path     = mysql_escape_string($_path);
      $sql  = "SELECT id,HEX(path) hexpath FROM $this->tablebase";
      $sql .= " WHERE threadid=$threadid";
      $sql .= " AND active=1";
      $sql .= " AND path!=''";
      if ($_path)
        $sql .= " AND STRCMP(path, 0x$path)=1";
      $sql .= " ORDER BY hexpath LIMIT 1";
      $res = mysql_query($sql)
              or die("TefinchDB::_get_next_entry_id(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->id;
    }


    /* Given a threadid, this function returns the id of the previous
     * thread in the given forum, or 0 if there is no previous thread.
     * The threadid equals the id of the toplevel node in a thread.
     */
    function _get_prev_thread_id($_forumid, $_threadid) {
      $forumid  = $_forumid  * 1;
      $threadid = $_threadid * 1;
      $sql  = "SELECT threadid FROM $this->tablebase";
      $sql .= " WHERE forumid=$forumid AND threadid<$threadid AND active=1";
      $sql .= " ORDER BY threadid DESC LIMIT 1";
      $res = mysql_query($sql)
              or die("TefinchDB::_get_prev_thread_id(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->threadid;
    }


    /* Given a threadid, this function returns the id of the next
     * thread in the given forum, or 0 if there is no next thread.
     * The threadid equals the id of the toplevel node in a thread.
     */
    function _get_next_thread_id($_forumid, $_threadid) {
      $forumid  = $_forumid  * 1;
      $threadid = $_threadid * 1;
      $sql  = "SELECT threadid FROM $this->tablebase";
      $sql .= " WHERE forumid=$forumid AND threadid>$threadid AND active=1";
      $sql .= " ORDER BY threadid LIMIT 1";
      $res = mysql_query($sql) or die("TefinchDB::get_next_thread_id(): Fail.");
      $row = mysql_fetch_object($res);
      return $row->threadid;
    }


    /***********************************************************************
     * Public API.
     ***********************************************************************/
    /* Returns an entry from the given forum.
     * $_forum: The forum id.
     * $_id:    The id of the entry.
     * Returns: An object containing the fields
     *            - id
     *            - name
     *            - title
     *            - text
     *            - active
     *            - time
     */
    function get_entry($_f, $_id) {
      $id    = $_id * 1;
      $sql  = "SELECT id,threadid,threadid tid,HEX(path) path,n_descendants,";
      $sql .= "name,title,text,active,";
      $sql .= "UNIX_TIMESTAMP(created) unixtime,";
      $sql .= "DATE_FORMAT(created, '$this->timeformat') time";
      $sql .= " FROM $this->tablebase";
      $sql .= " WHERE id=$id";
      $res = mysql_query($sql) or die("TefinchDB::get_entry(): Failed.");
      $row = mysql_fetch_object($res);
      $row->prev_thread = $this->_get_prev_thread_id($_f, $row->tid);
      $row->next_thread = $this->_get_next_thread_id($_f, $row->tid);
      $row->prev_entry  = $this->_get_prev_entry_id($_f, $row->tid, $row->path);
      $row->next_entry  = $this->_get_next_entry_id($_f, $row->tid, $row->path);
      $row->n_children  = $row->n_descendants; //FIXME: Hack, hack, hack!!!
      return $row;
    }


    /* Insert a new child into the nested set.
     *        A(1|10)
     *          /  \
     *     B(2|7)  C(8|9)
     *      /  \
     *  C(3|4) D(5|6)
     *
     * $_forum:  The forum id.
     * $_parent: The id of the entry under which the new entry is placed.
     * $_name:   The name field of the entry.
     * $_title:  The title field of the entry.
     * $_text:   The text field of the entry.
     * Returns:  The id of the newly inserted entry.
     */
    function insert_entry($_forumid, $_parentid, $_name, $_title, $_text) {
      $forumid  = $_forumid  * 1;
      $parentid = $_parentid * 1;
      
      $this->_lock_write();
      
      // Fetch the parent row.
      $id = $_id * 1;
      $sql  = "SELECT forumid,threadid,HEX(path) path FROM $this->tablebase";
      $sql .= " WHERE id=$parentid";
      $res  = mysql_query($sql) or die("TefinchDB::insert_entry(): Failed.");
      $parentrow = mysql_fetch_object($res);
      
      $sql = "SET AUTOCOMMIT=0;";
      mysql_query($sql) or die("TefinchDB::insert_entry(): AC0 failed.");
      $sql = "BEGIN;";
      mysql_query($sql) or die("TefinchDB::insert_entry(): Begin failed.");
      
      // Insert the new node.
      $name  = mysql_escape_string($_name);
      $title = mysql_escape_string($_title);
      $text  = mysql_escape_string($_text);
      if ($parentrow) {
        $sql  = "INSERT INTO $this->tablebase";
        $sql .= " (forumid, threadid, n_descendants, name, title, text,";
        $sql .= "  created)";
        $sql .= " VALUES (";
        $sql .= " $parentrow->forumid, $parentrow->threadid, 0,";
        $sql .= " '$name', '$title', '$text', NULL";
        $sql .= ")";
        mysql_query($sql) or die("TefinchDB::insert_entry(): Insert1 failed.");
        $newid = mysql_insert_id();
        
        $sql  = "UPDATE $this->tablebase SET path=";
        if ($parentrow->path != '') {
          $sql .= " CONCAT(0x$parentrow->path,";
          $sql .= "        0x" . $this->_int2hex($newid) . ")";
        }
        else {
          $sql .= " 0x" . $this->_int2hex($newid);
        }
        $sql .= " WHERE id=$newid";
        mysql_query($sql) or die("TefinchDB::insert_entry(): Path failed.");
      }
      
      // Insert a new thread.
      else {
        $sql  = "INSERT INTO $this->tablebase";
        $sql .= " (path, forumid, threadid, n_descendants, name, title, text,";
        $sql .= "  created)";
        $sql .= " VALUES ('', $forumid, 0, 0, '$name', '$title', '$text',";
        $sql .= "  NULL)";
        mysql_query($sql) or die("TefinchDB::insert_entry(): Insert2 failed.");
        $newid = mysql_insert_id();
        
        // Set the thread id.
        // FIXME: Is there a better way to do this?
        $sql = "UPDATE $this->tablebase SET threadid=$newid WHERE id=$newid";
        mysql_query($sql) or die("TefinchDB::insert_entry(): Threadid failed.");
      }
      
      $sql  = "UPDATE $this->tablebase SET n_descendants=n_descendants+1";
      $sql .= " WHERE id=$parentid";
      mysql_query($sql) or die("TefinchDB::insert_entry(): Increment failed.");
      
      $sql = "COMMIT;";
      mysql_query($sql) or die("TefinchDB::insert_entry(): Commit failed.");
      
      $this->_unlock_write();
      return $newid;
    }


    /* Walks through the tree starting from $id, passing each row to the
     * function given in $func.
     *
     * Args: $_forum   The forum id.
     *       $_id      The node whose children we want to print.
     *       $_offset  The offset.
     *       $_fold    An object identifying folded nodes.
     *                 UNFOLDED).
     *       $_func    A reference to the function to which each row will be
     *                 passed.
     *       $_data    Passed through to $_func as an argument.
     *
     * Leaftypes:
     *   1 Parent without children.
     *   2 Parent with children.
     *   3 Branch-bottom child without children.
     *   4 Branch-bottom child with children.
     *   5 Non-branch-bottom child without children.
     *   6 Non-branch-bottom child with children.
     */
    function foreach_child($_forumid, $_id, $_offset, $_fold, $_func, $_data) {
      $forumid = $_forumid * 1;
      $id      = $_id      * 1;
      $offset  = $_offset  * 1;
      
      if ($id != 0) {
        $sql  = "SELECT id,HEX(path) path";
        $sql .= " FROM $this->tablebase";
        $sql .= " WHERE id=$id";
        $res = mysql_query($sql) or die("TefinchDB::foreach_child(): 1: Fail.");
      }
      else {
        // Select all root nodes.
        $sql  = "SELECT id,HEX(path) path,count(*)-1 n_children";
        $sql .= " FROM $this->tablebase";
        $sql .= " WHERE forumid=$forumid";
        $sql .= " GROUP BY threadid";
        $sql .= " HAVING path=''";
        $sql .= " ORDER BY threadid DESC,path";
        $sql .= " LIMIT $offset, $this->threadsperpage";
        $res = mysql_query($sql) or die("TefinchDB::foreach_child(): 2: Fail.");
      }
      
      // Build the SQL request to grab the complete threads.
      if (mysql_num_rows($res) <= 0)
        return;
      $sql  = "SELECT id,HEX(path) path,n_descendants,name,title,text,active,";
      $sql .= "UNIX_TIMESTAMP(created) unixtime,";
      $sql .= "DATE_FORMAT(created, '$this->timeformat') time";
      $sql .= " FROM $this->tablebase";
      $sql .= " WHERE (";
      
      $first = 1;
      while ($row = mysql_fetch_object($res)) {
        $childcount[$row->id] = $row->n_children;
        if (!$first)
          $sql .= " OR ";
        if ($_fold->is_folded($row->id))
          $sql .= "id=$row->id";
        else
          $sql .= "threadid=$row->id";
          //$sql .= "path LIKE CONCAT(0x$row->path, '%')";
        $first = 0;
      }
      
      $sql .= ") ORDER BY threadid DESC,path";
      
      // Walk through those threads.
      $res = mysql_query($sql) or die("TefinchDB::foreach_child(): 3 Failed.");
      $row = mysql_fetch_object($res);
      $indent  = 0;
      $indents = array();
      $parents = array($row);
      while ($row) {
        $nextrow = mysql_fetch_object($res);
        
        // Parent node types.
        if ($this->_is_parent($row)
          && !$this->_has_children($row))
          $row->leaftype = PARENT_WITHOUT_CHILDREN;
        else if ($this->_is_parent($row) && !$_fold->is_folded($row->id))
          $row->leaftype = PARENT_WITH_CHILDREN_UNFOLDED;
        else if ($this->_is_parent($row))
          $row->leaftype = PARENT_WITH_CHILDREN_FOLDED;
        
        // Children at a branch end.
        else if ($parents[$indent - 1]->n_descendants == 1
               && !$this->_is_childof($row, $nextrow))
          $row->leaftype = BRANCHBOTTOM_CHILD_WITHOUT_CHILDREN;
        else if ($parents[$indent - 1]->n_descendants == 1)
          $row->leaftype = BRANCHBOTTOM_CHILD_WITH_CHILDREN;
        
        // Other children.
        else if (!$this->_is_childof($row, $nextrow)) {
          $row->leaftype = CHILD_WITHOUT_CHILDREN;
          $parents[$indent - 1]->n_descendants--;
        }
        else {
          $row->leaftype = CHILD_WITH_CHILDREN;
          $parents[$indent - 1]->n_descendants--;
        }
        //echo "$row->title ($row->id, $row->path): $row->leaftype<br>\n";
        
        $row->n_children = $childcount[$row->id];
        $_func($row, $indents, $_data);
        
        // Indent.
        $parents[$indent] = $row;
        if ($row->leaftype == PARENT_WITH_CHILDREN_UNFOLDED
          || $row->leaftype == CHILD_WITH_CHILDREN
          || $row->leaftype == BRANCHBOTTOM_CHILD_WITH_CHILDREN) {
          if ($row->leaftype == CHILD_WITH_CHILDREN)
            $indents[$indent] = INDENT_DRAW_DASH;
          else
            $indents[$indent] = INDENT_DRAW_SPACE;
          $indent++;
        }
        // If the last row was a branch end, unindent.
        else if ($row->leaftype == BRANCHBOTTOM_CHILD_WITHOUT_CHILDREN) {
          $leaftype = $parents[$indent]->leaftype;
          while ($leaftype == BRANCHBOTTOM_CHILD_WITHOUT_CHILDREN
            || $leaftype == BRANCHBOTTOM_CHILD_WITH_CHILDREN) {
            $indent--;
            $indents[$indent] = 0;
            $leaftype = $parents[$indent]->leaftype;
          }
        }
        
        $row = $nextrow;
      }
    }
    
    
    /* This function performs exactly as foreach_child(), except that given a
     * an id, it first looks up the top-level parent of that node and walks
     * through all children of the top level node. */
    function foreach_child_in_thread($_forum, $_id, $_offset,
                                     $_fold, $_func, $_data) {
      $threadid = $this->_get_threadid($_id);
      $this->foreach_child($_forumid,
                           $threadid,
                           $_offset,
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
                                  $_updates,
                                  $_func,
                                  $_data) {
      $forumid = $_forumid * 1;
      $offset  = $_offset  * 1;
      $sql  = "SELECT id,name,title,text,active,";
      $sql .= "UNIX_TIMESTAMP(created) unixtime,";
      $sql .= "DATE_FORMAT(created, '$this->timeformat') time";
      $sql .= " FROM $this->tablebase";
      if ($_forumid)
        $sql .= " WHERE forumid=$forumid";
      if ($updates)
        $sql .= " ORDER BY updated";
      else
        $sql .= " ORDER BY created";
      $sql .= " DESC LIMIT $offset, $this->threadsperpage";
      $res = mysql_query($sql)
               or die("TefinchDB::foreach_latest_entry(): Failed.");
      while ($row = mysql_fetch_object($res))
        $_func($row, $_data);
    }
    
    
    /* Returns the total number of entries in the given forum. */
    function get_n_entries($_forumid) {
      $forumid = $_forumid * 1;
      $sql  = "SELECT COUNT(*) entries";
      $sql .= " FROM $this->tablebase";
      if ($_forumid)
        $sql .= " WHERE forumid=$forumid";
      $res = mysql_query($sql) or die("TefinchDB::get_n_entries(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->entries;
    }
    
    
     /* Returns the total number of threads in the given forum. */
    function get_n_threads($_forumid) {
      $forumid = $_forumid * 1;
      $sql  = "SELECT COUNT(DISTINCT threadid) threads";
      $sql .= " FROM $this->tablebase";
      if ($_forumid)
        $sql .= " WHERE forumid=$forumid";
      $res = mysql_query($sql) or die("TefinchDB::get_n_threads(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->threads;
    }
    
    
    /* Returns the number of nodes below $id. */
    function get_n_children($_forumid, $_id) {
      $id  = $_id * 1;
      $sql = "SELECT n_children FROM $this->tablebase WHERE id=$id";
      $res = mysql_query($sql) or die("TefinchDB::get_n_children(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->n_children;
    }
    
    
    function get_n_threads_per_page() {
      return $this->threadsperpage;
    }
    
    
    function set_n_threads_per_page($_tpp) {
      $this->threadsperpage = $_tpp;
    }
    
    
    function set_timeformat($_format) {
      $this->timeformat = $_format;
    }
  }
?>
