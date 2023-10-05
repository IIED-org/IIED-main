<?php

/**
 * This demonstrates the deprecated static calls that might be called from procedural code like `.module` files.
 */

/**
 * A simple example using the minimum number of arguments.
 */
function simple_example() {
    \Drupal::l('User Login', \Drupal\Core\Url::fromRoute('user.login'));
}
