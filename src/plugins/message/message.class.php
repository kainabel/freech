<?php
  /*
  Freech.
  Copyright (C) 2003-2009 Samuel Abels, <http://debain.org>

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
 * Decorates postings that are messages.
 */
class Message extends PostingDecorator {
    function _update_body_html() {
      trace('enter');
      // Perform non HTML generating formattings.
      $body = $this->get_quoted_body(0);

      // Let plugins perform formattings.
      $this->set_body_html($body);
      $this->emit('on_format_before_html', $this);
      trace('on_format_before_html completed');

      // Perform HTML generating formattings.
      $body = $this->get_body_html();
      $body = esc($body);
      $body = preg_replace('/^(&gt; [^\r\n]*)/m',
                           "<span class='quote'>$1</span>",
                           $body);
      $body = preg_replace('/  /', ' &nbsp;', $body);
      $body = nl2br($body);
      $this->set_body_html($body);

      $this->emit('on_format_after_html', $this);
      trace('leave');
    }


    function set_body_html($_html) {
      $this->body_html = $_html;
    }


    function get_body_html() {
      if ($this->body_html === NULL)
        $this->_update_body_html();
      return $this->body_html;
    }
}
?>
