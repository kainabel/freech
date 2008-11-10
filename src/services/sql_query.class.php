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
 * @version $Revision: 1.3 $
 * @package openrat.services
 */
class TefinchSqlQuery extends SqlQuery {
  // Constructor.
  function TefinchSqlQuery($query = '')
  {
    parent::SqlQuery($query);
    $tables = array (
      t_group            => cfg("db_tablebase") . 'group',
      t_user             => cfg("db_tablebase") . 'user',
      t_permission       => cfg("db_tablebase") . 'permission',
      t_group_permission => cfg("db_tablebase") . 'group_permission',
      t_group_user       => cfg("db_tablebase") . 'group_user',
      t_forum            => cfg("db_tablebase") . 'forum',
      t_message          => cfg("db_tablebase") . 'message'
    );
    $this->set_table_names($tables);
  }
}
?>
