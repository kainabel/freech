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

function util_get_next_sql_command($_fp) {
  $sql = '';
  while (!feof($_fp)) {
    $line = fgets($_fp);

    // Strip comments.
    $comment_start = strpos($line, '--');
    if ($comment_start !== FALSE)
      $line = substr($line, 0, $comment_start);

    // Collect the SQL statement. Statements are terminated by a semicolon.
    $sql .= trim($line);
    if (strpos($line, ';') !== FALSE)
      break;
  }

  // Strip the semicolon and everything after it.
  return substr($sql, 0, strpos($sql, ';'));
}

function util_execute_sql($_dbn, $_sql) {
  // Find the SQL command type.
  if (preg_match('/^ *CREATE TABLE[^\(]* +(\S+) +\(/', $_sql, $matches))
    $caption = 'Creating database table ' . $matches[1] . '.';
  elseif (preg_match('/^ *ALTER TABLE (\S+)/', $_sql, $matches))
    $caption = 'Altering database table ' . $matches[1] . '.';
  elseif (preg_match('/^ *INSERT INTO (\S+)/', $_sql, $matches))
    $caption = 'Inserting row into database table ' . $matches[1] . '.';
  else
    $caption = 'Executing SQL command.';

  // Connect to the database.
  $db = ADONewConnection($_dbn);
  if (!$db)
    return new Result($caption, FALSE, 'Database connection failed.');

  // Run.
  $res = $db->execute($_sql);
  if (!$res)
    return new Result($caption, FALSE, 'Request failed: '.$db->ErrorMsg());
  return new Result($caption, TRUE);
}
?>
