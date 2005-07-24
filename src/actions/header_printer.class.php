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
  class HeaderPrinter {
    var $smarty;
    var $db;
    
    function HeaderPrinter(&$_smarty, &$_db) {
      $this->smarty  = &$_smarty;
      $this->db      = &$_db;
    }
    
    
    function show() {
      $url = new URL('?', array_merge(cfg("urlvars"), $_GET));
      $url->set_var('do_login', 1);
      
      $this->smarty->clear_all_assign();
      $this->smarty->display("header.tmpl");
      print("\n");
    }
  }
?>
