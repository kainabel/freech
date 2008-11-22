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
      array('and',          '/^and\b/'),
      array('or',           '/^or\b/'),
      array('not',          '/^not\b/'),
      array('openbracket',  '/^\(/'),
      array('closebracket', '/^\)/'),
      array('whitespace',   '/^\s+/'),
      array('field',        '/^(\w+):/'),
      array('word',         '/^"([^"]*)"/'),
      array('word',         '/^(\w+)\b/'),
      array('unknown',      '/^./')
    );
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


  function _parse() {
    $this->offset  = 0;
    $sql           = '1';
    $next_op       = "AND";
    $next_like     = "LIKE";
    $this->fields  = array();
    $this->vars    = array();
    $open_brackets = 0;
    $field_number  = 1;
    $field_names   = array('name'     => 'name',
                           'user'     => 'name',
                           'username' => 'name',
                           'forumid'  => 'forumid',
                           'title'    => 'title',
                           'subject'  => 'title',
                           'text'     => 'text',
                           'body'     => 'text');
    list($token, $match) = $this->_get_next_token();
    while ($token != 'EOF') {
      //echo "TOKEN: $token<br>";
      switch ($token) {
        case 'field':
          // If the given attribute does not exists, treat it like any 
          // other search term.
          $field_name = $field_names[$match[1]];
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
          $value = preg_replace("/%%+/", '', '%'.$match[1].'%');
          if ($value != '%') {
            $open_brackets -= substr_count($next_op, ')');
            $var_name       = "$field_name$field_number";
            $sql           .= " $next_op $field_name LIKE ".'{'.$var_name.'}';
            $field_number++;
            array_push($this->fields, $field_name);
            $this->vars[$var_name] = $value;
          }
          $next_op = "AND";
          list($token, $match) = $this->_get_next_token();
          break;

        case 'word':
          $value = preg_replace("/%%+/", '', '%'.$match[1].'%');
          if ($value != '%') {
            $open_brackets -= substr_count($next_op, ')');
            $var_name       = "text$field_number";
            $sql           .= ' '.$next_op.' ';
            $sql           .= '(';
            $sql           .= ' title LIKE {'.$var_name.'}';
            $sql           .= ' OR';
            $sql           .= ' text LIKE {'.$var_name.'}';
            $sql           .= ')';
            $field_number++;
            array_push($this->fields, 'title');
            array_push($this->fields, 'text');
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
          $open_brackets++;
          list($token, $match) = $this->_get_next_token();
          break;

        case 'closebracket':
          if ($open_brackets > 0)
            $sql .= ")";
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
    //echo "SQL:".$sql; print_r($this->vars);
    $this->sql    = $sql;
  }


  function uses_field($_name) {
    return in_array($_name, $this->fields);
  }


  function add_where_expression($_sql_query) {
    $_sql_query->set_sql($_sql_query->sql() . $this->sql);
    foreach ($this->vars as $key => $value)
      $_sql_query->set_var($key, $value);
  }
}
?>
