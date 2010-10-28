<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels

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

  include 'freech.class.php';

  if (!cfg('rss_enabled')) {
    header("HTTP/1.0 410 Gone");
    die();
  }

  $freech = new Freech;

  // get forum_id's from disabled forums
  $freech->bad_id_list = array();
  foreach($freech->forumdb()->get_forums(0) as $forum) {
    $freech->bad_id_list[] = $forum->fields['id'];
  }

  if (isset($_GET['forum_id'])) {
    $forum_id = (string) $_GET['forum_id'];

    // blocking requests on inactive forums
    if ( array_search($_GET['forum_id'], $freech->bad_id_list) !== FALSE ) {
      header("HTTP/1.0 410 Gone");
      $freech->destroy();
      die();
    }

    // sorting out invalid forum_id's
    $all = array();
    foreach($freech->forumdb()->get_forums() as $forum) {
      $all[] = $forum->fields['id'];
    }
    if ( array_search($forum_id, $all) !== TRUE ) {
      header("HTTP/1.0 404 Not Found");
      $freech->destroy();
      die();
    }
    unset($all, $forum_id);
  }

  header('Content-Type: text/xml; charset=utf-8');
  $freech->print_rss($_GET['forum_id'],
                     cfg('site_title'),
                     cfg('rss_description'),
                     $_GET['hs'],
                     $_GET['len']);
  $freech->destroy();
?>
