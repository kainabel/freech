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
  include_once "language/$cfg[lang].inc.php";
  
  /* Prints the indexbar that is shown above a single entry out.
   * Args: $_prev_thread_id  The id of the previous thread, if any.
   *       $_next_thread_id  The id of the next thread, if any.
   *       $_prev_entry_id   The id of the previous entry, if any.
   *       $_next_entry_id   The id of the next entry, if any.
   *       $_has_child       Set TRUE, unless the entry is a leaf parent.
   *       $_queryvars       Variables that are appended to every link.
   */
  function message_index_print($_msg_id,
                               $_prev_thread_id,
                               $_next_thread_id,
                               $_prev_entry_id,
                               $_next_entry_id,
                               $_has_child,
                               $_queryvars) {
    global $lang;
    global $cfg;
    
    $holdvars   = array_merge($cfg[urlvars],
                              array('forum_id', 'fold', 'swap', 'hs'));
    
    // Print "index".
    print("<table width='100%' cellspacing='0' cellpadding='5' border='0'"
        . " bgcolor='#003399'>\n");
    print("\t<tr>\n");
    print("\t\t<td align='left'>\n");
    print("\t\t<font color='#FFFFFF' size='-1'><b>\n");

    if ($_prev_entry_id > 0) {
      $query = "";
      $query[msg_id] = $_prev_entry_id * 1;
      $query[read] = 1;
      print("<a href='?".build_url($_queryvars,$holdvars,$query)."'>"
           ."<font color='#FFFFFF'>&lt;&lt;</font></a>");
    } else 
    print("&lt;&lt;");
    print("&#032;$lang[entry]&#032;");
    if ($_next_entry_id > 0) {
      $query = "";
      $query[msg_id] = $_next_entry_id * 1;
      $query[read] = 1;
      print("<a href='?".build_url($_queryvars,$holdvars,$query)."'>"
           ."<font color='#FFFFFF'>&gt;&gt;</font></a>");
    } else
      print("&gt;&gt;");
    
    print("&nbsp;");
    if ($_next_thread_id > 0) {
      $query = "";
      $query[msg_id] = $_next_thread_id * 1;
      $query[read] = 1;
      print("<a href='?".build_url($_queryvars,$holdvars,$query)."'>"
           ."<font color='#FFFFFF'>&lt;&lt;</font></a>");
    } else {
      print("&lt;&lt;");
    }
    print("&#032;$lang[thread]&#032;");
    if ($_prev_thread_id > 0) {
      $query = "";
      $query[msg_id] = $_prev_thread_id * 1;
      $query[read] = 1;
      print("<a href='?".build_url($_queryvars,$holdvars,$query)."'>"
           ."<font color='#FFFFFF'>&gt;&gt;</font></a>");
    } else {
    print("&gt;&gt;");
    }
    $query = "";
    $query[write] = 1;
    print("&nbsp;&nbsp;<a href='?"
          . build_url($_queryvars,array_merge($holdvars,array('msg_id')),$query)
          . "'><font color='#FFFFFF'>$lang[writeanswer]</font></a>\n");
    print("&nbsp;&nbsp;<a href='?"
          . build_url($_queryvars, $holdvars, $query)
          . "'><font color='#FFFFFF'>$lang[writemessage]</font></a>\n");
    if ($_has_child) {
      $query = "";
      $query[read] = 1;
      print ("&nbsp;<a href='?");
      if ($_queryvars['thread'] === "0") {
        $query[thread] = '1';
        print build_url($_queryvars,array_merge($holdvars,array('msg_id')),$query)
              ."'><font color='#FFFFFF'>$lang[showthread]</font>";
      } else {
        $query[thread] = '0';
        print build_url($_queryvars,array_merge($holdvars,array('msg_id')),$query)
              ."'><font color='#FFFFFF'>$lang[hidethread]</font>";
      }
      print ("</a>");
    }
     
    print("</b></font>\n");
    print("\t\t</td>\n");
    print("\t</tr>\n");
    print("</table>\n");
  } 
  
  
  // FIXME: Move elsewhere.
  function heading_print($_queryvars, $_title) {
    global $lang;
    global $cfg;
    
    $holdvars   = array_merge($cfg[urlvars],
                              array('forum_id', 'fold', 'swap', 'hs'));
    
    // Print "index".
    print("<table width='100%' cellspacing='0' cellpadding='5' border='0'>\n");
    print("\t<tr>\n");
    print("\t\t<td align='left'>\n");
    print("\t\t<font size='-1'>\n");
    
    $query = "";
    $query['list'] = 1;
    if ($_GET['read'] === '1' || $_GET['llist']) print("&nbsp;&nbsp;<a href='?"
          . build_url($_queryvars, $holdvars, $query) . "'>Forum</a>"
          . "&nbsp;&nbsp;&gt;&nbsp;&nbsp;$_title");
    else print "&nbsp;&nbsp;Forum";
    
    print("</font>\n");
    print("\t\t</td>\n");
    print("\t\t<td align='right'>\n");
    print("\t\t<font size='-1'>\n");
    
    $query = "";
    $query['llist'] = 1;
    print("&nbsp;&nbsp;<a href='?"
          . build_url($_queryvars, $holdvars, $query) . "'>$lang[entryindex]</a>\n");
    print("</font>\n");
    print("\t\t</td>\n");        
    print("\t</tr>\n");
    print("</table>\n");
  } 
?>
