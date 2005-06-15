<?php
  /*
  Ammerum.
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
  include_once 'mysql_nested.inc.php';
  include_once 'thread_index.inc.php';
  include_once 'thread.inc.php';
  include_once 'httpquery.inc.php';
  
  $db = new AmmerumDB($cfg[db_host], $cfg[db_usr], $cfg[db_pass],
                      $cfg[db_name], $cfg[db_tablebase]);
  //$db->insert_entry(1, 1, "Samuel", "Testtitle9", "Testtext");
  //$tid = $db->get_previous_thread_id(1, 5); print $tid;
  //$tid = $db->get_next_thread_id(1, 5); print $tid;
  
  $_GET[hs]       = $_GET[hs] ? $_GET[hs] * 1 : 0;
  $_GET[forum_id] = $_GET[forum_id] ? $_GET[forum_id] * 1 : 1;
  parse_str($_SERVER['QUERY_STRING'], $queryvars);
  
  print("<html>\n"
      . "<head>\n"
      . "</head>\n"
      . "<body bgcolor='#FFFFFF' text='#000000' link='#003399' vlink='#666666'"
      . " alink='#5566DD'>\n");
  
  $n_threads = $db->get_n_threads($_GET[forum_id]);
  $tpp       = $db->get_n_threads_per_page();
  $ppi       = 5;
  $folding   = new ThreadFolding($_GET[fold], $_GET[swap]);
  
  threadindex_print($n_threads, $_GET[hs], $tpp, $ppi, $folding, $queryvars);
  
  print("<table border=0 width=100% cellpadding=0 cellspacing=0>\n");
  $db->foreach_child($_GET[forum_id],
                     1,
                     $_GET[hs],
                     $folding,
                     print_row,
                     array($folding, $queryvars));
  print("</table>\n");
  
  threadindex_print($n_threads, $_GET[hs], $tpp, $ppi, $folding, $queryvars);
  
  print("</body>\n"
      . "</html>\n");
  
  $db->close();
?>
