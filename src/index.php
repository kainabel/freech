<?php
  /*
  Freech.
  Copyright (C) 2008 Samuel Abels

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

// Must be called before any other output is produced.
$forum = new Freech;

// Let the forum do its work.
$forum->run();

// Print the page header.
$forum->print_head();

// Print the body.
$forum->show();

// Done.
$forum->destroy();
?>
</body>
</html>
