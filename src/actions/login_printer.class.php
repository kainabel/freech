<?php
  /*
  Freech.
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
    function show($_user, $_hint = '') {
      $url = new URL('?', array_merge(cfg("urlvars"), $_GET));
      $url->set_var('do_login', 1);

      $resend_url = new URL('?', array_merge(cfg("urlvars")));
      $resend_url->set_var('resend_confirm', 1);
      $resend_url->set_var('login', $_user->get_login());

      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('user',       $_user);
      $this->smarty->assign_by_ref('hint',       $_hint);
      $this->smarty->assign_by_ref('action',     $url->get_string());
      $this->smarty->assign_by_ref('resend_url', $resend_url->get_string());
      $this->parent->append_content($this->smarty->fetch('login.tmpl'));
    }
  }
?>
