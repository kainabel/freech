<?php
  /*
  Freech.
  Copyright (C) 2005-2008 Samuel Abels

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
  class ModLogDB {
    var $db;

    function ModLogDB(&$_db) {
      $this->db = $_db;
    }


    function _get_sql_from_query(&$_search) {
      if (!$_search)
        $_search = array();

      $query = new FreechSqlQuery();
      $sql   = 'SELECT m.*,';
      $sql  .= ' a.attribute_name,a.attribute_type,a.attribute_value,';
      $sql  .= ' UNIX_TIMESTAMP(m.created) created';
      $sql  .= ' FROM {t_modlog} m';
      $sql  .= ' LEFT JOIN {t_modlog_attribute} a ON a.modlog_id=m.id';
      $sql  .= ' WHERE 1';
      foreach ($_search as $key => $value) {
        if (is_int($value))
          $sql .= " AND m.$key={".$key.'}';
        else
          $sql .= " AND m.$key LIKE {".$key.'}';
        $query->set_var($key, $value);
      }
      $sql .= ' ORDER BY m.id DESC';
      $query->set_sql($sql);
      return $query->sql();
    }


    function &_get_item_from_row(&$row) {
      if (!$row)
        return;
      $item = new ModLogItem;
      $item->set_from_db($row);
      return $item;
    }


    function &_pop_item_from_result(&$res) {
      if ($res->EOF)
        return;
      $row  = $res->FetchObj();
      $item = $this->_get_item_from_row($row);
      do {
        $item->set_attribute_from_db($row);
        $res->MoveNext();
        if ($res->EOF)
          break;
        $row = $res->FetchObj();
        if ($row->id != $item->get_id())
          break;
      } while (TRUE);
      return $item;
    }


    function _save_attribute($_modlog_id, $_name, $_value) {
      $sql  = 'INSERT INTO {t_modlog_attribute}';
      $sql .= ' (modlog_id, attribute_name, attribute_type, attribute_value)';
      $sql .= ' VALUES (';
      $sql .= ' {modlog_id},';
      $sql .= ' {attribute_name}, {attribute_type}, {attribute_value}';
      $sql .= ' )';
      $query = new FreechSqlQuery($sql);
      $query->set_int   ('modlog_id',       $_modlog_id);
      $query->set_string('attribute_name',  $_name);
      $query->set_string('attribute_type',  'string');  //FIXME
      $query->set_string('attribute_value', $_value);
      $this->db->_Execute($query->sql()) or die('ModLogDB::_save_attribute()');
    }


    function _save_attributes(&$_item) {
      foreach ($_item->get_attribute_list() as $name => $value)
        $this->_save_attribute($_item->get_id(), $name, $value);
    }


    /**
     * Logs an item.
     * Returns: The id of the (maybe newly inserted) group.
     */
    function log(&$_item) {
      if (!is_object($_item))
        die('ModLogDB::save_item(): Invalid arg.');
      $query = new FreechSqlQuery();
      $query->set_int   ('moderator_id',         $_item->get_moderator_id());
      $query->set_string('moderator_name',       $_item->get_moderator_name());
      $query->set_string('moderator_group_name', $_item->get_moderator_group_name());
      $query->set_string('moderator_icon',       $_item->get_moderator_icon());
      $query->set_string('action',               $_item->get_action());
      $query->set_string('reason',               $_item->get_reason());

      $sql  = 'INSERT INTO {t_modlog}';
      $sql .= ' (';
      $sql .= '  moderator_id,';
      $sql .= '  moderator_name,';
      $sql .= '  moderator_group_name,';
      $sql .= '  moderator_icon,';
      $sql .= '  action,';
      $sql .= '  reason,';
      $sql .= '  created';
      $sql .= ' )';
      $sql .= ' VALUES (';
      $sql .= '  {moderator_id},';
      $sql .= '  {moderator_name},';
      $sql .= '  {moderator_group_name},';
      $sql .= '  {moderator_icon},';
      $sql .= '  {action},';
      $sql .= '  {reason},';
      $sql .= '  NULL';
      $sql .= ' )';
      $query->set_sql($sql);

      $this->db->StartTrans();
      $this->db->_Execute($query->sql()) or die('ModLogDB::save_item: Ins');
      $newid = $this->db->Insert_ID();
      $_item->set_id($newid);
      $this->_save_attributes($_item);
      $this->db->CompleteTrans();
      return $newid;
    }


    /**
     * Returns all items that match the given criteria.
     * $_search: The search values.
     */
    function &get_items_from_query(&$_search, $_limit = -1, $_offset = 0) {
      // Get a list of item ids.
      $query = new FreechSqlQuery;
      $sql   = 'SELECT m.id';
      $sql  .= ' FROM {t_modlog} m';
      $sql  .= ' WHERE 1';
      foreach ($_search as $key => $value) {
        if (is_int($value))
          $sql .= " AND m.$key={".$key.'}';
        else
          $sql .= " AND m.$key LIKE {".$key.'}';
        $query->set_var($key, $value);
      }
      $sql .= ' ORDER BY m.id DESC';
      $query->set_sql($sql);
      $res  = $this->db->SelectLimit($query->sql(),
                                     (int)$_limit,
                                     (int)$_offset);

      // Now fetch the items, including attributes.
      $sql   = 'SELECT m.*,';
      $sql  .= ' a.attribute_name,a.attribute_type,a.attribute_value,';
      $sql  .= ' UNIX_TIMESTAMP(m.created) created';
      $sql  .= ' FROM {t_modlog} m';
      $sql  .= ' LEFT JOIN {t_modlog_attribute} a ON a.modlog_id=m.id';
      $sql  .= ' WHERE 0';
      while (!$res->EOF) {
        $row = $res->FetchObj();
        $sql .= ' OR m.id='.$row->id;
        $res->MoveNext();
      }
      $sql  .= ' ORDER BY m.id DESC';
      $query = new FreechSqlQuery($sql);
      $res   = $this->db->_Execute($query->sql());
      $list  = array();
      while ($item = $this->_pop_item_from_result($res))
        array_push($list, $item);
      return $list;
    }
  }
?>
