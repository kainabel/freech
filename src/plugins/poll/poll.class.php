<?php
  /*
  Freech.
  Copyright (C) 2003-2008 Samuel Abels, <http://debain.org>

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
class Poll {
  function Poll($_title = '', $_allow_multiple = FALSE) {
    $this->title          = $_title;
    $this->allow_multiple = $_allow_multiple;
    $this->options        = array();
  }


  function get_title() {
    return trim($this->title);
  }


  function get_options() {
    return $this->options;
  }


  function get_filled_options() {
    $options = array();
    foreach ($this->options as $option)
      if (trim($option) != '')
        array_push($options, $option);
    return $options;
  }


  function add_option($_caption) {
    array_push($this->options, $_caption);
  }


  function n_options() {
    return count($this->options);
  }


  function get_allow_multiple() {
    return $this->allow_multiple;
  }


  function set_allow_multiple($_allow) {
    $this->allow_multiple = $_allow;
  }


  function check() {
    if ($this->get_title() == '')
      return lang('poll_title_missing');
    $options = $this->get_filled_options();
    if (count($options) < 2)
      return lang('poll_too_few_options');
    return NULL;
  }
}
?>
