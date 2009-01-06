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
    print($forum->get_login_url()->get_html());
    print(' | '.$forum->get_registration_url()->get_html());
  }
  else {
    print($forum->get_logout_url()->get_html());
    print(' | '.$user->get_profile_url()->get_html(lang('myprofile')));
    print(' | '.$user->get_editor_url()->get_html(lang('account_mydata')));
    print(' | '.$user->get_postings_url()->get_html(lang('mypostings')));
  }
  $forum->show();
  $forum->destroy();

  $render_time = round($forum->get_render_time(), 2);
  //echo "Site rendered in $render_time seconds.";

  print("</body>\n"
      . "</html>\n");
?>
