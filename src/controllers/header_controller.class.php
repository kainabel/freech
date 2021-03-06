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
  class HeaderController extends Controller {
    function show($_title) {
      if ($this->visitordb)
        $n_online = $this->visitordb->get_n_visitors(time() - 60 * 5);

      $this->clear_all_assign();
      $this->assign       ('title',         $_title);
      $this->assign       ('site_title',    cfg('site_title'));
      $this->assign       ('n_online',      $n_online);
      $this->assign       ('style',         $this->api->get_style());
      $this->assign       ('head_js',       $this->api->get_js('head'));
      $this->assign       ('onload_js',     $this->api->get_js('onload'));
      $this->assign_by_ref('account_links', $this->api->links('account'));
      $this->render_php('header.php.tmpl');
    }
  }
?>
