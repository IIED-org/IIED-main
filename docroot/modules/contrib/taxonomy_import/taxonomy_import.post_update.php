<?php

/**
 * @file
 * Post update hook.
 */

/**
 * Implemens hook_post_update_NAME()
 *
 * Replace improperly-named configuration with correctly named configuration.
 */
function taxonomy_import_post_update_replace_misnamed_config() {
  // Get config from old location and delete it.
  $config = \Drupal::service('config.factory')->getEditable('import_taxonomy.config');
  $values = $config->get();
  $config->delete();

  // Write config to new location.
  $config = \Drupal::service('config.factory')->getEditable('taxonomy_import.config');
  $config->setData($values);
  $config->save();
}
