<?php
  /*
  Tefinch.
  Copyright (C) 2003 Samuel Abels, <spam debain org>

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
  include_once 'string.inc.php';
  include_once 'message.inc.php';
  
  
  class RSSPrinter {
    var $smarty;
    var $db;
    var $_messages;
    var $_title;
    var $_descr;
    var $_url;
    var $_countrycode;
    
    function RSSPrinter($_smarty, $_db) {
      $this->smarty    = $_smarty;
      $this->db        = $_db;
      $this->_messages = array();
    }
    
    
    function set_title($_title) {
      $this->_title = $_title;
    }
    
    
    function set_description($_descr) {
      $this->_descr = $_descr;
    }
    
    
    function set_base_url($_url) {
      $this->_url = $_url;
    }
    
    
    function set_language($_countrycode) {
      $this->_countrycode = $_countrycode;
    }
    
    
    function _print_row($_row, $_forum_id) {
      global $cfg;
      global $lang;
      
      if (!$_row->active)
        return;
      $_row->forum_id = $_forum_id;
      $_row->text     = message_format($_row->text);
      $_row->text     = preg_replace("/&nbsp;/", " ", $_row->text);
      $_row->url = $this->_url
                 . "?forum_id=$_row->forum_id&amp;msg_id=$_row->id&amp;read=1";
      array_push($this->_messages, $_row);
    }
    
    
    function show($_forum_id, $_off, $_n_entries) {
      global $cfg;
      global $lang;
      
      $this->_messages = array();
      
      if ($_n_entries < 1)
        $_n_entries = $cfg[rss_items];
      if ($_n_entries > $cfg[rss_maxitems])
        $n_entries = $cfg[rss_maxitems];
      
      $this->db->foreach_latest_entry($_forum_id,
                                      $_off,
                                      $_n_entries,
                                      FALSE,
                                      array(&$this, '_print_row'),
                                      $_forum_id);
      $this->smarty->clear_all_assign();
      $this->smarty->assign_by_ref('title',       $this->_title);
      $this->smarty->assign_by_ref('link',        $this->_url);
      $this->smarty->assign_by_ref('language',    $this->_countrycode);
      $this->smarty->assign_by_ref('description', $this->_descr);
      $this->smarty->assign_by_ref('messages',    $this->_messages);
      $this->smarty->display('../../rss.tmpl');
    }
  }
?>
