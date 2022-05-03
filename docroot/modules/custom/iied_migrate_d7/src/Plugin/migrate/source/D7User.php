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
    // UID 9296 good for testing. And 97 / 53
    // $query->condition('u.uid', '89');
    // $query->condition('u.uid', '354');
    return $query;
  }

}
