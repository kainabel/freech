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
  
  /* Prints the indexbar out.
   * Args: $_n_threads  The total number of threads
   *       $_offset     The offset in threads.
   *       $_tpp        Number of threads per page.
   *       $_ppi        Pages per index.
   */
  function threadindex_print($_n_threads, $_offset, $_tpp, $_ppi,
                             $_folding, $_queryvars) {
    global $lang;
    global $cfg;
    
    $holdvars   = array_merge($cfg[urlvars],
                              array('forum_id', 'fold', 'swap', 'hs'));
    
    $pages      = ceil($_n_threads / $_tpp);
    $activepage = ceil($_offset / $_tpp) + 1;
    $pageoffset = 1;
    
    if ($activepage > $_ppi / 2)
      $pageoffset = $activepage - ceil($_ppi / 2);
    if ($pageoffset + $_ppi > $pages)
      $pageoffset = $pages - $_ppi;
    if ($pageoffset < 1)
      $pageoffset = 1;
    
    // Print "index".
    print("<table width='100%' cellspacing='0' cellpadding='5' border='0'"
        . " bgcolor='#003399'>\n");
    print("\t<tr>\n");
    print("\t\t<td align='left'>\n");
    print("\t\t<font color='#FFFFFF' size='-1'><b>$lang[index]\n");
    
    // Always show a link to the first page.
    if ($pageoffset > 1) {
      $query     = "";
      $query[hs] = 0;
      print(" <a href='?"
          . build_url($_queryvars, $holdvars, $query)
          . "'>1</a>\n");
    }
    if ($pageoffset > 2)
      print(" ...\n");
    
    // Print the numbers. Print the active number using another color.
    for ($i = $pageoffset; $i <= $pageoffset + $_ppi && $i <= $pages; $i++) {
      if ($i == $activepage)
        print(" <font color='#FFFFFF'>$i</font>\n");
      else {
        $query     = "";
        $query[hs] = ($i - 1) * $_tpp;
        print(" <a href='?"
            . build_url($_queryvars, $holdvars, $query)
            . "'><font color='#FFFFFF'>$i</font></a>\n");
      }
    }
    
    // Always show a link to the last page.
    if ($pageoffset + $_ppi < $pages - 1)
      print(" ...\n");
    if ($pageoffset + $_ppi < $pages) {
      $query     = "";
      $query[hs] = ($pages - 1) * $_tpp;
      print(" <a href='?"
          . build_url($_queryvars, $holdvars, $query)
          . "'><font color='#FFFFFF'>$pages</font></a>\n");
    }

    if ($activepage > 1)
      print("&nbsp;<a href='?"
        . build_url($_queryvars, $holdvars, array ( hs => ( $activepage - 2)*$_tpp ))
        . "'><font color='#FFFFFF'>$lang[prev]</font></a>\n");
    else
      print("&nbsp;<font color='#FFFFFF'>$lang[prev]</font></a>\n");

    if ($activepage < $pages)
      print("&nbsp;<a href='?"
        . build_url($_queryvars, $holdvars, array ( hs => ($activepage)*$_tpp ))
        . "'><font color='#FFFFFF'>$lang[next]</font></a>\n");
    else
      print("&nbsp;<font color='#FFFFFF'>$lang[next]</font></a>\n");

    $fold  = $_folding->get_default();
    $swap  = $_folding->get_string_swap();
    
    if ($fold == UNFOLDED && $swap == '')
      print("&nbsp;&nbsp;$lang[unfoldall]\n");
    else {
      $query = "";
      $query[fold] = UNFOLDED;
      $query[swap] = '';
      print("&nbsp;&nbsp;<a href='?"
          . build_url($_queryvars, $holdvars, $query)
          . "'><font color='#FFFFFF'>$lang[unfoldall]</font></a>\n");
    }
    
    if ($fold == FOLDED && $swap == '')
      print("&nbsp;&nbsp;$lang[foldall]\n");
    else {
      $query = "";
      $query[fold] = FOLDED;
      $query[swap] = '';
      print("&nbsp;&nbsp;<a href='?"
          . build_url($_queryvars, $holdvars, $query)
          . "'><font color='#FFFFFF'>$lang[foldall]</font></a>\n");
    }
    
    $query = "";
    $query[write] = 1;
    print("&nbsp;&nbsp;<a href='?"
          . build_url($_queryvars, $holdvars, $query)
          . "'><font color='#FFFFFF'>$lang[writemessage]</font></a>\n");
    
    print("</b></font>\n");
    print("\t\t</td>\n");
    print("\t</tr>\n");
    print("</table>\n");
  }
  
  /* same as above, but shown, if a message is read */
  function messageindex_print($_msg_id,$_prev_thread_id,$_next_thread_id,
                              $_prev_entry_id,$_next_entry_id,$_has_child,
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
    // TODO, WORKING
    print("&lt;&lt;");
    print("&#032;$lang[entry]&#032;");
    print("&gt;&gt;");
    
    print("&nbsp;");
    if ($_prev_thread_id > 0) {
      print("<a href=''>&lt;&lt;</a>");
    } else {
      print("&lt;&lt;");
    }
    print("&#032;$lang[thread]&#032;");
    print("&gt;&gt;");
    
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
  
  function heading_print($_queryvars,$_title) {
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
          . build_url($_queryvars, $holdvars, $query) . "'>forum</a>\n"
          . "&nbsp;&nbsp;&gt;&nbsp;&nbsp;$_title");
    else print "&nbsp;&nbsp;forum";
    
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
