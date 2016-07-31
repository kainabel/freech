<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels
  
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
  unset($cfg);
  $oldwd = getcwd();
  chdir(dirname(__FILE__));
  include_once '../default_config.inc.php';
  if (is_readable('../data/config.inc.php'))
    include_once '../data/config.inc.php';
  if (is_readable('../data/user_config.inc.php'))
    include_once '../data/user_config.inc.php';
  chdir($oldwd);

  // changing URL protocol in $cfg['site_url'] by an incoming https request
  // Note: This only works with SSL port: 443!
  $proto_cfg = explode(':', cfg('site_url'), 2);
  if ($_SERVER['SERVER_PORT'] == '443') {
      $proto_cfg[0] = 'https';
      $cfg['site_url'] = implode(':', $proto_cfg);
  }
  unset($proto_cfg);

  function cfg($_key, $_default = NULL) {
    global $cfg;
    if (!$_key)
      die("cfg(): Invalid configuration key '$_key'.\n");
    if (isset($cfg[$_key]))
      return $cfg[$_key];
    if ($_default !== NULL)
      return $_default;
    die("cfg(): No such configuration key: '$_key'.\n");
  }


  function cfg_is($_key, $_compare) {
    global $cfg;
    return $cfg[$_key] == $_compare;
  }
?>
