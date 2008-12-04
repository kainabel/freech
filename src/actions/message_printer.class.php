<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels, <http://debain.org>

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
  class MessagePrinter extends ThreadPrinter {
    function MessagePrinter(&$_parent) {
      $this->ThreadPrinter(&$_parent);
    }


    function show(&$_forum_id, &$_msg) {
      $user       = $this->parent->get_current_user();
      $may_edit   = cfg("postings_editable")
                    && $user && $user->get_id() === $_msg->get_user_id();
      $indexbar   = &new IndexBarReadMessage($_msg, $may_edit);
      $showthread = $_msg && $_msg->has_thread() && $_COOKIE[thread] != 'hide';

      if (!$_msg) {
        $_msg = new Message;
        $_msg->set_subject(lang("noentrytitle"));
        $_msg->set_body(lang("noentrybody"));
      }
      elseif (!$_msg->is_active()) {
        $_msg->set_subject(lang("blockedtitle"));
        $_msg->set_body(lang("blockedentry"));
      }

      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('showthread', $showthread);
      if ($showthread) {
        $state  = new ThreadState(THREAD_STATE_UNFOLDED, '');
        $loader = new ThreadLoader($this->db, $state);
        $loader->load_thread_from_message($_forum_id, $_msg->get_id(), 0);
        $this->smarty->assign_by_ref('n_rows',   count($loader->messages));
        $this->smarty->assign_by_ref('messages', $loader->messages);
      }

      $this->smarty->assign_by_ref('indexbar', $indexbar);
      $this->smarty->assign_by_ref('message',  $_msg);
      $this->smarty->assign_by_ref('max_namelength',  cfg("max_namelength"));
      $this->smarty->assign_by_ref('max_titlelength', cfg("max_titlelength"));
      $this->parent->append_content($this->smarty->fetch('message_read.tmpl'));
    }


    /**
     * Shows a form for editing a message. The values given in $_message are
     * filled into the fields.
     */
    function show_compose(&$_message,
                          $_hint,
                          $_parent_id,
                          $_may_quote,
                          $_did_quote = -1) {
      $did_quote = $_did_quote === -1 ? (int)$_POST['did_quote'] : $_did_quote;
      $forum_id  = $this->parent->get_forum_id();
      $user      = $this->parent->get_current_user() or new User;

      $url  = new URL('?', cfg("urlvars"));
      $url->set_var('forum_id',  $forum_id);
      $url->set_var('parent_id', $_parent_id);

      $this->smarty->clear_all_assign();
      $this->smarty->assign('may_quote', $_may_quote);
      $this->smarty->assign('did_quote', $did_quote);
      $this->smarty->assign('parent_id', $_parent_id);
      $this->smarty->assign_by_ref('action',          $url->get_string());
      $this->smarty->assign_by_ref('hint',            $_hint);
      $this->smarty->assign_by_ref('user',            $user);
      $this->smarty->assign_by_ref('message',         $_message);
      $this->smarty->assign_by_ref('max_namelength',  cfg("max_namelength"));
      $this->smarty->assign_by_ref('max_titlelength', cfg("max_titlelength"));

      $this->parent->append_content($this->smarty->fetch('message_compose.tmpl'));
    }


    /**
     * Shows a form for editing a message. The values given in $_message are
     * filled into the fields, with the values from $_quoted inserted as a
     * quote.
     */
    function show_compose_quoted(&$_message,
                                 &$_parent_msg,
                                 $_hint,
                                 $_may_quote) {
      // Add "Message written by ... on ..." before the quoted stuff.
      if ($_parent_msg->is_active()) {
        $text  = preg_replace("/\[USER\]/",
                              $_parent_msg->get_username(),
                              lang("wrote"));
        $text  = preg_replace("/\[TIME\]/",
                              $_parent_msg->get_created_time(),
                              $text);
        $text .= "\n\n";
        $text .= preg_replace("/^/m",
                              "> ",
                              wordwrap_smart($_parent_msg->get_body()));
        $text .= "\n\n";
      }
      $_message->set_body($text . $_message->get_body());

      $this->show_compose($_message,
                          $_hint,
                          $_parent_msg->get_id(),
                          $_may_quote,
                          TRUE);
    }


    /**
     * Shows a form for editing a reply to the given message.
     */
    function show_compose_reply(&$_parent_msg, $_hint, $_may_quote) {
      $message = new Message;

      // Prepend 'Re: ' if necessary
      if (strpos($_parent_msg->get_subject(), lang("answer")) !== 0) {
        $subject = lang("answer") . $_parent_msg->get_subject();
        $message->set_subject(substr($subject, 0, cfg("max_titlelength")));
      }
      else
        $message->set_subject($_parent_msg->get_subject());

      $this->show_compose($message,
                          $_hint,
                          $_parent_msg->get_id(),
                          $_may_quote);
    }


    /* Show a preview form of the message. */
    function show_preview(&$_message, $_parent_id = '') {
      $url  = new URL('?', cfg("urlvars"));
      $url->set_var('forum_id',  $this->parent->get_forum_id());
      $url->set_var('parent_id', $_parent_id);

      $this->smarty->clear_all_assign();
      $this->smarty->assign('did_quote', $_POST['did_quote']);
      $this->smarty->assign('parent_id', (int)$_parent_id);
      $this->smarty->assign_by_ref('pagetitle', lang("preview"));
      $this->smarty->assign_by_ref('action',    $url->get_string());
      $this->smarty->assign_by_ref('message',   $_message);
      $this->parent->append_content($this->smarty->fetch('message_preview.tmpl'));

      return 0;
    }


    // Shows a page explaining that the message was successfully created.
    function show_created($_newmsg_id, $_parent_id = '', $_hint = '') {
      $messageurl = new URL('?', cfg("urlvars"));
      $messageurl->set_var('action',   'read');
      $messageurl->set_var('msg_id',   $_newmsg_id);
      $messageurl->set_var('forum_id', $this->parent->get_forum_id());

      $parenturl = new URL('?', cfg("urlvars"));
      $parenturl->set_var('action',   'read');
      $parenturl->set_var('msg_id',   $_parent_id);
      $parenturl->set_var('forum_id', $this->parent->get_forum_id());

      $forumurl = new URL('?', cfg("urlvars"));
      $forumurl->set_var('action',   'list');
      $forumurl->set_var('forum_id', $this->parent->get_forum_id());

      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('messageurl', $messageurl->get_string());
      if ($_parent_id)
        $this->smarty->assign_by_ref('parenturl', $parenturl->get_string());
      $this->smarty->assign_by_ref('hint',     $_hint);
      $this->smarty->assign_by_ref('forumurl', $forumurl->get_string());
      $this->parent->append_content($this->smarty->fetch('message_created.tmpl'));
    }
  }
?>
