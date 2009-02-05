<?php
#
#  This file was taken from: DaCMS Content Management System
#  Copyright (C) 2002 Jan Dankert, jandankert@jandankert.de
#
#  This program is free software; you can redistribute it and/or
#  modify it under the terms of the GNU General Public License
#  as published by the Free Software Foundation; either version 2
#  of the License, or (at your option) any later version.
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this program; if not, write to the Free Software
#  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#


/**
 * Representation of an SQL statement, including methods to bind variables
 * into the query.
 */
class SqlQuery
{
  var $query       = '';
  var $data        = array();
  var $table_names = array();
  
  // Constructor.
  function SqlQuery($query = '')
  {
    $this->query = $query;
  }
  
  
  // Overwrites the SQL query.
  function set_sql($query = '')
  {
    $this->query = $query;
    foreach ($this->table_names as $t=>$name) {
      $this->query = str_replace( '{'.$t.'}', $name, $this->query);
    }
  }

  
  function set_table_names(&$tables)
  {
    $this->table_names = $tables;
    foreach ($this->table_names as $t=>$name) {
      $this->query = str_replace( '{'.$t.'}', $name, $this->query);
    }
  }
  
  
  function set_var($name, $value)
  {
    if   ( is_string($value) )
         $this->set_string( $name,$value );
  
    if   ( is_null($value) )
         $this->set_null( $name );
    
    if   ( is_bool($value) )
         $this->set_bool( $name,$value );

    if   ( is_int($value) )
         $this->set_int( $name,$value );
  }
  
  
  function set_int($name, $value)
  {
    $this->data[$name] = array('type'=>'int', 'value'=>(int)$value);
  }
  
  
  function set_string($name, $value)
  {
    $value = addslashes($value);
    $value = "'".$value."'";
    $this->data[$name] = array('type'=>'string', 'value'=>$value);
  }
  
  
  function set_hex($name, $value)
  {
    $value = addslashes($value);
    $value = '0x'.$value;
    $this->data[$name] = array('type'=>'hex', 'value'=>$value);
  }
  
  
  function set_bool($name, $value)
  {
    if ($value)
      $this->set_int($name, 1);
    else
      $this->set_int($name, 0);
  }
  
  
  function set_null($name)
  {
    $this->data[$name] = array('type'=>'null', 'value'=>'NULL');
  }
  
  
  function &sql()
  {
    return $this->get_sql();
  }
  
  
  function &get_sql()
  {
    $query = $this->query;
    foreach ($this->data as $name=>$data) {
      $value = str_replace('}', '\}', $data['value']);
      $query = str_replace( '{'.$name.'}', $value, $query);
    }
    $query = str_replace('\}', '}', $query);
    return $query;
  }
}
?>
