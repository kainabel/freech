<?php
  /*
  Freech.
  Copyright (C) 2003-2008 Samuel Abels, <http://debain.org>

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
class PollController extends Controller {
  function show_error($_error) {
    $this->clear_all_assign();
    $this->assign_by_ref('error', $_error);
    $this->render('error.inc.tmpl');
  }


  function show_form($_poll, $_error = '') {
    $url = new FreechURL;
    $url->set_var('action', 'poll_submit');

    while ($_poll->n_options() < 2)
      $_poll->add_option('');

    $this->clear_all_assign();
    $this->assign_by_ref('action', $url->get_string());
    $this->assign_by_ref('poll',   $_poll);
    $this->assign_by_ref('error',  $_error);
    $this->render(dirname(__FILE__).'/form.tmpl');
  }


  function get_poll($_poll, $_ack = '') {
    $url = new FreechURL;
    $url->set_var('action',   'poll_vote');
    $url->set_var('forum_id', $_poll->get_forum_id());

    $result_url = $_poll->get_url();
    $result_url->set_var('result', 1);

    $this->clear_all_assign();
    $this->assign_by_ref('action',     $url->get_string());
    $this->assign_by_ref('poll',       $_poll);
    $this->assign_by_ref('result_url', $result_url->get_string());
    $this->assign_by_ref('ack',        $_ack);
    return $this->smarty->fetch(dirname(__FILE__).'/poll.tmpl');
  }


  function get_poll_result($_poll, $_ack = '', $_hint = '') {
    $this->clear_all_assign();
    $this->assign_by_ref('poll', $_poll);
    $this->assign_by_ref('ack',  $_ack);
    $this->assign_by_ref('hint', $_hint);
    return $this->smarty->fetch(dirname(__FILE__).'/poll_result.tmpl');
  }
}
?>
