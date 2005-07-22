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
   * Represents a user.
   */
  class User {
    var $fields;  ///< Properties of the user, such as name, login, signature.
    var $groups;  ///< The groups in which the user is.
    
    /// Constructor.
    function User() {
      $this->clear();
    }
    
    
    /// Resets all values.
    function clear() {
      $this->fields = array();
      $this->fields[login]        = "anonymous";
      $this->fields[firstname]    = "Anonymous";
      $this->fields[lastname]     = "George";
      $this->fields[created]      = time();
      $this->fields[updated]      = time();
      $this->fields[lastlogin]    = time();
      $this->groups = array();
    }
    
    
    /// Sets all values from a given database row.
    function set_from_db(&$_db_row) {
      if (!is_object($_db_row))
        die("User:set_from_db(): Non-object.");
      $this->clear();
      $this->fields[id]           = $_db_row->id;
      $this->fields[login]        = $_db_row->login;
      $this->fields[passwordhash] = $_db_row->password;
      $this->fields[firstname]    = $_db_row->firstname;
      $this->fields[lastname]     = $_db_row->lastname;
      $this->fields[mail]         = $_db_row->mail;
      $this->fields[homepage]     = $_db_row->homepage;
      $this->fields[im]           = $_db_row->im;
      $this->fields[signature]    = $_db_row->signature;
      $this->fields[created]      = $_db_row->created;
      $this->fields[updated]      = $_db_row->updated;
      $this->fields[lastlogin]    = $_db_row->lastlogin;
    }
    
    
    /// Set a unique id for the user.
    function set_id($_id) {
      $this->fields[id] = $_id * 1;
    }
    
    
    function get_id() {
      return $this->fields[id];
    }
    
    
    function set_login($_login) {
      if (strlen($_login) < cfg("min_loginlength"))
        return ERR_USER_LOGIN_TOO_SHORT;
      if (strlen($_login) > cfg("max_loginlength"))
        return ERR_USER_LOGIN_TOO_LONG;
      $this->fields[login] = $_login * 1;
    }
    
    
    function &get_login() {
      return $this->fields[login];
    }
    
    
    function set_password($_password) {
      if (strlen($_password) < cfg("min_passwordlength"))
        return ERR_USER_PASSWORD_TOO_SHORT;
      if (strlen($_password) > cfg("max_passwordlength"))
        return ERR_USER_PASSWORD_TOO_LONG;
      $this->fields[passwordhash] = crypt($_password);
    }
    
    
    function flush_password() {
      unset($this->fields[passwordhash]);
    }
    
    
    function is_valid_password($_password) {
      return crypt($_password, $this->fields[passwordhash])
          == $this->fields[passwordhash];
    }
    
    
    function set_firstname($_firstname) {
      if (strlen($_firstname) < cfg("min_firstnamelength"))
        return ERR_USER_FIRSTNAME_TOO_SHORT;
      if (strlen($_firstname) > cfg("max_firstnamelength"))
        return ERR_USER_FIRSTNAME_TOO_LONG;
      $this->fields[firstname] = $_firstname;
    }
    
    
    function &get_firstname() {
      return $this->fields[firstname];
    }
    
    
    function set_lastname($_lastname) {
      if (strlen($_lastname) < cfg("min_lastnamelength"))
        return ERR_USER_LASTNAME_TOO_SHORT;
      if (strlen($_lastname) > cfg("max_lastnamelength"))
        return ERR_USER_LASTNAME_TOO_LONG;
      $this->fields[lastname] = $_lastname;
    }
    
    
    function &get_lastname() {
      return $this->fields[lastname];
    }
    
    
    function set_mail($_mail) {
      //FIXME: make a much better check.
      if (!preg_match("/^[a-z0-9\._]+@[a-z0-9\-\._]+\.[a-z]+$/i", $_mail))
        return ERR_USER_MAIL_NOT_VALID;
      if (strlen($_mail) > cfg("max_maillength"))
        return ERR_USER_MAIL_TOO_LONG;
      $this->fields[mail] = $_mail;
    }
    
    
    function &get_mail() {
      return $this->fields[mail];
    }
    
    
    function set_homepage($_homepage) {
      if (!preg_match("/^http/i", $_homepage))
        $_homepage = "http://" . $_homepage;
      //FIXME: make a much better check.
      if (!preg_match("/[a-z0-9\._]\.[a-z0-9\._]+\.[a-z]+$/i", $_homepage))
        return ERR_USER_HOMEPAGE_NOT_VALID;
      if (strlen($_homepage) > cfg("max_homepageurllength"))
        return ERR_USER_HOMEPAGE_TOO_LONG;
      $this->fields[homepage] = $_homepage;
    }
    
    
    function &get_homepage() {
      return $this->fields[homepage];
    }
    
    
    /// Instant messenger address.
    function set_im($_im) {
      if (strlen($_im) > cfg("max_imlength"))
        return ERR_USER_IM_TOO_LONG;
      $this->fields[im] = $_im;
    }
    
    
    function &get_im() {
      return $this->fields[im];
    }
    
    
    /// A signature that can be addded below a message that the user writes.
    function set_signature($_signature) {
      if (strlen($_signature) > cfg("max_signaturelength"))
        return ERR_USER_SIGNATURE_TOO_LONG;
      $this->fields[signature] = $_signature;
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
    
    
    function set_updated_time($_updated) {
      $this->fields[updated] = $_updated * 1;
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
    
    
    function add_to_group(&$_group) {
      $this->groups[$_group->get_name()] =& $_group;
    }
    
    
    function remove_from_group(&$_group) {
      if (!$this->is_in_group($_group))
        return 0;
      if (count($this->groups) <= 1)
        return ERR_USER_REMOVED_FROM_LAST_GROUP;
      unset($this->groups[$_group->get_name()]);
      return 0;
    }
    
    
    function is_in_group(&$_group) {
      return isset($this->groups[$_group->get_name()]);
    }
    
    
    function has_permission(&$_permission) {
      foreach ($this->groups as $group) {
        if ($group->has_permission($_permission))
          return TRUE;
      }
      return FALSE;
    }
    
    
    /// Returns an error code if any of the required fields is not filled.
    function check_complete() {
      if (ctype_space($this->fields[login]))
        return ERR_USER_LOGIN_INCOMPLETE;
      
      if (ctype_space($this->fields[passwordhash]))
        return ERR_USER_PASSWORD_INCOMPLETE;
      
      if (ctype_space($this->fields[firstname]))
        return ERR_USER_FIRSTNAME_INCOMPLETE;
      
      if (ctype_space($this->fields[lastname]))
        return ERR_USER_LASTNAME_INCOMPLETE;
      
      return 0;
    }
  }
?>
