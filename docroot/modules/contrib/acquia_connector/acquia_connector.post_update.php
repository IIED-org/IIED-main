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

/**
 * Whether you use Search or not, you need to clear container cache.
 */
function acquia_connector_post_update_remove_cronservice() {
  drupal_flush_all_caches();
}

/**
 * Clear Caches to get Acquia Connector's new path.
 */
function acquia_connector_post_update_update_settings_path() {
  drupal_flush_all_caches();
}

/**
 * Migrate acquia telemetry settings to connector.
 */
function acquia_connector_post_update_migrate_acquia_telemetry() {
  // Bring over API key and Debug settings from previous module, uninstall.
  $acquia_connector_config = \Drupal::configFactory()->getEditable('acquia_connector.settings');

  if (\Drupal::moduleHandler()->moduleExists('acquia_telemetry')) {
    $api_key = \Drupal::configFactory()->get('acquia_telemetry.settings')
      ->get('api_key');
    $debug = \Drupal::state()->get('acquia_telemetry.loud');
    if ($debug) {
      \Drupal::state()->set('acquia_connector.telemetry.loud', TRUE);
    }
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $module_installer->uninstall(['acquia_telemetry']);
  }
  else {
    $api_key = 'f32aacddde42ad34f5a3078a621f37a9';
  }
  $acquia_connector_config->set('spi.amplitude_api_key', $api_key);
  $acquia_connector_config->save();

  // Reload the service container.
  $kernel = \Drupal::service('kernel');
  $kernel->invalidateContainer();
}
