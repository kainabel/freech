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
/**
 * A base class for all views. Views render the forum overview as well
 * as the page on which a posting is shown.
 */
class View extends Controller {
  function show($_forum_id, $_offset) {
    die('show() not implemented.');
  }


  function show_thread(&$_thread) {
    die('show_thread() not implemented.');
  }


  function show_posting(&$_posting) {
    die('show_posting() not implemented.');
  }
}
?>
