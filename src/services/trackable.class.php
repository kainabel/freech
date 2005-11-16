<?php
/// Simple synchronous signal implementation.
class Trackable {
  var $callbacks;
  var $idmap;
  var $idpool;
 
  function Trackable() {
    $this->callbacks = array();
    $this->idmap     = array();
    $this->idpool    = 1;
  }

  /**
   * Purpose: Add a listener to the event with the given name.
   * Args: $event: The name of the event.
   *       $func: The function to be called by the event.
   * Returns: An id that can be used later to unregister the event.
   */
  function signal_connect($event, $func) {
    if (!$event || !preg_match("/^[a-z_]+$/", $event))
      die("Trackable::signal_connect(): Invalid event.");
    if (!$func)
      die("Trackable::signal_connect(): Invalid function.");
    $registered = &$this->callbacks[$event];
    if ($registered)
      $registered[$this->idpool] = &$func;
    else
      $this->callbacks[$event] = array($this->idpool => $func);
    $this->idmap[$this->idpool] = $event;
    $this->idpool++;
    return $this->idpool - 1;
  }
  
  /**
   * Unregisters the given function from the event.
   * Args: $id: The id that was returned by signal_connect().
   * Returns: FALSE id the id was not found, TRUE otherwise.
   */
  function signal_disconnect($id) {
    if (!is_int($id))
      die("Trackable::signal_disconnect(): Invalid id.");
    if (!isset($this->idmap[$id]))
      return FALSE;
    $event       = $this->idmap[$id];
    $registered = &$this->callbacks[$event];
    unset($this->idmap[$id]);
    unset($registered[$id]);
    return TRUE;
  }

  /**
   * Triggers the event with the given name.
   * If $args is given, the value is passed to the event handler.
   */
  function emit($event, $args = '') {
    if (!$event || !preg_match("/^[a-z_]+$/", $event)) 
      die("Trackable::emit(): Invalid event.");
    $registered = &$this->callbacks[$event];
    if (!$registered)
      return;
    foreach ($registered as $id => $func)
      call_user_func($func, $args);
  }
}
?>
