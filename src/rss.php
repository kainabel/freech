<?php
  /*
  Tefinch.
  Copyright (C) 2003 Samuel Abels, <spam debain org>
                     Robert Weidlich, <tefinch xenim de>
  
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
  include_once 'forum.inc.php';
  
  $forum = new TefinchForum();
  header("Content-Type: text/xml; charset=utf-8");
  if ($_GET[forum_id] != 1)
    die("If you touch that URL again I will sue you!");
  if ($_GET[len] > 20)
    $_GET[len] = 20;
  $forum->print_rss($_GET[forum_id],
                    "Tefinch Bastelforum",
                    "Bastelforum - http://debain.org/tefinch",
                    "en",
                    $_GET[hs],
                    $_GET[len]);
  $forum->destroy();
?>
