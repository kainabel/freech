<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels, <http://debain.org>
  
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
  include_once 'config.inc.php';
  
  function &cfg($_key, $_default = NULL) {
    global $cfg;
    if ($_key && isset($cfg[$_key]))
      return $cfg[$_key];
    die("cfg(): Invalid configuration key '$_key'.\n");
  }


  function &cfg_is($_key, $_compare) {
    global $cfg;
    return $cfg[$_key] == $_compare;
  }
?>
