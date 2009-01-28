<?php
  /*
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
  /**
   * Abstract base class for all controllers.
   */
  class Controller {
    var $parent;
    var $eventbus;
    var $smarty;
    var $db;
    
    function Controller($_api) {
      $this->api       = $_api;
      $this->smarty    = $_api->smarty();
      $this->userdb    = $_api->userdb();
      $this->forumdb   = $_api->forumdb();
      $this->visitordb = $_api->visitordb();
      $this->eventbus  = $_api->eventbus();
    }

    function clear_all_assign() {
      $this->smarty->clear_all_assign();
    }

    function assign($_name, $_value) {
      $this->smarty->assign($_name, $_value);
    }

    function assign_by_ref($_name, $_value) {
      $this->smarty->assign_by_ref($_name, $_value);
    }

    function render($_template) {
      $template_dir = 'templates';
      $theme_dir    = 'themes/' . cfg('theme');
      if (is_readable($theme_dir . '/' . $_template))
        $this->smarty->template_dir = $theme_dir;
      else
        $this->smarty->template_dir = $template_dir;
      $this->assign_by_ref('__user',      $this->api->user());
      $this->assign_by_ref('__group',     $this->api->group());
      $this->assign_by_ref('__theme_dir', 'themes/' . cfg('theme'));
      $cache_id = $this->smarty->template_dir . '/' . $_template;
      $content  = $this->smarty->fetch($_template, $cache_id);
      $this->api->append_content($content);
    }
  }
?>
