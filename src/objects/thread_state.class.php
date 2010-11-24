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
  define('THREAD_STATE_FOLDED',   1);
  define('THREAD_STATE_UNFOLDED', 2);

  class ThreadState {
    var $default = THREAD_STATE_UNFOLDED;
    var $swapped = array();

    function ThreadState($_state, $_swapped) {
      if ($_state)
        $this->default = (int)$_state;

      $_swapped = explode('.', $_swapped, 1000);
      $i = 0;
      while ($_swapped[$i] != '') {
        $this->swapped[$_swapped[$i] * 1] = $_swapped[$i] * 1;
        $i++;
      }
    }


    function get_default() {
      return $this->default;
    }


    function swap($_id) {
      if ($this->swapped[$_id] == $_id)
        unset($this->swapped[$_id]);
      else
        $this->swapped[$_id] = $_id;
    }


    function get_string() {
      return implode('.', $this->swapped);
    }


    function is_folded($_id) {
      $id = $_id * 1;
      $swapped = $this->swapped;
      if ($this->default == THREAD_STATE_FOLDED)
        return $swapped[$id] != $id;
      return $swapped[$id] == $id;
    }


    function get_string_swap($_id = '') {
      if (!$_id)
        return implode('.', $this->swapped);
      $id = $_id * 1;
      $swapped = $this->swapped;
      if ($swapped[$id] == $id)
        unset($swapped[$id]);
      else
        $swapped[$id] = $id;
      ksort($swapped);
      return implode('.', $swapped);
    }
  }
?>
