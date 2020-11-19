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
 * Uninstall Acquia Search and use Acquia Search Solr.
 */
function acquia_connector_post_update_move_search_modules() {
  if (\Drupal::moduleHandler()->moduleExists('acquia_search')) {
    $config_factory = \Drupal::configFactory();
    $config_to_delete = [
      'block.block.exposedformacquia_searchpage',
      'search_api.index.acquia_search_index',
      'search_api.server.acquia_search_server',
      'views.view.acquia_search',
    ];
    foreach ($config_to_delete as $config_name) {
      if ($config = $config_factory->getEditable($config_name)) {
        $config->delete();
      }
    }
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $module_installer->uninstall(['acquia_search']);
    if (\Drupal::moduleHandler()->moduleExists('search_api_solr_multilingual')) {
      $module_installer->uninstall(['search_api_solr_multilingual']);
    }
  }
}
