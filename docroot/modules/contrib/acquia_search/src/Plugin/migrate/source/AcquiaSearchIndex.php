<?php

namespace Drupal\acquia_Search\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 acquia search index source from database.
 *
 * @MigrateSource(
 *   id = "d7_acquia_search_index",
 *   source_module = "acquia_search"
 * )
 */
class AcquiaSearchIndex extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('apachesolr_index_bundles', 'a')->fields('a');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'env_id' => $this->t('The name of the environment'),
      'entity_type' => $this->t('The type of entity.'),
      'bundle' => $this->t('The bundle to index.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['env_id']['type'] = 'string';
    $ids['bundle']['type'] = 'string';
    return $ids;
  }

}
