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
  define("ERR_MESSAGE_INCOMPLETE",           -101);
  define("ERR_MESSAGE_NAME_TOO_LONG",        -102);
  define("ERR_MESSAGE_TITLE_TOO_LONG",       -103);
  define("ERR_MESSAGE_BODY_TOO_LONG",        -104);
  
  define("ERR_USER_LOGIN_INCOMPLETE",        -201);
  define("ERR_USER_LOGIN_TOO_SHORT",         -202);
  define("ERR_USER_LOGIN_TOO_LONG",          -203);
  define("ERR_USER_PASSWORD_INCOMPLETE",     -211);
  define("ERR_USER_PASSWORD_TOO_SHORT",      -212);
  define("ERR_USER_PASSWORD_TOO_LONG",       -213);
  define("ERR_USER_FIRSTNAME_INCOMPLETE",    -221);
  define("ERR_USER_FIRSTNAME_TOO_SHORT",     -222);
  define("ERR_USER_FIRSTNAME_TOO_LONG",      -223);
  define("ERR_USER_LASTNAME_INCOMPLETE",     -231);
  define("ERR_USER_LASTNAME_TOO_SHORT",      -232);
  define("ERR_USER_LASTNAME_TOO_LONG",       -233);
  define("ERR_USER_MAIL_NOT_VALID",          -241);
  define("ERR_USER_MAIL_TOO_LONG",           -242);
  define("ERR_USER_HOMEPAGE_NOT_VALID",      -251);
  define("ERR_USER_HOMEPAGE_TOO_LONG",       -252);
  define("ERR_USER_IM_TOO_LONG",             -261);
  define("ERR_USER_SIGNATURE_TOO_LONG",      -271);
  define("ERR_USER_REMOVED_FROM_LAST_GROUP", -281);
  
  define("ERR_GROUP_NAME_INCOMPLETE",        -301);
  define("ERR_GROUP_NAME_TOO_SHORT",         -302);
  define("ERR_GROUP_NAME_TOO_LONG",          -303);
  
  unset $err;
  $err[ERR_MESSAGE_INCOMPLETE]           = lang("somethingmissing");
  $err[ERR_MESSAGE_NAME_TOO_LONG]        = lang("nametoolong");
  $err[ERR_MESSAGE_TITLE_TOO_LONG]       = lang("titletoolong");
  $err[ERR_MESSAGE_BODY_TOO_LONG]        = lang("messagetoolong");
?>
