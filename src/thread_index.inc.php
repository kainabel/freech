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
  function thread_index_print($_smarty,
                              $_n_threads,
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
    if ($pages <= 0)
      $pages = 1;
    $activepage = ceil($_offset / $_tpp) + 1;
    $pageoffset = 1;
    
    if ($activepage > $_ppi / 2)
      $pageoffset = $activepage - ceil($_ppi / 2);
    if ($pageoffset + $_ppi > $pages)
      $pageoffset = $pages - $_ppi;
    if ($pageoffset < 1)
      $pageoffset = 1;
    
    // Always show a link to the first page.
    if ($pageoffset > 1) {
      $query      = "";
      $query[hs]  = 0;
      $numbers[1] = "?" . build_url($_queryvars, $holdvars, $query);
    }
    if ($pageoffset > 2)
      $numbers['...'] = 0;
    
    // Print the numbers. Print the active number using another color.
    for ($i = $pageoffset; $i <= $pageoffset + $_ppi && $i <= $pages; $i++) {
      if ($i == $activepage)
        $numbers[$i] = 0;
      else {
        $query       = "";
        $query[hs]   = ($i - 1) * $_tpp;
        $numbers[$i] = "?" . build_url($_queryvars, $holdvars, $query);
      }
    }
    
    // Always show a link to the last page.
    if ($pageoffset + $_ppi < $pages - 1)
      $numbers['...'] = 0;
    if ($pageoffset + $_ppi < $pages) {
      $query           = "";
      $query[hs]       = ($pages - 1) * $_tpp;
      $numbers[$pages] = "?" . build_url($_queryvars, $holdvars, $query);
    }
    
    $_smarty->assign('index', $lang[index]);
    $_smarty->assign('numbers', $numbers);
    
    // "Newer threads" link.
    $newer_threads[text] = $lang[next];
    if ($activepage > 1) {
      $query              = "";
      $query[hs]          = ($activepage - 2) * $_tpp;
      $newer_threads[url] = "?" . build_url($_queryvars, $holdvars, $query);
    }
    $_smarty->assign('newer_threads', $newer_threads);
    
    // "Older threads" link.
    $older_threads[text] = $lang[prev];
    if ($activepage < $pages) {
      $query              = "";
      $query[hs]          = $activepage * $_tpp;
      $older_threads[url] = "?" . build_url($_queryvars, $holdvars, $query);
    }
    $_smarty->assign('older_threads', $older_threads);
    
    if ($_folding) {
      $fold  = $_folding->get_default();
      $swap  = $_folding->get_string_swap();
      
      // "Unfold all" link.
      $unfold_all[text] = $lang[unfoldall];
      if ($fold != UNFOLDED || $swap != '') {
        $query           = "";
        $query[fold]     = UNFOLDED;
        $query[swap]     = '';
        $unfold_all[url] = "?" . build_url($_queryvars, $holdvars, $query);
      }
      $_smarty->assign('unfold_all', $unfold_all);
      
      // "Fold all" link.
      $fold_all[text] = $lang[foldall];
      if ($fold != FOLDED || $swap != '') {
        $query         = "";
        $query[fold]   = FOLDED;
        $query[swap]   = '';
        $fold_all[url] = "?" . build_url($_queryvars, $holdvars, $query);
      }
      $_smarty->assign('fold_all', $fold_all);
    }
    
    // "New message" link.
    $query            = "";
    $query[write]     = 1;
    $new_thread[text] = $lang[writemessage];
    $new_thread[url]  = "?" . build_url($_queryvars, $holdvars, $query);
    $_smarty->assign('new_thread', $new_thread);
    
    $_smarty->display('thread_index.tmpl');
    print("\n");
  }
?>
