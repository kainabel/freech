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
  
  /* WARNING: THIS FILE CONTAINS UNSTABLE, BOGUS, EVIL PROTOTYPE CODE THAT SHOULD
     NOT BE USED IN A PRODUCTION ENVIRONMENT! DON'T BLAME ME IF YOU STILL DO THAT.
   */
?>
<?php
  class AccountDB {
    var $db;
    var $users;   // Caches users.
    var $groups;  // Caches groups.
    
    function AccountDB(&$_db) {
      $this->db = &$_db;
    }
    
    
    /***********************************************************************
     * Private API.
     ***********************************************************************/
    function _lock_write($_forum) {
      $query = &new SqlQuery("LOCK TABLE {$_forum} WRITE");
      //$this->db->execute($query->sql()) or die("AccountDB::lock_write()");
    }
    
    
    function _unlock_write() {
      $query = &new SqlQuery("UNLOCK TABLES");
      //$this->db->execute($query->sql()) or die("AccountDB::unlock_write()");
    }
    
    
    function _add_to_group($_groupid, $_user) {
      if (!is_object($_user))
        die("AccountDB::_add_to_group(): Invalid user.");
      $sql   = "INSERT INTO {t_group_user}";
      $sql  .= "  (g_id, u_id)";
      $sql  .= " VALUES";
      $sql  .= "  ({g_id}, {u_id})";
      $query->set_sql($sql);
      $query->set_int('g_id', $_groupid);
      $query->set_int('u_id', $_user->get_id());
      $newid = $this->db->Execute($query->sql())
                            or die("AccountDB::_add_to_group");
      return $newid;
    }
    
    
    /***********************************************************************
     * Public API.
     ***********************************************************************/
    /* Returns the user with the given id.
     * $_id:    The id of the user.
     */
    function &get_user($_id) {
      if (!$_id)
        die("AccountDB::get_user(): Invalid id.");
      if (isset($this->users[$_id]))
        return $this->users[$_id];
      $sql   = "SELECT *";
      $sql  .= " FROM {t_user}";
      //FIXME: HACK: replaced id by login
      $sql  .= " WHERE login={id}";
      $query = &new SqlQuery($sql);
      $query->set_string('id', $_id);
      $row   = $this->db->GetRow($query->sql()) or die("AccountDB::get_user()");
      $user  = &new User;
      $user->set_from_db($row);
      $this->users[$row[id]] = &$user;
      return $user;
    }
    
    
    /* Insert a new user or save an existing one.
     *
     * $_user:    The user to be saved.
     * Returns:   The id of the (maybe newly inserted) user.
     */
    function save_user(&$_user) {
      if (!is_object($_user))
        die("AccountDB::save_user(): Invalid arg.");
      $query = &new SqlQuery();
      $query->set_int   ('id',        $_user->get_id());
      $query->set_string('login',     $_user->get_login());
      $query->set_string('password',  $_user->get_password_hash());
      $query->set_string('firstname', $_user->get_firstname());
      $query->set_string('lastname',  $_user->get_lastname());
      $query->set_string('mail',      $_user->get_mail());
      $query->set_string('homepage',  $_user->get_homepage());
      $query->set_string('im',        $_user->get_im());
      $query->set_string('signature', $_user->get_signature());
      $query->set_string('updated',   $_user->get_updated_unixtime());
      if ($_user->get_id() < 1) {
        $sql   = "INSERT INTO {t_user}";
        $sql  .= " (";
        $sql  .= "  login, password, firstname, lastname,";
        $sql  .= "  mail, homepage, im, signature, updated, created";
        $sql  .= " )";
        $sql  .= " VALUES (";
        $sql  .= "  {login}, {password}, {firstname}, {lastname},";
        $sql  .= "  {mail}, {homepage}, {im}, {signature}, {updated}, NULL";
        $sql  .= ")";
        $query->set_sql($sql);
        $this->db->Execute($query->sql())
                              or die("AccountDB::save_user");
        $newid = $this->db->Insert_Id();
        $_user->set_id($newid);
        $this->users[$newid] = &$_user;
        //FIXME: Map to groups.
        return $newid;
      }
      
      $sql   = "UPDATE {t_user} SET";
      $sql  .= " login={login}, password={password},";
      $sql  .= " firstname={firstname}, lastname={lastname},";
      $sql  .= " mail={mail}, homepage={homepage},";
      $sql  .= " im={im}, signature={signature},";
      $sql  .= " updated={updated}";
      $sql  .= " WHERE id={id}";
      $query->set_sql($sql);
      $this->db->Execute($query->sql()) or die("AccountDB::save_user(): Upd");
      $this->users[$_user->get_id()] = &$_user;
      //FIXME: Map to groups.
      return $_user->get_id();
    }
    
    
    /* Returns the group with the given id.
     * $_id:    The id of the group.
     */
    function &get_group($_id) {
      if (!$_id)
        die("AccountDB::get_group(): Invalid id.");
      if (isset($this->groups[$_id]))
        return $this->groups[$_id];
      $sql   = "SELECT *";
      $sql  .= " FROM {t_group}";
      $sql  .= " WHERE id={id}";
      $query = &new SqlQuery($sql);
      $query->set_int('id', $_id);
      $row  = $this->db->GetRow($query->sql()) or die("AccountDB::get_group()");
      $group = &new Group;
      $group->set_from_db($row);
      $this->groups[$row[id]] = &$group;
      return $group;
    }
    
    
    /* Insert a new group or save an existing one.
     *
     * $_group:   The group to be saved.
     * Returns:   The id of the (maybe newly inserted) group.
     */
    function save_group(&$_group) {
      if (!is_object($_group))
        die("AccountDB::save_group(): Invalid arg.");
      $query = &new SqlQuery();
      $query->set_int   ('id',      $_group->get_id());
      $query->set_string('name',    $_group->get_name());
      $query->set_string('active',  $_group->get_active());
      $query->set_string('updated', $_group->get_updated_unixtime());
      if ($_group->get_id() < 1) {
        $sql   = "INSERT INTO {t_group}";
        $sql  .= " (";
        $sql  .= "  name, active, created";
        $sql  .= " )";
        $sql  .= " VALUES (";
        $sql  .= "  {name}, {active}, NULL";
        $sql  .= ")";
        $query->set_sql($sql);
        $newid = $this->db->Execute($query->sql())
                              or die("AccountDB::save_group(): Insert");
        $_group->set_id($newid);
        $this->groups[$newid] = &$_group;
        return $newid;
      }
      
      $sql  = "UPDATE {t_group} SET";
      $sql .= " name={name}, active={active},updated={updated}";
      $sql .= " WHERE id={id}";
      $query->set_sql($sql);
      $this->db->Execute($query->sql()) or die("AccountDB::save_group(): Upd");
      $this->groups[$_group->get_id()] = &$_group;
      return $_group->get_id();
    }
    
    
    /* Walks through all users starting at $_offset, passing each user to the
     * function given in $func. If the given group id is -1, all users are
     * being selected, if it is 0, only those users that are in no group are
     * being selected.
     *
     * Args: $_groupid The id of the group from which users are to be selected.
     *       $_offset  The offset.
     *       $_limit   The maximum number of users to walk through, or -1.
     *       $_func    A reference to the function to which each user is passed.
     *       $_data    Passed through to $_func as an argument.
     *
     * Returns: The number of users processed.
     */
    function foreach_user($_groupid, $_offset, $_limit, $_func, $_data) {
      $sql   = "SELECT u.*,";
      $sql  .= " g.id g_id, g.name g_name, g.active g_active,";
      $sql  .= " g.created g_created,g.updated g_updated";
      if ($_groupid > 0) {
        $sql  .= " FROM      {t_group_user} gu1";
        $sql  .= " LEFT JOIN {t_group_user} gu2 ON gu1.u_id=gu2.u_id";
        $sql  .= " LEFT JOIN {t_user}       u   ON gu2.u_id=u.id";
        $sql  .= " LEFT JOIN {t_group}      g   ON gu2.g_id=g.id";
        $sql  .= " WHERE gu1.g_id={g_id}";
        $query = &new SqlQuery($sql);
        $query->set_int('g_id', $_groupid);
      }
      elseif ($_groupid == 0) {
        $sql  .= " FROM      {t_user}       u";
        $sql  .= " LEFT JOIN {t_group_user} gu ON gu.u_id=u.id";
        $sql  .= " LEFT JOIN {t_group}      g  ON gu.g_id=g.id";
        $sql  .= " WHERE g.id IS NULL";
        $query = &new SqlQuery($sql);
      }
      else {
        $sql  .= " FROM      {t_user}       u";
        $sql  .= " LEFT JOIN {t_group_user} gu ON gu.u_id=u.id";
        $sql  .= " LEFT JOIN {t_group}      g  ON gu.g_id=g.id";
        $query = &new SqlQuery($sql);
      }
      $res     = $this->db->SelectLimit($query->sql(), $_limit, $_offset)
                              or die("AccountDB::foreach_user(): Select");
      $numrows = $res->RecordCount();
      $row     = &$res->FetchRow();
      while ($row) {
        $nextrow = &$res->FetchRow();
        
        // Create a user and cache it.
        if (!isset($this->users[$row[id]])) {
          $user = &new User;
          $this->users[$row[id]] = &$user;
        }
        else
          $user = &$this->users[$row[id]];
        $user->set_from_db($row);
        
        // If the user also has a group attached, create one and let him know.
        if ($row[g_id]) {
          // Create a group and cache it.
          if (!isset($this->groups[$row[g_id]])) {
            $group = &new Group();
            $this->groups[$row[g_id]] = &$group;
          }
          else
            $group = &$this->groups[$row[g_id]];
          $group->set_id($row[g_id]);
          $group->set_name($row[g_name]);
          $group->set_active($row[g_active]);
          $group->set_created_unixtime($row[g_created]);
          $group->set_updated_unixtime($row[g_updated]);
          
          // Attach a reference to the group to the user.
          $user->add_to_group($group);
        }
        
        // Invoke the callback only when all groups of this user were collected.
        if ($nextrow[id] != $row[id])
          call_user_func($_func, $user, $_data);
        $row = &$nextrow;
      }
      return $numrows;
    }
    
    
    /* Walks through all groups starting at $_offset, passing each group to the
     * function given in $func.
     *
     * Args: $_offset  The offset.
     *       $_limit   The maximum number of groups to walk through, or -1.
     *       $_func    Reference to the function to which each group is passed.
     *       $_data    Passed to $_func as an argument.
     *
     * Returns: The number of groups processed.
     */
    function foreach_group($_offset, $_limit, $_func, $_data) {
      $sql   = "SELECT g.*";
      $sql  .= " FROM {t_group}";
      $query = &new SqlQuery($sql);
      $res   = $this->db->SelectLimit($query->sql(), $_limit, $_offset)
                            or die("AccountDB::foreach_user(): Select");
      $numrows = $res->RecordCount();
      while ($row = &$res->FetchRow()) {
        // Create a group and cache it.
        if (!isset($this->groups[$row[id]])) {
          $group = &new Group();
          $this->groups[$row[id]] = &$group;
        }
        else
          $group = &$this->groups[$row[id]];
        $group->set_from_db($row);
        call_user_func($_func, $group, $_data);
      }
      return $numrows;
    }
    
    //FIXME: the 'or' after $this->db->GetOne will match too, if the query returns 0 (affects next function also)
    /* Returns the number of users in the group with the given id, the
     * number of all users if no group was given. */
    function get_n_users($_groupid = -1) {
      if ($_groupid > 0) {
        $sql  = "SELECT COUNT(*)";
        $sql .= " FROM {t_group_user} gu";
        $sql .= " WHERE gu.g_id={g_id}";
        $query = &new SqlQuery($sql);
        $query->set_int('g_id', $_group->get_id());
        $n = $this->db->GetOne($query->sql())
                          or die("AccountDB::get_n_users(): 1");
      }
      elseif ($_groupid == 0) {
        $sql  = "SELECT COUNT(*)";
        $sql .= " FROM      {t_user}       u";
        $sql .= " LEFT JOIN {t_group_user} gu ON gu.u_id=u.id";
        $sql .= " WHERE g.id=NULL";
        $query = &new SqlQuery($sql);
        $n = $this->db->GetOne($query->sql())
                          or die("AccountDB::get_n_users(): 2");
      }
      else {
        $query = &new SqlQuery("SELECT COUNT(*) FROM {t_user}");
        $n = $this->db->GetOne($query->sql())
                          or die("AccountDB::get_n_users(): 3");
      }
      return $n;
    }
    
    
    /* Returns the number of groups. */
    function get_n_groups() {
      $query = &new SqlQuery("SELECT COUNT(*) FROM {t_group}");
      $n = $this->db->GetOne($query->sql())
                        or die("AccountDB::get_n_groups(): 3");
      return $n;
    }
    
    /* checks if loginname is already in use */
    function login_exists($_login) {
      $query = &new SqlQuery("SELECT IF(COUNT(login),'false','true') FROM {t_user} WHERE login={login}");
      $query->set_string('login',$_login);
      $n = $this->db->GetOne($query->sql())
                        or die("AccountDB::login_exists".$n);
      if ($n == 'false')
        return false;
      else
        return true;
    }
  }
?>
