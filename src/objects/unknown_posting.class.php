<?php
  /*
  Freech.
  Copyright (C) 2003-2009 Samuel Abels

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
 * Decorates postings that are messages.
 */
class UnknownPosting extends PostingDecorator {
  function get_body() {
    $renderer = $this->posting->get_renderer();
    return "Plugin for posting type \"$renderer\" inactive or not installed.";
  }


  function get_body_html() {
    return '<div class="error">'.$this->get_body().'</div>';
  }


  function is_editable() {
    return FALSE;
  }


  function get_allow_answer() {
    return FALSE;
  }
}
?>
