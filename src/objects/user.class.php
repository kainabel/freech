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
define('USER_STATUS_DELETED',     0);
define('USER_STATUS_ACTIVE',      1);
define('USER_STATUS_UNCONFIRMED', 2);
define('USER_STATUS_BLOCKED',     3);

  /**
   * Represents a user.
   */
  class User {
    var $fields;  ///< Properties of the user, such as name, mail.

    /// Constructor.
    function User($_name = '') {
      $this->clear();
      $this->set_name($_name);
    }


    /// Resets all values.
    function clear() {
      $this->fields = array();
      $this->fields[group_id]    = -1;
      $this->fields[name]        = '';
      $this->fields[firstname]   = '';
      $this->fields[lastname]    = '';
      $this->fields[status]      = USER_STATUS_UNCONFIRMED;
      $this->fields[public_mail] = FALSE;
      $this->fields[created]     = time();
      $this->fields[lastlogin]   = time();
    }


    /// Sets all values from a given database row.
    function set_from_assoc(&$_row) {
      $this->clear();
      $this->fields = array_merge($this->fields, $_row);
    }


    /// Set a unique id for the user.
    function set_id($_id) {
      $this->fields[id] = (int)$_id;
    }


    function get_id() {
      return $this->fields[id];
    }


    function is_anonymous() {
      return $this->get_id() == cfg('anonymous_user_id');
    }


    function set_group_id($_group_id) {
      $this->fields[group_id] = (int)$_group_id;
    }


    function get_group_id() {
      return $this->fields[group_id];
    }


    function set_name($_name) {
      $this->fields[name] = preg_replace('/\s+/', ' ', trim($_name));
    }


    function get_name() {
      return $this->fields[name];
    }


    function get_normalized_name() {
      $src  = array(".", "_", "-", "0", "1", "5", "6", "8");
      $dst  = array(" ", " ", " ", "O", "I", "S", "G", "B");
      $norm = str_replace($src, $dst, $this->fields[name]);
      $src  = array("ü",  "ä",  "ö",  "ß");
      $dst  = array("ue", "ae", "oe", "ss");
      $norm = str_replace($src, $dst, $norm);
      $norm = preg_replace("/\s+/", " ", $norm);
      return strtolower(trim($norm));
    }


    function get_soundexed_name() {
      $name = preg_replace("/\s*\d+\s*/", "", $this->get_normalized_name());
      return soundex($name);
    }


    function get_lexical_similarity($_user) {
      $name1 = $this->get_normalized_name();
      $name2 = $_user->get_normalized_name();
      $len       = max(strlen($name1), strlen($name2));
      $dist      = levenshtein($name1, $name2);
      if ($dist == 0)
        return 100;
      return 100 - ($dist / $len * 100);
    }


    function is_lexically_similar_to($_user) {
      return $this->get_lexical_similarity($_user) > 70;
    }


    function set_password($_password, $_salt = NULL) {
      if (strlen($_password) < cfg('min_passwordlength')) {
        $err = _('Please choose a password with at least %d characters.');
        return sprintf($err, cfg('min_passwordlength'));
      }
      if (strlen($_password) > cfg('max_passwordlength')) {
        $err = _('Please choose a password with at most %d characters.');
        return sprintf($err, cfg('max_passwordlength'));
      }
      if (!$_salt)
        $_salt = cfg('salt');
      $this->fields['password'] = crypt($_salt . $_password);
      return 0;
    }


    function get_password_hash() {
      return $this->fields['password'];
    }


    function is_valid_password($_password) {
      return crypt(cfg('salt') . $_password, $this->fields['password'])
          == $this->fields['password'];
    }


    function set_firstname($_firstname) {
      $this->fields[firstname] = preg_replace('/\s+/', ' ', trim($_firstname));
    }


    function &get_firstname() {
      return $this->fields[firstname];
    }


    function set_lastname($_lastname) {
      $this->fields[lastname] = preg_replace("/\s+/", " ", trim($_lastname));
    }


    function get_lastname() {
      return $this->fields[lastname];
    }


    function set_mail($_mail, $_mail_is_public = FALSE) {
      $this->fields[mail]        = strtolower(trim($_mail));
      $this->fields[public_mail] = $_mail_is_public;
    }


    function get_mail() {
      return $this->fields[mail];
    }


    function check_mail() {
      //FIXME: make a much better check.
      if (!preg_match('/^[a-z0-9\-\._]+@[a-z0-9\-\._]+\.[a-z]+$/',
                      $this->fields[mail]))
        return _('Please enter a valid email address.');
      if (strlen($this->fields[mail]) > cfg('max_maillength'))
        return sprintf(_('Please enter an email address with at most %d'
                       . ' characters.'),
                       cfg('max_maillength'));
      return 0;
    }


    function mail_is_public() {
      return $this->fields[public_mail];
    }


    function set_homepage($_homepage) {
      $_homepage = trim($_homepage);
      if ($_homepage && !preg_match("/^http/i", $_homepage))
        $_homepage = "http://" . $_homepage;
      $this->fields[homepage] = $_homepage;
    }


    function get_homepage() {
      return $this->fields[homepage];
    }


    function get_homepage_url() {
      return new FreechURL($this->fields[homepage]);
    }


    function get_homepage_url_html() {
      return $this->get_homepage_url()->get_html();
    }


    /// Instant messenger address.
    function set_im($_im) {
      $this->fields[im] = trim($_im);
    }


    function &get_im() {
      return $this->fields[im];
    }


    function get_icon() {
      return 'data/group_icons/'.$this->fields['group_id'].'.png';
    }


    function get_icon_name() {
      return $this->fields['icon_name'];
    }


    function get_created_unixtime() {
      return $this->fields[created];
    }


    /// Returns the formatted time.
    function get_created_time($_format = '') {
      if (!$_format)
        $_format = cfg('dateformat');
      return strftime($_format, $this->fields[created]);
    }


    function get_updated_unixtime() {
      return $this->fields[updated];
    }


    /// Returns the formatted time.
    function get_updated_time($_format = '') {
      if (!$_format)
        $_format = cfg('dateformat');
      return strftime($_format, $this->fields[updated]);
    }


    function set_last_login_time($_lastlogin) {
      $this->fields[lastlogin] = (int)$_lastlogin;
    }


    function get_last_login_unixtime() {
      return $this->fields[lastlogin];
    }


    function &get_editor_url() {
      $url = new FreechURL('', '[' . _('Edit') . ']');
      $url->set_var('action',   'user_editor');
      $url->set_var('username', $this->get_name());
      return $url;
    }


    function get_editor_url_html() {
      return $this->get_editor_url()->get_html();
    }


    function &get_profile_url() {
      $caption = sprintf(_('Profile of %s'), $this->get_name());
      $url     = new FreechURL('', $caption);
      $url->set_var('action',   'user_profile');
      $url->set_var('username', $this->get_name());
      return $url;
    }


    function get_profile_url_string() {
      return $this->get_profile_url()->get_string();
    }


    function get_profile_url_html($_label) {
      return $this->get_profile_url()->get_html($_label);
    }


    function &get_postings_url() {
      $caption = sprintf(_('Postings of %s'), $this->get_name());
      $url     = new FreechURL('', $caption);
      $url->set_var('action',   'user_postings');
      $url->set_var('username', $this->get_name());
      return $url;
    }


    function get_postings_url_string() {
      return $this->get_postings_url()->get_string();
    }


    function get_postings_url_html($_label) {
      return $this->get_postings_url()->get_html($_label);
    }


    function get_confirmation_hash() {
      $hash = md5(cfg('salt')
                . $this->get_id()
                . $this->get_firstname()
                . $this->get_lastname()
                . $this->get_name());
      return preg_replace('/\./', 'x', $hash);
    }


    function set_status($_status) {
      $this->fields[status] = (int)$_status;
    }


    function get_status() {
      return $this->fields[status];
    }


    function is_active() {
      return $this->fields[status] == USER_STATUS_ACTIVE;
    }


    function is_deleted() {
      return $this->get_status() == USER_STATUS_DELETED;
    }


    function is_confirmed() {
      return $this->fields[status] != USER_STATUS_UNCONFIRMED;
    }


    function is_locked() {
      return $this->fields[status] == USER_STATUS_BLOCKED;
    }


    function &get_status_names($_status = -1) {
      $list = array(
        USER_STATUS_DELETED     => _('Deleted'),
        USER_STATUS_ACTIVE      => _('Active'),
        USER_STATUS_UNCONFIRMED => _('Unconfirmed'),
        USER_STATUS_BLOCKED     => _('Locked')
      );
      if ($_status >= 0)
        return $list[$_status];
      return $list;
    }


    function get_status_name() {
      return $this->get_status_names($this->fields[status]);
    }


    // Convenience function that marks the user deleted and also deletes
    // all field values except for the name.
    function set_deleted() {
      $id       = $this->get_id();
      $group_id = $this->get_group_id();
      $name     = $this->get_name();
      $this->clear();
      $this->set_id($id);
      $this->set_group_id($group_id);
      $this->set_name($name);
      $this->set_status(USER_STATUS_DELETED);
    }


    /// Returns an error code if any of the required fields is not filled.
    function check_complete() {
      if (ctype_space($this->fields[name]))
        return _('Please enter a username.');

      if (strlen($this->fields[name]) < cfg('min_usernamelength')) {
        $err = _('Your login name is too short. Please enter at least'
               . ' %d characters.');
        return sprintf($err, cfg('min_usernamelength'));
      }

      if (strlen($this->fields[name]) > cfg('max_usernamelength')) {
        $err = _('Your login name is too long. Please enter at most'
               . ' %d characters.');
        return sprintf($err, cfg('max_usernamelength'));
      }

      if (!preg_match(cfg('username_pattern'), $this->fields[name]))
        return _('Your username contains invalid characters.');

      if (ctype_space($this->fields['password']))
        return _('Please enter a password.');

      if (ctype_space($this->fields[firstname]))
        return _('Please enter a firstname.');

      if (strlen($this->fields[firstname]) < cfg('min_firstnamelength')) {
        $err = _('Your firstname is too short. Please enter at least'
               . ' %d characters.');
        return sprintf($err, cfg('min_firstnamelength'));
      }

      if (strlen($this->fields[firstname]) > cfg('max_firstnamelength')) {
        $err = _('Your firstname is too long. Please enter at most'
               . ' %d characters.');
        return sprintf($err, cfg('max_firstnamelength'));
      }

      if (ctype_space($this->fields[lastname]))
        return _('Please enter a lastname.');

      if (strlen($this->fields[lastname]) < cfg('min_lastnamelength')) {
        $err = _('Your lastname is too short. Please enter at least'
               . ' %d characters.');
        return sprintf($err, cfg('min_lastnamelength'));
      }

      if (strlen($this->fields[lastname]) > cfg('max_lastnamelength')) {
        $err = _('Your lastname is too long. Please enter at most'
               . ' %d characters.');
        return sprintf($err, cfg('max_lastnamelength'));
      }

      //FIXME: make a much better check.
      if ($this->fields[homepage]) {
        if (!preg_match('/^http:\/\/[\w\._\-\/\?\&=\%;,\+\(\)]+$/i',
                        $this->fields[homepage]))
          return _('Please enter a valid homepage URL.');

        if (strlen($this->fields[homepage]) > cfg('max_homepageurllength')) {
          $err = _('Your homepage URL is too long. Please enter at most'
                 . ' %d characters.');
          return sprintf($err, cfg('max_homepageurllength'));
        }
      }

      if (strlen($this->fields[im]) > cfg('max_imlength')) {
        $err = _('Your instant messenger address is too long. Please enter at'
               . ' most %d characters.');
        return sprintf($err, cfg('max_imlength'));
      }

      return $this->check_mail();
    }
  }
?>
