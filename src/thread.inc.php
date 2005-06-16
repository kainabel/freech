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
  include_once 'httpquery.inc.php';
  include_once 'mysql_nested.inc.php';
  
  // Draws the indent according to the given array.
  function _print_indent($_indents) {
    $i = 0;
    while ($_indents[$i]) {
      switch ($_indents[$i]) {
      case INDENT_DRAW_DASH:
        print("<img src='img/l.png' width=20 height=23 alt='' />");
        break;
      
      case INDENT_DRAW_SPACE:
        print("<img src='img/null.png' width=20 height=23 alt='' />");
        break;
      
      default:
        break;
      }
      $i++;
    }
  }
  
  
  // Draws the appropriate tree image.
  function _print_treeimage($_row, $_folding, $_queryvars) {
    global $cfg;
    $holdvars = array_merge($cfg[urlvars],
                            array('forum_id', 'fold', 'swap', 'hs'));
    
    switch ($_row->leaftype) {
    case PARENT_WITHOUT_CHILDREN:
      print("<img src='img/o.png' width=9 height=21 alt='' />");
      break;
      
    case PARENT_WITH_CHILDREN_UNFOLDED:
      $query = "";
      $swap = $_folding->get_string_swap($_row->id);
      $query[swap] = $swap ? $swap : '';
      $query[fold] = $_folding->get_default();
      print("<a href='?" . build_url($_queryvars, $holdvars, $query) . "'>");
      print("<img src='img/m.png' border=0 width=9 height=21 alt='' />");
      print("</a>");
      break;
      
    case PARENT_WITH_CHILDREN_FOLDED:
      $query = "";
      $swap = $_folding->get_string_swap($_row->id);
      $query[swap] = $swap ? $swap : '';
      $query[fold] = $_folding->get_default();
      print("<a href='?" . build_url($_queryvars, $holdvars, $query) . "'>");
      print("<img src='img/p.png' border=0 width=9 height=21 alt='' />");
      print("</a>");
      break;
      
    case BRANCHBOTTOM_CHILD_WITHOUT_CHILDREN:
    case BRANCHBOTTOM_CHILD_WITH_CHILDREN:
      print("<img src='img/e.png' width=20 height=23 alt='' />");
      break;
      
    case CHILD_WITHOUT_CHILDREN:
    case CHILD_WITH_CHILDREN:
      print("<img src='img/x.png' width=20 height=23 alt='' />");
      break;
    }
  }
  
  
  function print_row($_row, $_indents, $_data) {
    global $cfg;
    
    list($folding, $queryvars) = $_data;
    $holdvars = array_merge($cfg[urlvars],
                            array('forum_id', 'fold', 'swap', 'hs'));
    
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
    _print_indent($_indents);
    _print_treeimage($_row, $folding, $queryvars);
    print("</td>\n");
    
    // Title.
    $query = "";
    $query[msg_id] = $_row->id;
    $query[read] = 1;
    print("<td align='left'>"
        . "<font size='-1'>"
        . "&nbsp;<a href='?" . build_url($queryvars, $holdvars, $query) . "'>"
        . "$_row->title</a>&nbsp;");
    if ($_row->leaftype == PARENT_WITH_CHILDREN_FOLDED) {
      print("(".( ceil (($_row->rgt - $_row->lft)/2)-1 ) .")");
    }
    print("</font>"
        . "</td>\n");
    
    // End inner table.
    print("</tr>\n");
    print("</table>\n");
    print("</td>\n");
    
    // Some space.
    print("<td>&nbsp;</td>");
    print("<td><img src='img/null.png' alt='' width=4 height=23 /></td>\n");
    
    // User name.
    print("<td align='left'><font size='-1'>$_row->name&nbsp;</font></td>\n");
    
    // Date.
    print("<td align='right' nowrap><font size='-1' color='red'>"
        . "$_row->time"
        . "</font></td>\n");
    
    print("<td align='center' width=8>");
    print("<img src='img/null.png' width=8 height=23 alt='' />");
    print("</td>\n");
    
    print("</tr>\n");
  }
  
  function print_row_simple($_row, $_data) {
    global $cfg;
    
    list($folding, $queryvars) = $_data;
    $holdvars = array_merge($cfg[urlvars],
                            array('forum_id', 'fold', 'swap', 'hs'));
    
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
        . "&nbsp;<a href='?" . build_url($queryvars, $holdvars, $query) . "'>"
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
    print("<td align='right' nowrap><font size='-1' color='red'>"
        . "$_row->time"
        . "</font></td>\n");
    
    print("<td align='center' width=8>");
    print("<img src='img/null.png' width=8 height=23 alt='' />");
    print("</td>\n");
    
    print("</tr>\n");
  } 
?>
