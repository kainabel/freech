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
$table_keys = array (
  '{' . 't_group'            . '}',
  '{' . 't_user'             . '}',
  '{' . 't_permission'       . '}',
  '{' . 't_forum'            . '}',
  '{' . 't_thread'           . '}',
  '{' . 't_posting'          . '}',
  '{' . 't_visitor'          . '}',
  '{' . 't_modlog'           . '}',
  '{' . 't_modlog_attribute' . '}',
  '{' . 't_poll_option'      . '}',
  '{' . 't_poll_vote'        . '}',
  '{' . 't_user_rating'      . '}'
);
$table_names = array (
  cfg('db_tablebase') . 'group',
  cfg('db_tablebase') . 'user',
  cfg('db_tablebase') . 'permission',
  cfg('db_tablebase') . 'forum',
  cfg('db_tablebase') . 'thread',
  cfg('db_tablebase') . 'posting',
  cfg('db_tablebase') . 'visitor',
  cfg('db_tablebase') . 'modlog',
  cfg('db_tablebase') . 'modlog_attribute',
  cfg('db_tablebase') . 'poll_option',
  cfg('db_tablebase') . 'poll_vote',
  cfg('db_tablebase') . 'user_rating'
);

/**
 * Representation of an SQL statement, including methods to bind variables
 * into the query.
 */
class FreechSqlQuery {
  var $query = '';
  var $data  = array();

  // Constructor.
  function FreechSqlQuery($query = '') {
    $this->query = $query;
  }


  // Overwrites the SQL query.
  function set_sql($query = '') {
    $this->query = $query;
  }


  function set_var($name, $value) {
    if   ( is_string($value) )
         $this->set_string( $name,$value );

    if   ( is_null($value) )
         $this->set_null( $name );

    if   ( is_bool($value) )
         $this->set_bool( $name,$value );

    if   ( is_int($value) )
         $this->set_int( $name,$value );
  }


  function set_int($name, $value) {
    $this->data[$name] = (int)$value;
  }


  function set_string($name, $value) {
    $value = addslashes($value);
    $this->data[$name] = '\''.str_replace('}', '\}', $value).'\'';
  }


  function set_hex($name, $value) {
    $value = addslashes($value);
    $this->data[$name] = '0x'.str_replace('}', '\}', $value);
  }


  function set_bool($name, $value) {
    if ($value)
      $this->set_int($name, 1);
    else
      $this->set_int($name, 0);
  }


  function set_null($name) {
    $this->data[$name] = 'NULL';
  }


  function &sql() {
    global $table_keys, $table_names;
    $query = str_replace($table_keys, $table_names, $this->query);
    foreach ($this->data as $name => $value)
      $query = str_replace('{'.$name.'}', $value, $query);
    $query = str_replace('\}', '}', $query);
    return $query;
  }
}
?>
