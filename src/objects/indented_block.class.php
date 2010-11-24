<?php
  /*
  Freech.
  Copyright (C) 2003 Samuel Abels

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
class IndentedBlock {
  function IndentedBlock($_depth) {
    $this->depth = $_depth;
    $this->text  = '';
  }


  function get_depth() {
    return $this->depth;
  }


  function append($_line) {
    // Lines ending with a space have been auto-wrapped and may be joined
    // together using a space.
    // Lines ending with a tab character have been auto-wrapped and may be 
    // joined without a space.
    // Other lines are explicitely wrapped by the user, so they are
    // not joined.
    if (preg_match('/(.*) $/', $_line, $matches))
      $this->text .= $matches[1].' ';
    elseif (preg_match('/(.*)\t$/', $_line, $matches))
      $this->text .= $matches[1];
    else
      $this->text .= $_line."\n";
  }


  function get_text() {
    return $this->text;
  }


  function is_empty() {
    return trim($this->text) == '';
  }


  function get_quoted_text($_depth = 1) {
    if ($this->depth + $_depth == 0)
      return trim($this->text);
    $text    = '';
    $prefix .= str_repeat('> ', $this->depth + $_depth);
    $maxlen  = max(45, cfg('max_linelength') - strlen($prefix));
    foreach (explode("\n", $this->text) as $paragraph) {
      $paragraph = wordwrap($paragraph, $maxlen, " \n");
      foreach (explode("\n", $paragraph) as $line) {
        if (!$line)
          continue;
        if (strlen($line) > $maxlen + 2) {
          $line  = wordwrap($line, $maxlen, "\t\n", TRUE);
          $lines = explode("\n", trim($line));
          $line  = implode("\n".$prefix, $lines);
        }
        $text .= $prefix.$line."\n";
      }
      $text = trim($text)."\n";
    }
    return trim($text);
  }
}
?>
