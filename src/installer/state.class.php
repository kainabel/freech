<?php
  /*
  Freech.
  Copyright (C) 2003-2009 Samuel Abels, <http://debain.org>

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
class State {
  function State() {
    $this->id      = 0;
    $this->attribs = array();
  }


  function set($_name, $_value) {
    $this->attribs[$_name] = $_value;
  }


  function get($_name, $_default = NULL) {
    if (!$this->attribs[$_name])
      return $_default;
    return $this->attribs[$_name];
  }


  function get_attributes() {
    return $this->attribs;
  }
}
?>