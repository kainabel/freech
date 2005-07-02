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
  include_once "language/$cfg[lang].inc.php";
  include_once "string.inc.php";
  
  /* Show created message 
    $_msg_id - id of the created message
  */
  function message_created($_newmsg_id, $_queryvars) {
    global $cfg;
    global $lang;
    $holdvars      = array_merge($cfg[urlvars], array('forum_id', 'hs'));
    $query         = array('list' => 1, 'forum_id' => $_queryvars['forum_id']);
    $forumurl      = build_url($_queryvars, $holdvars, $query);
    $query         = array('forum_id' => $_queryvars[forum_id],
                           'msg_id'   => $_newmsg_id,
                           'read'     => 1);
    $messageurl    = build_url($_queryvars, $holdvars, $query);
    $query         = array('forum_id' => $_queryvars[forum_id],
                           'msg_id'   => $_queryvars[msg_id],
                           'read'     => 1);
    $parenturl     = build_url($_queryvars, $holdvars, $query);
    
    // Give some status info and the usual links.
    print("<p><h2>$lang[entrysuccess]</h2><br>");
    print("<a href='?$messageurl'>$lang[backtoentry]</a><br>");
    if ($_queryvars['msg_id']) 
      print("<a href='?$parenturl'>$lang[backtoparent]</a><br>");
    print("<a href='?$forumurl'>$lang[backtoindex]</a>");  
  }
?>
