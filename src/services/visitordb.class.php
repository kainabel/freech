<?php
  /*
  Freech.
  Copyright (C) 2008 Samuel Abels, <http://debain.org>

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
  class VisitorDB {
    var $db;
    
    function VisitorDB(&$_db) {
      $this->db = &$_db;
    }
    
    
    function &_ip_hash($_ip) {
      // Note that this needs to work with both, IPv4 and IPv6.
      $ip_net = preg_replace('/[\d\w]+$/', '', $_ip);
      return md5($ip_net . cfg("salt"));
    }


    /* Returns the id of the entry with the given ip, if it
     * has been here in the last n minutes.
     * $_ip: The ip address of the user.
     * $_since: The start time.
     */
    function &_id_of($_ip, $_since) {
      $sql  = "SELECT id FROM {t_visitor}";
      $sql .= " WHERE ip_hash={ip_hash} and visit > {since}";
      $query = &new FreechSqlQuery($sql);
      $query->set_string('ip_hash', $_ip);
      $query->set_int('since', $_since);
      $row = $this->db->GetRow($query->sql());
      if (!$row)
        return;
      return $row[id];
    }


    function _update_time($_id) {
      $sql  = "UPDATE {t_visitor}";
      $sql .= " SET visit={visit}";
      $sql .= " WHERE id={id}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('visit', time());
      $query->set_string('id', $_id);
      $this->db->Execute($query->sql()) or die("VisitorDB::_update_time()");
    }


    function _is_bot($_host) {
      return preg_match("/slurp|googlebot|msnbot|crawl/", $_host) != 0;
    }


    function _insert_entry($_ip_hash, $_count) {
      $sql  = "INSERT INTO {t_visitor}";
      $sql .= " (ip_hash, counter, visit)";
      $sql .= " VALUES ({ip_hash}, {counter}, {visit})";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('visit', time());
      $query->set_string('ip_hash', $_ip_hash);
      $query->set_string('counter', $_count);
      $this->db->Execute($query->sql()) or die("VisitorDB::_insert_entry()");
      $newid = $this->db->Insert_ID();
    }


    /* Delete old and unneeded entries from the table. */
    function _flush() {
      $sql  = "DELETE FROM {t_visitor}";
      $sql .= " WHERE visit < {end}";
      $query = &new FreechSqlQuery($sql);
      $query->set_int('end', time() - 60 * 60 * 24 * 30);
      $this->db->Execute($query->sql()) or die("VisitorDB::_flush()");
    }


    /* Returns the user with the given id.
     * $_ip: The ip address of the user.
     */
    function &count() {
      // If the current user was here in the last 10 minutes, just 
      // update his timestamp and return.
      $ip      = getenv("REMOTE_ADDR");
      $ip_hash = $this->_ip_hash($ip);
      $id      = $this->_id_of($ip_hash, time() - 60 * 10);
      if ($id)
        return $this->_update_time($id);

      // Else, check if it is a likely bot.
      $host = gethostbyaddr($ip);
      if ($this->_is_bot($host))
        return;

      // Ending up here we have a new visitor. Save it.
      // Note that this needs to work with both, IPv4 and IPv6.
      $count   = $this->get_n_visitors();
      $this->_insert_entry($ip_hash, $count + 1);

      // To limit the number of rows, delete old entries.
      $this->_flush();
    }


    /* Returns the number of visitors. */
    function get_n_visitors($_since = 0) {
      if ($_since == 0)
        $query = &new FreechSqlQuery("SELECT MAX(counter) FROM {t_visitor}");
      else {
        $sql   = "SELECT COUNT(*) FROM {t_visitor}";
        $sql  .= " WHERE visit > {start}";
        $query = &new FreechSqlQuery($sql);
        $query->set_int('start', $_since);
      }
      return $this->db->GetOne($query->sql()) or 0;
    }
  }
?>
