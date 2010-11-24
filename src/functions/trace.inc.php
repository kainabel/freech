<?php
  /*
  Freech.
  Copyright (C) 2009 Samuel Abels

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

/*
  If activated in config a strace compatible protocol file is provided.
  A graph can be created via 'plot-timeline.py trace.log -o file.png'.
*/

unset($tracer);

if (cfg('trace_calls')) {
  require_once dirname(__FILE__).'/../services/call_tracer.class.php';
  $tracer = new CallTracer(cfg('trace_log'));

  function trace() {
    $args = func_get_args();
    global $tracer;
    call_user_func_array(array(&$tracer, 'trace'), &$args);
  }

} else {
  function trace() {return;}
}

?>
