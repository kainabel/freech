<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels, <http://debain.org>
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
  
  $forum = new FreechForum();
  
  $forum->print_head();
  print("<table width='100%'>"
      . " <tr>"
      . "  <td align='center'>"
      . "  <a href='.'><img src='themes/" . cfg("theme") . "/img/logo.png' alt='' border=0 width=254 height=107 /></a>"
      . "  </td>"
      . " </tr>"
      . "</table><br />\n");
  if ($forum->get_current_forum_id() != 1)
    die("If you touch that URL again I will sue you!");
  if (!$forum->get_current_user()->is_anonymous())
    print("<a href='?action=logout'>".lang("logout")."</a>");
  else
    print("<a href='".htmlentities($forum->get_login_url())."'>".lang("login")."</a> <a href='?action=register'>".lang("register")."</a>");
  $forum->show();
  $forum->destroy();
  
  print("</body>\n"
      . "</html>\n");
?>
