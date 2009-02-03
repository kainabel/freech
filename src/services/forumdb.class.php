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
  define('INDENT_DRAW_DASH',  1);
  define('INDENT_DRAW_SPACE', 2);

  class ForumDB {
    var $db;

    function ForumDB(&$_api) {
      $this->api = $_api;
      $this->db  = $_api->db();
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
      $query = new FreechSqlQuery($sql);
      $query->set_int('id', $_id);
      $row = $this->db->GetRow($query->sql()) or die("ForumDB::_get_path()");
      return $row[path];
    }


    function _get_thread_id($_id) {
      $sql = "SELECT thread_id FROM {t_posting} WHERE id={id}";
      $query = new FreechSqlQuery($sql);
      $query->set_int('id', $_id);
      $row = $this->db->GetRow($query->sql());
      return $row && $row[thread_id] ? $row[thread_id] : 0;
    }


    function &_decorate_posting(&$_posting) {
      if (!$_posting)
        return NULL;
      $renderer = $this->renderers[$_posting->get_renderer()];
      if ($renderer)
        return new $renderer($_posting, $this->api);
      return new UnknownPosting($_posting, $this->api);
    }


    function &_get_posting_from_row(&$_row) {
      $posting = new Posting;
      $posting->set_from_db($_row);
      return $this->_decorate_posting($posting);
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


    /***********************************************************************
     * Public API.
     ***********************************************************************/
    function register_renderer($_name, $_decorator_name) {
      $this->renderers[$_name] = $_decorator_name;
    }


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
      $sql  = "SELECT forum_id,thread_id,HEX(path) path,status,force_stub";
      $sql .= " FROM {t_posting}";
      $sql .= " WHERE id={parent_id}";
      $query = new FreechSqlQuery($sql);
      $query->set_int('parent_id', $_parent_id);
      $parentrow = $this->db->GetRow($query->sql());

      $this->db->StartTrans();

      // Insert the new node.
      if ($parentrow) {
        if ($parentrow[force_stub])
          die('ForumDB::insert(): Responses have been deactivated.');
        if ($parentrow[status] != POSTING_STATUS_ACTIVE)
          die('ForumDB::insert(): Parent inactive.');
        if ($parentrow[status] != POSTING_STATUS_ACTIVE)
          die('ForumDB::insert(): Parent inactive.');
        if (strlen($parentrow[path]) / 2 > 252)
          die('ForumDB::insert(): Hierarchy too deep.');

        // Insert a new child.
        $sql  = "INSERT INTO {t_posting}";
        $sql .= " (forum_id, origin_forum_id, thread_id, priority,";
        $sql .= "  user_id, user_is_special, user_icon, user_icon_name,";
        $sql .= "  renderer, username, subject, body,";
        $sql .= "  hash, ip_hash, created, force_stub)";
        $sql .= " VALUES (";
        $sql .= " {forum_id}, {forum_id}, {thread_id}, {priority},";
        $sql .= " {user_id}, {user_is_special}, {user_icon}, {user_icon_name},";
        $sql .= " {renderer}, {username}, {subject}, {body},";
        $sql .= " {hash}, {ip_hash}, NULL, {force_stub}";
        $sql .= ")";
        $query = new FreechSqlQuery($sql);
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
        $query->set_bool  ('force_stub',      $_posting->get_force_stub());
        $this->db->Execute($query->sql()) or die('ForumDB::insert(): Ins1');
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
        $query = new FreechSqlQuery($sql);
        $query->set_int('newid', $_posting->get_id());
        $this->db->Execute($query->sql())
                or die("ForumDB::insert(): Path.");

        // Update the child counter of the thread.
        $sql   = 'UPDATE {t_thread}';
        $sql  .= ' SET n_children=n_children+1,';
        $sql  .= ' updated=NULL';
        $sql  .= ' WHERE id={thread_id}';
        $query = new FreechSqlQuery($sql);
        $query->set_int('thread_id', $parentrow[thread_id]);
        $this->db->Execute($query->sql()) or die('ForumDB::insert(): n++');

        // Update n_descendants of the parent.
        $sql   = "UPDATE {t_posting} SET n_descendants=n_descendants+1";
        $sql  .= " WHERE id={parent_id}";
        $query = new FreechSqlQuery($sql);
        $query->set_int('parent_id', $_parent_id);
        $this->db->Execute($query->sql()) or die('ForumDB::insert(): n_desc');
      }

      // Insert a new thread.
      else {
        // Create the thread in the thread table.
        $sql   = "INSERT INTO {t_thread}";
        $sql  .= " (forum_id, created)";
        $sql  .= " VALUES";
        $sql  .= " ({forum_id}, NULL)";
        $query = new FreechSqlQuery($sql);
        $query->set_int('forum_id', $_forum_id);
        $this->db->Execute($query->sql())
                or die("ForumDB::insert(): Insert2.".$query->sql());
        $thread_id = $this->db->Insert_Id();

        // Insert the posting.
        $sql  = "INSERT INTO {t_posting} (";
        $sql .= " path, forum_id, origin_forum_id, thread_id, priority,";
        $sql .= " user_id, user_is_special, user_icon, user_icon_name,";
        $sql .= " renderer, is_parent, username,";
        $sql .= " subject, body, hash, ip_hash, created,";
        $sql .= " force_stub";
        $sql .= ") VALUES (";
        $sql .= " '', {forum_id}, {forum_id}, {thread_id}, {priority},";
        $sql .= " {user_id}, {user_is_special}, {user_icon}, {user_icon_name},";
        $sql .= " {renderer}, 1,";
        $sql .= " {username}, {subject}, {body}, {hash}, {ip_hash}, NULL,";
        $sql .= " {force_stub}";
        $sql .= ")";
        $query = new FreechSqlQuery($sql);
        $query->set_int   ('forum_id',        $_forum_id);
        $query->set_int   ('thread_id',       $thread_id);
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
        $query->set_bool  ('force_stub',      $_posting->get_force_stub());
        $this->db->Execute($query->sql())
                or die('ForumDB::insert(): Insert2.'.$query->sql());
        $_posting->set_id($this->db->Insert_Id());
      }

      $this->db->CompleteTrans();
      return $_posting->get_id();
    }


    function save($_forum_id, $_parent_id, &$_posting) {
      //FIXME: This currently does not support moving postings (i.e. changing
      // the path, thread, or forum)
      //$this->db->debug = true;

      $this->db->StartTrans();
      $sql   = "UPDATE {t_posting} SET";
      $sql  .= " forum_id={forum_id},";
      //$sql  .= " thread_id={thread_id},";
      $sql  .= " priority={priority},";
      $sql  .= " user_id={user_id},";
      $sql  .= " user_is_special={user_is_special},";
      $sql  .= " user_icon={user_icon},";
      $sql  .= " user_icon_name={user_icon_name},";
      $sql  .= " renderer={renderer},";
      $sql  .= " username={username},";
      $sql  .= " subject={subject},";
      $sql  .= " body={body},";
      $sql  .= " hash={hash},";
      $sql  .= " ip_hash={ip_hash},";
      $sql  .= " status={status},";
      $sql  .= " force_stub={force_stub},";
      $sql  .= " updated=FROM_UNIXTIME({updated})";
      $sql  .= " WHERE id={id}";
      $query = new FreechSqlQuery($sql);
      $query->set_int   ('id',              $_posting->get_id());
      $query->set_int   ('forum_id',        $_forum_id);
      //$query->set_int   ('thread_id',       $_posting->get_thread_id());
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
      $query->set_string('status',          $_posting->get_status());
      $query->set_bool  ('force_stub',      $_posting->get_force_stub());
      $this->db->Execute($query->sql()) or die('ForumDB::save(): 2');

      $this->db->CompleteTrans();
    }


    /**
     * Moves the entire thread from the given forum into another forum.
     */
    function move_thread($_thread_id, $_forum_id) {
      $this->db->StartTrans();
      $sql   = 'UPDATE {t_thread} SET forum_id={forum_id} WHERE id={id}';
      $query = new FreechSqlQuery($sql);
      $query->set_int('id',       $_thread_id);
      $query->set_int('forum_id', $_forum_id);
      $this->db->Execute($query->sql()) or die('ForumDB::move_thread(): 1');

      $sql   = 'UPDATE {t_posting} SET forum_id={forum_id}';
      $sql  .= ' WHERE thread_id={id}';
      $query->set_sql($sql);
      $this->db->Execute($query->sql()) or die('ForumDB::move_thread(): 2');
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
      $sql   = "SELECT p.*,";
      $sql  .= "HEX(p.path) path,";
      $sql  .= "UNIX_TIMESTAMP(p.updated) updated,";
      $sql  .= "UNIX_TIMESTAMP(p.created) created,";
      $sql  .= "u.name current_username";
      $sql  .= " FROM {t_posting} p";
      $sql  .= " JOIN {t_user} u ON u.id=p.user_id";
      $sql  .= " WHERE p.id={id}";
      $query = new FreechSqlQuery($sql);
      $query->set_int('id', $_id);
      if (!$row = $this->db->GetRow($query->sql()))
        return;
      if (strlen($row[path]) / 2 > 252)  // Path as long as the the DB field.
        $row[allow_answer] = FALSE;
      if ($row[is_parent])
        $row[relation] = POSTING_RELATION_PARENT_UNFOLDED;

      return $this->_get_posting_from_row($row);
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
          $row[relation] = POSTING_RELATION_PARENT_STUB;
        else if ($this->_is_parent($row)
              && !$_thread_state->is_folded($row[id]))
          $row[relation] = POSTING_RELATION_PARENT_UNFOLDED;
        else if ($this->_is_parent($row))
          $row[relation] = POSTING_RELATION_PARENT_FOLDED;

        // Children at a branch end.
        else if ($parents[$indent - 1][n_descendants] == 1
               && !$this->_is_childof($row, $nextrow))
          $row[relation] = POSTING_RELATION_BRANCHEND_STUB;
        else if ($parents[$indent - 1][n_descendants] == 1)
          $row[relation] = POSTING_RELATION_BRANCHEND;

        // Other children.
        else if (!$this->_is_childof($row, $nextrow)) {
          $row[relation] = POSTING_RELATION_CHILD_STUB;
          $parents[$indent - 1][n_descendants]--;
        }
        else {
          $row[relation] = POSTING_RELATION_CHILD;
          $parents[$indent - 1][n_descendants]--;
        }
        //echo "$row[subject] ($row[id], $row[path]): $row[relation]<br>\n";

        $posting = $this->_get_posting_from_row($row);
        $posting->set_indent($indents);
        call_user_func($_func, $posting, $_data);

        // Indent.
        $parents[$indent] = $row;
        if ($row[relation] == POSTING_RELATION_PARENT_UNFOLDED
          || $row[relation] == POSTING_RELATION_CHILD
          || $row[relation] == POSTING_RELATION_BRANCHEND) {
          if ($row[relation] == POSTING_RELATION_CHILD)
            $indents[$indent] = INDENT_DRAW_DASH;
          else
            $indents[$indent] = INDENT_DRAW_SPACE;
          $indent++;
        }
        // If the last row was a branch end, unindent.
        else if ($row[relation] == POSTING_RELATION_BRANCHEND_STUB) {
          $relation = $parents[$indent][relation];
          while ($relation == POSTING_RELATION_BRANCHEND_STUB
            || $relation == POSTING_RELATION_BRANCHEND) {
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
     * Args: $_forum     The forum id.
     *       $_thread_id The thread whose children we want to print.
     *       $_offset    The offset.
     *       $_limit     The maximum number of threads to walk.
     *       $_thread_state An object identifying folded nodes.
     *       $_func      A reference to the function to which each row will be
     *                   passed.
     *       $_data      Passed through to $_func as an argument.
     *
     * Returns: The number of rows processed.
     */
    function foreach_child($_forum_id,
                           $_thread_id,
                           $_offset,
                           $_limit,
                           $_updated_threads_first,
                           $_thread_state,
                           $_func,
                           $_data) {
      $limit  = $_limit  * 1;
      $offset = $_offset * 1;

      // Select all root nodes.
      if ($_thread_id == 0) {
        if ($_updated_threads_first) {
          //FIXME: this may be optimized by duplicating the priority
          // of top-level postings into the thread table.
          $sql  = "SELECT t.id thread_id,p.id";
          $sql .= " FROM {t_thread} t";
          $sql .= " JOIN {t_posting} p ON t.id=p.thread_id";
          $sql .= " WHERE p.forum_id={forum_id} AND p.is_parent=1";
          $sql .= " ORDER BY p.priority DESC, t.updated DESC";
        }
        else {
          $sql .= "SELECT p.thread_id, p.id";
          $sql .= " FROM freech_posting p";
          $sql .= " WHERE p.forum_id={forum_id} AND p.is_parent=1";
          $sql .= " ORDER BY p.priority DESC, p.created DESC";
        }
        $query = new FreechSqlQuery($sql);
        $query->set_int('forum_id', $_forum_id);
        //$this->db->debug=1;
        $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                       or die("ForumDB::foreach_child(): 2");
        if ($res->RecordCount() <= 0)
          return;
      }

      // Build the SQL request to grab the complete threads.
      $sql  = "SELECT t.n_children,p2.*,";
      $sql .= " HEX(p2.path) path,";
      $sql .= " UNIX_TIMESTAMP(t.updated) threadupdate,";
      $sql .= " UNIX_TIMESTAMP(p2.updated) updated,";
      $sql .= " UNIX_TIMESTAMP(p2.created) created";
      $sql .= " FROM {t_thread} t";
      $sql .= " JOIN {t_posting} p1 ON p1.thread_id=t.id";
      $sql .= " JOIN {t_posting} p2 ON p2.thread_id=t.id";
      $sql .= " WHERE p1.is_parent AND (";

      if ($_thread_id)
        $sql .= 't.id='.(int)$_thread_id;
      else {
        //FIXME: is it really faster to avoid pulling folded threads?
        $first = 1;
        while ($row = $res->FetchRow()) {
          if (!$first)
            $sql .= " OR ";
          if ($_thread_state->is_folded($row[id]))
            $sql .= "p2.id=$row[id]";
          else
            $sql .= "t.id=$row[thread_id]";
          $first = 0;
        }
      }

      $sql .= ")";
      if ($_updated_threads_first)
        $sql .= " ORDER BY p1.priority DESC, t.updated DESC, p2.path";
      else
        $sql .= " ORDER BY p1.priority DESC, t.id DESC, p2.path";

      // Walk through those threads.
      $query   = new FreechSqlQuery($sql);
      $res     = $this->db->Execute($query->sql())
                              or die('ForumDB::foreach_child: 3');
      $numrows = $res->RecordCount();
      $this->_walk_tree($res, $_thread_state, $_func, $_data);
      return $numrows;
    }


    /* This function performs exactly as foreach_child(), except that given a
     * an id, it first looks up the thread id of that node and walks
     * through all children of the thread. */
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
      if ($_search_values['thread_id']) {
        $sql .= " AND thread_id={thread_id}";
        $_query->set_int('thread_id', $_search_values['thread_id']);
      }
      if ($_search_values['is_parent']) {
        $sql .= " AND is_parent={is_parent}";
        $_query->set_bool('is_parent', $_search_values['is_parent']);
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
        $posting = $this->_get_posting_from_row($row);
        call_user_func($_func, $posting, $_data);
      }
      return $numrows;
    }


    function _get_foreach_postings_sql($_fields) {
      $sql   = "SELECT *,";
      $sql  .= "UNIX_TIMESTAMP(updated) updated,";
      $sql  .= "UNIX_TIMESTAMP(created) created";
      $sql  .= " FROM {t_posting}";
      $sql  .= " WHERE 1";
      $query = new FreechSqlQuery($sql);
      if ($_fields)
        $this->_add_where_expression($query, $_fields);
      return $query->sql() . ' ORDER BY created DESC';
    }


    function foreach_posting_from_fields($_fields,
                                         $_offset = 0,
                                         $_limit  = -1,
                                         $_func   = NULL,
                                         $_data   = NULL) {
      $limit    = $_limit  * 1;
      $offset   = $_offset * 1;
      $postings = array();
      $sql      = $this->_get_foreach_postings_sql($_fields);
      $res      = $this->db->SelectLimit($sql, $limit, $offset)
                            or die('ForumDB::foreach_posting_from_fields()');
      return $this->_walk_list($res, $_func, $_data);
    }


    function get_postings_from_fields($_fields,
                                      $_offset = 0,
                                      $_limit  = -1) {
      $limit    = $_limit  * 1;
      $offset   = $_offset * 1;
      $postings = array();
      $sql      = $this->_get_foreach_postings_sql($_fields);
      $res      = $this->db->SelectLimit($sql, $limit, $offset)
                            or die('ForumDB::foreach_posting_from_fields()');

      while (!$res->EOF) {
        $posting = $this->_get_posting_from_row($res->FetchRow());
        array_push($postings, $posting);
      }
      return $postings;
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
      $sql  .= " WHERE status={status} AND ";
      $query = new FreechSqlQuery($sql);
      $query->set_int('status', POSTING_STATUS_ACTIVE);
      $_search_query->add_where_expression($query);
      $sql  = $query->sql();
      $sql .= " ORDER BY subject_matches DESC,body_matches DESC,created DESC";
      $query->set_sql($sql);
      $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                            or die('ForumDB::foreach_posting_from_query()');
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
      $query = new FreechSqlQuery($sql);
      if ($_search_values)
        $this->_add_where_expression($query, $_search_values);
      $sql  = $query->sql();
      $sql .= " ORDER BY created DESC";
      $query->set_sql($sql);
      $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                            or die("ForumDB::foreach_posting_from_query()");
      $postings = array();
      while (!$res->EOF) {
        $posting = $this->_get_posting_from_row($res->FetchRow());
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

      $sql  = "SELECT p.*,";
      $sql .= "UNIX_TIMESTAMP(p.updated) updated,";
      $sql .= "UNIX_TIMESTAMP(p.created) created";
      $sql .= " FROM {t_posting} p";
      $sql .= " JOIN {t_thread} t ON t.id=p.thread_id";
      if ($_forum_id)
        $sql .= " WHERE p.forum_id={forum_id}";
      if ($_updates)
        $sql .= " ORDER BY p.priority DESC,p.updated DESC";
      else
        $sql .= " ORDER BY p.priority DESC,p.created DESC";
      $query = new FreechSqlQuery($sql);
      $query->set_int('forum_id', $_forum_id);
      $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                          or die('ForumDB::foreach_latest_posting()');
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
      //FIXME: this method can be dramatically simplified now by using the
      // 'updated' field of the thread table instead of generating that using
      // a JOIN.

      // Select the postings of the user.
      $sql  = "SELECT a.id,HEX(a.path) path";
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
      $query = new FreechSqlQuery($sql);
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
      $query   = new FreechSqlQuery($sql);
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
      $query = new FreechSqlQuery($sql);
      $query->set_int('since', $_since);
      $query->set_int('until', $_until);
      if ($_search_values)
        $this->_add_where_expression($query, $_search_values);
      return $this->db->GetOne($query->sql());
    }


    function get_n_postings_from_query($_search_query) {
      $sql  = "SELECT COUNT(*)";
      $sql .= " FROM {t_posting}";
      $sql .= " WHERE status={status} AND ";
      $query = new FreechSqlQuery($sql);
      $query->set_int('status', POSTING_STATUS_ACTIVE);
      $_search_query->add_where_expression($query);
      return $this->db->GetOne($query->sql());
    }


     /* Returns the total number of threads in the given forum. */
    function get_n_threads($_forum_id) {
      $sql  = "SELECT COUNT(*)";
      $sql .= " FROM {t_thread}";
      if ($_forum_id)
        $sql .= " WHERE forum_id={forum_id}";
      $query = new FreechSqlQuery($sql);
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
      $query = new FreechSqlQuery($sql);
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
      $query = new FreechSqlQuery($sql);
      $query->set_int('ip_hash', $_ip_hash);
      $query->set_int('since',   $_since);
      return $this->db->GetOne($query->sql());
    }


    /* Given a posting, this function returns the id of the previous
     * entry in the same forum, or 0 if there is no previous entry.
     */
    function get_prev_posting_id_in_forum($_posting) {
      $sql  = "SELECT id FROM {t_posting}";
      $sql .= " WHERE forum_id={forum_id}";
      $sql .= " AND status={status}";
      $sql .= " AND id<{id}";
      $sql .= " ORDER BY id DESC";
      $query = new FreechSqlQuery($sql);
      $query->set_int('id',       $_posting->get_id());
      $query->set_int('forum_id', $_posting->get_forum_id());
      $query->set_int('status',   POSTING_STATUS_ACTIVE);
      $res = $this->db->SelectLimit($query->sql(), 1)
                          or die('ForumDB::get_prev_posting_id_in_forum()');
      $row = $res->FetchRow($res);
      return $row[id];
    }


    /* Given a posting, this function returns walks through the preceeding
     * postings, passing each to the given function.
     */
    function foreach_prev_posting($_posting,
                                  $_limit,
                                  $_func,
                                  $_data = NULL) {
      $sql  = "SELECT *,";
      $sql .= "UNIX_TIMESTAMP(updated) updated,";
      $sql .= "UNIX_TIMESTAMP(created) created";
      $sql .= " FROM {t_posting}";
      $sql .= " WHERE forum_id={forum_id}";
      $sql .= " AND id<{id}";
      $sql .= " ORDER BY id DESC";
      $query = new FreechSqlQuery($sql);
      $query->set_int('id',       $_posting->get_id());
      $query->set_int('forum_id', $_posting->get_forum_id());
      $res = $this->db->SelectLimit($query->sql(), $_limit)
                          or die('ForumDB::foreach_prev_posting()');
      return $this->_walk_list($res, $_func, $_data);
    }


    /* Given a posting, this function returns the id of the next
     * entry in the same forum, or 0 if there is no next entry.
     */
    function get_next_posting_id_in_forum($_posting) {
      $sql  = "SELECT id FROM {t_posting}";
      $sql .= " WHERE forum_id={forum_id}";
      $sql .= " AND status={status}";
      $sql .= " AND id>{id}";
      $sql .= " ORDER BY id";
      $query = new FreechSqlQuery($sql);
      $query->set_int('id',       $_posting->get_id());
      $query->set_int('forum_id', $_posting->get_forum_id());
      $query->set_int('status',   POSTING_STATUS_ACTIVE);
      $res = $this->db->SelectLimit($query->sql(), 1)
                          or die('ForumDB::get_next_posting_id_in_forum()');
      $row = $res->FetchRow($res);
      return $row[id];
    }


    /* Given a posting, this function returns walks through the following
     * postings, passing each to the given function.
     */
    function foreach_next_posting($_posting,
                                  $_limit,
                                  $_func,
                                  $_data = NULL) {
      $sql  = "SELECT *,";
      $sql .= "UNIX_TIMESTAMP(updated) updated,";
      $sql .= "UNIX_TIMESTAMP(created) created";
      $sql .= " FROM {t_posting}";
      $sql .= " WHERE forum_id={forum_id}";
      $sql .= " AND id>{id}";
      $sql .= " ORDER BY id";
      $query = new FreechSqlQuery($sql);
      $query->set_int('id',       $_posting->get_id());
      $query->set_int('forum_id', $_posting->get_forum_id());
      $res = $this->db->SelectLimit($query->sql(), $_limit)
                          or die('ForumDB::foreach_next_posting()');
      return $this->_walk_list($res, $_func, $_data);
    }


    /* Given a posting, this function returns the id of the previous
     * entry in the same thread, or 0 if there is no previous entry.
     */
    function get_prev_posting_id_in_thread($_posting) {
      $thread_id = $_posting->get_thread_id();
      $path      = $_posting->_get_path();
      if (!$path)
        return 0;
      $sql  = "SELECT id FROM {t_posting}";
      $sql .= " WHERE thread_id={thread_id}";
      $sql .= " AND status={status}";
      $sql .= " AND STRCMP(CONCAT('0x', HEX(path)), '{path}')=-1";
      $sql .= " ORDER BY path DESC";
      $query = new FreechSqlQuery($sql);
      $query->set_int('thread_id', $thread_id);
      $query->set_hex('path',      $path);
      $query->set_int('status',    POSTING_STATUS_ACTIVE);
      $res = $this->db->SelectLimit($query->sql(), 1)
                          or die('ForumDB::get_prev_posting_id_in_thread()');
      $row = $res->FetchRow($res);
      return $row[id];
    }


    /* Given a posting, this function returns the id of the next
     * posting in the same thread, or 0 if there is no next entry.
     */
    function get_next_posting_id_in_thread($_posting) {
      $thread_id = $_posting->get_thread_id();
      $path      = $_posting->_get_path();
      $sql  = "SELECT id FROM {t_posting}";
      $sql .= " WHERE thread_id={thread_id}";
      $sql .= " AND status={status}";
      $sql .= " AND NOT is_parent";
      if ($path)
        $sql .= " AND STRCMP(CONCAT('0x', HEX(path)), '{path}')=1";
      $sql .= " ORDER BY path";
      $query = new FreechSqlQuery($sql);
      $query->set_int('thread_id', $thread_id);
      $query->set_hex('path',      $path);
      $query->set_int('status',    POSTING_STATUS_ACTIVE);
      $res = $this->db->SelectLimit($query->sql(), 1)
                          or die('ForumDB::get_next_posting_id_in_thread()');
      $row = $res->FetchRow($res);
      return $row[id];
    }


    /* Given a posting, this function returns the id of the last
     * active posting in the previous thread of the same forum, or 0
     * if there is no previous thread.
     */
    function get_prev_thread_id($_posting) {
      $forum_id  = $_posting->get_forum_id();
      $thread_id = $_posting->get_thread_id();
      $sql  = "SELECT id FROM {t_posting}";
      $sql .= " WHERE forum_id={forum_id} AND thread_id<{thread_id}";
      $sql .= " AND status={status}";
      $sql .= " ORDER BY thread_id DESC, path";
      $query = new FreechSqlQuery($sql);
      $query->set_int('forum_id',  $forum_id);
      $query->set_int('thread_id', $thread_id);
      $query->set_int('status',    POSTING_STATUS_ACTIVE);
      $res = $this->db->SelectLimit($query->sql(), 1)
                          or die("ForumDB::get_prev_thread_id()");
      $row = $res->FetchRow($res);
      return $row[id];
    }


    /* Given a posting, this function returns the id of the first
     * active posting in the next thread of the same forum, or 0
     * if there is no next thread.
     */
    function get_next_thread_id($_posting) {
      $forum_id  = $_posting->get_forum_id();
      $thread_id = $_posting->get_thread_id();
      $sql   = "SELECT id FROM {t_posting}";
      $sql  .= " WHERE forum_id={forum_id} AND thread_id>{thread_id}";
      $sql  .= " AND status={status}";
      $sql  .= " ORDER BY thread_id, path";
      $query = new FreechSqlQuery($sql);
      $query->set_int('forum_id',  $forum_id);
      $query->set_int('thread_id', $thread_id);
      $query->set_int('status',    POSTING_STATUS_ACTIVE);
      $res = $this->db->SelectLimit($query->sql(), 1)
                          or die("ForumDB::get_next_thread_id()");
      $row = $res->FetchRow($res);
      return $row[id];
    }


    function get_duplicate_id_from_posting($_posting) {
      $sql   = "SELECT id";
      $sql  .= " FROM {t_posting}";
      $sql  .= " WHERE created > FROM_UNIXTIME({since}) AND hash={hash}";
      $query = new FreechSqlQuery($sql);
      $query->set_int('since', time() - 60 * 60 * 2);
      $query->set_string('hash', $_posting->get_hash());
      $res = $this->db->Execute($query->sql())
                            or die('ForumDB::get_duplicate_id_from_posting()');
      if ($res->EOF)
        return;
      $row = $res->FetchRow();
      return $row[id];
    }


    function is_spam($_posting) {
      $sql   = "SELECT id";
      $sql  .= " FROM {t_posting}";
      $sql  .= " WHERE ip_hash={ip_hash}";
      $sql  .= " AND status={status}";
      $sql  .= " AND created > FROM_UNIXTIME({since})";
      $query = new FreechSqlQuery($sql);
      $query->set_string('ip_hash', $_posting->get_ip_address_hash());
      $query->set_int   ('status',  POSTING_STATUS_SPAM);
      $query->set_int   ('since',   time() - 60 * 60 * 24 * 7);
      $res = $this->db->SelectLimit($query->sql(), 1)
                                          or die('ForumDB::is_spam()');
      return !$res->EOF;
    }


    function get_flood_blocked_until($_posting) {
      $since  = time() - cfg('max_postings_time');
      $offset = cfg('max_postings') * -1;

      // Find out how many postings were sent from the given user lately.
      if (!$_posting->get_user_is_anonymous()) {
        $uid        = $_posting->get_user_id();
        $n_postings = $this->get_n_postings_from_user_id($uid, $since);
        if ($n_postings < cfg('max_postings'))
          return;
        $search       = array('user_id' => $uid);
        $last_posting = $this->get_posting_from_query($search, $offset);
      }

      // Find out how many postings were sent from the given IP lately.
      if (!$last_posting) {
        $ip_hash    = $_posting->get_ip_address_hash();
        $n_postings = $this->get_n_postings_from_ip_hash($ip_hash, $since);
        if ($n_postings < cfg('max_postings'))
          return;
        $search       = array('ip_hash' => $ip_hash);
        $last_posting = $this->get_posting_from_query($search, $offset);
      }

      if (!$last_posting)
        return;

      // If the too many postings were posted, block this.
      $post_time = $last_posting->get_created_unixtime();
      return $post_time + cfg('max_postings_time');
    }


    /* Save the given forum.
     */
    function save_forum($_forum) {
      if ($_forum->get_id()) {
        $sql  = "UPDATE {t_forum} SET";
        $sql .= " name={name},";
        $sql .= " description={description},";
        $sql .= " owner_id={owner_id},";
        $sql .= " status={status}";
        $sql .= " WHERE id={id}";
      }
      else {
        $sql  = "INSERT INTO {t_forum}";
        $sql .= " (name, description, owner_id, status)";
        $sql .= " VALUES";
        $sql .= " ({name}, {description}, {owner_id}, {status})";
      }
      $query = new FreechSqlQuery($sql);
      $query->set_int   ('id',          $_forum->get_id());
      $query->set_int   ('owner_id',    $_forum->get_owner_id());
      $query->set_string('name',        $_forum->get_name());
      $query->set_string('description', $_forum->get_description());
      $query->set_int   ('status',      $_forum->get_status());
      $this->db->Execute($query->sql()) or die('ForumDB::save_forum');
      if (!$_forum->get_id())
        $_forum->set_id($this->db->Insert_Id());
    }


    /* Returns the forum with the given id.
     */
    function get_forum_from_id($_id) {
      $sql   = "SELECT * FROM {t_forum}";
      $sql  .= " WHERE id={id}";
      $query = new FreechSqlQuery($sql);
      $query->set_int('id', $_id);
      $res = $this->db->Execute($query->sql())
                           or die('ForumDB::get_forum_from_id()');
      if ($res->EOF)
        return NULL;
      $obj   = $res->FetchObj($res);
      $forum = new Forum;
      $forum->set_from_db($obj);
      return $forum;
    }


    /* Returns a list of all forums with the given status.
     * If $_status is -1 all forums are returned.
     */
    function get_forums($_status = -1, $_limit = -1, $_offset = 0) {
      $sql = "SELECT * FROM {t_forum}";
      if ($_status > -1)
        $sql .= " WHERE status={status}";
      $sql  .= " ORDER BY priority DESC, id";
      $query = new FreechSqlQuery($sql);
      $query->set_int('status', $_status);
      $res = $this->db->SelectLimit($query->sql(),
                                    (int)$_limit,
                                    (int)$_offset)
                          or die('ForumDB::get_forums()');
      $forums = array();
      while (!$res->EOF) {
        $obj   = $res->FetchObj($res);
        $forum = new Forum;
        $forum->set_from_db($obj);
        array_push($forums, $forum);
        $res->MoveNext();
      }
      return $forums;
    }
  }
?>
