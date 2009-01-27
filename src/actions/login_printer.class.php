<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels, <http://debain.org>

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
    function show($_user, $_hint = '', $_refer_to) {
      $login_url = new URL('', cfg('urlvars'));
      $login_url->set_var('action', 'login');

      if ($_user->get_name() && !$_user->is_confirmed()) {
        $resend_url = new URL('',
                              cfg('urlvars'),
                              _('Resend confirmation email'));
        $resend_url->set_var('action',   'account_reconfirm');
        $resend_url->set_var('username', $_user->get_name());
      }

      $forgot_url = new URL('',
                            cfg('urlvars'),
                            _('Forgot your password?'));
      $forgot_url->set_var('action', 'password_forgotten');

      $this->clear_all_assign();
      $this->assign_by_ref('user',       $_user);
      $this->assign_by_ref('hint',       $_hint);
      $this->assign_by_ref('refer_to',   urlencode($_refer_to));
      $this->assign_by_ref('action',     $login_url->get_string());
      $this->assign_by_ref('resend_url', $resend_url);
      $this->assign_by_ref('forgot_url', $forgot_url);
      $this->render('login.tmpl');
    }


    function show_tmpl($_tmpl, $_user, $_hint = '') {
      $this->clear_all_assign();
      $this->assign_by_ref('user', $_user);
      $this->assign_by_ref('hint', $_hint);
      $this->render($_tmpl);
    }


    function show_password_changed($user, $_hint = '') {
      $this->show_tmpl('password_changed.tmpl', $user, $_hint);
    }


    function show_password_change($_user, $_hint = '') {
      $url = &new URL('', cfg('urlvars'));
      $url->set_var('action', 'password_submit');
      $this->clear_all_assign();
      $this->assign_by_ref('action', $url->get_string());
      $this->assign_by_ref('user',   $_user);
      $this->assign_by_ref('hint',   $_hint);
      $this->render('password_change.tmpl');
      $this->api->set_title(_('Password Change'));
    }


    function show_password_forgotten($_user, $_hint = '') {
      $url = new URL('', cfg('urlvars'));
      $url->set_var('action', 'password_mail_submit');
      $this->clear_all_assign();
      $this->assign_by_ref('user',   $_user);
      $this->assign_by_ref('hint',   $_hint);
      $this->assign_by_ref('action', $url->get_string());
      $this->render('password_forgotten.tmpl');
    }


    function show_password_mail_sent($user, $_hint = '') {
      $this->show_tmpl('password_mail_sent.tmpl', $user, $_hint);
    }
  }
?>
