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
