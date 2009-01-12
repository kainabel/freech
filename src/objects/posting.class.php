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
  define("MESSAGE_RELATION_UNKNOWN",         0);
  define("MESSAGE_RELATION_PARENT_STUB",     1);
  define("MESSAGE_RELATION_PARENT_UNFOLDED", 2);
  define("MESSAGE_RELATION_PARENT_FOLDED",   3);
  define("MESSAGE_RELATION_BRANCHEND_STUB",  4);
  define("MESSAGE_RELATION_BRANCHEND",       5);
  define("MESSAGE_RELATION_CHILD_STUB",      6);
  define("MESSAGE_RELATION_CHILD",           7);

  /**
   * Represents a posting in the forum and all associated data.
   */
  class Posting {
    // Constructor.
    function Posting() {
      $this->clear();
    }


    // Resets all values.
    function clear() {
      $this->fields = array();
      $this->fields[created]      = time();
      $this->fields[updated]      = $this->fields[created];
      $this->fields[relation]     = MESSAGE_RELATION_UNKNOWN;
      $this->fields[renderer]     = 'message';
      $this->fields[is_active]    = TRUE;
      $this->fields[priority]     = 0;
      $this->fields[user_id]      = 2; // Anonymous user.
      $this->fields[allow_answer] = TRUE;
      $this->fields[ip_hash]      = $this->_ip_hash($_SERVER['REMOTE_ADDR']);
      $this->fields[indent]       = array();
    }


    // Sets all values from a given database row.
    function set_from_db(&$_db_row) {
      if (!is_array($_db_row))
        die("Posting:set_from_db(): Non-array.");
      $this->clear();
      $this->fields[id]               = $_db_row[id];
      $this->fields[forum_id]         = $_db_row[forum_id];
      $this->fields[thread_id]        = $_db_row[thread_id];
      $this->fields[path]             = $_db_row[path];
      $this->fields[priority]         = $_db_row[priority];
      $this->fields[user_id]          = $_db_row[user_id];
      $this->fields[username]         = $_db_row[username];
      $this->fields[current_username] = $_db_row[current_username];
      $this->fields[user_is_special]  = $_db_row[user_is_special];
      $this->fields[user_icon]        = $_db_row[user_icon];
      $this->fields[user_icon_name]   = $_db_row[user_icon_name];
      $this->fields[renderer]         = $_db_row[renderer];
      $this->fields[subject]          = $_db_row[subject];
      $this->fields[body]             = $_db_row[body];
      $this->fields[updated]          = $_db_row[updated];
      $this->fields[created]          = $_db_row[created];
      $this->fields[n_children]       = $_db_row[n_children];
      $this->fields[n_descendants]    = $_db_row[n_descendants];
      $this->fields[ip_hash]          = $_db_row[ip_hash];
      if (isset($_db_row[relation]))
        $this->fields[relation]       = $_db_row[relation];
      $this->fields[is_parent]        = $_db_row[is_parent];
      $this->fields[is_active]        = $_db_row[is_active];
      if (isset($_db_row[allow_answer]))
        $this->fields[allow_answer]   = $_db_row[allow_answer];
    }


    function &_ip_hash($_ip) {
      // Note that this needs to work with both, IPv4 and IPv6.
      $ip_net = preg_replace('/[\d\w]+$/', '', $_ip);
      return md5($ip_net . cfg("salt"));
    }


    function _get_thread_id() {
      return $this->fields[thread_id];
    }


    function _get_path() {
      return $this->fields[path];
    }


    // Set a unique id for the posting.
    function set_id($_id) {
      $this->fields[id] = $_id * 1;
    }


    function get_id() {
      return $this->fields[id];
    }


    function set_forum_id($_forum_id) {
      $this->fields[forum_id] = $_forum_id * 1;
    }


    function get_forum_id() {
      return $this->fields[forum_id];
    }


    function is_parent() {
      return $this->fields[is_parent];
    }


    function set_priority($_priority) {
      $this->fields[priority] = $_priority * 1;
    }


    function get_priority() {
      return $this->fields[priority];
    }


    function set_user_id($_user_id) {
      $this->fields[user_id] = $_user_id;
    }


    function &get_user_id() {
      return $this->fields[user_id];
    }


    function set_from_group($_group) {
      $this->set_user_icon($_group->get_icon());
      $this->set_user_icon_name($_group->get_name());
      $this->set_user_is_special($_group->is_special());
    }


    function set_from_user($_user) {
      $this->set_user_id($_user->get_id());
      $this->set_username($_user->get_name());
      $this->set_signature($_user->get_signature());
    }


    function set_user_is_special($_special) {
      $this->fields[user_is_special] = $_special;
    }


    function get_user_is_special() {
      return $this->fields[user_is_special];
    }


    function get_user_is_anonymous() {
      return $this->fields[user_id] == cfg('anonymous_user_id');
    }


    function set_user_icon($_icon) {
      $this->fields[user_icon] = $_icon;
    }


    function get_user_icon() {
      return $this->fields[user_icon];
    }


    function set_user_icon_name($_name) {
      $this->fields[user_icon_name] = $_name;
    }


    function get_user_icon_name() {
      return $this->fields[user_icon_name];
    }


    function set_username($_username) {
      $this->fields[username] = preg_replace("/\s+/", " ", trim($_username));
    }


    function get_username() {
      return $this->fields[username];
    }


    function set_subject($_subject) {
      $this->fields[subject] = preg_replace("/\s+/", " ", trim($_subject));
    }


    function &get_subject() {
      return $this->fields[subject];
    }


    function set_body($_body) {
      $this->fields[body] = trim($_body);
    }


    function &get_body() {
      return $this->fields[body];
    }


    function set_signature($_signature) {
      $this->fields[signature] = trim($_signature);
    }


    function &get_signature() {
      return $this->fields[signature];
    }


    function set_renderer($_name) {
      $this->fields[renderer] = trim($_name);
    }


    function get_renderer() {
      return $this->fields[renderer];
    }


    function get_url() {
      $url = new URL('?', cfg('urlvars'), $this->get_subject());
      $url->set_var('action',   'read');
      $url->set_var('msg_id',   $this->get_id());
      $url->set_var('forum_id', $this->get_forum_id());
      if (cfg('remember_page'))
        $url->set_var('hs', (int)$_GET[hs]);
      return $url;
    }


    function get_url_html() {
      return $this->get_url()->get_html();
    }


    function get_url_string() {
      return $this->get_url()->get_string();
    }


    // The url behind the "+/-" toggle button.
    function get_fold_url() {
      if ($_GET['action'] == 'read') {
        $url = $this->get_url();
        $url->set_var('showthread', -1);
      }
      elseif ($_GET['action'] == 'user_postings') {
        $url = new URL('?', cfg('urlvars'));
        $url->set_var('action',   'user_postings');
        $url->set_var('username', $_GET['username']);
        if ($_GET['hs'])
          $url->set_var('hs', (int)$_GET[hs]);
        $url->set_var('forum_id',        $this->get_forum_id());
        $url->set_var('user_postings_c', $this->get_id());
      }
      else {
        $url = new URL('?', cfg('urlvars'));
        if ($_GET['hs'])
          $url->set_var('hs', (int)$_GET[hs]);
        $url->set_var('forum_id', $this->get_forum_id());
        $url->set_var('c',        $this->get_id());
      }
      $url->set_var('refer_to', $_SERVER['REQUEST_URI']);
      return $url;
    }


    function get_fold_url_string() {
      return $this->get_fold_url()->get_string();
    }


    // The url for locking the posting.
    function get_lock_url() {
      $url = new URL('?', cfg('urlvars'));
      $url->set_var('action',   'posting_lock');
      $url->set_var('msg_id',   $this->get_id());
      $url->set_var('refer_to', $_SERVER['REQUEST_URI']);
      return $url;
    }


    function get_lock_url_string() {
      return $this->get_lock_url()->get_string();
    }


    // The url for unlocking the posting.
    function get_unlock_url() {
      $url = new URL('?', cfg('urlvars'));
      $url->set_var('action',   'posting_unlock');
      $url->set_var('msg_id',   $this->get_id());
      $url->set_var('refer_to', $_SERVER['REQUEST_URI']);
      return $url;
    }


    function get_unlock_url_string() {
      return $this->get_unlock_url()->get_string();
    }


    // The url for changing the posting priority.
    function get_prioritize_url($_priority) {
      $url = new URL('?', cfg('urlvars'));
      $url->set_var('action',   'posting_prioritize');
      $url->set_var('msg_id',   $this->get_id());
      $url->set_var('priority', (int)$_priority);
      $url->set_var('refer_to', $_SERVER['REQUEST_URI']);
      return $url;
    }


    function get_prioritize_url_string($_priority) {
      return $this->get_prioritize_url($_priority)->get_string();
    }


    function get_user_profile_url() {
      if (isset($this->fields['current_username']))
        $username = $this->fields['current_username'];
      else
        $username = $this->get_username();
      $profile_url = new URL('?', cfg('urlvars'));
      $profile_url->set_var('action',   'user_profile');
      $profile_url->set_var('username', $username);
      return $profile_url;
    }


    function get_user_profile_url_string() {
      return $this->get_user_profile_url()->get_string();
    }


    function &get_hash() {
      return md5($this->get_username()
               . $this->get_subject()
               . $this->get_body());
    }


    function get_created_unixtime() {
      return $this->fields[created];
    }


    // Returns the formatted time.
    function get_created_time($_format = '') {
      if (!$_format)
        $_format = lang("dateformat");
      return date($_format, $this->fields[created]);
    }


    // Returns whether the row was newly created in the last X minutes.
    function is_new() {
      return (time() - $this->fields[created] < cfg("new_post_time"));
    }


    // Returns a number between 0 (old) and 100 (new) depending on the
    // time since the postings was posted.
    function get_newness() {
      if (!$this->is_new())
        return 0;
      $oldness = time() - $this->fields[created];
      return 100 - ($oldness / cfg("new_post_time") * 100);
    }


    function get_newness_hex($_reverse = FALSE) {
      $value = $this->get_newness() / 100 * 255;
      if ($_reverse)
        $value = 255 - $value;
      return substr("00" . dechex($value), -2);
    }


    function set_updated_unixtime($_time) {
      $this->fields[updated] = (int)$_time;
    }


    function get_updated_unixtime() {
      return $this->fields[updated];
    }


    // Returns the formatted time.
    function get_updated_time($_format = '') {
      if (!$_format)
        $_format = lang("dateformat");
      return date($_format, $this->fields[updated]);
    }


    function is_updated() {
      return $this->get_created_unixtime() != $this->get_updated_unixtime();
    }


    // The number of children.
    function set_n_children($_n_children) {
      $this->fields[n_children] = $_n_children;
    }


    function get_n_children() {
      if ($this->fields[relation] != MESSAGE_RELATION_PARENT_STUB
        && $this->fields[relation] != MESSAGE_RELATION_PARENT_UNFOLDED
        && $this->fields[relation] != MESSAGE_RELATION_PARENT_FOLDED)
        die("Posting:get_n_children(): This function must not be called on"
          . " non-parent rows.");
      return $this->fields[n_children] * 1;
    }


    function get_ip_address_hash($maxlen = NULL) {
      if ($maxlen)
        return substr($this->fields['ip_hash'], 0, $maxlen);
      return $this->fields['ip_hash'];
    }


    // The relation is the relation in the tree, see the define()s above.
    function set_relation($_relation) {
      $this->fields[relation] = $_relation;
    }


    function get_relation() {
      return $this->fields[relation];
    }


    function set_active($_active = TRUE) {
      $this->fields[is_active] = $_active;
    }


    function is_active() {
      return $this->fields[is_active];
    }


    function is_editable() {
      return TRUE;
    }


    function apply_block() {
      if ($this->is_active())
        return;
      $this->set_subject(lang('blockedtitle'));
      $this->set_username('------');
      $this->set_body('');
    }


    function set_allow_answer($_allow = TRUE) {
      $this->fields[allow_answer] = $_allow;
    }


    function get_allow_answer() {
      return $this->fields[allow_answer];
    }


    function has_thread() {
      if ($this->fields[relation] != MESSAGE_RELATION_PARENT_STUB
        && $this->fields[relation] != MESSAGE_RELATION_PARENT_UNFOLDED
        && $this->fields[relation] != MESSAGE_RELATION_PARENT_FOLDED)
        return TRUE;
      return $this->fields[n_descendants] != 0;
    }


    function set_indent($_indent) {
      $this->fields[indent] = $_indent;
    }


    function get_indent() {
      return $this->fields[indent];
    }


    function set_selected($_selected = TRUE) {
      $this->fields[selected] = $_selected;
    }


    function is_selected() {
      return $this->fields[selected];
    }


    function check_complete() {
      // The appended "\n" on the following three lines is a workaround for a bug in PHP.
      // http://bugs.php.net/bug.php?id=30945
      if (ctype_space($this->fields[username] . "\n")
       || ctype_space($this->fields[subject]  . "\n")
       || ctype_space($this->fields[body]     . "\n"))
        return ERR_MESSAGE_INCOMPLETE;

      if (strlen($this->fields[username]) > cfg("max_usernamelength"))
        return ERR_MESSAGE_NAME_TOO_LONG;
      if (!preg_match(cfg("username_pattern"), $this->fields[username]))
        return ERR_USER_LOGIN_INVALID_CHARS;


      if (strlen($this->fields[subject]) > cfg("max_subjectlength"))
        return ERR_MESSAGE_TITLE_TOO_LONG;

      if (strlen($this->fields[body]) > cfg("max_msglength"))
        return ERR_MESSAGE_BODY_TOO_LONG;

      if (!is_utf8($this->fields[username])
        || !is_utf8($this->fields[subject])
        || !is_utf8($this->fields[body]))
        return ERR_MESSAGE_BODY_NO_UTF8;

      return 0;
    }
  }
?>
