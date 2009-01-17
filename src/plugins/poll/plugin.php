<?php
/*
Plugin:      Poll
Version:     0.1
Author:      Samuel Abels
Description: Adds support for pollings.
Constructor: poll_init
Active:      1
*/
include_once dirname(__FILE__).'/poll.class.php';
include_once dirname(__FILE__).'/poll_printer.class.php';

function poll_init($forum) {
  // Register a class that is responsible for formatting the posting object
  // that holds the poll.
  $forum->register_renderer('multipoll', 'Poll');
  $forum->register_renderer('poll',      'Poll');

  // For anonymous users we don't need to do anything else.
  $user = $forum->get_current_user();
  if ($user->is_anonymous())
    return;

  $forum->get_eventbus()->signal_connect('on_run_before', 'poll_on_run');

  // Register our extra actions.
  $forum->register_action('poll_add',    'poll_on_add');
  $forum->register_action('poll_submit', 'poll_on_submit');
  $forum->register_action('poll_vote',   'poll_on_vote');
}


function poll_on_run($forum) {
  if (!$forum->get_current_group()->may('write'))
    return;

  // Add a link to the poll button in the index bar.
  $forum_id = $forum->get_current_forum_id();
  $url      = new URL('?', cfg('urlvars'), lang('poll_create'));
  $url->set_var('forum_id', $forum_id);
  $url->set_var('action'  , 'poll_add');
  $forum->page_links()->add_link($url, 400);
}


function poll_on_add($forum) {
  $printer = new PollPrinter($forum);
  $posting = new Posting;
  $poll    = new Poll($posting, $forum);
  $poll->set_forum_id($forum->get_current_forum_id());

  $forum->breadcrumbs()->add_separator();
  $forum->breadcrumbs()->add_text(lang('poll_create'));

  $max_polls      = cfg('max_polls', 2);
  $max_polls_time = time() - cfg('max_polls_time', 60 * 60 * 24);
  if (_n_polls_since($forum->_get_db(),
                     $forum->get_current_user(),
                     $max_polls_time) >= $max_polls)
    return $printer->show_error(lang('poll_limit_reached'));
  $printer->show_form($poll);
}


function poll_on_submit($forum) {
  $printer = new PollPrinter($forum);
  $poll    = _poll_get_from_post();

  // Add a new option to the poll form.
  if ($_POST['add_row']) {
    $option = new PollOption();
    if ($poll->n_options() >= $poll->get_max_options())
      return $printer->show_form($poll, lang('poll_too_many_options'));
    $poll->add_option($option);
    return $printer->show_form($poll);
  }

  // Create the new poll.
  elseif ($_POST['send']) {
    // Sanity check.
    $err = $poll->check();
    if ($err)
      return $printer->show_form($poll, $err);

    // Save the poll.
    $poll_id = _save_poll($forum, $poll);
    if (!$poll_id)
      return $printer->show_form($poll, lang('poll_save_failed'));

    // Refer to the poll.
    $forum->_refer_to_posting_id($poll->get_id());
  }
}


function poll_on_vote($forum) {
  $poll_id = (int)$_POST['poll_id'];
  $poll    = _get_poll_from_id($forum, $poll_id);
  $user    = $forum->get_current_user();
  $db      = $forum->_get_db();
  $printer = new PollPrinter($forum);

  if (!$_POST['options'])
    $forum->_refer_to_posting_id($poll->get_id());

  // Make sure that a user does not vote twice.
  if (_poll_did_vote($db, $user, $poll_id))
    return $printer->show_poll_result($poll);

  // Depending on whether it is allowed to check multiple values, we get
  // either a single value or an array of values. If a single value is
  // received, wrap it in an array for uniform access.
  $option_ids = array();
  if (!$poll->get_allow_multiple()) {
    // Make sure that we have at most one vote if the poll does not allow
    // for multiple boxes to be checked.
    if (is_array($_POST['options']))
      die('Multiple votes where only one is allowed.');
    array_push($option_ids, (int)$_POST['options']);
  }
  else {
    if (!is_array($_POST['options']))
      die('Type error; poll expected a list of votes but got only one value.');
    foreach ($_POST['options'] as $option_id)
      array_push($option_ids, (int)$option_id);
  }

  foreach ($option_ids as $option_id) {
    // Make sure that the casted votes belong to the given poll.
    if (!$poll->has_option_id($option_id))
      die('Invalid cast!');
    // Cast the vote.
    _poll_cast($db, $user, $option_id);
  }

  // Reload the poll (to include results) and show the result.
  $accept_url = $poll->get_url();
  $accept_url->set_var('accept', 1);
  $forum->_refer_to(cfg('site_url').$accept_url->get_string());
}


/***********************************************
 * Utilities.
 ***********************************************/
