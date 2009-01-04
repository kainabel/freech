<?php
  /*
  Freech.
  Copyright (C) 2003-2008 Samuel Abels, <http://debain.org>

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
class PollRenderer extends MessageRenderer {
  function get_subject($_message) {
    $subject = $_message->get_subject();
    return lang('poll', array('title' => $subject));
  }


  function get_body_html($_message) {
    // Fetch the poll from the database.
    $poll_id = $_message->get_id();
    $poll    = _get_poll_from_id($this->forum, $poll_id);
    $user    = $this->forum->get_current_user();
    $db      = $this->forum->_get_db();
    $printer = new PollPrinter($this->forum);

    if ($user->is_anonymous())
      return $printer->get_error(lang('poll_not_logged_in'));

    if ($_GET['accept'])
      $hint = lang('poll_vote_accepted');

    if (_poll_did_vote($db, $user, $poll_id))
      return $printer->get_poll_result($poll, $hint);
    return $printer->get_poll($poll);
  }


  function is_editable($_message) {
    return FALSE;
  }
}
?>
