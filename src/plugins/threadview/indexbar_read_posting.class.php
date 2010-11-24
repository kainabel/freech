<?php
  /*
  Freech.
  Copyright (C) 2008 Samuel Abels

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
  /**
   * Represents the Menu that is shown when reading a posting.
   */
  class ThreadViewIndexBarReadPosting extends Menu {
    var $items;


    // Constructor.
    function ThreadViewIndexBarReadPosting(&$_posting,
                                           $_prev_posting_id,
                                           $_next_posting_id,
                                           $_prev_thread_id,
                                           $_next_thread_id) {
      $this->Menu();

      if (!$_posting) {
        $this->add_separator();
        return;
      }

      $url = new FreechURL;
      $url->set_var('action',   'read');
      $url->set_var('msg_id',   1);
      $url->set_var('forum_id', $_posting->get_forum_id());

      // "Previous/Next Posting" buttons.
      if ($_prev_posting_id) {
        $url->set_var('msg_id', $_prev_posting_id);
        $url->set_label('<<');
        $this->add_link($url);
      }
      else
        $this->add_text('<<');

      $this->add_text(_('Message'));
      if ($_next_posting_id) {
        $url = clone($url);
        $url->set_var('msg_id', $_next_posting_id);
        $url->set_label('>>');
        $this->add_link($url);
      }
      else
        $this->add_text('>>');

      // "Previous Thread" button.
      $this->add_separator();
      if (cfg('thread_arrow_reverse'))
        $prev_id = $_next_thread_id;
      else
        $prev_id = $_prev_thread_id;
      if ($prev_id) {
        $url = clone($url);
        $url->set_var('msg_id', $prev_id);
        $url->set_label('<<');
        $this->add_link($url);
      }
      else
        $this->add_text('<<');

      // "Next Thread" button.
      $this->add_text(_('Thread'));
      $url = clone($url);
      if (cfg('thread_arrow_reverse'))
        $next_id = $_prev_thread_id;
      else
        $next_id = $_next_thread_id;
      if ($next_id) {
        $url = clone($url);
        $url->set_var('msg_id', $next_id);
        $url->set_label('>>');
        $this->add_link($url);
      }
      else
        $this->add_text('>>');
    }
  }
?>
