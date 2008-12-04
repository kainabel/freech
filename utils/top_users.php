<?php
// This script prints the number of postings for the top 10 users.
require_once 'adodb/adodb.inc.php';
include_once 'libuseful/SqlQuery.class.php5';
include_once 'services/sql_query.class.php';
include_once 'services/forumdb.class.php';
include_once 'functions/config.inc.php';

$db = &ADONewConnection(cfg("db_dbn"))
  or die("FreechForum::FreechForum(): Error: Can't connect."
       . " Please check username, password and hostname.");

$forumdb = new ForumDB($db);
$users   = $forumdb->get_top_posters(20);
foreach ($users as $user)
  echo $user['username'].':'.$user['n_postings'].'<br/>';
echo "DONE.";
?>

