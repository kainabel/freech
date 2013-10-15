<?php
  /*
  Copyright (C) 2005 Samuel Abels

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
  function esc($_string) {
    return htmlentities($_string, ENT_QUOTES, 'UTF-8');
  }


  function unesc($_string) {
    return html_entity_decode($_string, ENT_QUOTES, 'UTF-8');
  }


  function html_debug($_comment = '') {
    $backtrace = debug_backtrace();
    $caller    = $backtrace[0];
    $dir       = dirname(dirname(__FILE__));
    $file      = substr($caller['file'], strlen($dir) + 1);
    $line      = $caller['line'];
    echo sprintf("<!--\n## In %s, line %d: %s ##\n-->\n",
                 esc($file),
                 $line,
                 esc($_comment));
  }


  function html_options($_name, $_options, $_selected = '') {
    if (count($_options) == 0)
      return '';

    $html = '<select name="' . esc($_name) . '">';
    foreach ($_options as $id => $caption) {
      $select = ($id == $_selected) ? ' selected="selected"' : '';
      $html  .= "<option label='".esc($caption)."' value='$id'$select>";
      $html  .= esc($caption);
      $html  .= '</option>';
    }

    return $html . "</select>\n";
  }


  function html_checkboxes($_name, $_options, $_separator = "<br />\n") {
    if (count($_options) == 0)
      return '';

    $html = '';
    foreach ($_options as $id => $caption) {
      $html .= '<label>';
      $html .= "<input type='checkbox' name='".esc($_name)."[]' value='$id' />";
      $html .= esc($caption);
      $html .= '</label>';
      $html .= $_separator;
    }

    return $html;
  }


  function html_radios($_name, $_options, $_separator = "<br />\n") {
    if (count($_options) == 0)
      return '';

    $html = '';
    foreach ($_options as $id => $caption) {
      $html .= '<label>';
      $html .= "<input type='radio' name='".esc($_name)."' value='$id' /> ";
      $html .= esc($caption);
      $html .= '</label>';
      $html .= $_separator;
    }

    return $html;
  }


  function html_menu($_name, $_menu = '', $_separator = '', $_auto = TRUE) {
    if ($_menu->length() == 0)
      return '';

    $html = '<ul';
    if ($_name)
      $html .= " id='$_name' class='$_name'";
    $html .= '>';

    foreach ($_menu->get_items() as $item) {
      if ($_separator and $_auto && ++$i != 1)
        $html .= '<li class="separator">' . $_separator . '</li>';
      if ($item->is_separator())
        $html .= '<li class="separator">' . $_separator . '</li>';
      elseif ($item->is_html())
        $html .= '<li class="html">' . $item->get_html() . '</li>';
      elseif ($item->is_link())
        $html .= '<li class="link">' . $item->get_url_html() . '</li>';
      else
        $html .= '<li class="text">' . $item->get_text(TRUE) . '</li>';
    }
    return $html . "</ul>\n";
  }


  function html_get_homebutton() {
    $html = "<form id='home_button' action='/' method='post'>"
          . "<input type='submit' value='" . esc(_('Welcome page'))
          . "' /></form>\n";
    return $html;
  }


  // Removes the escapings that were added by magic-quotes.
  function stripslashes_deep($_value) {
    return is_array($_value)
         ? array_map('stripslashes_deep', $_value)
         : stripslashes($_value);
  }


  function is_utf8($_string) {
    return mb_check_encoding($_string, 'utf8');
  }


  function replace_vars($_string, $_vars) {
    foreach ($_vars as $key => $value)
      $_string = str_replace('['.strtoupper($key).']', $value, $_string);
    return $_string;
  }

  // Check: Is the plugin $name enabled?
  // e.g.: is_plugin_enabled('listview')
  function is_plugin_enabled($name = NULL) {
    global $cfg;
    if (array_key_exists($name, $cfg['plugins']) && $cfg['plugins'][$name]) {
      return TRUE;
    }
    return FALSE;
  }

?>
