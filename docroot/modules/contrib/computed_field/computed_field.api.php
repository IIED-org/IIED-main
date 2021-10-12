<?php

/**
 * @file
 * API documentation for the Computed Field module.
 */

/**
 * Alter the values of all computed fields.
 *
 * @param mixed $value
 *   The computed value that can be altered.
 * @param array $context
 *   An array containing the 'entity' and 'field_name'.
 */
function hook_computed_field_value_alter(&$value, $context) {
  $service = Drupal::service('computed_field.helpers');

  // Only proceed when the hook does not exist.
  if (!$service->computeFunctionNameExists($context['field_name'])) {
    // Set all unimplemented computed fields to 42.
    $value = 42;
  }
}

/**
 * Alter the value of a specific computed field.
 *
 * @param mixed $value
 *   The computed value that can be altered.
 * @param array $context
 *   An array containing the 'entity' and 'field_name'.
 */
function hook_computed_field_FIELD_NAME_value_alter(&$value, $context) {
  $service = Drupal::service('computed_field.helpers');

  // Only proceed when the hook does not exist.
  if (!$service->computeFunctionNameExists($context['field_name'])) {
    // Set this one computed field to 42.
    $value = 42;
  }
}
