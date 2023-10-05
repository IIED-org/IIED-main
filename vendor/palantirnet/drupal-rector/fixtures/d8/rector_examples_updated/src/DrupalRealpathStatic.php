<?php

namespace Drupal\rector_examples;

/**
 * Example of static method calls from a class.
 */
class DrupalRealpathStatic {

  /**
   * A simple example using the minimum number of arguments.
   */
  public function simple_example() {
    \Drupal::service('file_system')->realpath('public://');
  }

  /**
   * This shows using a variable as the uri parameter.
   */
  public function uri_as_variable() {
    $uri = 'public://';

    \Drupal::service('file_system')->realpath($uri);
  }

}
