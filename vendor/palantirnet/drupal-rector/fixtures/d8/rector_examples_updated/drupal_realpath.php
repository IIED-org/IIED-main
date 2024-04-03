<?php

/**
 * This demonstrates the deprecated static calls that might be called from procedural code like `.module` files.
 */

/**
 * A simple example using the minimum number of arguments.
 */
function simple_example() {
  \Drupal::service('file_system')->realpath('public://');
}

/**
 * This shows using a variable as the uri parameter.
 */
function uri_as_variable() {
  $uri = 'public://';

  \Drupal::service('file_system')->realpath($uri);
}
