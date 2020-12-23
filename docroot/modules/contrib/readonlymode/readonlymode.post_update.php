<?php

/**
 * @file
 * Post update functions for Readonly mode.
 */

/**
 * Update message configuration to use site name token.
 */
function readonlymode_post_update_token_support() {
  $config = \Drupal::configFactory()->getEditable('readonlymode.settings');
  $keys = ['messages.default', 'messages.not_saved'];
  array_map(function ($key) use ($config) {
    $config->set($key, str_replace('@site', '[site:name]', $config->get($key)));
  }, $keys);
  $config->save();
}
