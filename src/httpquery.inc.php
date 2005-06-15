<?php
  /*
  Ammerum.
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
  // Compatibility for older PHP versions.
  if (!function_exists('http_build_query')) {
    function http_build_query(&$queryvars,
                              $pref = 'flags_',
                              $f    = '',
                              $idx  = '') {
      $ret = '';
      foreach ($queryvars as $i => $j) {
        if ($j == '')
          continue;
        if ($idx != '')
          $i = $idx . "[$i]";
        if (is_array($j))
          $ret .= http_build_query($j, '', $f, $i);
        else {
          $j = urlencode($j);
          if (is_int($i))
            $ret .= "$f$pref$i=$j";
          else
            $ret .= "$f$i=$j";
        }
        
        $f = '&';
      }
      
      return $ret;
    }
  }
  
  // Generates a URL from the given variables.
  // $_urlvars:     The current URL, as returned from
  //                parse_str($_SERVER['QUERY_STRING']);
  // $_allowedvars: Variable names that are NOT listed here are filtered out.
  // $_addvars:     Variables to add to the URL. These variables are not
  //                required to be listed in $_allowedvars.
  function build_url($_urlvars, $_allowedvars, $_addvars) {
    foreach ($_allowedvars as $var)
      $vars[$var] = $_urlvars[$var];
    foreach ($_addvars as $key => $val)
      $vars[$key] = $val;
    return http_build_query($vars);
  }
?>
