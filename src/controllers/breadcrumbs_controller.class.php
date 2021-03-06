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
  class BreadCrumbsController extends Controller {
    function show(&$_breadcrumbs, $_show_page_links) {
      $this->clear_all_assign();
      $this->assign_by_ref('breadcrumbs',     $_breadcrumbs);
      $this->assign_by_ref('page_links',      $this->api->links('page'));
      $this->assign_by_ref('show_page_links', $_show_page_links);
      $this->assign_by_ref('search_links',    $this->api->links('search'));
      $this->render_php('breadcrumbs.php.tmpl');
    }
  }
?>
