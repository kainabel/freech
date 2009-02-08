<?php
class PluginRegistry {
  /**
   * Reads the header information from a plugin file.
   * Args: The plugin file that we are going to parse.
   * Returns: The extracted fields in an object.
   */
  function &_parse_plugin($filename) {
    if (!preg_match("/^[a-z0-9\-_\/]+\.php$/i", $filename))
      die("PluginRegistry::_parse_plugin(): Invalid file or path.");
    $fp  = fopen($filename, "r");
    $tag = '';
    fgets($fp);
    fgets($fp);
    while ($line = fgets($fp)) {
      if (preg_match("/^(Plugin:)\s+(.*)$/", $line, $matches)) {
        $tag          = $matches[1];
        $plugin->name = $matches[2];
      }
      else if (preg_match("/^(Version:)\s+(.*)$/", $line, $matches)) {
        $tag             = $matches[1];
        $plugin->version = $matches[2];
      }
      else if (preg_match("/^(Author:)\s+(.*)$/", $line, $matches)) {
        $tag            = $matches[1];
        $plugin->author = $matches[2];
      }
      else if (preg_match("/^(Description:)\s+(.*)$/", $line, $matches)) {
        $tag           = $matches[1];
        $plugin->descr = $matches[2];
      }
      else if ($tag == "Description:"
             && preg_match("/^\s+(.*)$/", $line, $matches))
        $plugin->descr .= " " . $matches[1];
      else if (preg_match("/^\s*\*\/$/", $line))
        break;
      else
        die("Invalid line in header of plugin '$filename': $line");
    }
    if (!$plugin->name
      || !$plugin->version
      || !$plugin->author
      || !$plugin->descr) {
      echo "Incomplete plugin header in '$filename'.<br>";
      echo "Name: $plugin->name<br>";
      echo "Version: $plugin->version<br>";
      echo "Author: $plugin->author<br>";
      echo "Description: $plugin->descr<br>";
      return;
    }
    return $plugin;
  }
  
  function activate_plugin_from_dirname($dirname, $args = '') {
    trace('activating %s', $dirname);
    include "$dirname/plugin.php";
    trace('included %s', $dirname);
    $init = basename($dirname).'_init';
    $init($args);
    trace('initialized %s', $dirname);
  }
}
?>
