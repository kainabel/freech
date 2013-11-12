<?php
/*
Plugin:      Hello World
Version:     0.1
Author:      Samuel Abels
Description: Demo plugin to shows how it works.
*/


function helloworld_init(&$api) {
  $eventbus = $api->eventbus();
  $eventbus->signal_connect('on_construct',            'helloworld_on_construct');
  // $eventbus->signal_connect('on_run_before',           'helloworld_on_run_before');
  $eventbus->signal_connect('on_header_print_before',  'helloworld_on_header_print_before');
  // $eventbus->signal_connect('on_header_print_after',   'helloworld_on_header_print_after');
  $eventbus->signal_connect('on_content_print_before', 'helloworld_on_content_print_before');
  $eventbus->signal_connect('on_content_print_after',  'helloworld_on_content_print_after');
  $eventbus->signal_connect('on_destroy',              'helloworld_on_destroy');
  
  $eventbus->signal_connect('on_message_read_print',   'helloworld_on_message_read_print');
  $eventbus->signal_connect('on_message_preview_print','helloworld_on_message_preview_print');
}


/**
* Please note: This is just a demo plugin for hooks and destroys potentially
*              now the validity of HTTP headers, CSS and XHTML code.
*/

function helloworld_on_construct(&$api) {
  echo("<pre>### Hello from " .__FUNCTION__ . "().\n");
  echo("### <- marker for injected messages by plugin helloworld.\n</pre>");
}


function helloworld_on_run_before(&$api) {
  echo("\n<pre>### Hello from " .__FUNCTION__ . "().</pre>\n");
}


function helloworld_on_header_print_before(&$api) {
  echo("\n<pre>### Hello from " .__FUNCTION__ . "().</pre>\n");
}


function helloworld_on_header_print_after(&$api) {
  echo("\n<pre>### Hello from " .__FUNCTION__ . "().</pre>\n");
}


function helloworld_on_content_print_before(&$api) {
  echo("\n<pre>### Hello from " .__FUNCTION__ . "().</pre>\n");
}


function helloworld_on_content_print_after(&$api) {
  echo("\n<pre>### Hello from " .__FUNCTION__ . "().</pre>\n");
}


function helloworld_on_destroy(&$api) {
  echo("\n<pre>### Hello from " .__FUNCTION__ . "().</pre>\n");
}


/**
* Hooks from plugin Message
*/

function helloworld_on_message_read_print(&$api, &$message) {
  echo("\n<pre>### Hello from " .__FUNCTION__ . "().</pre>\n");
  // echo var_dump($message->posting);
}


function helloworld_on_message_preview_print(&$api, &$message) {
  echo("\n<pre>### Hello from " .__FUNCTION__ . "().</pre>\n");
  // echo var_dump($message->posting);
}
