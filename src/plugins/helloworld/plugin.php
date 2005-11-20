<?php
/*
Plugin:      Hello World
Version:     0.1
Author:      Samuel Abels
Description: Demo plugin to shows how it works.
Constructor: helloworld_init
Active:      0
*/


function helloworld_init(&$forum) {
  $eventbus = &$forum->get_eventbus();
  $eventbus->signal_connect("on_construct",            "helloworld_on_construct");
  $eventbus->signal_connect("on_destroy",              "helloworld_on_destroy");
  $eventbus->signal_connect("on_header_print_before",  "helloworld_on_header_print");
  $eventbus->signal_connect("on_content_print_before", "helloworld_on_content_print");
}


function helloworld_on_construct() {
  print("Hello from helloworld_on_construct().");
}


function helloworld_on_header_print(&$html) {
  print("Hello from helloworld_on_header_print().");
}


function helloworld_on_content_print(&$html) {
  print("Hello from helloworld_on_content_print().");
}


function helloworld_on_destroy() {
  print("Hello from helloworld_on_destroy().");
}
?>
