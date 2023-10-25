<?php

/**
 * @file
 * Custom theme hooks.
 */

/**
 * Preprocess variables for all templates.
 * @param $variables
 */
function radix_preprocess(&$variables) {
  // Inject the current language into every template
  $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  $variables['language'] ??= $language;

  global $base_url;
  $variables['base_url'] ??= $base_url;

  $variables['path'] ??= \Drupal::service('path.current')->getPath();
}
