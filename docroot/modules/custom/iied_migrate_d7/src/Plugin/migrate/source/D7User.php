<?php

namespace Drupal\iied_migrate_d7\Plugin\migrate\source;

use Drupal\user\Plugin\migrate\source\d7\User;

/**
 * Source plugin for blog content.
 *
 * @MigrateSource(
 *   id = "iied_d7_user",
 *   source_module = "user"
 * )
 */
class D7User extends User {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    // UID 9296 for testing.
    // $query->condition('u.uid', '9296');
    return $query;
  }

}