<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels, <http://debain.org>
  
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
  
  function &lang($_phrase = '', $vars = array()) {
    global $lang;
    if (!$_phrase)
      return $lang;
    $text = $lang[$_phrase];
    if (!$text)
      return $_phrase;
    foreach ($vars as $key => $value)
      $text = str_replace('['.strtoupper($key).']', $value, $text);
    return $text;
  }
  
  
  function &smarty_lang($params) {
    if (!isset($params[text]))
      die("smarty_lang(): No text given.");
    $phrase = $params[text];
    unset($params[text]);
    return lang($phrase, $params);
  }
?>
