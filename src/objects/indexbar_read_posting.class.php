<?php
  /*
  Freech.
  Copyright (C) 2008 Samuel Abels, <http://debain.org>

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
  class IndexBarReadPosting extends Menu {
    var $items;


    // Constructor.
    function IndexBarReadPosting($_posting,
                                 $_prev_posting_id,
                                 $_next_posting_id,
                                 $_prev_thread_id,
                                 $_next_thread_id,
                                 $_may_write = FALSE,
                                 $_may_edit  = FALSE) {
      $this->Menu();

      if (!$_posting) {
        $this->add_separator();
        return;
      }

      $url = new URL('?', cfg('urlvars'));
      $url->set_var('action',   'read');
      $url->set_var('msg_id',   1);
      $url->set_var('forum_id', $_posting->get_forum_id());

      // "Previous/Next Entry" buttons.
      if ($_prev_posting_id > 0) {
        $url->set_var('msg_id', $_prev_posting_id);
        $url->set_label(lang('prev_symbol'));
        $this->add_link($url);
      }
      else
        $this->add_text(lang('prev_symbol'));

      $this->add_text(lang('entry'));
      if ($_next_posting_id > 0) {
        $url = clone($url);
        $url->set_var('msg_id', $_next_posting_id);
        $url->set_label(lang('next_symbol'));
        $this->add_link($url);
      }
      else
        $this->add_text(lang('next_symbol'));

      // "Previous Thread" button.
      $this->add_separator();
      if (cfg('thread_arrow_rev'))
        $prev_id = $_next_thread_id;
      else
        $prev_id = $_prev_thread_id;
      if ($prev_id) {
        $url = clone($url);
        $url->set_var('msg_id', $prev_id);
        $url->set_label(lang('prev_symbol'));
        $this->add_link($url);
      }
      else
        $this->add_text(lang('prev_symbol'));

      // "Next Thread" button.
      $this->add_text(lang('thread'));
      $url = clone($url);
      if (cfg('thread_arrow_rev'))
        $next_id = $_prev_thread_id;
      else
        $next_id = $_next_thread_id;
      if ($next_id) {
        $url = clone($url);
        $url->set_var('msg_id', $next_id);
        $url->set_label(lang('next_symbol'));
        $this->add_link($url);
      }
      else
        $this->add_text(lang('next_symbol'));

      // "Edit" button.
      if ($_may_edit) {
        $this->add_separator();
        $url = clone($url);
        $url->set_var('msg_id', $_posting->get_id());
        $url->set_var('action', 'edit');
        $url->set_label(lang('editposting'));
        $this->add_link($url);
      }

      // "Reply" button.
      if ($_may_write) {
        $this->add_separator();
        $url = clone($url);
        $url->delete_var('msg_id');
        if ($_posting->is_active() && $_posting->get_allow_answer()) {
          $url->set_var('action',    'respond');
          $url->set_var('parent_id', $_posting->get_id());
          $url->set_label(lang('writeanswer'));
          $this->add_link($url);
        }
        else
          $this->add_text(lang('writeanswer'));

        // "New Thread" button.
        $this->add_separator();
        $url = clone($url);
        $url->delete_var('parent_id');
        $url->set_var('action', 'write');
        $url->set_label(lang('writemessage'));
        $this->add_link($url);
      }

      // "Show/Hide Thread" button.
      $url = new URL('?', cfg('urlvars'));
      $url->set_var('action',   'read');
      $url->set_var('forum_id', $_posting->get_forum_id());
      $url->set_var('refer_to', $_SERVER['REQUEST_URI']);
      if ($_posting->has_thread()) {
        $this->add_separator();
        $url->set_var('msg_id', $_posting->get_id());
        if ($_COOKIE[thread] === 'hide') {
          $url->set_var('showthread', 1);
          $url->set_label(lang('showthread'));
          $this->add_link($url);
        }
        else {
          $url->set_var('showthread', -1);
          $url->set_label(lang('hidethread'));
          $this->add_link($url);
        }
      }
    }
  }
?>
