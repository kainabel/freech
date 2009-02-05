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
  class MessageController extends Controller {
    /**
     * Shows a form for editing a message. The values given in $_message are
     * filled into the fields.
     */
    function show_compose(&$_message, $_parent_id, $_may_quote) {
      $forum_id = $this->api->forum()->get_id();

      $url = new FreechURL;
      $url->set_var('forum_id',  $forum_id);
      $url->set_var('parent_id', $_parent_id);

      $this->clear_all_assign();
      $this->assign('onsubmit_js',        $this->api->get_js('onsubmit'));
      $this->assign('form_html',          $this->api->get_html('form'));
      $this->assign('may_quote',          $_may_quote);
      $this->assign('parent_id',          $_parent_id);
      $this->assign('action',             $url->get_string());
      $this->assign('max_usernamelength', cfg('max_usernamelength'));
      $this->assign('max_subjectlength',  cfg('max_subjectlength'));
      $this->assign_by_ref('message', $_message);
      $this->render(dirname(__FILE__).'/compose.tmpl');
      $this->api->set_title($_message->get_subject());
    }


    /**
     * Shows a form for editing a message. The values given in $_message are
     * filled into the fields, with the values from $_quoted inserted as a
     * quote.
     */
    function show_compose_quoted(&$_message, &$_parent_msg) {
      // Add "Posting written by ... on ..." before the quoted stuff.
      if ($_parent_msg->is_active()) {
        $text  = sprintf(_('%s wrote on %s:'),
                         $_parent_msg->get_username(),
                         $_parent_msg->get_created_time());
        $text .= "\n\n";
        $text .= $_parent_msg->get_quoted_body();
        $text .= "\n\n";
      }
      $_message->set_body($text . $_message->get_body());

      $this->show_compose($_message, $_parent_msg->get_id(), FALSE);
    }


    /**
     * Shows a form for editing a reply to the given message.
     */
    function show_compose_reply(&$_parent_msg) {
      $message = new Posting;

      // Prepend 'Re: ' if necessary
      if (strpos($_parent_msg->get_subject(), _('Re: ')) !== 0) {
        $subject = _('Re: ') . $_parent_msg->get_subject();
        $message->set_subject(substr($subject, 0, cfg('max_subjectlength')));
      }
      else
        $message->set_subject($_parent_msg->get_subject());

      $this->show_compose($message, $_parent_msg->get_id(), TRUE);
    }


    /* Show a preview form of the message. */
    function show_preview(&$_message, $_parent_id, $_may_quote) {
      $url  = new FreechURL;
      $url->set_var('forum_id',  $this->api->forum()->get_id());
      $url->set_var('parent_id', $_parent_id);

      $this->clear_all_assign();
      $this->assign_by_ref('onsubmit_js', $this->api->get_js('onsubmit'));
      $this->assign_by_ref('form_html',   $this->api->get_html('form'));
      $this->assign_by_ref('may_quote',   $_may_quote);
      $this->assign_by_ref('parent_id',   (int)$_parent_id);
      $this->assign_by_ref('pagetitle', _('Preview'));
      $this->assign_by_ref('action',    $url->get_string());
      $this->assign_by_ref('message',   $_message);
      $this->render(dirname(__FILE__).'/preview.tmpl');
      $this->api->set_title($_message->get_subject());
    }
  }
?>
