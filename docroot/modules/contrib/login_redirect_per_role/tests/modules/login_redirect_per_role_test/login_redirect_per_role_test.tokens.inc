<?php

/**
 * @file
 * Token integration for the Login And Logout Redirect Per Role tests module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function login_redirect_per_role_test_token_info() {
  $info = [];

  $info['tokens']['site']['valid-path'] = [
    'name' => 'Valid path',
  ];

  $info['tokens']['site']['invalid-path'] = [
    'name' => 'Invalid path',
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function login_redirect_per_role_test_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type === 'site') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'valid-path':
          $replacements[$original] = Url::fromRoute('login_redirect_per_role_test.valid_path')->toString();
          break;

        case 'invalid-path':
          $replacements[$original] = Url::fromRoute('login_redirect_per_role_test.invalid_path')->toString();
      }
    }
  }

  return $replacements;
}
