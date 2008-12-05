<?php
// This script updates the soundex value of all users in the database.
// May be used after changing the algorithm for finding similar users.
require_once 'adodb/adodb.inc.php';
include_once 'libuseful/SqlQuery.class.php5';
include_once 'services/sql_query.class.php';
include_once 'services/userdb.class.php';
include_once 'functions/config.inc.php';
include_once 'objects/user.class.php';

$db = &ADONewConnection(cfg("db_dbn"))
  or die("FreechForum::FreechForum(): Error: Can't connect."
       . " Please check username, password and hostname.");

function print_user($user, $userdb) {
  echo "USER: ".$user->get_username()." = ".$user->get_soundexed_username()."<br>";
  $userdb->save_user($user);
}

$userdb = new UserDB($db);
$userdb->foreach_user_from_query(NULL, -1, -1, 'print_user', $userdb);
echo "DONE.";
?>
