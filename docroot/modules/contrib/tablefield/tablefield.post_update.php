<?php

/**
 * @file
 * Post update functions for tablefield.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Resave field config to apply the tablefield schema changes.
 */
function tablefield_post_update_implement_tablefield_field_config_schema(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'field_config', function (FieldConfigInterface $fieldConfig) {
      if ($fieldConfig->getFieldStorageDefinition()->getType() !== 'tablefield') {
        return FALSE;
      }

      $settings = $fieldConfig->getSettings();
      $settings['export'] = (bool) $settings['export'];
      $settings['restrict_rebuild'] = (bool) $settings['restrict_rebuild'];
      $settings['restrict_import'] = (bool) $settings['restrict_import'];
      $settings['lock_values'] = (bool) $settings['lock_values'];
      $settings['cell_processing'] = (int) $settings['cell_processing'];
      $settings['empty_rules']['ignore_table_structure'] = (bool) $settings['empty_rules']['ignore_table_structure'];
      $settings['empty_rules']['ignore_table_header'] = (bool) $settings['empty_rules']['ignore_table_header'];
      $fieldConfig->set('settings', $settings);
      return TRUE;
    });
}

/**
 * Resave entity view display config to apply the tablefield schema changes.
 */
function tablefield_post_update_implement_tablefield_entity_view_display_schema(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'entity_view_display', function (EntityViewDisplayInterface $entityViewDisplay) {
      $updated = FALSE;
      foreach ($entityViewDisplay->getComponents() as $key => $component) {
        if ($component['type'] === 'tablefield') {
          $component['settings']['row_header'] = (bool) $component['settings']['row_header'];
          $component['settings']['column_header'] = (bool) $component['settings']['column_header'];
          $entityViewDisplay->setComponent($key, $component);
          $updated = TRUE;
        }
      }
      return $updated;
    });
}
