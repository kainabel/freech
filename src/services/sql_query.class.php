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
 *
 * @author $Author: knipknap $
 * @version $Revision: 1.1 $
 * @package openrat.services
 */
class SqlQuery
{
  var $query = '';
  var $data  = Array();
  
  // Constructor.
  function SqlQuery($query = '')
  {
    $this->query = $query;
    $this->data  = array();
    
    foreach( table_names() as $t=>$name )
    {
      $this->query = str_replace( '{'.$t.'}',$name,$this->query );
    }
  }
  
  
  // Overwrites the SQL query.
  function set_query($query = '')
  {
    $this->query = $query;
    
    foreach( table_names() as $t=>$name )
    {
      $this->query = str_replace( '{'.$t.'}',$name,$this->query );
    }
    
    foreach( $this->data as $name=>$data )
    {
      if ( $data['type']=='string' ) $this->setString($name,$data['value'] );
      if ( $data['type']=='int'    ) $this->setInt   ($name,$data['value'] );
      if ( $data['type']=='null'   ) $this->setNull  ($name                );
    }
  }
  
  
  function set_var($name, $value)
  {
    if   ( is_string($value) )
         $this->set_string( $name,$value );
  
    if   ( is_null($value) )
         $this->set_null( $name );
    
    if   ( is_int($value) )
         $this->set_int( $name,$value );
  }
  
  
  function set_int($name, $value)
  {
    $this->data[ $name ] = array( 'type'=>'int','value'=>$value );
    $this->query = str_replace( '{'.$name.'}',intval($value),$this->query );
  }
  
  
  function set_string($name, $value)
  {
    $this->data[ $name ] = array( 'type'=>'string','value'=>$value );
    $value = addslashes($value);
    $value = "'".$value."'";
    $this->query = str_replace( '{'.$name.'}',$value,$this->query );
  }
  
  
  function set_boolean($name, $value)
  {
    if        ( $value )
         $this->set_int( $name,1 );
    else        $this->setInt( $name,0 );
  }
  
  
  function set_null($name)
  {
    $this->data[ $name ] = array( 'type'=>'null' );
    $this->query = str_replace( '{'.$name.'}','NULL',$this->query );
  }
  
  
  function sql()
  {
    return $this->get_sql();
  }
  
  
  function &get_sql()
  {
    return $this->query;
  }
}
?>
