<?php
  /*
  Tefinch.
  Copyright (C) 2003 Samuel Abels, <spam debain org>
  
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
  unset($lang);
  if (preg_match("/^[a-z0-9_]+$/i", cfg(lang)))
    include_once "language/" . cfg(lang) . ".inc.php";
  
  function &lang($_phrase = '') {
    global $lang;
    if (!$_phrase)
      return $lang;
    if (!$lang[$_phrase])
      return $_phrase;
    return $lang[$_phrase];
  }
  
  
  function &smarty_lang($params) {
    if (!isset($params[text]))
      die("smarty_lang(): No text given.");
    return lang($params[text]);
  }
?>