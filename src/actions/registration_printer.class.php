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
  class RegistrationPrinter extends PrinterBase {
    function show($user, $error = '') {
      $url = &new URL('?create_account=1', cfg("urlvars"));
      
      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('action',    $url->get_string());
      $this->smarty->assign_by_ref('user',      $user);
      $this->smarty->assign_by_ref('password',  $_POST['password']);
      $this->smarty->assign_by_ref('password2', $_POST['password2']);
      $this->smarty->assign_by_ref('error',     $error);
      $this->parent->append_content($this->smarty->fetch('registration.tmpl'));
    }

    function show_tmpl($_tmpl, $_user, $_hint = '') {
      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('user', $_user);
      $this->smarty->assign_by_ref('hint', $_hint);
      $this->parent->append_content($this->smarty->fetch($_tmpl));
    }

    function show_mail_sent($user, $_hint = '') {
      $this->show_tmpl('registration_mail_sent.tmpl', $user, $_hint);
    }

    function show_done($user, $_hint = '') {
      $this->show_tmpl('registration_done.tmpl', $user, $_hint);
    }

    function show_change_password($_user, $_hint = '') {
      $url = &new URL('?', array_merge(cfg("urlvars"), $_GET));
      $url->delete_var('change_password');
      $url->set_var('submit_password', 1);
      
      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('action', $url->get_string());
      $this->smarty->assign_by_ref('user',   $_user);
      $this->smarty->assign_by_ref('hint',   $_hint);
      $this->parent->append_content($this->smarty->fetch('change_password.tmpl'));
    }
  }
?>
