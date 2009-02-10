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
    
    
    function _ip_hash($_ip) {
      // Note that this needs to work with both, IPv4 and IPv6.
      $ip_net = preg_replace('/[\d\w]+$/', '', $_ip);
      return md5($ip_net . cfg('salt'));
    }


    /* Returns the id of the entry with the given ip, if it
     * has been here in the last n minutes.
     * $_ip: The ip address of the user.
     * $_since: The start time.
     */
    function _was_here($_ip_hash, $_since) {
      trace('Enter');
      // IDX: visitor:ip_hash
      $sql   = "SELECT 1 FROM {t_visitor}";
      $sql  .= " WHERE ip_hash={ip_hash} and visit > {since}";
      $query = new FreechSqlQuery($sql);
      $query->set_string('ip_hash', $_ip_hash);
      $query->set_int   ('since',   $_since);
      $res = $this->db->_Execute($query->sql());
      trace('Leave');
      if ($res->EOF)
        return FALSE;
      return TRUE;
    }


    function _update_time($_ip_hash) {
      trace('Enter');
      // IDX: visitor:ip_hash
      $sql  = "UPDATE {t_visitor}";
      $sql .= " SET visit={visit}";
      $sql .= " WHERE ip_hash={ip_hash}";
      $query = new FreechSqlQuery($sql);
      $query->set_int   ('visit',   time());
      $query->set_string('ip_hash', $_ip_hash);
      $this->db->_query($query->sql(), NULL) or die('VisitorDB:_update_time');
      trace('Leave');
    }


    function _is_bot($_host) {
      return preg_match('/slurp|googlebot|msnbot|crawl/', $_host) != 0;
    }


    function _insert_entry($_ip_hash, $_count) {
      $sql  = "INSERT INTO {t_visitor}";
      $sql .= " (ip_hash, counter, visit)";
      $sql .= " VALUES ({ip_hash}, {counter}, {visit})";
      $sql .= " ON DUPLICATE KEY UPDATE counter={counter}, visit={visit}";
      $query = new FreechSqlQuery($sql);
      $query->set_string('ip_hash', $_ip_hash);
      $query->set_string('counter', $_count);
      $query->set_int   ('visit',   time());
      $this->db->_query($query->sql(), NULL) or die('VisitorDB:_insert_entry');
    }


    /* Delete old and unneeded entries from the table. */
    function _flush() {
      // IDX: visitor:visit
      $sql  = "DELETE FROM {t_visitor}";
      $sql .= " WHERE visit < {end}";
      $query = new FreechSqlQuery($sql);
      $query->set_int('end', time() - 60 * 60 * 24 * 30);
      $this->db->_query($query->sql(), NULL);
    }


    function count() {
      trace('Enter');
      // If the current user was here in the last 10 minutes, just 
      // update his timestamp and return.
      $ip      = getenv('REMOTE_ADDR');
      $ip_hash = $this->_ip_hash($ip);
      if ($this->_was_here($ip_hash, time() - 60 * 10))
        return $this->_update_time($ip_hash);

      // Else, check if it is a likely bot.
      $host = gethostbyaddr($ip);
      if ($this->_is_bot($host))
        return;

      // Ending up here we have a new visitor. Save it.
      // Note that this needs to work with both, IPv4 and IPv6.
      $count = $this->get_n_visitors();
      $this->_insert_entry($ip_hash, $count + 1);

      // To limit the number of rows, delete old entries.
      $this->_flush();
    }


    /* Returns the number of visitors. */
    function get_n_visitors($_since = 0) {
      trace('Enter');
      if ($_since == 0) {
        // IDX: visitor:counter
        $query = new FreechSqlQuery("SELECT MAX(counter) FROM {t_visitor}");
      }
      else {
        // IDX: visitor:visit
        $sql   = "SELECT COUNT(*) FROM {t_visitor}";
        $sql  .= " WHERE visit > {start}";
        $query = new FreechSqlQuery($sql);
        $query->set_int('start', $_since);
      }
      $n = $this->db->GetOne($query->sql());
      trace('Leave');
      if (!$n)
        return 0;
      return $n;
    }
  }
?>
