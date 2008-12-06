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
  class PrinterBase {
    var $parent;
    var $registry;
    var $eventbus;
    var $smarty;
    var $db;
    
    function PrinterBase(&$_parent) {
      $this->parent   = &$_parent;
      $this->registry = $_parent->get_registry();
      $this->eventbus = $_parent->get_eventbus();
      $this->smarty   = $_parent->get_smarty();
      $this->db       = $_parent->get_forumdb();
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
      $current_user  = $this->parent->get_current_user();
      $current_group = $this->parent->get_current_group();
      $user          = $current_user  ? $current_user  : new User;
      $group         = $current_group ? $current_group : new Group;
      $this->smarty->assign       ('__logged_in', $current_user ? TRUE : FALSE);
      $this->smarty->assign_by_ref('__user',      $user);
      $this->smarty->assign_by_ref('__group',     $group);
      $this->parent->append_content($this->smarty->fetch($_template));
    }
  }
?>
