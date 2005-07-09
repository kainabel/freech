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
   *       $_folding    The object indicating the current folding status.
   *       $_queryvars  Variables that are appended to every link.
   */
  function thread_index_print($_n_threads,
                              $_offset,
                              $_tpp,
                              $_ppi,
                              $_folding,
                              $_queryvars) {
    global $lang;
    global $cfg;
    
    $holdvars   = array_merge($cfg[urlvars],
                              array('forum_id', 'list', 'hs'));
    
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
    print("<table width='100%' cellspacing='0' cellpadding='3' border='0'"
        . " bgcolor='#003399' id='index'>\n");
    print("\t<tr>\n");
    print("\t\t<td align='left'>\n");
    print("\t\t<font size='-1'>$lang[index]\n");
    
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
        print(" $i\n");
      else {
        $query     = "";
        $query[hs] = ($i - 1) * $_tpp;
        print(" <a href='?"
            . build_url($_queryvars, $holdvars, $query)
            . "'>$i</a>\n");
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
          . "'>$pages</a>\n");
    }

    if ($activepage > 1)
      print("&nbsp;<a href='?"
        . build_url($_queryvars, $holdvars, array ( hs => ( $activepage - 2)*$_tpp ))
        . "'>$lang[next]</a>\n");
    else
      print("&nbsp;$lang[next]\n");

    if ($activepage < $pages)
      print("&nbsp;<a href='?"
        . build_url($_queryvars, $holdvars, array ( hs => ($activepage)*$_tpp ))
        . "'>$lang[prev]</a>\n");
    else
      print("&nbsp;$lang[prev]\n");

    if ($_folding) {
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
            . "'>$lang[unfoldall]</a>\n");
      }
    
      if ($fold == FOLDED && $swap == '')
        print("&nbsp;&nbsp;$lang[foldall]\n");
      else {
        $query = "";
        $query[fold] = FOLDED;
        $query[swap] = '';
        print("&nbsp;&nbsp;<a href='?"
            . build_url($_queryvars, $holdvars, $query)
            . "'>$lang[foldall]</a>\n");
      }
    }
    
    $query = "";
    $query[write] = 1;
    print("&nbsp;&nbsp;<a href='?"
          . build_url($_queryvars, $holdvars, $query)
          . "'>$lang[writemessage]</a>\n");
    
    print("</font>\n");
    print("\t\t</td>\n");
    print("\t</tr>\n");
    print("</table>\n");
  }
?>
