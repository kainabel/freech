<?php
class PluginRegistry {
  var $plugins;

  function PluginRegistry() {
  }
 

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
    $plugin->active = true;
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
      else if (preg_match("/^(Constructor:)\s+(.*)$/", $line, $matches)) {
        $tag                 = $matches[1];
        $plugin->constructor = $matches[2];
      }
      else if (preg_match("/^(Active:)\s+(.*)$/", $line, $matches)) {
        $tag            = $matches[1];
        if ($matches[2] == "0")
          $plugin->active = false;
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
        die("Invalid header in plugin '$filename'.");
    }
    if (!$plugin->name
      || !$plugin->version
      || !$plugin->author
      || !$plugin->descr
      || !$plugin->constructor) {
      echo "Incomplete plugin header in '$filename'.";
      return;
    }
    return $plugin;
  }
  
  /**
   * Reads all plugins from the given directory and parses their headers.
   */
  function read_plugins($dirname) {
    if (!preg_match("/^[a-z\-_0-9]+$/i", $dirname))
      die("PluginRegistry::read_plugins(): Invalid path.");
    $list = scandir($dirname);
    foreach ($list as $path) {
      $path = "$dirname/$path";
      if (!is_dir($path))
        continue;
      if (!is_file("$path/plugin.php"))
        continue;
      $this->plugins[$path] = $this->_parse_plugin("$path/plugin.php");
    }
  }

  /**
   * Activates all known plugins.
   * Args: $args: Arguments passed to the plugin constructor.
   */
  function activate_plugins($args = '') {
    foreach ($this->plugins as $path => $plugin) {
      if (!$plugin->active)
        continue;
      include "$path/plugin.php";
      $init = $plugin->constructor;
      $init($args);
    }
  }
}
?>