function _poll_get_from_post() {
  $n_options = (int)$_POST['n_options'];
  $posting   = new Posting;
  $poll      = new Poll($posting, NULL);
  $poll->set_title($_POST['poll_title']);
  $poll->set_allow_multiple($_POST['allow_multiple'] == 'on');
  $poll->set_forum_id((int)$_POST['forum_id']);
  for ($i = 0; $i < $n_options; $i++) {
    $option = new PollOption($_POST["poll_option$i"]);
    $poll->add_option($option);
  }
  return $poll;
}


function _save_poll($forum, $poll) {
  $forum_id = $forum->get_current_forum_id();
  $user     = $forum->get_current_user();
  $group    = $forum->get_current_group();
  $subject  = $poll->get_subject();
  $subject  = lang('poll', array('title' => $subject));
  $poll->set_subject($subject);
  $poll->set_from_user($user);
  $poll->set_from_group($group);

  // Save the poll.
  $db = $forum->_get_db();
  $db->StartTrans();
  $forum->get_forumdb()->insert($forum_id, NULL, $poll);

  // Now save the corresponding poll options.
  foreach ($poll->get_filled_options() as $option)
    _save_poll_option($db, $poll->get_id(), $option);
  $db->CompleteTrans();

  return $poll->get_id();
}


function _n_polls_since($db, $user, $_since = 0) {
  $sql  = 'SELECT COUNT(*) n_polls';
  $sql .= ' FROM {t_posting}';
  $sql .= ' WHERE user_id={user_id}';
  $sql .= " AND (renderer='poll' or renderer='multipoll')";
  if ($_since)
    $sql .= ' AND created > FROM_UNIXTIME({since})';
  $query = &new FreechSqlQuery($sql);
  $query->set_int('user_id', $user->get_id());
  if ($_since)
    $query->set_int('since', $_since);
  return $db->GetOne($query->sql());
}


function _save_poll_option($db, $poll_id, $option) {
  $sql  = 'INSERT INTO {t_poll_option}';
  $sql .= ' (poll_id, name)';
  $sql .= ' VALUES (';
  $sql .= ' {poll_id}, {name}';
  $sql .= ')';
  $query = &new FreechSqlQuery($sql);
  $query->set_int   ('poll_id', $poll_id);
  $query->set_string('name',    $option);
  $db->Execute($query->sql()) or die('_save_poll_option(): Insert');
  return $db->Insert_Id();
}


function _get_poll_from_id($forum, $poll_id) {
  // Load the posting first, and map it back into a poll.
  $posting = $forum->_get_posting_from_id_or_die($poll_id);
  $poll    = new Poll($posting, $forum);

  // Load the options of the poll.
  $sql  = 'SELECT * FROM {t_poll_option}';
  $sql .= ' WHERE poll_id={poll_id}';
  $query = &new FreechSqlQuery($sql);
  $query->set_int('poll_id', $poll_id);
  $db  = $forum->_get_db();
  $res = $db->Execute($query->sql()) or die('_get_poll_from_id()');

  while ($row = $res->FetchRow($res)) {
    $option = new PollOption($row[name], $row[id]);
    $poll->add_option($option);
  }

  // Now load the results.
  $sql  = 'SELECT o.id,COUNT(v.option_id) votes';
  $sql .= ' FROM {t_poll_option} o';
  $sql .= ' LEFT JOIN {t_poll_vote} v ON o.id=v.option_id';
  $sql .= ' WHERE o.poll_id={poll_id}';
  $sql .= ' GROUP BY o.id';
  $query = &new FreechSqlQuery($sql);
  $query->set_int('poll_id', $poll_id);
  $db  = $forum->_get_db();
  $res = $db->Execute($query->sql()) or die('_get_poll_from_id()');
  while ($row = $res->FetchRow())
    $poll->add_result($row[id], $row[votes]);
  return $poll;
}


function _poll_did_vote($db, $user, $poll_id) {
  $sql  = 'SELECT o.id';
  $sql .= ' FROM {t_poll_option} o';
  $sql .= ' LEFT JOIN {t_poll_vote} v ON o.id=v.option_id';
  $sql .= ' WHERE o.poll_id={poll_id} and v.user_id={user_id}';
  $query = &new FreechSqlQuery($sql);
  $query->set_int('user_id', $user->get_id());
  $query->set_int('poll_id', $poll_id);
  $res = $db->Execute($query->sql()) or die('_poll_did_vote()');
  if ($res->EOF)
    return FALSE;
  return TRUE;
}


function _poll_cast($db, $user, $option_id) {
  $sql  = 'INSERT INTO {t_poll_vote}';
  $sql .= ' (option_id, user_id)';
  $sql .= ' VALUES (';
  $sql .= ' {option_id}, {user_id}';
  $sql .= ')';
  $query = &new FreechSqlQuery($sql);
  $query->set_int('option_id', $option_id);
  $query->set_int('user_id',   $user->get_id());
  $db->Execute($query->sql()) or die('_poll_cast(): Insert');
}
?>
