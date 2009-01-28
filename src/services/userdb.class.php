<?php
  /*
  Freech.
  Copyright (C) 2005-2008 Samuel Abels, <http://debain.org>

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
  class UserDB {
    var $db;
    var $users;   // Caches users.

    function UserDB(&$_db) {
      $this->db = &$_db;
    }


    /**
     * Insert a new user or save an existing one.
     *
     * $_user:    The user to be saved.
     * Returns:   The id of the (maybe newly inserted) user.
     */
    function save_user(&$_user) {
      if (!is_object($_user))
        die('UserDB::save_user(): Invalid arg.');
      $query = new FreechSqlQuery();
      $query->set_int   ('id',          $_user->get_id());
      $query->set_int   ('group_id',    $_user->get_group_id());
      $query->set_int   ('status',      $_user->get_status());
      $query->set_int   ('lastlogin',   $_user->get_last_login_unixtime());
      $query->set_bool  ('public_mail', $_user->mail_is_public());
      $query->set_string('name',        $_user->get_name());
      $query->set_string('soundexname', $_user->get_soundexed_name());
      $query->set_string('password',    $_user->get_password_hash());
      $query->set_string('firstname',   $_user->get_firstname());
      $query->set_string('lastname',    $_user->get_lastname());
      $query->set_var   ('mail',        $_user->get_mail());
      $query->set_string('homepage',    $_user->get_homepage());
      $query->set_string('im',          $_user->get_im());
      $query->set_string('signature',   $_user->get_signature());
      if ($_user->get_id() < 1) {
        $sql   = "INSERT INTO {t_user}";
        $sql  .= " (";
        $sql  .= "  group_id, name, soundexname, password,";
        $sql  .= "  firstname, lastname,";
        $sql  .= "  mail, public_mail, homepage, im, signature, status,";
        $sql  .= " created, lastlogin";
        $sql  .= " )";
        $sql  .= " VALUES (";
        $sql  .= "  {group_id}, {name}, {soundexname}, {password},";
        $sql  .= "  {firstname}, {lastname},";
        $sql  .= "  {mail}, {public_mail}, {homepage}, {im}, {signature},";
        $sql  .= "  {status}, NULL, FROM_UNIXTIME({lastlogin})";
        $sql  .= ")";
        $query->set_sql($sql);
        $this->db->Execute($query->sql()) or die("UserDB::save_user: Ins");
        $newid = $this->db->Insert_ID();
        $_user->set_id($newid);
        $this->users[$newid] = &$_user;
        return $newid;
      }

      $sql   = "UPDATE {t_user} SET";
      $sql  .= " group_id={group_id},";
      $sql  .= " name={name}, soundexname={soundexname},";
      $sql  .= " password={password},";
      $sql  .= " firstname={firstname}, lastname={lastname},";
      $sql  .= " mail={mail}, public_mail={public_mail}, homepage={homepage},";
      $sql  .= " im={im}, signature={signature}, status={status},";
      $sql  .= " lastlogin=FROM_UNIXTIME({lastlogin})";
      $sql  .= " WHERE id={id}";
      $query->set_sql($sql);
      $this->db->Execute($query->sql()) or die("UserDB::save_user(): Upd");
      $this->users[$_user->get_id()] = &$_user;
      return $_user->get_id();
    }


    function _get_sql_from_query($_search) {
      if (!$_search)
        $_search = array();

      $query = new FreechSqlQuery;
      $sql   = "SELECT *,";
      $sql  .= "UNIX_TIMESTAMP(updated) updated,";
      $sql  .= "UNIX_TIMESTAMP(created) created";
      $sql  .= " FROM {t_user}";
      $sql  .= " WHERE 1";
      foreach ($_search as $key => $value) {
        if (is_int($value))
          $sql .= " AND $key={".$key.'}';
        else
          $sql .= " AND $key LIKE {".$key.'}';
        $query->set_var($key, $value);
      }
      $sql .= " ORDER BY name";
      $query->set_sql($sql);
      return $query->sql();
    }


    function _get_user_from_row($row) {
      if (!$row)
        return;
      $user = new User;
      $user->set_from_db($row);
      $this->users[$row[id]] = $user;
      return $user;
    }


    /**
     * Returns the user with the given id.
     * $_id: The id of the user.
     */
    function get_user_from_id($_id) {
      if (!$_id)
        die("UserDB::get_user_from_id(): Invalid id.");
      $sql = $this->_get_sql_from_query(array('id' => $_id));
      $row = $this->db->GetRow($sql);
      return $this->_get_user_from_row($row);
    }


    /**
     * Returns the user with the given name.
     * $_name: The name of the user.
     */
    function get_user_from_name($_name) {
      if (!$_name)
        die("UserDB::get_user_from_name(): Invalid name.");
      $sql = $this->_get_sql_from_query(array('name' => $_name));
      $row = $this->db->GetRow($sql);
      return $this->_get_user_from_row($row);
    }


    /**
     * Returns the user with the given email address.
     * $_mail: The email address of the user.
     */
    function get_user_from_mail($_mail) {
      if (!$_mail)
        die("UserDB::get_user_from_mail(): Invalid email address.");
      $sql = $this->_get_sql_from_query(array('mail' => $_mail));
      $row = $this->db->GetRow($sql);
      return $this->_get_user_from_row($row);
    }


    function foreach_user_from_query($_search,
                                     $_limit,
                                     $_offset,
                                     $_func,
                                     $_data = NULL) {
      $sql  = $this->_get_sql_from_query($_search);
      $res  = $this->db->SelectLimit($sql, (int)$_limit, (int)$_offset);
      $rows = $res->RecordCount();
      while ($row = $res->FetchRow()) {
        $user = $this->_get_user_from_row($row);
        call_user_func($_func, $user, $_data);
      }
      return $rows;
    }


    /* Returns the number of users that match the given search values. */
    function get_n_users_from_query($_search = NULL) {
      if (!$_search)
        $_search = array();

      $query = new FreechSqlQuery();
      $sql   = "SELECT COUNT(*) FROM {t_user}";
      $sql  .= " WHERE 1";
      foreach ($_search as $key => $value) {
        if (is_int($value))
          $sql .= " AND $key={".$key.'}';
        else
          $sql .= " AND $key LIKE {".$key.'}';
        $query->set_var($key, $value);
      }
      $query->set_sql($sql);
      $n_users = $this->db->GetOne($query->sql());
      if (!$n_users)
        return 0;
      return $n_users;
    }


    /**
     * Returns a list of all users whose name is similar to the
     * given one.
     * $_name: The name for which to find similar users.
     */
    function get_similar_users_from_name($_name,
                                         $_limit = -1,
                                         $_offset = 0) {
      if (!$_name)
        die('UserDB::get_similar_users_from_name(): Invalid name.');
      $user    = new User($_name);
      $soundex = $user->get_soundexed_name();
      $search  = array('soundexname' => $soundex);
      $sql     = $this->_get_sql_from_query($search);
      $res     = $this->db->SelectLimit($sql, (int)$_limit, (int)$_offset)
                      or die("UserDB::get_similar_users_from_name(): Select");
      $users = array();
      while ($row = $res->FetchRow())
        array_push($users, $this->_get_user_from_row($row));
      return $users;
    }


    /**
     * Returns TRUE if the given username is available, FALSE otherwise.
     * $_username: The name of the user.
     */
    function username_is_available($_username) {
      $needle = new User($_username);
      $users  = $this->get_similar_users_from_name($_username);
      foreach ($users as $user)
        if ($user->is_lexically_similar_to($needle))
          return FALSE;
      return TRUE;
    }


    /**
     * Returns the number of all users whose name is similar to the
     * given one.
     * $_name: The name for which to find similar users.
     */
    function count_similar_users_from_name($_name) {
      if (!$_name)
        die('UserDB::count_similar_users_from_name(): Invalid name.');
      $user    = new User($_name);
      $soundex = $user->get_soundexed_name();

      $query = new FreechSqlQuery();
      $sql   = "SELECT COUNT(*) FROM {t_user}";
      $sql  .= " WHERE soundexname={soundexname}";
      $query->set_sql($sql);
      $query->set_string('soundexname', $soundex);
      $n_users = $this->db->GetOne($query->sql());
      if (!$n_users)
        return 0;
      return $n_users;
    }


    /**
     * Returns the most recently created users.
     * $_limit: The maximum number of results.
     */
    function get_newest_users($_limit) {
      // Ordering by 'created' is slow, so using the primary key instead.
      // Status == 0 is USER_STATUS_DELETED.
      $sql   = "SELECT *,";
      $sql  .= "UNIX_TIMESTAMP(updated) updated,";
      $sql  .= "UNIX_TIMESTAMP(created) created";
      $sql  .= " FROM {t_user}";
      $sql  .= " ORDER BY id DESC";
      $query = new FreechSqlQuery($sql);
      $res = $this->db->SelectLimit($query->sql(), $_limit)
                             or die("UserDB::get_newest_users(): Select");
      $users = array();
      while ($row = &$res->FetchRow()) {
        $user = new User;
        $user->set_from_db($row);
        $this->users[$row[id]] = &$user;
        array_push($users, $user);
      }
      return $users;
    }


    /**
     * Returns the users who wrote the highest number of postings.
     * $_limit: The maximum number of results.
     */
    function get_top_users($_limit, $_since = 0) {
      $sql   = "SELECT u.*, g.name icon_name, COUNT(*) n_postings,";
      $sql  .= "UNIX_TIMESTAMP(u.updated) updated,";
      $sql  .= "UNIX_TIMESTAMP(u.created) created";
      $sql  .= " FROM {t_user}    u";
      $sql  .= " JOIN {t_group}   g ON g.id=u.group_id";
      $sql  .= " JOIN {t_posting} m ON m.user_id=u.id";
      $sql  .= " WHERE u.id != {anonymous}";
      if ($_since > 0)
        $sql .= " AND m.created>FROM_UNIXTIME({since})";
      $sql  .= " GROUP BY u.id";
      $sql  .= " ORDER BY n_postings DESC";
      $query = new FreechSqlQuery($sql);
      $query->set_int('anonymous', cfg('anonymous_user_id'));
      $query->set_int('since',     $_since);
      $res   = $this->db->SelectLimit($query->sql(), (int)$_limit)
                    or die("UserDB::get_top_users()");
      $users = array();
      while ($row = &$res->FetchRow()) {
        $user = new User;
        $user->set_from_db($row);
        $this->users[$row[id]] = &$user;
        $user->n_postings = $row['n_postings'];
        array_push($users, $user);
      }
      return $users;
    }
  }
?>
