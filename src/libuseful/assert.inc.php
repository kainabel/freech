<?php
  /*
  Copyright (C) 2006 Samuel Abels, <spam debain org>
  
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
  function assert_cb($script, $line, $message) {
    echo "<hr />";
    echo 'assert_cb(): Assertion at <b>', $script,'</b>:';
    echo ' line <b>', $line,"</b>: $message<br/><br/>";
    echo "<table border='1'>";
    $bt = debug_backtrace();
   
    echo "<thead><tr><th>file</th><th>line</th><th>function</th>".
         "</tr></thead>";
    $first = TRUE;
    foreach ($bt as $call)
    {
      if ($first) {
        $first = FALSE;
        continue;
      }
      if (!isset($call['file']))
        $call['file'] = '[PHP]';
      if (!isset($call['line']))
        $call['line'] = '';

      echo "<tr><td>{$call["file"]}</td><td>{$call["line"]}</td>".
           "<td>{$call["function"]}</td></tr>";
    }
    echo "</table></div><hr /></p>";
    die();
  }
  assert_options(ASSERT_CALLBACK, 'assert_cb');
?>
