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
function util_check_php_function_exists($_name) {
  $caption = sprintf('Checking support for "%s".', $_name);
  if (function_exists($_name))
    return new Result($caption, TRUE);
  return new Result($caption, FALSE, 'The function does not exists.');
}

function util_check_db_connection($_dbn) {
  $caption = 'Trying to connect to the database.';
  $db      = ADONewConnection($_dbn);
  if ($db)
    return new Result($caption, TRUE);
  return new Result($caption, FALSE, 'Connection failed.');
}

function util_check_db_supports_constraints($_dbn) {
  $caption = 'Checking whether database supports constraints.';
  $db      = ADONewConnection($_dbn);
  if (!$db)
    return new Result($caption, FALSE, 'Database connection failed.');
  $res = $db->execute('SHOW variables LIKE "have_innodb"');
  if (!$res)
    return new Result($caption, FALSE, 'Request failed.');
  $supported = $res->FetchRow();
  if (!$supported)
    return new Result($caption, FALSE, 'No supported.');
  return new Result($caption, TRUE);
}
?>
