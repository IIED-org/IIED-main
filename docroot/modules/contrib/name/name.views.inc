<?php

/**
 * @file
 * Views integration for name fields.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function name_field_views_data(FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);
  $field_name = $field_storage->getName();
  $field_type = $field_storage->getType();

  $columns = [
    'title' => 'standard',
    'given' => 'standard',
    'middle' => 'standard',
    'family' => 'standard',
    'generational' => 'standard',
    'credentials' => 'standard',
  ];

  foreach ($data as $table_name => $table_data) {
    $data[$table_name][$field_name]['filter'] = [
      'field' => $field_name,
      'table' => $table_name,
      'field_name' => $field_name,
      'id' => 'name_fulltext',
      'allow_empty' => TRUE,
    ];

    if ($field_type == 'name') {
      // Adds every name column as view field for every name field.
      foreach ($columns as $column => $plugin_id) {
        $data[$table_name][$field_name . '_' . $column]['field'] = [
          'id' => $plugin_id,
          'field_name' => $field_name,
          'property' => $column,
        ];
      }
    }
  }

  return $data;
}
