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
    return htmlentities($_string, ENT_QUOTES);    
  }
  
  
  function string_unescape($_string) {
    return html_entity_decode($_string,ENT_QUOTES);
  }
  
  
  // Removes the escapings that were added by magic-quotes.
  function stripslashes_deep($value) {
    return is_array($value)
         ? array_map('stripslashes_deep', $value)
         : stripslashes($value);
  }
?>
