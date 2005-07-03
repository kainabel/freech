<?php
  /*
  Tefinch.
  Copyright (C) 2003 Samuel Abels, <spam debain org>
                     Robert Weidlich, <tefinch xenim de>
  
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
  include_once 'forum.inc.php';
  
  forum_init();
  
  header("Content-Type: text/html; charset=utf-8");
  print("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"".
        "\"http://www.w3.org/TR/html4/loose.dtd\">");
  print("<html>\n"
      . "<head>\n"
      . "<meta http-equiv=Content-Type content=\"text/html; charset=utf-8\">\n"
      . "<title>Tefinch</title>"
      . "</head>\n"
      . "<body bgcolor='#FFFFFF' text='#000000' link='#003399' vlink='#666666'"
      . " alink='#5566DD'>\n");
  
  forum_print();
  
  print("</body>\n"
      . "</html>\n");
?>
