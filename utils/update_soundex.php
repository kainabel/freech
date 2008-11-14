<?php
// This script updates the soundex value of all users in the database.
// May be used after changing the algorithm for finding similar users.
require_once 'adodb/adodb.inc.php';
include_once 'libuseful/SqlQuery.class.php5';
include_once 'services/sql_query.class.php';
include_once 'services/accountdb.class.php';
include_once 'functions/config.inc.php';
include_once 'objects/user.class.php';

$db = &ADONewConnection(cfg("db_dbn"))
  or die("FreechForum::FreechForum(): Error: Can't connect."
       . " Please check username, password and hostname.");

function print_user($user, $accountdb) {
  echo "USER: ".$user->get_login()." = ".$user->get_soundexed_login()."<br>";
  $accountdb->save_user($user);
}

$accountdb = new AccountDB($db);
$accountdb->foreach_user(-1, -1, -1, 'print_user', $accountdb);
echo "DONE.";
?>
