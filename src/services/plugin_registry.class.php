<?php
class PluginRegistry {
  var $plugins;
  var $eventbus;

  function PluginRegistry() {
    // (Ab)use a Trackable as an eventbus.
    $this->eventbus = &new Trackable;
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
   */
  function activate_plugins() {
    foreach ($this->plugins as $path => $plugin) {
      include "$path/plugin.php";
      $init = $plugin->constructor;
      $init($this);
    }
  }
  
  /**
   * Purpose: Hook a function into Tefinch.
   * Args: $hook: The name of the hook.
   *       $func: The function to be called by the hook.
   * Returns: An id that can be used later to unregister the hook.
   *
   * Currently supported hooks:
   * on_construct:
   *   Called from within the TefinchForum() constructor before any
   *   other output is produced.
   *   The return value of the callback is ignored.
   *   Args: None.
   *
   * on_destroy:
   *   Called from within TefinchForum->destroy().
   *   The return value of the callback is ignored.
   *   Args: None.
   */
  function add_listener($hook, $func) {
    if (!$hook || !preg_match("/^[a-z_]+$/", $hook))
      die("PluginRegistry::add_listener(): Invalid hook.");
    if (!$func)
      die("PluginRegistry::add_listener(): Invalid function.");
    return $this->eventbus->signal_connect($hook, $func);
  }
  
  /**
   * Unregisters the given function from the hook.
   * Args: $id: The id that was returned by add_listener().
   * Returns: FALSE id the id was not found, TRUE otherwise.
   */
  function remove_listener($id) {
    if (!is_int($id))
      die("PluginRegistry::remove_listener(): Invalid id.");
    return $this->eventbus->signal_disconnect($id);
  }

  /**
   * Triggers the event with the given name.
   * If $args is given, the value is passed to the event handler.
   */
  function emit($hook, $args = '') {
    if (!$hook || !preg_match("/^[a-z_]+$/", $hook)) 
      die("PluginRegistry::emit(): Invalid hook.");
    return $this->eventbus->emit($hook, $args);
  }
}
?>
