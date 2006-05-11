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
  class FooterPrinter extends PrinterBase {
    function show() {
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('list',     1);
      $url->set_var('forum_id', (int)$_GET[forum_id]);
      if ($_COOKIE[view] === 'plain') {
        $url->set_var('changeview', 't');
        $order_by_thread   = $url->get_string();
        $order_by_time     = '';
      } else {
        $url->set_var('changeview', 'c');
        $order_by_thread   = '';
        $order_by_time     = $url->get_string();
      }
      $this->smarty->clear_all_assign();
      $url = new URL('?', cfg("urlvars"));
      /*FIXME: use group to distinguish between logged in users
       *       and anonymous users
       */
      if ($this->user->get_id()) {
        $url_options = $url;
        $url_options->set_var('edit_account', 1);
        $this->smarty->assign_by_ref('edit_account', $url_options->get_string());
        $url_logout = $url;
        $url_logout->set_var('logout', 1);
        $this->smarty->assign_by_ref('logout', $url_logout->get_string());
      } else {
        $url_login = $url;
        $url_login->set_var('do_login', 1);
        $this->smarty->assign_by_ref('login', $url_login->get_string());
      }        
      $version[url]  = "http://debain.org/software/tefinch/";
      $version[text] = "Tefinch Forum v0.9.10";
      $this->smarty->assign_by_ref('order_by_thread', $order_by_thread);
      $this->smarty->assign_by_ref('order_by_time',   $order_by_time);
      $this->smarty->assign_by_ref('version',         $version);
      $this->parent->append_content($this->smarty->fetch('footer.tmpl'));
    }
  }
?>
