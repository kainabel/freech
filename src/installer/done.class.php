<?php
  /*
  Freech.
  Copyright (C) 2003-2008 Samuel Abels, <http://debain.org>

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
class Done extends Step {
  function show() {
    // Store the new version number in the database.
    $dbn    = $this->state->get('dbn');
    $result = util_store_attribute($dbn, 'version', FREECH_VERSION);
    $errors = array($result);

    // Write the configuration file.
    $config = array('db_host' => $this->state->get('db_host'),
                    'db_usr'  => $this->state->get('db_user'),
                    'db_pass' => $this->state->get('db_password'),
                    'db_name' => $this->state->get('db_name'),
                    'salt'    => util_get_random_string(10));
    $result = util_write_config('../data/config.inc.php', $config);
    array_push($errors, $result);

    // Show the template.
    $this->render('done.tmpl', array('errors' => $errors));

    // clear entire compile directory
    $this->smarty->clear_compiled_tpl();
    $this->smarty->compile_dir = '../data/smarty_templates_c';
    $this->smarty->clear_compiled_tpl();
  }

  function check() {
    return FALSE;
  }
}
?>
