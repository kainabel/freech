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
  class FooterPrinter extends PrinterBase {
    function show($_forum_id) {
      // Create the URL pointing to the posting search.
      $search_url = new URL('?', cfg("urlvars"));
      $search_url->set_var('action',   'search');
      $search_url->set_var('forum_id', (int)$_forum_id);

      // Create the URLs for changing the posting ordering.
      $order_url = new URL('?', cfg("urlvars"));
      $order_url->set_var('forum_id', (int)$_forum_id);
      $order_url->set_var('refer_to', $_SERVER['REQUEST_URI']);
      if ($_COOKIE[view] === 'plain') {
        $order_url->set_var('changeview', 't');
        $order_by_thread_url = $order_url->get_string();
        $order_by_time_url   = '';
      }
      else {
        $order_url->set_var('changeview', 'c');
        $order_by_thread_url = '';
        $order_by_time_url   = $order_url->get_string();
      }

      // Render the resulting template.
      $version[url]  = "http://debain.org/software/freech/";
      $version[text] = "Freech Forum ".FREECH_VERSION;
      $this->clear_all_assign();
      $this->assign_by_ref('order_by_thread', $order_by_thread_url);
      $this->assign_by_ref('order_by_time',   $order_by_time_url);
      $this->assign_by_ref('search',          $search_url->get_string());
      $this->assign_by_ref('version',         $version);
      $this->render('footer.tmpl');
    }
  }
?>
