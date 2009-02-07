<?php
  /*
  Freech.
  Copyright (C) 2009 Samuel Abels, <http://debain.org>

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
class CallTracer {
  function CallTracer($_logfile = NULL) {
    if ($_logfile)
      $this->logfile = fopen($_logfile, 'w+');
  }

  function trace($_comment = '') {
    // Dissect the call.
    $backtrace = debug_backtrace();
    $caller    = $backtrace[2];
    $dir       = dirname(dirname(__FILE__));
    $file      = substr($caller['file'], strlen($dir));
    $func      = $caller['function'];
    $line      = $caller['line'];

    // Fake some strace output.
    list($msec, $sec) = explode(' ', microtime());
    list($foo, $msec) = explode('.', $msec);
    $msec   = (int)substr($msec, 0, -2);
    $str    = "MARK: $file($line): $func, $_comment";
    $access = sprintf("0000 $sec.%06d access(\"$str\", F_OK)\n", $msec);
    if ($this->logfile)
      fwrite($this->logfile, $access);
  }
}
?>
