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
      $this->ThreadPrinter(&$_parent, new ThreadFolding(UNFOLDED, ''));
    }


    function show(&$_forum_id, &$_msg_id) {
      $msg        = $this->db->get_message($_forum_id, $_msg_id);
      $indexbar   = &new IndexBarReadMessage($msg);
      $showthread = $msg && $msg->has_thread() && $_COOKIE[thread] != 'hide';

      if (!$msg) {
        $msg = new Message;
        $msg->set_subject(lang("noentrytitle"));
        $msg->set_body(lang("noentrybody"));
      }
      elseif (!$msg->is_active()) {
        $msg->set_subject(lang("blockedtitle"));
        $msg->set_body(lang("blockedentry"));
      }
      
      if ($showthread)
        $n = $this->db->foreach_child_in_thread($_forum_id,
                                                $_msg_id,
                                                0,
                                                cfg("tpp"),
                                                $this->folding,
                                                array(&$this, '_append_row'),
                                                '');

      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('indexbar',   $indexbar);
      $this->smarty->assign_by_ref('showthread', $showthread);
      $this->smarty->assign_by_ref('n_rows',     $n);
      $this->smarty->assign_by_ref('message',    $msg);
      $this->smarty->assign_by_ref('messages',   $this->messages);
      $this->smarty->assign_by_ref('max_namelength',  cfg("max_namelength"));
      $this->smarty->assign_by_ref('max_titlelength', cfg("max_titlelength"));
      $this->parent->append_content($this->smarty->fetch('message_read.tmpl'));
    }
    
    
    /**
     * Shows a form for editing a message. The values given in $_message are
     * filled into the fields.
     */
    function show_compose(&$_message, $_hint, $_quotebutton) {
      $url = new URL('?', cfg("urlvars"));
      $url->set_var('msg_id',   (int)$_GET[msg_id]);
      $url->set_var('forum_id', (int)$_GET[forum_id]);
      
      $user = $this->parent->get_current_user() or new User;

      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('action',          $url->get_string());
      $this->smarty->assign_by_ref('hint',            $_hint);
      $this->smarty->assign_by_ref('user',            $user);
      $this->smarty->assign_by_ref('message',         $_message);
      $this->smarty->assign_by_ref('max_namelength',  cfg("max_namelength"));
      $this->smarty->assign_by_ref('max_titlelength', cfg("max_titlelength"));

      if ($_quotebutton)
        $this->smarty->assign('msg_id', (int)$_GET[msg_id]);
      $this->parent->append_content($this->smarty->fetch('message_compose.tmpl'));
    }
    
    
    /**
     * Shows a form for editing a message. The values given in $_message are
     * filled into the fields, with the values from $_quoted inserted as a
     * quote.
     */
    function show_compose_quoted(&$_message, &$_quoted, $_hint, $_quotebutton) {
      // Add "Message written by ... on ..." before the quoted stuff.
      if ($_GET[msg_id] && $_quoted->is_active()) {
        $text  = preg_replace("/\[USER\]/",
                              $_quoted->get_username(),
                              lang("wrote"));
        $text  = preg_replace("/\[TIME\]/",
                              $_quoted->get_created_time(),
                              $text);
        $text .= "\n\n";
        $text .= preg_replace("/^/m",
                              "> ",
                              wordwrap_smart($_quoted->get_body()));
        $text .= "\n\n";
      }
      $_message->set_body($text . $_message->get_body());
      $this->show_compose($_message, $_hint, $_quotebutton);
    }
    
    
    /**
     * Shows a form for editing a reply to the given message.
     */
    function show_compose_reply(&$_parent_msg, $_hint, $_quotebutton) {
      $message = new Message;
      
      // Prepend 'Re: ' if necessary
      if (strpos($_parent_msg->get_subject(), lang("answer")) !== 0) {
        $subject = lang("answer") . $_parent_msg->get_subject();
        $message->set_subject(substr($subject, 0, cfg("max_titlelength")));
      }
      else
        $message->set_subject($_parent_msg->get_subject());
      
      $this->show_compose($message, $_hint, $_quotebutton);
    }
    
    
    /* Show a preview form of the message. */
    function show_preview(&$_message, $_parent_id = '') {
      $url  = new URL('?', array_merge($_GET, cfg("urlvars")));
      $url->mask(array('forum_id', 'msg_id', 'hs'));
      
      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('pagetitle', lang("preview"));
      $this->smarty->assign_by_ref('action',    $url->get_string());
      $this->smarty->assign_by_ref('message',   $_message);
      $this->smarty->assign('msg_id', (int)$_parent_id);
      $this->parent->append_content($this->smarty->fetch('message_preview.tmpl'));
      
      return 0;
    }
    
    
    // Shows a page explaining that the message was successfully created.
    function show_created($_newmsg_id, $_hint = '') {
      $messageurl = new URL('?', cfg("urlvars"));
      $messageurl->set_var('read',     1);
      $messageurl->set_var('msg_id',   $_newmsg_id);
      $messageurl->set_var('forum_id', (int)$_GET[forum_id]);
      
      $parenturl = new URL('?', cfg("urlvars"));
      $parenturl->set_var('read',     1);
      $parenturl->set_var('msg_id',   (int)$_GET[msg_id]);
      $parenturl->set_var('forum_id', (int)$_GET[forum_id]);
      
      $forumurl = new URL('?', cfg("urlvars"));
      $forumurl->set_var('list',     1);
      $forumurl->set_var('forum_id', (int)$_GET[forum_id]);
      
      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('messageurl', $messageurl->get_string());
      if ($_GET[msg_id]) 
        $this->smarty->assign_by_ref('parenturl', $parenturl->get_string());
      $this->smarty->assign_by_ref('hint',     $_hint);
      $this->smarty->assign_by_ref('forumurl', $forumurl->get_string());
      $this->parent->append_content($this->smarty->fetch('message_created.tmpl'));
    }
  }
?>
