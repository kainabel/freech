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
class PollOption {
  function PollOption($_name = '', $_id = NULL) {
    $this->id   = $_id;
    $this->name = trim($_name);
  }
}

class Poll extends PostingDecorator {
  function Poll($_posting, $_forum) {
    $this->PostingDecorator($_posting, $_forum);
    if ($_posting->get_renderer() != 'poll'
      && $_posting->get_renderer() != 'multipoll')
      $this->set_allow_multiple(FALSE);
    $this->options     = array();
    $this->results     = array();
    $this->max_options = 20;
  }


  function set_title($_title) {
    $this->posting->set_body(lang('poll', array('title' => $_title)));
    return $this->posting->set_subject($_title);
  }


  function get_title() {
    return $this->posting->get_subject();
  }


  function set_subject($_subject) {
    return $this->posting->set_subject($_subject);
  }


  function get_subject() {
    return $this->posting->get_subject();
  }


  function get_body_html() {
    if (!$this->is_active())
      return '';
    // Fetch the poll from the database.
    $poll_id = $this->posting->get_id();
    $poll    = _get_poll_from_id($this->forum, $poll_id);
    $user    = $this->forum->get_current_user();
    $db      = $this->forum->_get_db();
    $printer = new PollPrinter($this->forum);

    if ($user->is_anonymous())
      return $printer->get_poll_result($poll, '', lang('poll_anonymous'));

    if ($_GET['result'])
      return $printer->get_poll_result($poll);

    if ($_GET['accept'])
      $hint = lang('poll_vote_accepted');

    if (_poll_did_vote($db, $user, $poll_id))
      return $printer->get_poll_result($poll, $hint);
    return $printer->get_poll($poll);
  }


  function apply_block() {
    return $this->posting->apply_block();
  }


  function get_option($_id) {
    foreach ($this->options as $option)
      if ($option->id == $_id)
        return $option->name;
    die('no such option');
  }


  function get_options() {
    $options = array();
    foreach ($this->options as $option)
      array_push($options, $option->name);
    return $options;
  }


  function get_filled_options() {
    $options = array();
    foreach ($this->options as $option)
      if (trim($option->name) != '')
        array_push($options, $option->name);
    return $options;
  }


  function get_option_map() {
    $options = array();
    foreach ($this->options as $option)
      if (trim($option->name) != '')
        $options[$option->id] = $option->name;
    ksort($options);
    return $options;
  }


  function has_option_id($_id) {
    foreach ($this->options as $option)
      if ($option->id == $_id)
        return TRUE;
    return FALSE;
  }


  function add_option($_option) {
    array_push($this->options, $_option);
  }


  function n_options() {
    return count($this->options);
  }


  function get_max_options() {
    return $this->max_options;
  }


  function has_duplicate_options() {
    $options = array();
    foreach ($this->options as $option)
      if ($option->name != '' && $options[$option->name])
        return TRUE;
      else
        $options[$option->name] = TRUE;
    return FALSE;
  }


  function add_result($_option_id, $_votes) {
    $this->results[(int)$_option_id] = (int)$_votes;
  }


  function get_results() {
    return $this->results;
  }


  function get_allow_multiple() {
    if ($this->posting->get_renderer() == 'multipoll')
      return TRUE;
    elseif ($this->posting->get_renderer() == 'poll')
      return FALSE;
    die('Item is not a poll.');
  }


  function set_allow_multiple($_allow) {
    if ($_allow)
      $this->posting->set_renderer('multipoll');
    else
      $this->posting->set_renderer('poll');
  }


  function set_signature() {
    // Prevent a signature from being added.
  }


  function get_signature() {
    return '';
  }


  function is_editable() {
    return FALSE;
  }


  function check() {
    if ($this->get_title() == '')
      return lang('poll_title_missing');
    if (strlen($this->get_title()) > cfg('max_subjectlength'))
      return lang('poll_title_too_long');
    $options = $this->get_filled_options();
    if (count($options) < 2)
      return lang('poll_too_few_options');
    if (count($options) > $this->get_max_options())
      return lang('poll_too_many_options');
    foreach ($options as $option)
      if (strlen($option) > cfg('max_subjectlength'))
        return lang('poll_option_too_long');
    if ($this->has_duplicate_options())
      return lang('poll_duplicate_option');
    return NULL;
  }
}
?>
