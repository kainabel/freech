<?php
  /*
  Tefinch.
  Copyright (C) 2005 Robert Weidlich, <tefinch xenim de>

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
  if (preg_match("/^[a-z0-9_]+$/i", $cfg[lang]))
    include_once "language/$cfg[lang].inc.php";
  include_once "string.inc.php";
  include_once "message.inc.php";
  
  function message_submit($_db,
                          $_forumid,
                          $_parentid,
                          $_name,
                          $_subject,
                          $_message) {
    global $cfg;
    global $lang;
    
    $err = message_check($_name, $_subject, $_message);
    if ($err)
      return $err;
    
    // Insert the message into db.
    $newmsg_id = $_db->insert_entry($_forumid,
                                    $_parentid,
                                    $_name,
                                    $_subject,
                                    $_message);
    
    return $newmsg_id;
  }
?>
