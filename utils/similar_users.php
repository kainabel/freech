<?php
// This script allows for checking for similar user names. May be used 
// when testing new algorithms for the similarity check.
require_once 'adodb/adodb.inc.php';
include_once 'libuseful/SqlQuery.class.php5';
include_once 'services/sql_query.class.php';
include_once 'services/userdb.class.php';
include_once 'functions/config.inc.php';
include_once 'objects/user.class.php';

$db = &ADONewConnection(cfg("db_dbn"))
  or die("FreechForum::FreechForum(): Error: Can't connect."
       . " Please check username, password and hostname.");

function print_user($user, $needle) {
  echo "Match: ".$user->get_username()." = ".$user->get_lexical_similarity($needle)."<br>";
}

$userdb = new UserDB($db);
$needle = new User;
$needle->set_username($_GET['name']);

if ($_GET['name2']) {
  $user = new User();
  $user->set_username($_GET['name2']);
  die("Similarity: ".$user->get_lexical_similarity($needle)."<br>");
}

$users = $userdb->get_similar_users($needle);
foreach ($users as $user)
  print_user($user, $needle);
echo "DONE.";
?>
