<?php

/**
 * @file
 * Hooks for gin_login.
 */

/**
 * Implements hook_gin_login_route_definitions_alter().
 */
function hook_gin_login_route_definitions_alter(&$route_definitions) {
  if (\Drupal::moduleHandler()->moduleExists('gin_login')) {
    $route_definitions['user.login.alternative'] = [
      'page' => 'page__user__login',
      'template' => 'page--user--login',
      'preprocess functions' => ['gin_login_preprocess_ginlogin'],
    ];
  }
}
