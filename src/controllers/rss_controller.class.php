<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels

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
  class RSSController extends Controller {
    var $postings;
    var $title;
    var $descr;
    var $url;
    var $countrycode;

    function RSSController(&$_api) {
      $this->Controller($_api);
      $this->postings = array();
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


    function _append_row(&$_posting, $_data) {

      // don't show postings from inactive forums
      if ( array_search($_posting->get_forum_id(), $this->api->bad_id_list)
           !== FALSE )
        return;

      // don't show blocked/inactive postings from active forums
      if (!$_posting->is_active())
        return;

      // Required to enable correct formatting of the posting.
      $_posting->set_body(preg_replace('/&nbsp;/',
                                       ' ',
                                       $_posting->get_body()));

      // Append everything to a list.
      array_push($this->postings, $_posting);
    }


    function show($_forum_id, $_off, $_n_entries) {
      $this->postings = array();

      if ($_n_entries < 1)
        $_n_entries = cfg('rss_items');
      $n_entries = min(cfg('rss_max_items'), $_n_entries);

      $this->forumdb->foreach_latest_posting((int)$_forum_id,
                                             (int)$_off,
                                             (int)$n_entries,
                                             FALSE,
                                             FALSE,
                                             array($this, '_append_row'),
                                             '');

      $this->clear_all_assign();
      $this->assign_by_ref('title',       $this->title);
      $this->assign_by_ref('link',        $this->url);
      $this->assign_by_ref('site',        cfg('site_url'));
      $this->assign_by_ref('show_message',cfg('rss_show_message'));
      $this->assign_by_ref('language',    $this->countrycode);
      $this->assign_by_ref('description', $this->descr);
      $this->assign_by_ref('postings',    $this->postings);
      $this->render_php('rss.php.tmpl');
    }
  }
?>
