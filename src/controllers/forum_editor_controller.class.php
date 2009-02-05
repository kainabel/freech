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
class ForumEditorController extends Controller {
  function show(&$_forum = NULL) {
    $url = new FreechURL;
    $url->set_var('action', 'forum_submit');

    // Render the template.
    $this->clear_all_assign();
    $this->assign_by_ref('action',      $url->get_string());
    $this->assign_by_ref('forum',       $_forum);
    $this->assign_by_ref('status_list', $_forum->get_status_names());
    $this->render('forum_editor.tmpl');
  }
}
?>
