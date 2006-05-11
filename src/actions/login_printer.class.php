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
  class LoginPrinter extends PrinterBase {
    function show($_hint = '') {
      $url = new URL('?', array_merge(cfg("urlvars"), $_GET));
      $url->set_var('do_login', 1); 
      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('hint', $_hint);
      $this->smarty->assign_by_ref('action', $url->get_string());
      $url = new URL('?',cfg("urlvars"));
      $url->set_var('register', 1);
      $this->smarty->assign_by_ref('register', $url->get_string());
      $this->parent->append_content($this->smarty->fetch('login.tmpl'));
    }
    
    function show_successful() {
      $url = new URL('?', array_merge(cfg("urlvars"), $_GET));
      $url->delete_var('do_login', 1);
      
      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('start_url', $url->get_string());
      $url->set_var('edit_account', 1);
      $this->smarty->assign_by_ref('edit_account_url', $url->get_string());
      $this->parent->append_content($this->smarty->fetch('logged_in.tmpl'));
    }
  }
?>
