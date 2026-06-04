<?php

/**
 * @file
 * Describes API functions for field_token_value module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow modules to alter the output of field_token_value fields prior to render.
 *
 * @param array $element
 *   The html_tag render array to be altered.
 * @param array $wrapper_info
 *   An array containing the information for the wrapper to be used.
 */
function hook_field_token_value_output_alter(&$element, $wrapper_info) {
  // Attach a CSS file if the paragraph wrapper is being used.
  if ($wrapper_info['tag'] == 'p') {
    $element['#attached']['css'][] = \Drupal::service('extension.list.module')->getPath('my_module') . '/css/my-styles.css';
  }
}

/**
 * Alters the array of wrappers available.
 *
 * @param array $wrappers
 *   An array containing all available wrappers.
 */
function hook_field_token_value_wrapper_info_alter(&$wrappers) {
  if (isset($wrappers['p'])) {
    // Add an ID attribute and update the summary.
    $wrappers['p']['attributes']['id'] = 'my-paragraph-id';
    $wrappers['p']['summary'] = t('Wrap the value in a paragraph with a custom ID attribute.');
  }
}

/**
 * @} End of "addtogroup hooks".
 */
