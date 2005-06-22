<?php
  /*
  Tefinch.
  Copyright (C) 2003 Samuel Abels, <spam debain org>

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
  include_once 'string.inc.php';
  include_once 'httpquery.inc.php';
  include_once 'latest_index.inc.php';
  
  function latest_print_row($_row, $_queryvars) {
    global $cfg;
    $holdvars = array_merge($cfg[urlvars],
                            array('forum_id', 'hs'));
    
    $_row->name  = string_escape($_row->name);
    $_row->title = string_escape($_row->title);

    // Open a new row.
    print("<tr valign='middle' bgcolor='#ffffff'>\n");
    
    print("<td align='center' width=8>");
    print("<img src='img/null.png' width=8 height=23 alt='' />");
    print("</td>\n");
    
    // Inner table.
    print("<td align='left'>\n");
    
    // Title.
    $query = "";
    $query[msg_id] = $_row->id;
    $query[read] = 1;
    print("<td align='left'>"
        . "<font size='-1'>"
        . "&nbsp;<a href='?" . build_url($_queryvars, $holdvars, $query) . "'>"
        . "$_row->title</a>&nbsp;"
        . "</font>"
        . "</td>\n");
    
    print("</td>\n");
    
    // Some space.
    print("<td>&nbsp;</td>");
    print("<td><img src='img/null.png' alt='' width=4 height=23 /></td>\n");
    
    // User name.
    print("<td align='left'><font size='-1'>$_row->name&nbsp;</font></td>\n");
    
    // Date.
    if ((time() - $_row->unixtime) < 86400) $color = 'red';
                                     else $color = 'black';
    
    print("<td align='right' nowrap><font size='-1' color='$color'>"
        . "$_row->time"
        . "</font></td>\n");
    
    print("<td align='center' width=8>");
    print("<img src='img/null.png' width=8 height=23 alt='' />");
    print("</td>\n");
    
    print("</tr>\n");
  }
?>
