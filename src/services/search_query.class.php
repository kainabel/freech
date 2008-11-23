<?php
  /*
  Freech.
  Copyright (C) 2008 Samuel Abels, <http://debain.org>

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
class Token {
  function Token() {
  }
}

class SearchQuery {
  // Constructor.
  function SearchQuery($_query = '')
  {
    $this->token_list = array(
      array('and',          '/^and\b/i'),
      array('or',           '/^or\b/i'),
      array('not',          '/^not\b/i'),
      array('openbracket',  '/^\(/'),
      array('closebracket', '/^\)/'),
      array('whitespace',   '/^\s+/'),
      array('field',        '/^(\w+):/'),
      array('word',         '/^"([^"]*)"/'),
      array('word',         '/^([^"\:\s\(\)\\\']+)/'),
      array('unknown',      '/^./')
    );
    $this->field_names = array('name'     => 'name',
                               'user'     => 'name',
                               'userid'   => 'u_id',
                               'username' => 'name',
                               'forumid'  => 'forumid',
                               'title'    => 'title',
                               'subject'  => 'title',
                               'text'     => 'text',
                               'body'     => 'text');
    $this->int_columns = array('forumid', 'u_id');
    $this->set_query($_query);
  }


  function set_query($_query) {
    $this->query = trim($_query);
    $this->_parse();
  }


  function _get_next_token() {
    if (strlen($this->query) <= $this->offset)
      return array('EOF', NULL);

    // Walk through the list of tokens, trying to find a match.
    foreach ($this->token_list as $pair) {
      list($token_name, $token_regex) = $pair;
      $n_matches = preg_match($token_regex,
                              substr($this->query, $this->offset),
                              $matches,
                              0);
      if ($n_matches == 0)
        continue;
      $this->offset += strlen($matches[0]);
      return array($token_name, $matches);
    }

    // Ending up here no matching token was found.
    return array(NULL, NULL);
  }


  function _format_value($_field, $_value) {
    if (in_array($_field, $this->int_columns))
      return (int)$_value;

    // Escape special chars.
    $value = str_replace('%', '\%', $_value);
    $value = str_replace('_', '\_', $value);

    // Translate wildcard chars.
    $value = str_replace('*', '%',  $value);
    $value = str_replace('?', '_',  $value);

    // Add '%' at beginning and end, and remove duplicate '%'.
    return preg_replace("/^%%+$/", '', '%'.$value.'%');
  }


  function _add_field($_fieldname, $_varname) {
    if (!isset($this->fields[$_fieldname])) {
      $this->fields[$_fieldname] = array($_varname);
      return;
    }
    array_push($this->fields[$_fieldname], $_varname);
  }


  function _parse() {
    $this->offset  = 0;
    $sql           = '1';
    $next_op       = "AND";
    $next_like     = "LIKE";
    $this->fields  = array();
    $this->vars    = array();
    $open_brackets = 0;
    $field_number  = 1;
    list($token, $match) = $this->_get_next_token();
    //echo "QUERY: ".$this->query."<br>";
    while ($token != 'EOF') {
      //echo "TOKEN: $token<br>";
      switch ($token) {
        case 'field':
          // If the given attribute does not exists, treat it like any 
          // other search term.
          $field_name = $this->field_names[$match[1]];
          if (!$field_name) {
            $token = 'word';
            continue;
          }

          // A field value is required.
          list($token, $match) = $this->_get_next_token();
          if ($token != 'word') {
            $next_op = "AND";
            continue;
          }

          // Create the SQL statement.
          $value = $this->_format_value($field_name, $match[1]);
          if ($value != '%') {
            $open_brackets += substr_count($next_op, '(');
            $var_name = "$field_name$field_number";
            $sql     .= " $next_op $field_name LIKE ".'{'.$var_name.'}';
            $field_number++;
            $this->_add_field($field_name, $var_name);
            $this->vars[$var_name] = $value;
          }
          $next_op = "AND";
          list($token, $match) = $this->_get_next_token();
          break;

        case 'word':
          $value = $this->_format_value('text', $match[1]);
          if ($value != '%') {
            $open_brackets += substr_count($next_op, '(');
            $var_name = "text$field_number";
            $sql     .= ' '.$next_op.' ';
            $sql     .= '(';
            $sql     .= ' title LIKE {'.$var_name.'}';
            $sql     .= ' OR';
            $sql     .= ' text LIKE {'.$var_name.'}';
            $sql     .= ')';
            $field_number++;
            $this->_add_field('title', $var_name);
            $this->_add_field('text',  $var_name);
            $this->vars[$var_name] = $value;
          }
          $next_op = "AND";
          list($token, $match) = $this->_get_next_token();
          break;

        case 'and':
        case 'or':
          $next_op = strtoupper($match[0]);
          list($token, $match) = $this->_get_next_token();
          break;

        case 'not':
          $next_op .= ' NOT';
          list($token, $match) = $this->_get_next_token();
          break;

        case 'openbracket':
          $next_op .= " (";
          list($token, $match) = $this->_get_next_token();
          break;

        case 'closebracket':
          if ($open_brackets > 0) {
            $open_brackets--;
            $sql .= ")";
          }
          list($token, $match) = $this->_get_next_token();
          break;

        case 'whitespace':
          list($token, $match) = $this->_get_next_token();
          break;

        case 'EOF':
          break;

        default:
          //die("Unknown token $token (".$match[0].") at $this->offset");
          list($token, $match) = $this->_get_next_token();
          break;
      }
    }
    $sql .= str_repeat(')', $open_brackets);
    //echo "SQL:".$sql."<br>"; print_r($this->vars); print_r($this->fields);
    $this->sql    = $sql;
  }


  function uses_field($_name) {
    return isset($this->fields[$_name]);
  }


  function get_field_values($_name) {
    if (!$this->uses_field($_name))
      return array();
    return $this->fields[$_name];
  }


  function add_where_expression($_sql_query) {
    $_sql_query->set_sql($_sql_query->sql() . $this->sql);
    foreach ($this->vars as $key => $value)
      $_sql_query->set_var($key, $value);
  }
}
?>
