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
  class ModLogPrinter extends PrinterBase {
    function show($_offset = 0) {
      $modlogdb = $this->api->modlogdb();
      $items    = $modlogdb->get_items_from_query(array(),
                                                  cfg('modlog_epp'),
                                                  (int)$_offset);
      $this->clear_all_assign();
      //$this->assign_by_ref('indexbar', $indexbar);
      $this->assign_by_ref('n_rows', count($items));
      $this->assign_by_ref('items',  $items);
      $this->render('modlog.tmpl');
    }


    function show_lock_posting($_posting, $_error = '') {
      $url = new URL('', cfg('urlvars'));
      $url->set_var('action', 'posting_lock_submit');
      $url->set_var('msg_id', $_posting->get_id());

      $this->clear_all_assign();
      $this->assign_by_ref('action',   $url->get_string());
      $this->assign_by_ref('refer_to', $_posting->get_url_string());
      $this->assign_by_ref('posting',  $_posting);
      $this->assign_by_ref('error',    $_error);
      $this->render('posting_lock.tmpl');
    }
  }
?>
