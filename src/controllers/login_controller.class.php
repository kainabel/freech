<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels

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
  class LoginController extends Controller {
    function show(&$_user, $_refer_to) {
      $login_url = new FreechURL;
      $login_url->set_var('action', 'login');

      if ($_user->get_name() && !$_user->is_confirmed()) {
        $resend_url = new FreechURL('', _('Resend confirmation email'));
        $resend_url->set_var('action',   'account_reconfirm');
        $resend_url->set_var('username', $_user->get_name());
      }

      $forgot_url = new FreechURL('', _('Forgot your password?'));
      $forgot_url->set_var('action', 'password_forgotten');

      $this->clear_all_assign();
      $this->assign_by_ref('user',       $_user);
      $this->assign       ('refer_to',   urlencode($_refer_to));
      $this->assign       ('action',     $login_url->get_string());
      $this->assign_by_ref('resend_url', $resend_url);
      $this->assign_by_ref('forgot_url', $forgot_url);
      $this->render_php('login.php.tmpl');
    }


    function show_tmpl($_tmpl, &$_user) {
      $this->clear_all_assign();
      $this->assign_by_ref('user', $_user);
      $this->render_php($_tmpl);
    }


    function show_password_changed(&$user) {
      $this->show_tmpl('password_changed.php.tmpl', $user);
    }


    function show_password_change(&$_user) {
      $url = new FreechURL;
      $url->set_var('action', 'password_submit');
      $this->clear_all_assign();
      $this->assign       ('action', $url->get_string());
      $this->assign_by_ref('user',   $_user);
      $this->render_php('password_change.php.tmpl');
      $this->api->set_title(_('Password Change'));
    }


    function show_password_forgotten(&$_user) {
      $url = new FreechURL;
      $url->set_var('action', 'password_mail_submit');
      $this->clear_all_assign();
      $this->assign_by_ref('user',   $_user);
      $this->assign       ('action', $url->get_string());
      $this->render_php('password_forgotten.php.tmpl');
    }


    function show_password_mail_sent(&$_user) {
      $this->show_tmpl('password_mail_sent.php.tmpl', $_user);
    }
  }
?>
