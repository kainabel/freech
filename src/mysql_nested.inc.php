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
    function _lock_write($table) {
      $sql = "LOCK TABLE $table WRITE";
      //mysql_query($sql) or die("TefinchDB::lock_write(): Failed!\n");
    }
    
    
    function _unlock_write() {
      $sql = "UNLOCK TABLES";
      //mysql_query($sql) or die("TefinchDB::unlock_write(): Failed!\n");
    }
    
    
    function _get_rightmost($table, $_id) {
      $id = $_id * 1;
      if ($id == 0)
        $sql = "SELECT rgt FROM $table WHERE lft=0";
      else
        $sql = "SELECT rgt FROM $table WHERE id=$id";
      $res = mysql_query($sql) or die("TefinchDB::_get_rightmost(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->rgt ? $row->rgt : 0;
    }
    
    
    function _get_threadid($table, $_id) {
      $id = $_id * 1;
      if ($id == 0)
        $sql = "SELECT threadid FROM $table WHERE lft=0";
      else
        $sql = "SELECT threadid FROM $table WHERE id=$id";
      $res = mysql_query($sql) or die("TefinchDB::_get_threadid(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->threadid ? $row->threadid : 0;
    }
    
    
    // Returns the id of the toplevel parent (not the root node).
    // This returns a value that is in fact equal to _get_threadid, but it
    // also works for nodes that have no threadid assigned yet, at the expence
    // of performance.
    function _get_toplevel($table, $_lft, $_rgt) {
      $id  = $_id  * 1;
      $lft = $_lft * 1;
      $rgt = $_rgt * 1;
      $sql  = "SELECT id FROM $table";
      $sql .= " WHERE lft<=$lft AND rgt>=$rgt AND lft!=0";
      $sql .= " ORDER BY lft LIMIT 1";
      $res = mysql_query($sql) or die("TefinchDB::_is_parent(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->id;
    }
    
    
    function _is_leaf($row) {
      return $row->rgt - $row->lft == 1;
    }
    
    
    /* Given the id of any node, this function returns the id of the previous
     * entry in the same thread, or 0 if there is no previous entry.
     */
    function _get_prev_entry_id($_forum, $_threadid, $_lft) {
      $lft      = $_lft * 1;
      $threadid = $_threadid * 1;
      $forum    = $this->tablebase . ($_forum * 1);
      $sql  = "SELECT id FROM $forum t1";
      $sql .= " WHERE t1.threadid=$threadid AND lft<$lft AND active=1";
      $sql .= " ORDER BY lft DESC LIMIT 1";
      $res = mysql_query($sql)
              or die("TefinchDB::get_previous_entry_id(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->id;
    }
    
    
    /* Given the id of any node, this function returns the id of the next
     * entry in the same thread, or 0 if there is no next entry.
     */
    function _get_next_entry_id($_forum, $_threadid, $_lft) {
      $lft      = $_lft * 1;
      $threadid = $_threadid * 1;
      $forum    = $this->tablebase . ($_forum * 1);
      $sql  = "SELECT id FROM $forum t1";
      $sql .= " WHERE t1.threadid=$threadid AND lft>$lft AND active=1";
      $sql .= " ORDER BY lft LIMIT 1";
      $res = mysql_query($sql)
              or die("TefinchDB::get_next_entry_id(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->id;
    }
    
    
    /* Given the id of any node, this function returns the id of the previous
     * thread in the given forum, or 0 if there is no previous thread.
     * The threadid equals the id of the toplevel node in a thread.
     */
    function _get_prev_thread_id($_forum, $_threadid) {
      $threadid = $_threadid * 1;
      $forum    = $this->tablebase . ($_forum * 1);
      $sql  = "SELECT threadid FROM $forum t1";
      $sql .= " WHERE t1.threadid<$threadid AND active=1";
      $sql .= " ORDER BY threadid DESC LIMIT 1";
      $res = mysql_query($sql)
              or die("TefinchDB::get_previous_thread_id(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->threadid;
    }
    
    
    /* Given the id of any node, this function returns the id of the next
     * thread in the given forum, or 0 if there is no next thread.
     * The threadid equals the id of the toplevel node in a thread.
     */
    function _get_next_thread_id($_forum, $_threadid) {
      $threadid = $_threadid * 1;
      $forum    = $this->tablebase . ($_forum * 1);
      $sql  = "SELECT threadid FROM $forum t1";
      $sql .= " WHERE t1.threadid>$threadid AND active=1";
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
      $forum = $this->tablebase . ($_f * 1);
      $id    = $_id * 1;
      $sql  = "SELECT id,threadid tid,lft,name,title,text,active,";
      $sql .= "UNIX_TIMESTAMP(created) unixtime,";
      $sql .= "DATE_FORMAT(created, '$this->timeformat') time";
      $sql .= " FROM $forum";
      $sql .= " WHERE id=$id";
      $res = mysql_query($sql) or die("TefinchDB::get_entry(): Failed.");
      $row = mysql_fetch_object($res);
      $row->prev_thread = $this->_get_prev_thread_id($_f, $row->tid);
      $row->next_thread = $this->_get_next_thread_id($_f, $row->tid);
      $row->prev_entry  = $this->_get_prev_entry_id($_f,  $row->tid, $row->lft);
      $row->next_entry  = $this->_get_next_entry_id($_f,  $row->tid, $row->lft);
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
    function insert_entry($_forum, $_parent, $_name, $_title, $_text) {
      $forum  = $this->tablebase . ($_forum * 1);
      $parent = $_parent * 1;
      
      $this->_lock_write($forum);
      $rightmost = $this->_get_rightmost($forum, $parent);
      
      $sql = "SET AUTOCOMMIT=0;";
      mysql_query($sql) or die("TefinchDB::insert_entry(): AC0 failed.");
      $sql = "BEGIN;";
      mysql_query($sql) or die("TefinchDB::insert_entry(): Begin failed.");
      
      // Update all higher-value nodes to make space for a new node.
      $sql  = "UPDATE $forum";
      $sql .= " SET lft = CASE WHEN lft > $rightmost";
      $sql .= "                THEN lft + 2";
      $sql .= "                ELSE lft END,";
      $sql .= "     rgt = CASE WHEN rgt >= $rightmost";
      $sql .= "                THEN rgt + 2";
      $sql .= "                ELSE rgt END";
      $sql .= " WHERE rgt >= $rightmost";
      $sql .= " ORDER BY rgt DESC";
      mysql_query($sql) or die("TefinchDB::insert_entry(): Update failed.");
      
      // Insert the new node.
      $name  = mysql_escape_string($_name);
      $title = mysql_escape_string($_title);
      $text  = mysql_escape_string($_text);
      $sql  = "INSERT INTO $forum";
      $sql .= " (lft, rgt, name, title, text, created)";
      $sql .= " VALUES ($rightmost, $rightmost + 1,";
      $sql .= "         '$name', '$title', '$text', NULL)";
      mysql_query($sql) or die("TefinchDB::insert_entry(): Insert failed.");
      $newid = mysql_insert_id();
      
      // Set the thread id.
      // FIXME: Is there a better way to do this?
      $threadid = $this->_get_toplevel($forum, $rightmost, $rightmost + 1);
      $sql = "UPDATE $forum SET threadid=$threadid WHERE id=$newid";
      mysql_query($sql) or die("TefinchDB::insert_entry(): Threadid failed.");
      
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
    function foreach_child($_forum, $_id, $_offset, $_fold, $_func, $_data) {
      $forum  = $this->tablebase . ($_forum * 1);
      $id     = $_id     * 1;
      $offset = $_offset * 1;
      
      //print("ID: $_id<br/>\n");
      
      if ($id != 1) {
        // Get the range of children below the given node.
        $sql = "SELECT lft,rgt FROM $forum WHERE id=$id";
        $res = mysql_query($sql) or die("TefinchDB::foreach_child(): 1: Fail.");
        $row = mysql_fetch_object($res);
        $leftmost  = $row ? $row->lft : 0;
        $rightmost = $row ? $row->rgt : 0;
      }
      else {
        // Select all *direct* children of the rootnode.
        $sql  = "SELECT t1.id,t1.lft,t1.rgt";
        $sql .= " FROM $forum t1";
        $sql .= " WHERE t1.id=t1.threadid AND t1.lft!=0";
        $sql .= " ORDER BY threadid DESC";
        $sql .= " LIMIT $offset, $this->threadsperpage";
        $res = mysql_query($sql) or die("TefinchDB::foreach_child(): 2: Fail.");
      }
      
      // Build the SQL request to grab the complete threads.
      $numrows = mysql_num_rows($res);
      $sql  = "SELECT t1.id,t1.lft,t1.rgt,t1.name,t1.title,t1.active,t1.text,";
      $sql .= "UNIX_TIMESTAMP(t1.created) unixtime,";
      $sql .= "DATE_FORMAT(t1.created, '$this->timeformat') time";
      $sql .= " FROM $forum t1";
      $sql .= " WHERE t1.lft!=0";
      if ($id != 1 || $numrows > 0)
        $sql .= " AND (";
      
      if ($id != 1)
        $sql .= " t1.lft BETWEEN $leftmost AND $rightmost";
      else {
        $first = 1;
        while ($row = mysql_fetch_object($res)) {
          if (!$first)
            $sql .= " OR ";
          if ($_fold->is_folded($row->id))
            $sql .= "(t1.lft=$row->lft AND t1.rgt=$row->rgt)";
          else
            $sql .= "(t1.lft BETWEEN $row->lft AND $row->rgt)";
          $first = 0;
        }
      }
      
      if ($id != 1 || $numrows > 0)
        $sql .= ")";
      $sql .= " ORDER BY t1.threadid DESC,t1.lft";
      
      // Walk through those threads.
      $res = mysql_query($sql) or die("TefinchDB::foreach_child(): 3 Failed.");
      $indent  = 0;
      $indents = array();
      $parents = array();
      while ($row = mysql_fetch_object($res)) {
        // If the last row was a branch end, unindent.
        if ($lastrow->leaftype == BRANCHBOTTOM_CHILD_WITHOUT_CHILDREN) {
          $leaftype = $parents[$indent]->leaftype;
          while ($leaftype == BRANCHBOTTOM_CHILD_WITHOUT_CHILDREN
            || $leaftype == BRANCHBOTTOM_CHILD_WITH_CHILDREN) {
            $indent--;
            $indents[$indent] = 0;
            $leaftype = $parents[$indent]->leaftype;
          }
        }
        
        // Classify the leaf.
        if ($indent == 0 && $this->_is_leaf($row))
          $row->leaftype = PARENT_WITHOUT_CHILDREN;
        else if ($indent == 0 && !$_fold->is_folded($row->id))
          $row->leaftype = PARENT_WITH_CHILDREN_UNFOLDED;
        else if ($indent == 0)
          $row->leaftype = PARENT_WITH_CHILDREN_FOLDED;
        else if ($row->rgt + 1 == $parents[$indent - 1]->rgt
              && $this->_is_leaf($row))
          $row->leaftype = BRANCHBOTTOM_CHILD_WITHOUT_CHILDREN;
        else if ($row->rgt + 1 == $parents[$indent - 1]->rgt)
          $row->leaftype = BRANCHBOTTOM_CHILD_WITH_CHILDREN;
        else if ($this->_is_leaf($row))
          $row->leaftype = CHILD_WITHOUT_CHILDREN;
        else
          $row->leaftype = CHILD_WITH_CHILDREN;
        
        $_func($row, $indents, $_data);
        
        // Indent.
        $parents[$indent] = $row;
        if ($row->leaftype == PARENT_WITH_CHILDREN_UNFOLDED
          || $row->leaftype == CHILD_WITH_CHILDREN
          || $row->leaftype == BRANCHBOTTOM_CHILD_WITH_CHILDREN) {
          if ($row->leaftype == CHILD_WITHOUT_CHILDREN
           || $row->leaftype == CHILD_WITH_CHILDREN)
            $indents[$indent] = INDENT_DRAW_DASH;
          else
            $indents[$indent] = INDENT_DRAW_SPACE;
          $indent++;
        }
        
        $lastrow = $row;
      }
    }
    
    
    /* This function performs exactly as foreach_child(), except that given a
     * an id, it first looks up the top-level parent of that node and walks
     * through all children of the top level node. */
    function foreach_child_in_thread($_forum, $_id, $_offset,
                                     $_fold, $_func, $_data) {
      $id       = $_id    * 1;
      $forum    = $this->tablebase . ($_forum * 1);
      $threadid = $this->_get_threadid($forum, $id);
      $this->foreach_child($_forum, $threadid, $_offset, $_fold, $_func, $_data);
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
    function foreach_latest_entry($_forum,
                                  $_offset,
                                  $_updates,
                                  $_func,
                                  $_data) {
      $forum  = $this->tablebase . ($_forum * 1);
      $offset = $_offset * 1;
      $sql  = "SELECT id,name,title,text,active,";
      $sql .= "UNIX_TIMESTAMP(created) unixtime,";
      $sql .= "DATE_FORMAT(created, '$this->timeformat') time";
      $sql .= " FROM $forum";
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
    function get_n_entries($_forum) {
      $forum  = $this->tablebase . ($_forum * 1);
      $sql  = "SELECT COUNT(id) entries";
      $sql .= " FROM $forum t1";
      $sql .= " WHERE t1.lft!=0";
      $res = mysql_query($sql) or die("TefinchDB::get_n_entries(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->entries;
    }
    
    
     /* Returns the total number of threads in the given forum. */
    function get_n_threads($_forum) {
      $forum  = $this->tablebase . ($_forum * 1);
      $sql  = "SELECT COUNT(DISTINCT threadid) threads";
      $sql .= " FROM $forum t1";
      $sql .= " WHERE t1.lft!=0";
      $res = mysql_query($sql) or die("TefinchDB::get_n_threads(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->threads;
    }


    /* Returns the number of nodes below $id. */
    function get_n_children($_forum, $_id) {
      $forum  = $this->tablebase . ($_forum * 1);
      $id     = $_id * 1;
      $sql = "SELECT lft,rgt FROM $forum WHERE id=$id";
      $res = mysql_query($sql) or die("TefinchDB::get_n_children(): Failed.");
      $row = mysql_fetch_object($res);
      return $row->rgt - $row->lft;
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
