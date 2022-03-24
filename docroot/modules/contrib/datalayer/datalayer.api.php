<?php

/**
 * @file
 * Documentation for the Data Layer module.
 *
 * This file contains no working PHP code; it exists to provide additional
 * documentation for doxygen as well as to document hooks in the standard
 * Drupal manner.
 */

/**
 * @defgroup datalayer_hooks Data Layer hooks
 *
 * @{
 * Hooks that can be implemented to externally extend the Data Layer module.
 */

/**
 * Provide candidate entity properties for output to the screen.
 */
function hook_datalayer_meta() {
  // EXAMPLE:
  // Add your own entity property to output.
  return [
    'some_property',
  ];
}

/**
 * Provide candidate entity properties for specific entity types.
 */
function hook_datalayer_ENTITY_TYPE_meta() {
  // EXAMPLE:
  // Add your own entity property to output.
  return [
    'some_property',
  ];
}

/**
 * Alter the Data Layer data before it is output to the screen.
 *
 * @param array $properties
 *   Data layer properties to output on entiity pages.
 */
function hook_datalayer_meta_alter(array $properties) {
  // EXAMPLE:
  // Remove author uid if anonymous or admin.
  if ($properties['uid'] == 0 || $properties['uid'] == 1) {
    unset($properties['uid']);
  }
}

/**
 * Alter entity specific Data Layer data before it is output to the screen.
 *
 * @param array $properties
 *   Data layer properties to output on entiity pages.
 */
function hook_datalayer_ENTITY_TYPE_meta_alter(array $properties) {
  // EXAMPLE:
  // Remove author uid if anonymous or admin.
  if ($properties['uid'] == 0 || $properties['uid'] == 1) {
    unset($properties['uid']);
  }
}

/**
 * Alter the Data Layer data before it is output to the screen.
 *
 * @param array $data_layer
 *   GTM data layer data for the current page.
 */
function hook_datalayer_alter(array &$data_layer) {
  // EXAMPLE:
  // Set the "site" variable to return in lowercase.
  $data_layer['site'] = strtolower($data_layer['site']);
}

/**
 * Alter the Data Layer field values before output.
 *
 * @param array $value
 *   Value for the field item being added to the datalayer.
 * @param \Drupal\Core\Field\FieldItemInterface $field_item
 *   Individual field item the value is from.
 * @param string $field_type
 *   Type of field, such as 'string' or 'entity_refernce'.
 */
function hook_datalayer_field_alter(array &$value, \Drupal\Core\Field\FieldItemInterface $field_item, $field_type) {
  // EXAMPLE:
  // Remove the filter format for a text with summary field.
  if ($field_type == 'text_with_summary') {
    unset($value['format']);
  }
}

/**
 * @}
 * End hook documentation.
 */
