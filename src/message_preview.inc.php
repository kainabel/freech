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
  include_once "config.inc.php";
  include_once "language/$cfg[lang].inc.php";
  include_once "string.inc.php";
  include_once "message.inc.php";
  
  /* Show a preview of the message */
  function message_preview($_name,
                           $_subject,
                           $_message,
                           $_msg_id,
                           $_queryvars) {
    global $cfg;
    global $lang;
    $holdvars = array_merge($cfg[urlvars],
                            array('forum_id', 'msg_id', 'fold', 'swap', 'hs'));
    print("<p><font color='red' size='+1'>$lang[preview]</font></p>\n"
         ."<p><table border='0' cellpadding='0' cellspacing='0' width='100%'>\n"
         ."<tbody><tr>");
    message_print($_name,
                  $_subject,
                  $_message,
                  date(preg_replace("/%/","", $lang[dateformat]), time()),
                  $_queryvars);
    print("<p><form action='?".build_url($_queryvars, $holdvars,'')
        ."' method='POST'>\n"
        . "<input type='hidden' name='name' value='".string_escape($_name)."'>\n"
        . "<input type='hidden' name='subject' value='".string_escape($_subject)."'>\n"
        . "<input type='hidden' name='message' value='".string_escape($_message)."'>\n"
        . "<input type='hidden' name='msg_id' value='$_msg_id'>\n"
        . "<input type='submit' name='edit' value='$lang[change]'>\n"
        . "<input type='submit' name='send' value='$lang[send]'></p></table>\n");
  }
?>
