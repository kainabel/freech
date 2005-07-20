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
  define("ERR_MESSAGE_INCOMPLETE",     -1);
  define("ERR_MESSAGE_NAME_TOO_LONG",  -2);
  define("ERR_MESSAGE_TITLE_TOO_LONG", -3);
  define("ERR_MESSAGE_BODY_TOO_LONG",  -4);
  
  $err[ERR_MESSAGE_INCOMPLETE]     = $lang[somethingmissing];
  $err[ERR_MESSAGE_NAME_TOO_LONG]  = $lang[nametoolong];
  $err[ERR_MESSAGE_TITLE_TOO_LONG] = $lang[titletoolong];
  $err[ERR_MESSAGE_BODY_TOO_LONG]  = $lang[messagetoolong];
?>
