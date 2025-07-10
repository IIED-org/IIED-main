<?php

/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

declare(strict_types=1);

namespace Drupal\ckeditor5_premium_features_mentions\DataProvider;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserStorageInterface;

/**
 * Provides the user data for the editor featuers.
 */
class MentionDataProvider {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface|\Drupal\Core\Entity\EntityStorageInterface
   */
  protected UserStorageInterface $userStorage;

  /**
   * Creates the provider instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * Returns users matching specified query with privilege to be mentioned.
   *
   * @param string $query
   *   Username query phrase.
   * @param int $users_limit
   *   Maximum number of users to return.
   */
  public function getPrivilegedEditors(string $query, int $users_limit = 10): array {
    $offset = 0;
    $query_limit = 100;
    $matched_users = [];
    $matching_users_count = $this->userStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('name', $query, 'CONTAINS')
      ->condition('status', 1)
      ->count()->execute();

    do {
      $user_ids = $this->userStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('name', $query, 'CONTAINS')
        ->condition('status', 1)
        ->range($offset, $query_limit)
        ->execute();

      /** @var \Drupal\user\UserInterface[] $users */
      $users = $this->userStorage->loadMultiple($user_ids);

      foreach ($users as $user_to_check) {
        if (count($matched_users) >= $users_limit) {
          break;
        }

        if ($user_to_check->hasPermission('to be mentioned')) {
          $matched_users[] = $user_to_check;
        }
      }
    } while ($offset + $query_limit < $matching_users_count && count($matched_users) < $users_limit);

    return $matched_users;
  }

}
