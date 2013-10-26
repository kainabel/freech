<?php
/*
  Freech.
  Copyright (C) 2003-2008 Samuel Abels

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

// unused function
function util_get_db_vars_from_config() {
  $arr = array();
  $dbn = cfg('db_dbn', NULL);
  $file = __FILE__; // message marker

  $line = __LINE__;
  $line++;
  $pattern = '~(\w+)://([^:]+):([^@]+)@([^/]+)/(.+)~';
  if (!preg_match($pattern, $dbn, $tmp)) {
    $arr['help'] = "Doo, I need some assistance! My RegEx pattern in file: '"
    . $file . "' line: " . $line . " won't match on string from var \$cfg['db_dbn'].";
    return $arr;
  }
  list( , // unused var
    $arr['db_type'],
    $arr['db_user'],
    $arr['db_pass'],
    $arr['db_host'],
    $arr['db_name']) = $tmp;
  $arr['db_prefix'] = cfg('db_tablebase', NULL);
  return $arr;
}

function util_check_php_function_exists($_name) {
  $caption = sprintf('Checking support for "%s".', $_name);
  if (function_exists($_name))
    return new Result($caption, TRUE);
  return new Result($caption, FALSE, 'The function does not exists.');
}

function util_check_db_connection($host, $user, $pass, $name, $create) {
  $caption = 'Trying to connect to the database.';
  $db = ADONewConnection(ADODB_DRIVER);
  if (!$create) {
    $db->Connect($host, $user, $pass, $name);
    if ($db->ErrorNo() != 0)
      return new Result($caption, FALSE, $db->ErrorMsg());
  } else {
    $db->Connect($host, $user, $pass, '');
    if ($_POST['adodb_debug'])
      $db->debug = TRUE;
    if ($db->ErrorNo() != 0)
      return new Result($caption, FALSE, $db->ErrorMsg());
    $res = $db->execute('CREATE DATABASE IF NOT EXISTS `' . $name . '`');
    if (!$res)
      return new Result($caption, FALSE,
        "No permission to create databases. " . $db->ErrorMsg());
  }
}

function util_check_db_supports_constraints($_dbn) {

  $caption = 'Checking whether database supports constraints.';
  $db = ADONewConnection($_dbn);
  if ($_POST['adodb_debug']) {
    $db->debug = TRUE;
  }
  if (!$db)
    return new Result($caption, FALSE, 'Database connection failed.');
  $res = $db->execute('SHOW variables LIKE "%innodb%"');
  if (!$res)
    return new Result($caption, FALSE, $db->ErrorMsg());
  $supported = $res->FetchRow();
  if (!$supported)
    return new Result($caption, FALSE, 'Constraints not supported.');
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
    $sql.= ' ' . trim($line);
    if (strpos($line, ';') !== FALSE)
      break;
  }

  // Strip the semicolon and everything after it.
  return trim(substr($sql, 0, strpos($sql, ';')));
}

function util_execute_sql($_dbn, $_sql) {

  // Find the SQL command type.
  if (preg_match('/^ *CREATE TABLE[^\(]* +(\S+) +\(/', $_sql, $matches))
    $caption = 'Creating database table ' . $matches[1] . '.';
  elseif (preg_match('/^ *ALTER TABLE (\S+)/', $_sql, $matches))
    $caption = 'Altering database table ' . $matches[1] . '.';
  elseif (preg_match('/^ *INSERT INTO (\S+)/', $_sql, $matches))
    $caption = 'Inserting row into database table ' . $matches[1] . '.';
  else $caption = 'Executing SQL command.';

  // Connect to the database.
  $db = ADONewConnection($_dbn);
  if (!$db)
    return new Result($caption, FALSE, 'Database connection failed.');
  if ($_POST['adodb_debug'])
    $db->debug = TRUE;
  $db->StartTrans();

  // Run.
  $res = $db->execute($_sql);
  $db->CompleteTrans();
  $err = $db->ErrorMsg();
  if ($res)
    return new Result($caption, TRUE);
  elseif (strstr($err, 'Duplicate'))
    return new Result($caption, TRUE, 'Warning: ' . $err);
  elseif (strstr($err, 'errno: 121') && strstr($_sql, 'ALTER TABLE'))
    return new Result($caption, TRUE, 'Warning: ' . $err);
  else
    return new Result($caption, FALSE, 'Request failed: ' . $err . $_sql);
}

function util_get_attribute($_dbn, $_name, $_default = NULL) {

  if (empty($db_base)) {
    $db_base = cfg('db_tablebase');
  }

  $caption = 'Get version number of the Freech installation';

  // Connect to the database.
  $db = ADONewConnection($_dbn);
  if (!$db) return new Result($caption, FALSE,
    'Database connection failed. The table is possibly absent. '
    . 'Check the installation by hand.');
  if ($_POST['adodb_debug'])
    $db->debug = TRUE;

  // Insert or update.
  $sql = 'SELECT value FROM ' . $db_base . 'info WHERE name={name}';
  $query = new FreechSqlQuery($sql);
  $query->set_string('name', $_name);

  // Run,.
  $res = $db->execute($query->sql());
  if (!$res)
    return $_default;
  $row = $res->FetchObj();
  return $row->value;
}

function util_store_user(&$_user, &$_obj) {

  $caption = 'Saving admin account data';
  $db_base = $_obj->state->get('db_base');
  $db_dbn  = $_obj->state->get('dbn');
  $table   = $db_base . 'user';
  if (empty($db_base))
    return new Result($caption, FALSE, 'Table prefix was not set.');

  // Connect to the database.
  $db = ADONewConnection($db_dbn);
  if (!$db)
    return new Result($caption, FALSE, 'Database connection failed.');
  if ($_POST['adodb_debug'])
    $db->debug = TRUE;
  $sql = "SELECT * FROM {$table} WHERE id = -1";
  $rs = $db->Execute($sql);
  if (!$rs)
    return new Result($caption, FALSE, 'Request failed: ' . $db->ErrorMsg());

  $record = array();
  $record = $_user->fields;
  $record['group_id'] = 1;
  $record['soundexname'] = $_user->get_soundexed_name();
  $insertSQL = $db->GetInsertSQL($rs, $record);
  $res = $db->Execute($insertSQL); # Insert the record into the database

  if (!$res)
    return new Result($caption, FALSE, 'Request failed: ' . $db->ErrorMsg());
  return new Result($caption, TRUE);
}

function util_store_version(&$_obj) {

  $caption = 'Saving Freech version number';
  $db_base = $_obj->state->get('db_base');
  $db_dbn  = $_obj->state->get('dbn');
  $table   = $db_base . 'info';

  if (empty($db_base))
    return new Result($caption, FALSE, 'Table prefix was not set.');

  // Connect to the database.
  $db = ADONewConnection($db_dbn);
  if (!$db)
    return new Result($caption, FALSE, 'Database connection failed.');
  if ($_POST['adodb_debug'])
    $db->debug = TRUE;
  $record = array();
  $record['name']  = 'version';
  $record['value'] = FREECH_VERSION;

  // Run,.
  $res = $db->Replace($table, $record, 'name', $autoquote = TRUE);
  if (!$res)
    return new Result($caption, FALSE, 'Request failed: ' . $db->ErrorMsg());
  return new Result($caption, TRUE);
}

function util_write_config($_filename, $_config) {

  $caption = 'Writing configuration file.';
  if (!$fp = fopen($_filename, 'w'))
    return new Result($caption, FALSE, 'Failed to open file.');
  fwrite($fp, "<?php\n/*\n");
  fwrite($fp, "look for more options in ../default_config.inc.php\n*/\n\n");
  foreach ($_config as $key => $value) {
    if (is_bool($value)) {
      fwrite($fp, "\$cfg['$key'] = (bool) " . intval($value) . ";\n");
    } else {
      fwrite($fp, "\$cfg['$key'] = '$value';\n");
    }
  }
  if (isset($_POST['debug_ignore']) && $_POST['debug_ignore'])
    fwrite($fp, "\n// Do NOT use on productive systems!\n");
    fwrite($fp, "\$cfg['ignore_installer_dir'] = (bool) 1;\n");

  fwrite($fp, "\n?>\n");
  return new Result($caption, TRUE);
}

function util_get_random_string($length = '') {
  $code = md5(uniqid(rand(), TRUE));
  if ($length) return substr($code, 0, $length);
  return $code;
}
?>
