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
  /**
   * Prints an index bar. An index bar is any bar with links to other pages.
   */
  class IndexBarPrinter {
    var $print_strategy;
    var $smarty;
    var $items;
    
    /// Constructor.
    /**
     * /param $_strategy The strategy to be used to draw the index bar.
     * /param $_args     The url to the page.
     */
    function IndexBarPrinter(&$_smarty, $_strategy, $_args = array()) {
      $classname = "IndexBarStrategy_" . $_strategy;
      $this->print_strategy = &new $classname($_args);
      if (!$this->print_strategy)
        die("IndexBarPrinter::IndexBarPrinter(): Invalid strategy.");
      
      $this->smarty = $_smarty;
      $this->items  = array();
    }
    
    
    /// Adds a link, text or separator to the index.
    /**
     * Adds a link, text or separator to the index.
     * /param $_text The link name (or number), or NULL if a separator.
     * /param $_url  The url to the page, or NULL if not a link.
     */
    function _add_item($_text = '', $_url = '') {
      $item[text] = $_text;
      if ($_url)
        $item[url] = $_url->get_string();
      array_push($this->items, $item);
    }
    
    
    /// Prints the index bar using Smarty.
    /**
     * The bar is printed to the smarty instance.
     */
    function show() {
      if (count($this->items) <= 0) {
        $this->print_strategy->foreach_link(array(&$this, '_add_item'));
        $this->smarty->clear_all_assign();
        $this->smarty->assign_by_ref('items', $this->items);
      }
      $this->smarty->display('indexbar.tmpl');
      print("\n");
    }
  }
?>