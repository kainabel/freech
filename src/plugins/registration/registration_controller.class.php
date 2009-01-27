<?php
  /*
  Freech.
  Copyright (C) 2008 Samuel Abels, <http://debain.org>

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
  class RegistrationController extends Controller {
    function show($user, $error = '') {
      $url = new URL('', cfg('urlvars'));
      $url->set_var('action', 'account_create');
      
      $this->clear_all_assign();
      $this->assign_by_ref('action',    $url->get_string());
      $this->assign_by_ref('user',      $user);
      $this->assign_by_ref('password',  $_POST['password']);
      $this->assign_by_ref('password2', $_POST['password2']);
      $this->assign_by_ref('error',     $error);
      $this->render(dirname(__FILE__).'/registration.tmpl');
      $this->api->set_title(_('User Registration'));
    }


    function show_tmpl($_tmpl, $_user, $_hint = '') {
      $this->clear_all_assign();
      $this->assign_by_ref('user', $_user);
      $this->assign_by_ref('hint', $_hint);
      $this->render(dirname(__FILE__).'/'.$_tmpl);
    }


    function show_mail_sent($user, $_hint = '') {
      $this->show_tmpl('mail_sent.tmpl', $user, $_hint);
    }


    function show_done($user, $_hint = '') {
      $this->show_tmpl('done.tmpl', $user, $_hint);
    }
  }
?>
