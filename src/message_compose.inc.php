<?php
  /*
  Tefinch.
  Copyright (C) 2005 Robert Weidlich, <tefinch xenim de>

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
  include_once "language/$cfg[lang].inc.php";
  include_once "string.inc.php";
  
  /* Print out a form to compose the message.
   * $_name, $_subject, $_message allow to give default values
   * $_hint is useful to print an error message
   * $_quotebutton: When TRUE, the "Quote" button is shown.
   */
  function message_compose($_name,
                           $_subject,
                           $_message,
                           $_hint,
                           $_quotebutton,
                           $_queryvars) {
    global $cfg;
    global $lang;
    $holdvars = array_merge($cfg[urlvars],
                            array('forum_id', 'msg_id', 'hs'));
    
    print("<form action='?".build_url($_queryvars, $holdvars,'')
        ."' method='POST' accept-charset='utf-8'>\n"
        . "<font size='+1' color='red'>$lang[writeamessage]</font>\n");
    
    if ($_hint)
      print("<br><font size='+1' color='red'>$_hint</font>\n");
    
    print("\n<p>\n"
        . "<b>$lang[name]</b>&nbsp;<i>$lang[required]</i><br>\n"
        . "<input type='text' size='80' name='name' value='"
        . string_escape($_name)."' maxlength='$cfg[max_namelength]'>\n"
        
        . "\n<p>\n"
        . "<b>$lang[msgtitle]</b>&nbsp;<i>$lang[required]</i><br>\n"
        . "<input type='text' size='80' name='subject' value='"
        . string_escape($_subject)."' maxlength='$cfg[max_titlelength]'>\n"
        
        . "\n<p>\n"
        . "<b>$lang[msgbody]</b>&nbsp;<i>$lang[required]</i><br>\n"
        . "<textarea name='message' cols='80' rows='20' wrap='virtual'>"
        . string_escape($_message)
        . "</textarea>\n"
        
        . "<p>\n");
    
    if ($_quotebutton) {
      print("<input type='hidden' name='msg_id' value='$_queryvars[msg_id]'>\n");
      print("<input type='submit' name='quote' value='$lang[quote]'>&nbsp;\n");
    }
    print("<input type='submit' name='preview' value='$lang[preview]'>&nbsp;\n"
        . "<input type='submit' name='send' value='$lang[send]'>\n"
        . "</form>\n"); 
  }
  
  
  /* Same as above, but grabs the title from the database instead, leaving
   * all other fields empty.
   */
  function message_compose_reply($_title, $_hint, $_queryvars) {
    global $lang;
    // Prepend 'Re: ' if necessary
    if (strpos($_title, $lang[answer]) !== 0) { 
       $_title = $lang[answer] . $_title;
    } else { 
       $_title = $_title;
    }
    message_compose('', $_title, '', $_hint, TRUE, $_queryvars);
  }
?>
