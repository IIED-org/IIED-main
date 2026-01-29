<?php

/**
 * @file
 * Builds placeholder replacement tokens for user-related data.
 */

declare(strict_types=1);

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function matomo_token_info(): array {
  $user = [];
  $user['matomo-role-names'] = [
    'name' => \t('User role names'),
    'description' => \t('The role names the user account is a member of as comma separated list.'),
    'needs-data' => 'user',
  ];
  $user['matomo-role-ids'] = [
    'name' => \t('User role ids'),
    'description' => \t('The role ids the user account is a member of as comma separated list.'),
    'needs-data' => 'user',
  ];

  return [
    'tokens' => ['user' => $user],
  ];
}

/**
 * Implements hook_tokens().
 */
function matomo_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  $sanitize = !empty($options['sanitize']);
  $replacements = [];

  if ($type == 'user' && !empty($data['user'])) {
    $account = $data['user'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Basic user account information.
        case 'matomo-role-names':
          $names = \implode(',', $account->getRoles());
          $replacements[$original] = $sanitize ? Html::escape($names) : $names;
          $bubbleable_metadata->addCacheableDependency($account);
          break;

        case 'matomo-role-ids':
          $ids = \implode(',', \array_keys($account->getRoles()));
          $replacements[$original] = $sanitize ? Html::escape($ids) : $ids;
          $bubbleable_metadata->addCacheableDependency($account);
          break;
      }
    }
  }

  return $replacements;
}
