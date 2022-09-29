<?php

/**
 * @file
 * Charts post-update file.
 */

use Drupal\Core\Serialization\Yaml;

/**
 * Initialize advanced requirements cdn config value.
 */
function charts_post_update_initialize_advanced_requirements_cdn(&$sandbox) {
  $config = \Drupal::service('config.factory')
    ->getEditable('charts.settings');

  if ($config) {
    $config->set('advanced', ['requirements' => ['cdn' => TRUE]]);
    $config->save();
  }
}

/**
 * Update the existing default config display colors to increase from 10 to 25.
 */
function charts_post_update_existing_default_colors_to_twenty_five(&$sandbox) {
  $config = \Drupal::service('config.factory')->getEditable('charts.settings');
  $existing_colors = $config->get('charts_default_settings.display.colors');

  if (is_countable($existing_colors) && count($existing_colors) === 10) {
    $path = \Drupal::service('extension.list.module')->getPath('charts');
    $default_install_settings_file = $path . '/config/install/charts.settings.yml';
    if (!file_exists($default_install_settings_file)) {
      return;
    }

    $default_install_settings = Yaml::decode(file_get_contents($default_install_settings_file));
    $install_colors = $default_install_settings['charts_default_settings']['display']['colors'];
    // We only want to add the last 15 colors to make it 25.
    $colors = array_merge($existing_colors, array_slice($install_colors, -15));
    $config->set('charts_default_settings.display.colors', $colors);
    $config->save();
  }
}

/**
 * Initialize the config value for the debug setting.
 */
function charts_post_update_initialize_debug_option(&$sandbox) {
  $config = \Drupal::service('config.factory')
    ->getEditable('charts.settings');

  if ($config) {
    $config->set('advanced.debug', FALSE);
    $config->save();
  }
}

/**
 * Migrate library default settings to library_config key.
 */
function charts_post_update_migrate_library_default_settings_to_library_config(&$sandbox) {
  /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
  $config_factory = \Drupal::service('config.factory');
  $config = $config_factory->getEditable('charts.settings');

  if ($config) {
    $library_id = $config->get('charts_default_settings.library');
    if ($library_id) {
      $old_key = 'charts_default_settings.' . $library_id . '_settings';
      $library_config = $config->get($old_key);
      $config->set('charts_default_settings.library_config', $library_config);
      $config->clear($old_key);
      $config->save();
    }
  }
}

/**
 * Clear library_config set on the wrong place. See drupal.org/i/3310145.
 */
function charts_post_update_clear_library_config_key_added_on_wrong_place(&$sandbox) {
  /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
  $config_factory = \Drupal::service('config.factory');
  $config = $config_factory->getEditable('charts.settings');
  if ($config && $config->get('library_config')) {
    // Not to be confused with "charts_default_settings.library_config" which is
    // the proper key.
    $config->clear('library_config');
    $config->save();
  }
}
