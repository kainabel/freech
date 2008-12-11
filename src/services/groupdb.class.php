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
  class GroupDB {
    var $db;
    var $groups;   // Caches groups.

    function GroupDB(&$_db) {
      $this->db = &$_db;
    }


    function _get_sql_from_query($_search) {
      if (!$_search)
        $_search = array();

      $query = new FreechSqlQuery();
      $sql   = "SELECT g.*,";
      $sql  .= " UNIX_TIMESTAMP(g.updated) updated,";
      $sql  .= " UNIX_TIMESTAMP(g.created) created,";
      $sql  .= " p.id permission_id,";
      $sql  .= " p.name permission_name,";
      $sql  .= " p.allow permission_allow";
      $sql  .= " FROM {t_group} g";
      $sql  .= " LEFT JOIN {t_permission} p ON p.group_id=g.id";
      $sql  .= " WHERE 1";
      foreach ($_search as $key => $value) {
        if (is_int($value))
          $sql .= " AND g.$key={".$key.'}';
        else
          $sql .= " AND g.$key LIKE {".$key.'}';
        $query->set_var($key, $value);
      }
      $sql .= " ORDER BY g.name";
      $query->set_sql($sql);
      return $query->sql();
    }


    function _get_group_from_row($row) {
      if (!$row)
        return;
      $group = new Group;
      $group->set_from_db($row);
      $this->groups[$row->id] = $group;
      return $group;
    }


    function _pop_group_from_result($res) {
      if ($res->EOF)
        return;
      $row   = $res->FetchObj();
      $group = $this->_get_group_from_row($row);
      do {
        if ($row->permission_id) {
          if ($row->permission_allow)
            $group->grant($row->permission_name);
          else
            $group->deny($row->permission_name);
        }
        $res->MoveNext();
        if ($res->EOF)
          break;
        $row = $res->FetchObj();
        if ($row->id != $group->get_id())
          break;
      } while (TRUE);
      return $group;
    }


    /**
     * Insert a new group or save an existing one.
     *
     * $_group: The group to be saved.
     * Returns: The id of the (maybe newly inserted) group.
     */
    function save_group($_group) {
      if (!is_object($_group))
        die("GroupDB::save_group(): Invalid arg.");
      $query = &new FreechSqlQuery();
      $query->set_int   ('id',         $_group->get_id());
      $query->set_string('name',       $_group->get_name());
      $query->set_bool  ('is_special', $_group->is_special());
      $query->set_bool  ('is_active',  $_group->is_active());
      if ($_group->get_id() < 1) {
        $sql   = "INSERT INTO {t_group}";
        $sql  .= " (";
        $sql  .= "  id, name, is_special, is_active, created";
        $sql  .= " )";
        $sql  .= " VALUES (";
        $sql  .= "  {id}, {name}, {is_special}, {is_active}, NULL";
        $sql  .= " )";
        $query->set_sql($sql);
        $this->db->Execute($query->sql()) or die("GroupDB::save_group: Ins");
        $newid = $this->db->Insert_ID();
        $_group->set_id($newid);
        $this->groups[$newid] = &$_group;
        return $newid;
      }

      $sql   = "UPDATE {t_group} SET";
      $sql  .= " id={id},";
      $sql  .= " name={name},";
      $sql  .= " is_special={is_special},";
      $sql  .= " is_active={is_active}";
      $sql  .= " WHERE id={id}";
      $query->set_sql($sql);
      $this->db->Execute($query->sql()) or die("GroupDB::save_group(): Upd");
      $this->groups[$_group->get_id()] = $_group;

      //FIXME: Save permissions.
      return $_group->get_id();
    }


    /**
     * Returns the first group that matches the given criteria.
     * $_search: The search values.
     */
    function get_group_from_query($_search) {
      $sql = $this->_get_sql_from_query($_search);
      $res = $this->db->Execute($sql)
                              or die("GroupDB::get_group_from_query()");
      return $this->_pop_group_from_result($res);
    }


    /**
     * Returns all groups that match the given criteria.
     * $_search: The search values.
     */
    function get_groups_from_query($_search, $_limit = -1, $_offset = 0) {
      $sql  = $this->_get_sql_from_query($_search);
      $res  = $this->db->SelectLimit($sql, (int)$_limit, (int)$_offset);
      $list = array();
      while ($group = $this->_pop_group_from_result($res))
        array_push($list, $group);
      return $list;
    }


    /**
     * Passes all groups that match the given criteria to the given function.
     * $_search: The search values.
     */
    function foreach_group_from_query($_search,
                                      $_func,
                                      $_data   = NULL,
                                      $_limit  = -1,
                                      $_offset = 0) {
      $sql  = $this->_get_sql_from_query($_search);
      $res  = $this->db->SelectLimit($sql, (int)$_limit, (int)$_offset);
      while ($group = $this->_pop_group_from_result($res))
        call_group_func($_func, $group, $_data);
    }
  }
?>
