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
      $path = $this->db->GetOne($query->sql()) or die('ForumDB::_get_path()');
      return $path;
    }


    function _decorate_posting(&$_posting) {
      if (!$_posting)
        return NULL;
      $renderer = $this->renderers[$_posting->get_renderer()];
      if ($renderer)
        return new $renderer($_posting, $this->api);
      include_once '../objects/unknown_posting.class.php';
      return new UnknownPosting($_posting, $this->api);
    }


    function _get_posting_from_assoc(&$_row) {
      $posting = new Posting;
      $posting->set_from_assoc($_row);
      return $this->_decorate_posting($posting);
    }


    function _add_where_expression(&$_query, &$_search_values) {
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
        $query->set_string('body',            $_posting->get_body());
        $query->set_string('hash',            $_posting->get_hash());
        $query->set_string('ip_hash',         $_posting->get_ip_address_hash());
        $query->set_bool  ('force_stub',      $_posting->get_force_stub());
        $this->db->_Execute($query->sql()) or die('ForumDB::insert(): Ins1');
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
        $this->db->_Execute($query->sql())
                or die("ForumDB::insert(): Path.");

        // Update the child counter of the thread.
        $sql   = 'UPDATE {t_thread}';
        $sql  .= ' SET n_children=n_children+1,';
        $sql  .= ' updated=NULL';
        $sql  .= ' WHERE id={thread_id}';
        $query = new FreechSqlQuery($sql);
        $query->set_int('thread_id', $parentrow[thread_id]);
        $this->db->_Execute($query->sql()) or die('ForumDB::insert(): n++');

        // Update n_descendants of the parent.
        $sql   = "UPDATE {t_posting} SET n_descendants=n_descendants+1";
        $sql  .= " WHERE id={parent_id}";
        $query = new FreechSqlQuery($sql);
        $query->set_int('parent_id', $_parent_id);
        $this->db->_Execute($query->sql()) or die('ForumDB::insert(): n_desc');
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
        $this->db->_Execute($query->sql())
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
        $query->set_string('body',            $_posting->get_body());
        $query->set_string('hash',            $_posting->get_hash());
        $query->set_string('ip_hash',         $_posting->get_ip_address_hash());
        $query->set_bool  ('force_stub',      $_posting->get_force_stub());
        $this->db->_Execute($query->sql())
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
      $this->db->_Execute($query->sql()) or die('ForumDB::save(): 2');

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
      $this->db->_Execute($query->sql()) or die('ForumDB::move_thread(): 1');

      $sql   = 'UPDATE {t_posting} SET forum_id={forum_id}';
      $sql  .= ' WHERE thread_id={id}';
      $query->set_sql($sql);
      $this->db->_Execute($query->sql()) or die('ForumDB::move_thread(): 2');
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
      $res = $this->db->_Execute($query->sql())
                                  or die('ForumDB::get_posting_from_id()');
      if ($res->EOF)
        return;

      $row = $res->fields;
      if (strlen($row['path']) / 2 > 252)  // Path as long as the the DB field.
        $row['allow_answer'] = FALSE;
      if ($row['is_parent'])
        $row['relation'] = POSTING_RELATION_PARENT_UNFOLDED;

      return $this->_get_posting_from_assoc($row);
    }


    function get_threads_from_id($_thread_ids, $_include_inactive = TRUE) {
      trace('enter');
      if (count($_thread_ids) == 0)
        return array();

      // Note that the query returns the threads in order,
      // but it does NOT sort the postings in each thread, as that would
      // require mixing "order by ... ASC" with "order by ... DESC", which
      // is *very* slow.
      // IDX: thread:id
      // IDX: posting:thread_id
      $sql   = 'SELECT t.n_children,p.*,';
      $sql  .= ' HEX(p.path) path,';
      $sql  .= ' UNIX_TIMESTAMP(t.updated) threadupdate,';
      $sql  .= ' UNIX_TIMESTAMP(p.updated) updated,';
      $sql  .= ' UNIX_TIMESTAMP(p.created) created';
      $sql  .= ' FROM {t_posting} p';
      $sql  .= ' JOIN {t_thread} t ON p.thread_id=t.id';
      $sql  .= ' WHERE t.id IN (' . implode(',', $_thread_ids) . ')';
      if (!$_include_inactive)
        $sql .= ' AND p.status={status}';
      $sql  .= ' ORDER BY t.id DESC';
      $query = new FreechSqlQuery($sql);
      $query->set_int('status', POSTING_STATUS_ACTIVE);
      $res   = $this->db->_Execute($query->sql())
                                or die('ForumDB::get_threads_from_id()');
      trace('sql executed');

      $threads = array();
      while (!$res->EOF) {
        $thread = new Thread;
        $thread->set_from_db($this, $res);
        array_push($threads, $thread);
      }

      trace('threads received');
      return $threads;
    }


    function get_thread_from_id($_thread_id, $_include_inactive = TRUE) {
      $threads = $this->get_threads_from_id(array($_thread_id),
                                            $_include_inactive);
      return $threads[0];
    }


    function get_threads_from_forum_id($_forum_id,
                                       $_include_inactive,
                                       $_offset,
                                       $_limit) {
      $limit  = $_limit  * 1;
      $offset = $_offset * 1;
      trace('enter');

      // Select all root nodes.
      // IDX: posting:priority-created-is_parent-n_descendants-forum_id-status
      $sql   = 'SELECT p.thread_id';
      $sql  .= ' FROM {t_posting} p';
      $sql  .= ' WHERE p.is_parent=1 AND p.forum_id={forum_id}';
      $sql  .= ' AND (p.status={status} OR p.n_descendants!=0)';
      $sql  .= ' ORDER BY p.priority DESC, p.created DESC';
      $query = new FreechSqlQuery($sql);
      $query->set_int('forum_id', $_forum_id);
      $query->set_int('status',   POSTING_STATUS_ACTIVE);
      $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                            or die('ForumDB::get_threads_from_forum_id()');
      trace('sql executed');

      $thread_ids = array();
      while (!$res->EOF) {
        array_push($thread_ids, $res->fields['thread_id']);
        $res->MoveNext();
      }

      trace('thread ids received');
      return $this->get_threads_from_id($thread_ids, $_include_inactive);
    }


    function _walk_list(&$_res, $_func, $_data) {
      $numrows = $_res->RecordCount();
      while (!$_res->EOF) {
        $posting = $this->_get_posting_from_assoc($_res->fields);
        call_user_func($_func, $posting, $_data);
        $_res->MoveNext();
      }
      return $numrows;
    }


    function _get_foreach_postings_sql(&$_fields, $_desc = TRUE) {
      $sql   = "SELECT *,";
      $sql  .= "UNIX_TIMESTAMP(updated) updated,";
      $sql  .= "UNIX_TIMESTAMP(created) created";
      $sql  .= " FROM {t_posting}";
      $sql  .= " WHERE status={status}";
      $query = new FreechSqlQuery($sql);
      $query->set_int('status', POSTING_STATUS_ACTIVE);
      if ($_fields)
        $this->_add_where_expression($query, $_fields);
      if ($_desc)
        return $query->sql() . ' ORDER BY created DESC';
      return $query->sql() . ' ORDER BY created';
    }


    function foreach_posting_from_fields(&$_fields,
                                         $_desc   = TRUE,
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


    function get_postings_from_fields(&$_fields,
                                      $_desc   = TRUE,
                                      $_offset = 0,
                                      $_limit  = -1) {
      $limit    = $_limit  * 1;
      $offset   = $_offset * 1;
      $postings = array();
      $sql      = $this->_get_foreach_postings_sql($_fields, $_desc);
      $res      = $this->db->SelectLimit($sql, $limit, $offset)
                            or die('ForumDB::foreach_posting_from_fields()');

      while (!$res->EOF) {
        $posting = $this->_get_posting_from_assoc($res->fields);
        array_push($postings, $posting);
        $res->MoveNext();
      }
      return $postings;
    }


    function foreach_posting_from_query(&$_search_query,
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


    function get_postings_from_query(&$_search_values,
                                     $_offset = 0,
                                     $_limit  = -1) {
      $limit  = $_limit  * 1;
      $offset = $_offset * 1;

      $sql   = "SELECT *,";
      $sql  .= "UNIX_TIMESTAMP(updated) updated,";
      $sql  .= "UNIX_TIMESTAMP(created) created";
      $sql  .= " FROM {t_posting}";
      $sql  .= " WHERE status={status}";
      $query = new FreechSqlQuery($sql);
      $query->set_int('status', POSTING_STATUS_ACTIVE);
      if ($_search_values)
        $this->_add_where_expression($query, $_search_values);
      $sql  = $query->sql();
      $sql .= " ORDER BY created DESC";
      $query->set_sql($sql);
      $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                            or die('ForumDB::foreach_posting_from_query()');
      $postings = array();
      while (!$res->EOF) {
        $posting = $this->_get_posting_from_assoc($res->fields);
        array_push($postings, $posting);
        $res->MoveNext();
      }
      return $postings;
    }


    function get_posting_from_query(&$_search_values, $_offset = 0) {
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
      $sql .= " WHERE p.status={status}";
      if ($_forum_id)
        $sql .= " AND p.forum_id={forum_id}";
      if ($_updates)
        $sql .= " ORDER BY p.priority DESC,p.updated DESC";
      else
        $sql .= " ORDER BY p.priority DESC,p.created DESC";
      $query = new FreechSqlQuery($sql);
      $query->set_int('status',   POSTING_STATUS_ACTIVE);
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
     */
    function get_postings_from_user($_user_id, $_offset, $_limit) {
      $limit  = $_limit  * 1;
      $offset = $_offset * 1;

      // Select the postings of the user.
      // IDX: posting:user_id-created
      $sql  = "SELECT a.id";
      $sql .= " FROM {t_posting} a";
      $sql .= " WHERE a.user_id={userid}";
      $sql .= " ORDER BY a.created DESC";
      $query = new FreechSqlQuery($sql);
      $query->set_int('userid', $_user_id);
      $res = $this->db->SelectLimit($query->sql(), $limit, $offset)
                     or die('ForumDB::foreach_posting_from_user(): 1');

      if ($res->EOF)
        return array();
      $parent_ids = array();
      while (!$res->EOF) {
        array_push($parent_ids, $res->fields['id']);
        $res->MoveNext();
      }

      // Grab the direct responses to those postings.
      // IDX: posting:id
      // IDX: posting:thread_id
      $sql  = "SELECT b.*,";
      $sql .= " a.id thread_id,";
      $sql .= " b.n_descendants n_children,";
      $sql .= " IF(a.id=b.id, 1, 0) is_parent,";
      $sql .= " IF(a.id=b.id, '', HEX(SUBSTRING(b.path, -5))) path,";
      $sql .= " UNIX_TIMESTAMP(t.updated) threadupdate,";
      $sql .= " UNIX_TIMESTAMP(b.updated) updated,";
      $sql .= " UNIX_TIMESTAMP(b.created) created";
      $sql .= " FROM {t_posting} a";
      $sql .= " LEFT JOIN {t_posting} b ON b.thread_id=a.thread_id";
      $sql .= " AND b.path LIKE CONCAT(REPLACE(REPLACE(REPLACE(a.path, '\\\\', '\\\\\\\\'), '_', '\\_'), '%', '\\%'), '%')";
      $sql .= " AND LENGTH(b.path)<=LENGTH(a.path)+5";
      $sql .= " JOIN {t_thread} t ON b.thread_id=t.id";
      $sql .= ' WHERE a.id IN (' . implode(',', $parent_ids) . ')';
      $sql .= " ORDER BY a.id DESC";

      $query = new FreechSqlQuery($sql);
      $res   = $this->db->_Execute($query->sql())
                          or die('ForumDB::foreach_posting_from_user()');

      $threads = array();
      while (!$res->EOF) {
        $thread = new Thread;
        $thread->set_from_db($this, $res);
        array_push($threads, $thread);
      }

      return $threads;
    }


    /* Returns the total number of entries in the given forum. */
    function get_n_postings($_search_values = NULL, $_since = 0, $_until = 0) {
      $sql  = "SELECT COUNT(*)";
      $sql .= " FROM {t_posting}";
      $sql .= " WHERE status={status}";
      if ($_since)
        $sql .= " AND created > FROM_UNIXTIME({since})";
      if ($_until)
        $sql .= " AND created < FROM_UNIXTIME({until})";
      $query = new FreechSqlQuery($sql);
      $query->set_int('status', POSTING_STATUS_ACTIVE);
      $query->set_int('since',  $_since);
      $query->set_int('until',  $_until);
      if ($_search_values)
        $this->_add_where_expression($query, $_search_values);
      return $this->db->GetOne($query->sql());
    }


    function get_n_postings_from_query(&$_search_query) {
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
      $sql .= " FROM {t_posting}";
      $sql .= " WHERE is_parent=1 AND (status={status} OR n_descendants!=0)";
      if ($_forum_id)
        $sql .= " AND forum_id={forum_id}";
      $query = new FreechSqlQuery($sql);
      $query->set_int('status',   POSTING_STATUS_ACTIVE);
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
    function get_prev_posting_id_in_forum(&$_posting) {
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
      return $res->fields['id'];
    }


    /* Given a posting, this function returns walks through the preceeding
     * postings, passing each to the given function.
     */
    function foreach_prev_posting(&$_posting,
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
    function get_next_posting_id_in_forum(&$_posting) {
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
      return $res->fields['id'];
    }


    /* Given a posting, this function returns walks through the following
     * postings, passing each to the given function.
     */
    function foreach_next_posting(&$_posting,
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
    function get_prev_posting_id_in_thread(&$_posting) {
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
      return $res->fields['id'];
    }


    /* Given a posting, this function returns the id of the next
     * posting in the same thread, or 0 if there is no next entry.
     */
    function get_next_posting_id_in_thread(&$_posting) {
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
      return $res->fields['id'];
    }


    /* Given a posting, this function returns the id of the last
     * active posting in the previous thread of the same forum, or 0
     * if there is no previous thread.
     */
    function get_prev_thread_id(&$_posting) {
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
      return $res->fields['id'];
    }


    /* Given a posting, this function returns the id of the first
     * active posting in the next thread of the same forum, or 0
     * if there is no next thread.
     */
    function get_next_thread_id(&$_posting) {
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
      return $res->fields['id'];
    }


    function get_duplicate_id_from_posting(&$_posting) {
      $sql   = "SELECT id";
      $sql  .= " FROM {t_posting}";
      $sql  .= " WHERE created > FROM_UNIXTIME({since}) AND hash={hash}";
      $query = new FreechSqlQuery($sql);
      $query->set_int('since', time() - 60 * 60 * 2);
      $query->set_string('hash', $_posting->get_hash());
      $res = $this->db->_Execute($query->sql())
                            or die('ForumDB::get_duplicate_id_from_posting()');
      if ($res->EOF)
        return;
      return $res->fields['id'];
    }


    function is_spam(&$_posting) {
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


    function get_flood_blocked_until(&$_posting) {
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
    function save_forum(&$_forum) {
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
      $this->db->_Execute($query->sql()) or die('ForumDB::save_forum');
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
      $res = $this->db->_Execute($query->sql())
                           or die('ForumDB::get_forum_from_id()');
      if ($res->EOF)
        return NULL;
      $forum = new Forum;
      $forum->set_from_assoc($res->fields);
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
        $forum = new Forum;
        $forum->set_from_assoc($res->fields);
        array_push($forums, $forum);
        $res->MoveNext();
      }
      return $forums;
    }
  }
?>
