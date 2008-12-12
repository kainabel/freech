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

  // Must be called before any other output is produced.
  $forum = new FreechForum();

  // Print the page header.
  $forum->print_head();
  print("<table width='100%'>"
      . " <tr>"
      . "  <td align='center'>"
      . "  <a href='.'><img src='themes/" . cfg("theme") . "/img/logo.png' alt='' border=0 width=254 height=107 /></a>"
      . "  </td>"
      . " </tr>"
      . "</table><br />\n");

  // Permit only one forum at this time.
  if ($forum->get_current_forum_id() != 1)
    die("If you touch that URL again I will sue you!");

  // Permit only one forum at this time.
  $user = $forum->get_current_user();
  if ($user->is_anonymous()) {
    print("<a href='".$forum->get_login_url(TRUE)."'>".lang("login")."</a>");
    print(" | <a href='".$forum->get_registration_url()."'>".lang("register")."</a>");
  }
  else {
    print("<a href='".$forum->get_logout_url()."'>".lang("logout")."</a>");
    print(" | <a href='".$user->get_profile_url()."'>".lang("myprofile")."</a>");
    print(" | <a href='".$user->get_editor_url()."'>".lang("account_mydata")."</a>");
    print(" | <a href='".$user->get_postings_url()."'>".lang("mypostings")."</a>");
  }
  print(" | <a href='".$forum->get_statistics_url()."'>".lang("statistics")."</a>");
  $forum->show();
  $forum->destroy();

  $render_time = round($forum->get_render_time(), 2);
  //echo "Site rendered in $render_time seconds.";

  print("</body>\n"
      . "</html>\n");
?>
