<?php
  /*
  Freech.
  Copyright (C) 2008 Samuel Abels, <http://debain.org>

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

  // Must be called before any other output is produced.
  $forum = new FreechForum();

  // Print the page header.
  $forum->print_head();

  // Permit only one forum at this time.
  if ($forum->get_current_forum_id() != 1)
    die('Other forums are currently not enabled.');

  $forum->show();
  $forum->destroy();

  $render_time = round($forum->get_render_time(), 2);
  //echo "Site rendered in $render_time seconds.";
?>
</body>
</html>
