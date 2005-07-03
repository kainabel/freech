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
   *       $_has_thread      Set TRUE, unless the entry is a leaf parent.
   *       $_queryvars       Variables that are appended to every link.
   */
  function message_index_print($_msg_id,
                               $_prev_thread_id,
                               $_next_thread_id,
                               $_prev_entry_id,
                               $_next_entry_id,
                               $_has_thread,
                               $_queryvars) {
    global $lang;
    global $cfg;
    
    $holdvars   = array_merge($cfg[urlvars],
                              array('forum_id', 'hs'));
    
    // Print "index".
    print("<table width='100%' cellspacing='0' cellpadding='3' border='0'"
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
    if ($_has_thread) {
      $query = "";
      $query[read] = 1;
      print ("&nbsp;<a href='?");
      if ($_COOKIE[thread] === 'hide') {
        $query[showthread] = '1';
        print build_url($_queryvars,array_merge($holdvars,array('msg_id')),$query)
              ."'><font color='#FFFFFF'>$lang[showthread]</font>";
      } else {
        $query[showthread] = '-1';
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
?>
