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
require_once '../smarty/Smarty.class.php';
require_once '../adodb/adodb.inc.php';
include_once '../freech.class.php';
include_once '../functions/config.inc.php';
include_once '../config.inc.php';
include_once '../libuseful/SqlQuery.class.php5';
include_once '../libuseful/string.inc.php';
include_once '../services/sql_query.class.php';
include_once '../services/userdb.class.php';
include_once '../objects/user.class.php';
include_once 'result.class.php';
include_once 'util.inc.php';
include_once 'state.class.php';
include_once 'statedb.class.php';
include_once 'step.class.php';
include_once 'welcome.class.php';
include_once 'check_requirements.class.php';
include_once 'database_setup.class.php';
include_once 'default_user_setup.class.php';
include_once 'install.class.php';
include_once 'done.class.php';

$steps = array('Welcome',
               'CheckRequirements',
               'DatabaseSetup',
               'Install',
               'DefaultUserSetup',
               'Done');

if (get_magic_quotes_gpc()) {
  $_GET    = array_map('stripslashes_deep', $_GET);
  $_POST   = array_map('stripslashes_deep', $_POST);
  $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
}

$statedb = new StateDB('../data/installer');
$smarty  = new Smarty;
$smarty->template_dir = '.';
$smarty->compile_dir  = '../data/installer';
$smarty->cache_dir    = '../data/smarty_cache';
$smarty->config_dir   = '../data/smarty_configs';
$smarty->display('header.tmpl');

$step_id      = (int)$_GET['step'];
$prev_step_id = $step_id - 1;
$state        = $statedb->get($prev_step_id);
$step_cls     = $steps[$step_id];

if ($prev_step_id >= 0) {
  $prev_step_cls = $steps[$prev_step_id];
  $prev_step     = new $prev_step_cls($prev_step_id, $smarty, $state);
  if ($prev_step->check() && $prev_step->submit()) {
    $statedb->save($step_id, $state);
    $step = new $step_cls($step_id, $smarty, $state);
    $step->show();
  }
}
else {
  $step = new $step_cls($step_id, $smarty, $state);
  $step->show();
}

$smarty->display('footer.tmpl');
?>
