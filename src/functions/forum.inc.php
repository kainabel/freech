<?php
  /*
  Freech.
  Copyright (C) 2009 Samuel Abels, <http://debain.org>

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
function init_user_from_post_data($_user = NULL) {
  if (!$_user)
    $_user = new User($_POST['username']);
  $_user->set_password($_POST['password']);
  $_user->set_firstname($_POST['firstname']);
  $_user->set_lastname($_POST['lastname']);
  $_user->set_mail($_POST['mail'], $_POST['publicmail'] == 'on');
  $_user->set_homepage($_POST['homepage']);
  $_user->set_im($_POST['im']);
  $_user->set_signature($_POST['signature']);
  return $_user;
}


// Dies if the confirmation hash passed in through GET is not valid.
function assert_user_confirmation_hash_is_valid(&$user) {
  if (!$user)
    die('Invalid user');
  $given_hash = $_GET['hash'] ? $_GET['hash'] : $_POST['hash'];
  $hash       = $user->get_confirmation_hash();
  if ($user->get_confirmation_hash() !== $given_hash)
    die('Invalid confirmation hash');
  if ($user->is_locked())
    die('User is locked');
}
?>
