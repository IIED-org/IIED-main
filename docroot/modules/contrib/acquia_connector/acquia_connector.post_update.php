<?php

/**
 * @file
 * Connector updates once other modules have made their own updates.
 */

/**
 * Move subscription data to state.
 */
function acquia_connector_post_update_move_subscription_data_state() {
  $config = \Drupal::configFactory()->getEditable('acquia_connector.settings');

  // Handle subscription data first.
  $subscription_data = $config->get('subscription_data');
  if ($subscription_data) {
    \Drupal::state()->set('acquia_subscription_data', $subscription_data);
    $config->clear('subscription_data')->save();
  }

  // Now handle SPI vars.
  $spi_moved_keys = [
    'def_vars',
    'def_waived_vars',
    'def_timestamp',
    'new_optional_data',
  ];
  foreach ($spi_moved_keys as $key) {
    $data = $config->get("spi.$key");
    if ($data) {
      \Drupal::state()->set("acquia_spi_data.$key", $data);
      $config->clear("spi.$key")->save();
    }
  }
}

/**
 * Whether you use Search or not, you need to clear container cache.
 */
function acquia_connector_post_update_move_acquia_search_modules() {
  drupal_flush_all_caches();
}
