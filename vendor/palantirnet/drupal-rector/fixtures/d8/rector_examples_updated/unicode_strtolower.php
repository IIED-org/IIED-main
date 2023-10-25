<?php

use Drupal\Component\Utility\Unicode;

/**
 * This demonstrates the deprecated static calls that might be called from procedural code like `.module` files.
 */

/**
 * A simple example using the minimum number of arguments.
 */
function simple_example() {
  $string = mb_strtolower('example');
}

/**
 * Example of using all arguments.
 */
function example_with_all_arguments() {
  $string = mb_strtolower('example');
}
