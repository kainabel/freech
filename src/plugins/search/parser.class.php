<?php
  /*
  Freech.
  Copyright (C) 2008 Samuel Abels

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
class Parser {
  // Constructor.
  function Parser($_token_list)
  {
    $this->token_list = $_token_list;
    $this->_reset();
  }


  function _reset() {
    $this->offset = 0;
    $this->line   = 0;
    $this->error  = '';
  }


  function _get_next_token() {
    if (strlen($this->input) <= $this->offset)
      return array('EOF', NULL);

    // Walk through the list of tokens, trying to find a match.
    foreach ($this->token_list as $pair) {
      list($token_name, $token_regex) = $pair;
      $n_matches = preg_match($token_regex,
                              substr($this->input, $this->offset),
                              $matches,
                              0);
      if ($n_matches == 0)
        continue;
      $this->offset += strlen($matches[0]);
      $this->line   += substr_count($matches[0], "\n");
      return array($token_name, $matches);
    }

    // Ending up here no matching token was found.
    return array(NULL, NULL);
  }
}
?>
