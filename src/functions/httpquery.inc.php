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
  // Compatibility for older PHP versions.
  if (!function_exists('http_build_query')) {
    function http_build_query(&$_queryvars,
                              $_pref = 'flags_',
                              $_f    = '',
                              $_idx  = '') {
      $ret = '';
      foreach ($_queryvars as $i => $j) {
        if ($j == '')
          continue;
        if ($_idx != '')
          $i = $_idx . "[$i]";
        if (is_array($j))
          $ret .= http_build_query($j, '', $_f, $i);
        else {
          $j = urlencode($j);
          if (is_int($i))
            $ret .= "$_f$_pref$i=$j";
          else
            $ret .= "$_f$i=$j";
        }
        
        $_f = '&';
      }
      
      return $ret;
    }
  }
?>
