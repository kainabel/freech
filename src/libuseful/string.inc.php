<?php
  /*
  Copyright (C) 2005 Samuel Abels, <http://debain.org>

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
  function string_escape(&$_string) {
    return htmlentities($_string, ENT_QUOTES, 'UTF-8');
  }


  // Removes the escapings that were added by magic-quotes.
  function stripslashes_deep(&$_value) {
    return is_array($_value)
         ? array_map('stripslashes_deep', $_value)
         : stripslashes($_value);
  }


  function is_utf8($_string) {
    return mb_check_encoding($_string, 'utf8');
  }


  function replace_vars($_string, $_vars) {
    foreach ($_vars as $key => $value)
      $_string = str_replace('['.strtoupper($key).']', $value, $_string);
    return $_string;
  }
?>
