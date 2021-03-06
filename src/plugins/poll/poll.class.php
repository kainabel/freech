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
class PollOption {
  function PollOption($_name = '', $_id = NULL) {
    $this->id   = $_id;
    $this->name = trim($_name);
  }
}

class Poll extends PostingDecorator {
  function Poll(&$_posting, &$_forum) {
    $this->PostingDecorator($_posting, $_forum);
    if ($_posting->get_renderer() != 'poll'
      && $_posting->get_renderer() != 'multipoll')
      $this->set_allow_multiple(FALSE);
    $this->options     = array();
    $this->results     = array();
    $this->max_options = 20;
  }


  function set_title($_title) {
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

  function set_body($_description) {
    $body = $this->get_nice_body($_description);
    return $this->posting->set_body($body);
  }

  function get_body() {
    return $this->posting->get_body();
  }


  function has_description() {
    return ($this->get_subject() != $this->get_body());
  }

  function get_description_html() {

    $body = $this->get_body();
    $body = $this->get_nice_body($body);

    // Perform HTML generating formattings.
    $body = esc($body);
    $body = preg_replace('/  /', ' &nbsp;', $body);
    $body = nl2br($body);

    //TODO: connect to plugin linkify

    return $body;
  }

  function get_body_html() {

    // bypassed, if permitted
    if (!$this->is_active() && !$this->api->group()->may('bypass'))
      return '';

    include_once dirname(__FILE__).'/poll_controller.class.php';
    // Fetch the poll from the database.
    $poll_id    = $this->posting->get_id();
    $poll       = _get_poll_from_id($this->api, $poll_id);
    $user       = $this->api->user();
    $db         = $this->api->db();
    $controller = new PollController($this->api);

    if ($user->is_anonymous()) {
      $controller->add_hint(new \hint\Hint(_('Please log in to cast your vote.')));
      return $controller->get_poll_result($poll);
    }

    if ($_GET['result'] or cfg('set_read_only'))
      return $controller->get_poll_result($poll);

    if ($_GET['accept'])
      $controller->add_hint(new \hint\Hint(_('Thank You for your vote.')));

    if (_poll_did_vote($db, $user, $poll_id))
      return $controller->get_poll_result($poll);
    return $controller->get_poll($poll);
  }

  //TODO: unused function?
  function apply_block() {
    return $this->posting->apply_block();
  }


  function get_option($_id) {
    foreach ($this->options as $option)
      if ($option->id == $_id)
        return $option->name;
    die('no such option');
  }


  function &get_options() {
    $options = array();
    foreach ($this->options as $option)
      array_push($options, $option->name);
    return $options;
  }


  function &get_filled_options() {
    $options = array();
    foreach ($this->options as $option)
      if (trim($option->name) != '')
        array_push($options, $option->name);
    return $options;
  }


  function &get_option_map() {
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
    foreach ($this->options as $option) {
      $name = strtoupper($option->name);
      if ($name != '' && $options[$name])
        return TRUE;
      else
        $options[$name] = TRUE;
    }
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


  function is_editable() {
    return FALSE;
  }

  function get_nice_body($body) {
    // input clean up
    return preg_replace("~\n((?:[\s\n\xa0]+)\n){1,}~", "\n\n", $body);
  }

  function check() {
    if ($this->get_title() == '')
      return _('Please enter a title.');
    if (strlen($this->get_title()) > cfg('max_subjectlength'))
      return _('The poll title is too long.');
    $options = $this->get_filled_options();
    if (count($options) < 2)
      return _('Please add more options.');
    if (count($options) > $this->get_max_options())
      return _('Too many options.');
    foreach ($options as $option)
      if (strlen($option) > cfg('max_subjectlength'))
        return _('An option is too long.');
    if ($this->has_duplicate_options())
      return _('The poll has duplicate options.');
    if (strlen($this->get_body()) > cfg('max_msglength'))
      return sprintf(_('Your description exceeds the maximum length'
                     . ' of %d characters.'),
                     cfg('max_msglength'));
    if (!is_utf8($this->get_subject())
      || !is_utf8($this->get_body()))
      return _('Your message contains invalid characters.');

    // mark on submit/send as empty description
    if ((ctype_space($this->get_body() . "\n")) && ($_POST['send'])) {
      $this->set_body( sprintf(_('Poll: %s'), $this->get_subject()) );
    }
    return NULL;
  }
}
?>
