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
  class RSSPrinter extends PrinterBase {
    var $messages;
    var $title;
    var $descr;
    var $url;
    var $countrycode;
    
    function RSSPrinter(&$_forum) {
      $this->PrinterBase(&$_forum);
      $this->messages = array();
    }
    
    
    function set_title($_title) {
      $this->title = $_title;
    }
    
    
    function set_description($_descr) {
      $this->descr = $_descr;
    }
    
    
    function set_base_url($_url) {
      $this->url = $_url;
    }
    
    
    function set_language($_countrycode) {
      $this->countrycode = $_countrycode;
    }
    
    
    function _append_row(&$_message, $_forum_id) {
      if (!$_message->is_active())
        return;
      
      // Required to enable correct formatting of the message.
      $_message->set_body(preg_replace("/&nbsp;/", " ", $_message->get_body()));
      
      // Append everything to a list.
      array_push($this->messages, $_message);
    }
    
    
    function show($_forum_id, $_off, $_n_entries) {
      $this->messages = array();
      
      if ($_n_entries < 1)
        $_n_entries = cfg("rss_items");
      if ($_n_entries > cfg("rss_maxitems"))
        $n_entries = cfg("rss_maxitems");
      
      $this->db->foreach_latest_message($_forum_id,
                                        $_off,
                                        $_n_entries,
                                        FALSE,
                                        array(&$this, '_append_row'),
                                        $_forum_id);
      
      $this->clear_all_assign();
      $this->assign_by_ref('title',       $this->title);
      $this->assign_by_ref('link',        $this->url);
      $this->assign_by_ref('language',    $this->countrycode);
      $this->assign_by_ref('description', $this->descr);
      $this->assign_by_ref('messages',    $this->messages);
      $this->render('../../rss.tmpl');
    }
  }
?>
