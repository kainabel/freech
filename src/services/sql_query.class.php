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
class FreechSqlQuery extends SqlQuery {
  // Constructor.
  function FreechSqlQuery($query = '')
  {
    parent::SqlQuery($query);
    $tables = array (
      t_group       => cfg("db_tablebase") . 'group',
      t_user        => cfg("db_tablebase") . 'user',
      t_permission  => cfg("db_tablebase") . 'permission',
      t_forum       => cfg("db_tablebase") . 'forum',
      t_message     => cfg("db_tablebase") . 'message',
      t_visitor     => cfg("db_tablebase") . 'visitor',
      t_poll_option => cfg("db_tablebase") . 'poll_option',
      t_poll_vote   => cfg("db_tablebase") . 'poll_vote'
    );
    $this->set_table_names($tables);
  }
}
?>
