<?php

/**
 * @file
 * Theme and preprocess functions for users.
 */

/**
 * Implements template_preprocess_user().
 */
function radix_preprocess_user(&$variables) {
  // Add the view mode to the template.
  $variables['view_mode'] = $variables['elements']['#view_mode'];
}

/**
 * Implements hook_theme_suggestions_hook_alter().
 */
function radix_theme_suggestions_user_alter(array &$suggestions, array $variables) {
  // Add a suggestion based on the view mode.
  $suggestions[] = $variables['theme_hook_original'] . '__' . $variables['elements']['#view_mode'];
}
