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
define("USER_STATUS_ACTIVE",      0);
define("USER_STATUS_UNCONFIRMED", 1);
define("USER_STATUS_BLOCKED",     2);

  /**
   * Represents a user.
   */
  class User {
    var $fields;  ///< Properties of the user, such as name, mail, signature.

    /// Constructor.
    function User($_username = '') {
      $this->clear();
      $this->set_username($_username);
    }


    /// Resets all values.
    function clear() {
      $this->fields = array();
      $this->fields[group_id]    = -1;
      $this->fields[username]    = "";
      $this->fields[firstname]   = "";
      $this->fields[lastname]    = "";
      $this->fields[status]      = USER_STATUS_UNCONFIRMED;
      $this->fields[public_mail] = FALSE;
      $this->fields[created]     = time();
      $this->fields[lastlogin]   = time();
    }


    /// Sets all values from a given database row.
    function set_from_db(&$_db_row) {
      if (!is_array($_db_row))
        die("User:set_from_db(): Non-array.");
      $this->clear();
      $this->fields[id]           = $_db_row[id];
      $this->fields[group_id]     = $_db_row[group_id];
      $this->fields[username]     = $_db_row[username];
      $this->fields[passwordhash] = $_db_row[password];
      $this->fields[firstname]    = $_db_row[firstname];
      $this->fields[lastname]     = $_db_row[lastname];
      $this->fields[mail]         = $_db_row[mail];
      $this->fields[public_mail]  = $_db_row[public_mail];
      $this->fields[homepage]     = $_db_row[homepage];
      $this->fields[im]           = $_db_row[im];
      $this->fields[signature]    = $_db_row[signature];
      $this->fields[status]       = $_db_row[status];
      $this->fields[created]      = $_db_row[created];
      $this->fields[updated]      = $_db_row[updated];
      $this->fields[lastlogin]    = $_db_row[lastlogin];
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


    function set_username($_username) {
      $this->fields[username] = preg_replace("/\s+/", " ", trim($_username));
    }


    function &get_username() {
      return $this->fields[username];
    }


    function &get_normalized_username() {
      $src  = array(".", "_", "-", "0", "1", "5", "6", "8");
      $dst  = array(" ", " ", " ", "O", "I", "S", "G", "B");
      $norm = str_replace($src, $dst, $this->fields[username]);
      $src  = array("ü",  "ä",  "ö",  "ß");
      $dst  = array("ue", "ae", "oe", "ss");
      $norm = str_replace($src, $dst, $norm);
      $norm = preg_replace("/\s+/", " ", $norm);
      return strtolower(trim($norm));
    }


    function &get_soundexed_username() {
      $username = preg_replace("/\s*\d+\s*/",
                               "",
                               $this->get_normalized_username());
      return soundex($username);
    }


    function &get_lexical_similarity($_user) {
      $username1 = $this->get_normalized_username();
      $username2 = $_user->get_normalized_username();
      $len       = max(strlen($username1), strlen($username2));
      $dist      = levenshtein($username1, $username2);
      if ($dist == 0)
        return 100;
      return 100 - ($dist / $len * 100);
    }


    function is_lexically_similar_to($_user) {
      return $this->get_lexical_similarity($_user) > 70;
    }


    function set_password($_password) {
      if (strlen($_password) < cfg("min_passwordlength"))
        return ERR_USER_PASSWORD_TOO_SHORT;
      if (strlen($_password) > cfg("max_passwordlength"))
        return ERR_USER_PASSWORD_TOO_LONG;
      $this->fields[passwordhash] = crypt(cfg("salt") . $_password);
      return 0;
    }


    function &get_password_hash() {
      return $this->fields[passwordhash];
    }


    function flush_password() {
      unset($this->fields[passwordhash]);
    }


    function is_valid_password($_password) {
      return crypt(cfg("salt") . $_password, $this->fields[passwordhash])
          == $this->fields[passwordhash];
    }


    function set_firstname($_firstname) {
      $this->fields[firstname] = preg_replace("/\s+/", " ", trim($_firstname));
    }


    function &get_firstname() {
      return $this->fields[firstname];
    }


    function set_lastname($_lastname) {
      $this->fields[lastname] = preg_replace("/\s+/", " ", trim($_lastname));
    }


    function &get_lastname() {
      return $this->fields[lastname];
    }


    function set_mail($_mail, $_mail_is_public = FALSE) {
      $this->fields[mail]        = strtolower(trim($_mail));
      $this->fields[public_mail] = $_mail_is_public;
    }


    function &get_mail() {
      return $this->fields[mail];
    }


    function check_mail() {
      //FIXME: make a much better check.
      if (!preg_match("/^[a-z0-9\-\._]+@[a-z0-9\-\._]+\.[a-z]+$/", $this->fields[mail]))
        return ERR_USER_MAIL_NOT_VALID;
      if (strlen($this->fields[mail]) > cfg("max_maillength"))
        return ERR_USER_MAIL_TOO_LONG;
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


    function &get_homepage() {
      return $this->fields[homepage];
    }


    /// Instant messenger address.
    function set_im($_im) {
      $this->fields[im] = trim($_im);
    }


    function &get_im() {
      return $this->fields[im];
    }


    /// A signature that can be addded below a message that the user writes.
    function set_signature($_signature) {
      $this->fields[signature] = trim($_signature);
    }


    function &get_signature() {
      return $this->fields[signature];
    }


    function get_created_unixtime() {
      return $this->fields[created];
    }


    /// Returns the formatted time.
    function get_created_time($_format = '') {
      if (!$_format)
        $_format = lang("dateformat");
      return date($_format, $this->fields[created]);
    }


    function get_updated_unixtime() {
      return $this->fields[updated];
    }


    /// Returns the formatted time.
    function get_updated_time($_format = '') {
      if (!$_format)
        $_format = lang("dateformat");
      return date($_format, $this->fields[updated]);
    }


    function set_last_login_time($_lastlogin) {
      $this->fields[lastlogin] = $_lastlogin * 1;
    }


    function get_last_login_unixtime() {
      return $this->fields[lastlogin];
    }


    /// Returns the formatted time.
    function get_last_login_time($_format = '') {
      if (!$_format)
        $_format = lang("dateformat");
      return date($_format, $this->fields[lastlogin]);
    }


    function get_user_data_url() {
      $url = new URL('?', cfg('urlvars'));
      $url->set_var('action',   'user_data');
      $url->set_var('username', $this->get_username());
      return $url->get_string();
    }


    function get_profile_url() {
      $url = new URL('?', cfg('urlvars'));
      $url->set_var('action',   'profile');
      $url->set_var('username', $this->get_username());
      return $url->get_string();
    }


    function get_confirmation_hash() {
      $hash = md5($this->get_id()
                . $this->get_firstname()
                . $this->get_lastname()
                . $this->get_username());
      return preg_replace("/\./", "x", $hash);
    }


    function set_status($_status) {
      $this->fields[status] = $_status * 1;
    }


    function get_status() {
      return $this->fields[status];
    }


    function get_status_names($_status = -1) {
      $list = array(
        USER_STATUS_ACTIVE      => lang('USER_STATUS_ACTIVE'),
        USER_STATUS_UNCONFIRMED => lang('USER_STATUS_UNCONFIRMED'),
        USER_STATUS_BLOCKED     => lang('USER_STATUS_BLOCKED')
      );
      if ($_status >= 0)
        return $list[$_status];
      return $list;
    }


    function get_status_name() {
      return $this->get_status_names($this->fields[status]);
    }


    /// Returns an error code if any of the required fields is not filled.
    function check_complete() {
      if (ctype_space($this->fields[username]))
        return ERR_USER_LOGIN_INCOMPLETE;
      if (strlen($this->fields[username]) < cfg("min_usernamelength"))
        return ERR_USER_LOGIN_TOO_SHORT;
      if (strlen($this->fields[username]) > cfg("max_usernamelength"))
        return ERR_USER_LOGIN_TOO_LONG;
      if (!preg_match(cfg("username_pattern"), $this->fields[username]))
        return ERR_USER_LOGIN_INVALID_CHARS;

      if (ctype_space($this->fields[passwordhash]))
        return ERR_USER_PASSWORD_INCOMPLETE;

      if (ctype_space($this->fields[firstname]))
        return ERR_USER_FIRSTNAME_INCOMPLETE;
      if (strlen($this->fields[firstname]) < cfg("min_firstnamelength"))
        return ERR_USER_FIRSTNAME_TOO_SHORT;
      if (strlen($this->fields[firstname]) > cfg("max_firstnamelength"))
        return ERR_USER_FIRSTNAME_TOO_LONG;

      if (ctype_space($this->fields[lastname]))
        return ERR_USER_LASTNAME_INCOMPLETE;
      if (strlen($this->fields[lastname]) < cfg("min_lastnamelength"))
        return ERR_USER_LASTNAME_TOO_SHORT;
      if (strlen($this->fields[lastname]) > cfg("max_lastnamelength"))
        return ERR_USER_LASTNAME_TOO_LONG;

      //FIXME: make a much better check.
      if ($this->fields[homepage]) {
        if (!preg_match('/^http:\/\/[\w\._\-\/\?\&=\%;,\+\(\)]+$/i', $this->fields[homepage]))
          return ERR_USER_HOMEPAGE_NOT_VALID;
        if (strlen($this->fields[homepage]) > cfg("max_homepageurllength"))
          return ERR_USER_HOMEPAGE_TOO_LONG;
      }

      if (strlen($this->fields[im]) > cfg("max_imlength"))
        return ERR_USER_IM_TOO_LONG;

      if (strlen($this->fields[signature]) > cfg("max_signaturelength"))
        return ERR_USER_SIGNATURE_TOO_LONG;
      if (substr_count($this->fields[signature], "\n") > cfg("max_signature_lines"))
        return ERR_USER_SIGNATURE_TOO_MANY_LINES;

      return $this->check_mail();
    }
  }
?>
