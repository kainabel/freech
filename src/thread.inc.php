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
  include_once 'config.inc.php';
  include_once 'string.inc.php';
  include_once 'httpquery.inc.php';
  include_once "mysql_$cfg[db_backend].inc.php";
  
  // Draws the indent according to the given array.
  function _thread_print_indent($_indents) {
    $i = 0;
    while ($_indents[$i]) {
      switch ($_indents[$i]) {
      case INDENT_DRAW_DASH:
        print("<img src='img/l.png' width=20 height=23 alt='|' />");
        break;
      
      case INDENT_DRAW_SPACE:
        print("<img src='img/null.png' width=20 height=23 alt='&nbsp;' />");
        break;
      
      default:
        break;
      }
      $i++;
    }
  }
  
  
  // Draws the appropriate tree image.
  function _thread_print_treeimage($_row, $_folding, $_queryvars) {
    global $cfg;
    $holdvars = array_merge($cfg[urlvars],
                            array('forum_id', 'hs', 'msg_id', 'read'));
    
    switch ($_row->leaftype) {
    case PARENT_WITHOUT_CHILDREN:
      print("<img src='img/o.png' width=9 height=21 alt='' />");
      break;
      
    case PARENT_WITH_CHILDREN_UNFOLDED:
      $swap = $_row->id;
      if ($_queryvars[read] == 1)
        $query[showthread] = -1;
      else
        $query[swap] = $swap ? $swap : '';
      print("<a href='?" . build_url($_queryvars, $holdvars, $query) . "'>");
      print("<img src='img/m.png' border=0 width=9 height=21 alt='0' />");
      print("</a>");
      break;
      
    case PARENT_WITH_CHILDREN_FOLDED:
      $query = "";
      $swap = $_row->id;
      $query[swap] = $swap ? $swap : '';
      print("<a href='?" . build_url($_queryvars, $holdvars, $query) . "'>");
      print("<img src='img/p.png' border=0 width=9 height=21 alt='0' />");
      print("</a>");
      break;
      
    case BRANCHBOTTOM_CHILD_WITHOUT_CHILDREN:
    case BRANCHBOTTOM_CHILD_WITH_CHILDREN:
      print("<img src='img/e.png' width=20 height=23 alt='`-' />");
      break;
      
    case CHILD_WITHOUT_CHILDREN:
    case CHILD_WITH_CHILDREN:
      print("<img src='img/x.png' width=20 height=23 alt='|-' />");
      break;
    }
  }
  
  
  function _thread_print_row($_row, $_indents, $_data) {
    global $cfg;
    global $lang;
    
    $_row->name  = string_escape($_row->name);
    $_row->title = string_escape($_row->title);
    list($folding, $queryvars) = $_data;
    $holdvars = array_merge($cfg[urlvars],
                            array('forum_id', 'hs'));
    
    // Open a new row.
    print("<tr valign='middle' bgcolor='#ffffff'>\n");
    
    print("<td align='center' width=8>");
    print("<img src='img/null.png' width=8 height=23 alt='' />");
    print("</td>\n");
    
    // Inner table.
    print("<td align='left'>\n");
    print("<table border=0 cellspacing=0 cellpadding=0>");
    print("<tr>\n");
    // Draw the tree components.
    print("<td align='left'>");
    _thread_print_indent($_indents);
    _thread_print_treeimage($_row, $folding, $queryvars);
    print("</td>\n");
    
    // Title.
    $query = "";
    $query[msg_id] = $_row->id;
    $query[read] = 1;
    $_row->title = str_replace(" ", "&nbsp;", $_row->title);
    print("<td align='left'><font size='-1'>&nbsp;");
    //print("$_row->id, $_row->path ");
    if (!$_row->active)
      print($lang[blockedtitle]."&nbsp;");
    elseif ($_row->id === $queryvars[msg_id] && $queryvars[read] === '1')
      print("<font color='green'>$_row->title</font>");
    else
      print("<a href='?" . build_url($queryvars, $holdvars, $query) . "'>"
          . "$_row->title</a>&nbsp;");

    if ($_row->leaftype == PARENT_WITH_CHILDREN_FOLDED) {
      print("($_row->n_children)");
    }
    print("</font>"
        . "</td>\n");
    
    // End inner table.
    print("</tr>\n");
    print("</table>\n");
    print("</td>\n");
    
    // Some space.
    //print("<td>&nbsp;</td>");
    print("<td><img src='img/null.png' alt='' width=4 height=23 /></td>\n");
    
    // User name.
    print("<td align='left'><font size='-1'>");
    if (!$_row->active)
      print("------");
    elseif ($_row->id === $_GET[msg_id] && $queryvars[read] === '1')
      print("<font color='green'>$_row->name</font>");
    else
      print $_row->name;
    print("&nbsp;</font></td>\n");
    
    // Date.
    if ($_row->id === $_GET[msg_id] && $queryvars[read] === '1') 
      $color = 'green';
    elseif ((time() - $_row->unixtime) < 86400)
      $color = 'red';
    else $color = 'black';
    print("<td align='right' nowrap><font size='-1' color='$color'>"
        . "$_row->time"
        . "</font></td>\n");
    
    print("<td align='center' width=8>");
    print("<img src='img/null.png' width=8 height=23 alt='' />");
    print("</td>\n");
    
    print("</tr>\n");
  }
  
  
  function thread_print($_db, $_forum_id, $_msg_id, $_offset, $_folding) {
    global $cfg;
    global $lang;
    print("<table border='0' cellpadding='0' cellspacing='0' width='100%'>");
    if ($_msg_id == 0)
      $n_rows = $_db->foreach_child($_forum_id,
                                    $_msg_id,
                                    $_offset,
                                    $cfg[tpp],
                                    $_folding,
                                    _thread_print_row,
                                    array($_folding, $_GET));
    else
      $n_rows = $_db->foreach_child_in_thread($_forum_id,
                                              $_msg_id,
                                              $_offset,
                                              $cfg[tpp],
                                              $_folding,
                                              _thread_print_row,
                                              array($_folding, $_GET));
    if ($n_rows == 0) {
      print("<tr><td height='4'></td></tr>");
      print("<tr><td align='center'><i>$lang[noentries]</i></td></tr>");
      print("<tr><td height='4'></td></tr>");
    }
    print("</table>");
  }
?>
