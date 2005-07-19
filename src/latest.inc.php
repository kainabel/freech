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
  
  
  class LatestPrinter {
    var $smarty;
    var $db;
    var $_html;
    
    function LatestPrinter($_smarty, $_db) {
      $this->smarty  = $_smarty;
      $this->db      = $_db;
    }
    
    
    function _print_row($_row, $_data) {
      global $cfg;
      global $lang;
      
      $this->smarty->clear_all_assign();
      $query         = "";
      $query[msg_id] = $_row->id;
      $query[read]   = 1;
      $holdvars      = array_merge($cfg[urlvars], array('forum_id'));
      if ($cfg[remember_page])
        array_push($holdvars, 'hs');
      
      $_row->name     = string_escape($_row->name);
      $_row->title    = string_escape($_row->title);
      $_row->url      = "?" . build_url($_GET, $holdvars, $query);
      $_row->new      = ((time() - $_row->unixtime) < 86400);
      if (!$_row->active) {
        $_row->title = $lang[blockedtitle];
        $_row->name  = "------";
        $_row->url   = "";
      }
      $_row->title    = str_replace(" ", "&nbsp;", $_row->title);
      
      $this->smarty->assign_by_ref('row', $_row);
      
      $this->_html .= $this->smarty->fetch('latest_row.tmpl') . "\n";
    }
    
    
    function show() {
      global $cfg;
      global $lang;
      
      $n_rows = $this->db->foreach_latest_entry($_GET[forum_id],
                                                $_GET[hs],
                                                $cfg[epp],
                                                FALSE,
                                                array(&$this, '_print_row'),
                                                '');
      if ($n_rows == 0) {
        $this->smarty->assign('noentries', $lang[noentries]);
        $this->_html .= $this->smarty->fetch('thread_no_row.tmpl') . "\n";
      }
      
      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('threads', $this->_html);
      $this->smarty->display('thread.tmpl');
    }
  }
?>
