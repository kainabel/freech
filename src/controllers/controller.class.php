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
    function Controller(&$_api) {
      $this->api       = $_api;
      $this->userdb    = $_api->userdb();
      $this->forumdb   = $_api->forumdb();
      $this->visitordb = $_api->visitordb();
      $this->eventbus  = $_api->eventbus();
      $this->hints     = array();
      $this->var_stack = array();
      $this->scope     = array();
    }

    function add_hint($_hint) {
      array_push($this->hints, $_hint);
    }

    function has_errors() {
      foreach ($this->hints as $hint)
        if ($hint->get_type() == 'error')
          return TRUE;
      return FALSE;
    }

    function clear_all_assign() {
      $this->scope = array();
    }

    function assign($_name, $_value) {
      $this->scope[$_name] = $_value;
    }

    function assign_by_ref($_name, &$_value) {
      $this->scope[$_name] = $_value;
    }

    function fetch_php($_template) {
      ob_start();
      $this->render_php($_template);
      $result = ob_get_contents();
      ob_end_clean();
      return $result;
    }

    function render_php($_template) {
      $template_dir = 'templates';
      $theme_dir    = 'themes/' . cfg('theme');
      $__user       = $this->api->user();
      $__group      = $this->api->group();
      $__theme_dir  = 'themes/' . cfg('theme');
      $__hints      = $this->hints;
      foreach ($this->scope as $key => $value)
        $$key = $value;
      trace('rendering PHP template %s', $template_dir.'/'.$_template);
      if (!is_readable($_template))
        $_template = $template_dir . '/' . $_template;
      require $_template;
      trace('rendered PHP template %s', $_template);
    }
  }
?>
