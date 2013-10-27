<?php
/*
  Freech.
  Copyright (C) 2003-2008 Samuel Abels

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
error_reporting(E_ALL ^ E_NOTICE | E_STRICT);

// Debug switches.
$_POST['do_not_act']   = (bool) 0; // Avoids creating tables, but not DB's.
$_POST['adodb_debug']  = (bool) 0; // Shows SQL queries.
$_POST['debug_ignore'] = (bool) 0; // Do NOT use on productive systems!

define('ADODB_DRIVER', 'mysqli');
require_once '../main_controller.class.php';

require_once 'result.class.php';
require_once 'util.inc.php';
require_once 'statedb.class.php';
require_once 'step.classes.php';

/* reflects the sequence of installation steps */
$steps = array('Welcome',            // Step 0
               'CheckRequirements',  // Step 1
               'DatabaseSetup',      // Step 2
               'DatabaseInstall',    // Step 3
               'DefaultSetup',       // Step 4
               'Done');              // Step 5

if (get_magic_quotes_gpc()) {
  $_GET    = array_map('stripslashes_deep', $_GET);
  $_POST   = array_map('stripslashes_deep', $_POST);
  $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
}
$data_dir = '../data/installer';
$statedb = new StateDB($data_dir);

require 'header.tmpl';

$step_id = (int)$_GET['step'];
$prev_step_id = $step_id - 1;
$state = $statedb->get($prev_step_id);
$step_cls = $steps[$step_id];

// Main loop.
if ($prev_step_id >= 0) {
  $prev_step_cls = $steps[$prev_step_id];
  $prev_step = new $prev_step_cls($prev_step_id, $state);
  // Test data from the previous step.
  if ($prev_step->check() && $prev_step->submit()) {
    $statedb->save($step_id, $state);
    $step = new $step_cls($step_id, $state);
    $step->show();
  }
} else {
  $statedb->remove();
  $step = new $step_cls($step_id, $state);
  $step->show();
}

require 'footer.tmpl';
?>
