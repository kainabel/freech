<?php
  /*
  Freech.
  Copyright (C) 2008 Samuel Abels

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
  class TopUsersController extends Controller {
    function show() {
      $userdb   = $this->api->userdb();
      $all_time = $userdb->get_top_users(20);
      $week     = $userdb->get_top_users(20, time() - 60*60*24*7);
      $this->clear_all_assign();
      $this->assign       ('plugin_dir', dirname(__FILE__));
      $this->assign_by_ref('all_time',   $all_time);
      $this->assign_by_ref('weekly',     $week);
      $this->render_php(dirname(__FILE__).'/top_users.php.tmpl');
      $this->api->set_title(_('Top Users'));
    }
  }
?>
