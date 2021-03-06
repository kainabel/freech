<?php
  /*
  Freech.
  Copyright (C) 2003-2008 Samuel Abels

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
    $this->add_hint(new \hint\Error($_error));
    $this->render_php('error.inc.php.tmpl');
  }


  function show_form(&$_poll) {
    $url = new FreechURL;
    $url->set_var('action', 'poll_submit');

    while ($_poll->n_options() < 2)
      $_poll->add_option('');

    $this->clear_all_assign();
    $this->assign       ('action', $url->get_string());
    $this->assign_by_ref('poll',   $_poll);
    $this->render_php(dirname(__FILE__).'/form.php.tmpl');
  }


  function get_poll(&$_poll) {
    $url = new FreechURL;
    $url->set_var('action',   'poll_vote');
    $url->set_var('forum_id', $_poll->get_forum_id());

    $result_url = $_poll->get_url();
    $result_url->set_var('result', 1);

    $this->clear_all_assign();
    $this->assign       ('action',     $url->get_string());
    $this->assign       ('result_url', $result_url->get_string());
    $this->assign_by_ref('poll',       $_poll);
    $result = $this->fetch_php(dirname(__FILE__).'/poll.php.tmpl');
    return $result;
  }


  function get_poll_result(&$_poll) {
    $this->clear_all_assign();
    $this->assign_by_ref('poll', $_poll);
    $result = $this->fetch_php(dirname(__FILE__).'/poll_result.php.tmpl');
    return $result;
  }
}
?>
