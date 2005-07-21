<?php
  /*
  Tefinch.
  Copyright (C) 2005 Samuel Abels, <spam debain org>
                     Robert Weidlich, <tefinch xenim de>

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
  function string_escape($_string) {
    return htmlentities($_string, ENT_QUOTES, 'UTF-8');
  }
  
  
  function string_unescape($_string) {
    return html_entity_decode($_string, ENT_QUOTES);
  }
  
  
  // Removes the escapings that were added by magic-quotes.
  function stripslashes_deep($_value) {
    return is_array($_value)
         ? array_map('stripslashes_deep', $_value)
         : stripslashes($_value);
  }
  
  
  // Like wordwrap, but does not wrap lines beginning with ">" and allows to
  // set a hard limit.
  function wordwrap_except_quoted($_string) {
    global $cfg;
    foreach (explode("\n", $_string) as $line) {
      if (strpos($line,"> ") === 0) {
        $text .= $line . "\n";
      } else {
        $text .= wordwrap(wordwrap($line, $cfg[max_linelength_soft]),
                          $cfg[max_linelength_hard],
                          "\n",
                          TRUE) . "\n";
      }
    }
    return $text;
  }
  
  
  // Like wordwrap, but when wrapping lines beginning with ">" it tries to be
  // smart in adding more ">" in the new line. It also sets a hard limit.
  function wordwrap_smart($_string) {
    global $cfg;
    $lines              = preg_replace("/\r/", "", $_string);
    $lines              = explode("\n", $lines);
    list($trash, $line) = each($lines);
    preg_match("/^([> ]*)(.*)$/", $line, $matches);
    $block_depth = substr_count($matches[1], ">");
    $block       = $matches[2];
    
    while (isset($line)) {
      if (list($trash, $next_line) = each($lines)) {
        preg_match("/^([> ]*)(.*)$/", $next_line, $matches);
        $next_block_depth = substr_count($matches[1], ">");
      }
      else
        $next_block_depth = -1;
      if ($block_depth == $next_block_depth) {
        $block .=  "\n" . $matches[2];
        $line   = $next_line;
        continue;
      }
      
      // Ending up here, a block was finished. Wrap it and format it.
      $block_wrapped = wordwrap($block,
                                $cfg[max_linelength_soft] - $block_depth);
      $block_wrapped = wordwrap($block_wrapped,
                                $cfg[max_linelength_hard] - $block_depth,
                                "\n",
                                TRUE);
      $block_array   = explode("\n", $block_wrapped);
      
      foreach ($block_array as $block_line) {
        if ($text != "")
          $text .= "\n";
        for ($i = 0; $i < $block_depth; $i++)
          $text .= "> ";
        $text .= $block_line;
      }
      
      $block       = $matches[2];
      $block_depth = $next_block_depth;
      $line        = $next_line;
    }
    
    return $text;
  }
?>